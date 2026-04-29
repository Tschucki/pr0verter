<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Enums\SubtitleMode;
use App\Events\FileUploadSuccessful;
use App\Jobs\ConversionJob;
use App\Models\Conversion;
use App\Models\File;
use FFMpeg\FFProbe;
use FFMpeg\FFProbe\DataMapping\Format as FFProbeFormat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    // VideoRule probes the upload via FFprobe — fake the binary so a hollow
    // UploadedFile passes validation.
    $format = Mockery::mock(FFProbeFormat::class);
    $format->shouldReceive('get')->with('duration')->andReturn(10.0);

    $probe = Mockery::mock(FFProbe::class);
    $probe->shouldReceive('format')->andReturn($format);

    app()->instance(FFProbe::class, $probe);
});

it('completes the upload → start → redirect → list → job dispatch flow', function (): void {
    Storage::fake('conversions');
    Bus::fake([ConversionJob::class]);
    Event::fake([FileUploadSuccessful::class]);

    // files.session_id is FK-constrained to sessions.id, but the StartSession
    // middleware mints a fresh session per test request and only persists it
    // *after* the response — so the File insert mid-request can never satisfy
    // the constraint. Skipping FK checks for this single test is the standard
    // workaround; RefreshDatabase rolls everything back afterwards.
    DB::statement('SET FOREIGN_KEY_CHECKS=0');

    $upload = UploadedFile::fake()->create('clip.mp4', 1024, 'video/mp4');

    $response = $this
        ->from(route('home'))
        ->post(route('converter.start'), [
            'file' => $upload,
            'audio' => true,
            'audioQuality' => 1.0,
            'maxSize' => 200,
            'audio_only' => false,
            'subtitleMode' => 'soft',
        ]);

    $response->assertRedirect(route('conversions.list'));

    $file = File::sole();
    $conversion = Conversion::sole();

    Storage::disk('conversions')->assertExists($file->filename);

    expect($file->disk)->toBe('conversions');
    expect($file->session_id)->toBe($conversion->session_id);

    expect($conversion)
        ->status->toBe(ConversionStatus::PENDING)
        ->file_id->toBe($file->id)
        ->subtitle_mode->toBe(SubtitleMode::Soft)
        ->audio->toBeTrue()
        ->audio_only->toBeFalse()
        ->max_size->toBe(200);

    Event::assertDispatched(FileUploadSuccessful::class);
    Bus::assertDispatched(
        ConversionJob::class,
        fn (ConversionJob $job): bool => $job->uniqueId() === $conversion->id,
    );

    // Follow the redirect to confirm the list page renders (Inertia HTML).
    $list = $this->get(route('conversions.list'));
    $list->assertOk()
        ->assertSee('Converter\/List', false);
});
