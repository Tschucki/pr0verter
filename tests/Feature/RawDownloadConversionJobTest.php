<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Enums\QualityTier;
use App\Enums\VideoCodec;
use App\Events\ConversionFinished;
use App\Events\ConversionProgressEvent;
use App\Events\ConversionUpdated;
use App\Jobs\ConversionJob;
use App\Models\Conversion;
use App\Models\File;
use App\Models\Statistic;
use App\Services\VideoAnalysisService;
use App\ValueObjects\VideoMetadata;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('conversions');
    Session::start();
});

function makeRawConversion(array $attributes = []): Conversion
{
    $sessionId = Session::getId();

    Storage::disk('conversions')->put('raw-source.mkv', 'untouched-binary');

    $file = File::factory()->create([
        'session_id' => $sessionId,
        'disk' => 'conversions',
        'filename' => 'raw-source.mkv',
        'extension' => 'mkv',
        'mime_type' => 'video/x-matroska',
        'size' => Storage::disk('conversions')->size('raw-source.mkv'),
    ]);

    return Conversion::factory()->create([
        'session_id' => $sessionId,
        'file_id' => $file->id,
        'status' => ConversionStatus::PENDING,
        'raw_download' => true,
        'audio_quality' => 1.0,
        ...$attributes,
    ]);
}

function fakeMetadata(): VideoMetadata
{
    return new VideoMetadata(
        width: 1920,
        height: 1080,
        duration: 12.5,
        videoCodec: VideoCodec::H264,
        audioCodec: null,
        framerate: 30.0,
        rotation: 0,
        audioSampleRate: 44100,
    );
}

function mockAnalysis(?Throwable $throws = null): void
{
    $mock = Mockery::mock(VideoAnalysisService::class);
    $expectation = $mock->shouldReceive('analyze')->once();

    $throws === null
        ? $expectation->andReturn(fakeMetadata())
        : $expectation->andThrow($throws);

    app()->instance(VideoAnalysisService::class, $mock);
}

it('finishes a raw download without encoding and leaves the source file untouched', function (): void {
    Event::fake([ConversionUpdated::class, ConversionFinished::class, ConversionProgressEvent::class]);

    $conversion = makeRawConversion();
    mockAnalysis();

    ConversionJob::dispatchSync($conversion->id);

    $conversion->refresh();

    expect($conversion->status)->toBe(ConversionStatus::FINISHED)
        ->and($conversion->downloadable)->toBeTrue()
        ->and($conversion->quality_tier)->toBe(QualityTier::FULL_HD->value)
        ->and($conversion->metadata['width'])->toBe(1920);

    // Source file must survive byte for byte, under its original name: no
    // re-encode, no converted sibling, no extension rewrite to mp4.
    expect(Storage::disk('conversions')->allFiles())->toBe(['raw-source.mkv'])
        ->and(Storage::disk('conversions')->get('raw-source.mkv'))->toBe('untouched-binary')
        ->and($conversion->file->filename)->toBe('raw-source.mkv')
        ->and($conversion->file->extension)->toBe('mkv');

    Event::assertDispatched(
        ConversionFinished::class,
        fn (ConversionFinished $event): bool => $event->sessionId === $conversion->file->session_id,
    );
    Event::assertDispatched(ConversionUpdated::class);
    Event::assertNotDispatched(ConversionProgressEvent::class);
});

it('walks through preparing before reaching finished', function (): void {
    $conversion = makeRawConversion();
    mockAnalysis();

    $seenStatuses = [];

    Event::listen(ConversionUpdated::class, function () use ($conversion, &$seenStatuses): void {
        $seenStatuses[] = $conversion->fresh()->status;
    });

    ConversionJob::dispatchSync($conversion->id);

    expect($seenStatuses)->toContain(ConversionStatus::PREPARING)
        ->and($seenStatuses)->toContain(ConversionStatus::FINISHED)
        ->and($conversion->fresh()->status)->toBe(ConversionStatus::FINISHED);
});

it('still finishes the raw download when the analysis fails', function (): void {
    Event::fake([ConversionUpdated::class, ConversionFinished::class]);

    $conversion = makeRawConversion();
    mockAnalysis(new RuntimeException('ffprobe exploded'));

    ConversionJob::dispatchSync($conversion->id);

    $conversion->refresh();

    expect($conversion->status)->toBe(ConversionStatus::FINISHED)
        ->and($conversion->downloadable)->toBeTrue()
        ->and($conversion->metadata)->toBeNull()
        ->and($conversion->thumbnail_path)->toBeNull();

    Event::assertDispatched(ConversionFinished::class);
});

it('tracks the raw download flag in the statistics', function (): void {
    $conversion = makeRawConversion();
    mockAnalysis();

    ConversionJob::dispatchSync($conversion->id);

    $statistic = Statistic::where('conversion_id', $conversion->id)->sole();

    expect($statistic->raw_download)->toBeTrue()
        ->and($statistic->status)->toBe(ConversionStatus::FINISHED)
        ->and($statistic->max_size)->toBeNull();
});
