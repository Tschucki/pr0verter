<?php

declare(strict_types=1);

use App\Conversion\MediaOperations\RemoveExifDataFilterOperation;
use App\Enums\ConversionStatus;
use App\Enums\SubtitleMode;
use App\Jobs\DownloadVideoJob;
use App\Models\Conversion;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->withoutVite();
});

it('stores a raw download conversion without any media operation settings', function (): void {
    Bus::fake([DownloadVideoJob::class]);

    // conversions.session_id is FK-constrained to sessions.id, but the session
    // is only persisted after the response — same workaround as ConverterFlowTest.
    DB::statement('SET FOREIGN_KEY_CHECKS=0');

    $response = $this
        ->from(route('home'))
        ->post(route('converter.start'), [
            'url' => 'https://example.com/video',
            'rawDownload' => true,
            'audio' => false,
            'audioQuality' => 0.5,
            'maxSize' => 200,
            'autoCrop' => true,
            'watermark' => true,
            'audio_only' => true,
            'subtitleMode' => 'burn',
        ]);

    $response->assertRedirect(route('conversions.list'));

    $conversion = Conversion::sole();

    expect($conversion)
        ->raw_download->toBeTrue()
        ->status->toBe(ConversionStatus::PENDING)
        ->max_size->toBeNull()
        ->audio->toBeTrue()
        ->audio_quality->toBe(1.0)
        ->auto_crop->toBeFalse()
        ->watermark->toBeFalse()
        ->audio_only->toBeFalse()
        ->trim_start->toBeNull()
        ->trim_end->toBeNull()
        ->subtitle_mode->toBe(SubtitleMode::None);

    expect($conversion->getMediaOperations())->toBe([]);
    expect($conversion->getFormatOperations())->toBe([]);

    Bus::assertDispatched(DownloadVideoJob::class);
});

it('builds the regular operations for the very same conversion without the flag', function (): void {
    $attributes = [
        'audio' => true,
        'audio_quality' => 1.0,
        'max_size' => null,
    ];

    $raw = Conversion::factory()->create([...$attributes, 'raw_download' => true]);
    $regular = Conversion::factory()->create([...$attributes, 'raw_download' => false]);

    // Operations whose constructor probes the media file (watermark, auto crop,
    // max size) are left out here — this guards the raw_download short circuit,
    // not the operation list itself.
    expect($raw->getMediaOperations())->toBe([])
        ->and($regular->getMediaOperations())->not->toBeEmpty()
        ->and(array_map(fn (object $operation): string => $operation::class, $regular->getMediaOperations()))
        ->toContain(RemoveExifDataFilterOperation::class);
});
