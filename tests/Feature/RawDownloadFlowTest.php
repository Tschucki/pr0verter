<?php

declare(strict_types=1);

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

it('still builds media operations for regular conversions', function (): void {
    $conversion = Conversion::factory()->create([
        'raw_download' => false,
        'watermark' => true,
        'max_size' => 200,
        'audio_quality' => 1.0,
    ]);

    expect($conversion->getMediaOperations())->not->toBe([]);
    expect($conversion->getFormatOperations())->not->toBe([]);
});
