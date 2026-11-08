<?php

declare(strict_types=1);

use App\Conversion\MediaOperations\AutoCropFilterOperation;
use App\Models\Conversion;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;
use Symfony\Component\Process\Exception\ProcessTimedOutException as SymfonyTimeoutException;
use Symfony\Component\Process\Process as SymfonyProcess;

beforeEach(function (): void {
    Storage::fake('conversions');
});

function autoCropConversion(): Conversion
{
    return Conversion::factory()->create([
        'auto_crop' => true,
        'audio_quality' => 1.0,
        'metadata' => ['width' => 1920, 'height' => 1080],
    ]);
}

it('detects the crop on keyframes only', function (): void {
    Process::fake(['*' => Process::result(output: 'crop=1920:800:0:140')]);

    new AutoCropFilterOperation(autoCropConversion());

    Process::assertRan(fn (PendingProcess $process): bool => str_contains($process->command, '-skip_frame nokey -i ')
        && str_contains($process->command, '-vf cropdetect -an -f null -'));
});

it('converts without crop when the detection times out', function (): void {
    Process::fake(function (PendingProcess $process): never {
        $symfonyProcess = new SymfonyProcess(['ffmpeg']);
        $symfonyProcess->setTimeout(180);

        throw new ProcessTimedOutException(
            new SymfonyTimeoutException($symfonyProcess, SymfonyTimeoutException::TYPE_GENERAL),
            new FakeProcessResult(command: $process->command, exitCode: 1),
        );
    });

    $operation = new AutoCropFilterOperation(autoCropConversion());

    $media = Mockery::mock(MediaOpener::class);
    $media->shouldNotReceive('addFilter');

    expect($operation->applyToMedia($media))->toBe($media);
});
