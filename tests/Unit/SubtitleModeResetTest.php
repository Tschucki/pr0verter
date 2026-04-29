<?php

declare(strict_types=1);

use App\Conversion\ConversionSettings;
use App\Enums\SubtitleMode;

it('parses subtitle_mode from request array', function () {
    $settings = new ConversionSettings(['subtitle_mode' => 'burn']);

    expect($settings->subtitleMode)->toBe(SubtitleMode::Burn);
});

it('defaults subtitle_mode to None when missing', function () {
    $settings = new ConversionSettings([]);

    expect($settings->subtitleMode)->toBe(SubtitleMode::None);
});

it('forces subtitle_mode to None when audio_only is true', function () {
    $settings = new ConversionSettings([
        'subtitle_mode' => 'burn',
        'audio_only' => true,
    ]);

    expect($settings->subtitleMode)->toBe(SubtitleMode::None);
});

it('serialises subtitle_mode back to string in toArray', function () {
    $settings = new ConversionSettings(['subtitle_mode' => 'soft']);

    expect($settings->toArray()['subtitle_mode'])->toBe('soft');
});
