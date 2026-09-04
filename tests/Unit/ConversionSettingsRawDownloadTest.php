<?php

declare(strict_types=1);

use App\Conversion\ConversionSettings;
use App\Enums\SubtitleMode;

it('drops every media operation setting when rawDownload is enabled', function (): void {
    $settings = ConversionSettings::fromRequest([
        'rawDownload' => true,
        'audio' => false,
        'audioQuality' => 0.5,
        'trimStart' => '10',
        'trimEnd' => '20',
        'maxSize' => 200,
        'autoCrop' => true,
        'watermark' => true,
        'interpolation' => true,
        'segments' => [['start' => 0, 'duration' => 5]],
        'audio_only' => true,
        'subtitleMode' => 'burn',
    ]);

    expect($settings)
        ->rawDownload->toBeTrue()
        ->audio->toBeTrue()
        ->audioQuality->toBe(1.0)
        ->trimStart->toBeNull()
        ->trimEnd->toBeNull()
        ->maxSize->toBeNull()
        ->autoCrop->toBeFalse()
        ->watermark->toBeFalse()
        ->interpolation->toBeFalse()
        ->segments->toBe([])
        ->audio_only->toBeFalse()
        ->subtitleMode->toBe(SubtitleMode::None);
});

it('keeps the regular settings untouched when rawDownload is disabled', function (): void {
    $settings = ConversionSettings::fromRequest([
        'rawDownload' => false,
        'audio' => true,
        'audioQuality' => 0.5,
        'maxSize' => 200,
        'watermark' => true,
    ]);

    expect($settings)
        ->rawDownload->toBeFalse()
        ->audioQuality->toBe(0.5)
        ->maxSize->toBe(200)
        ->watermark->toBeTrue();
});
