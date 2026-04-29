<?php

declare(strict_types=1);

use App\Conversion\Formats\H264FormatWithSubs;
use App\Enums\QualityTier;

it('appends subtitle input + mov_text mapping to extra params', function () {
    $format = new H264FormatWithSubs(QualityTier::FULL_HD, '/abs/path/clip.de.srt');

    $params = $format->getExtraParams();

    expect($params)->toContain('-i')
        ->toContain('/abs/path/clip.de.srt')
        ->toContain('-c:s')
        ->toContain('mov_text');
});

it('still includes base x264 params from parent', function () {
    $format = new H264FormatWithSubs(QualityTier::FULL_HD, '/abs/path/clip.srt');

    $params = $format->getExtraParams();

    expect($params)->toContain('-preset')
        ->toContain('medium')
        ->toContain('-pix_fmt')
        ->toContain('yuv420p');
});
