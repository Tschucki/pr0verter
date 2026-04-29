<?php

declare(strict_types=1);

use App\Conversion\Formats\H264Format;
use App\Conversion\Formats\H264FormatWithSubs;
use App\Conversion\MediaOperations\EmbedSubtitlesFormatOperation;
use App\Enums\QualityTier;
use App\Models\Conversion;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class);

it('returns H264FormatWithSubs preserving the quality tier', function () {
    Storage::fake('conversions');

    $conversion = Mockery::mock(Conversion::class)->makePartial();
    $conversion->subtitle_path = 'subs/clip.de.srt';
    $conversion->file = (object) ['disk' => 'conversions'];

    $original = new H264Format(QualityTier::FULL_HD);

    $op = new EmbedSubtitlesFormatOperation($conversion);
    $newFormat = $op->applyToFormat($original);

    expect($newFormat)->toBeInstanceOf(H264FormatWithSubs::class)
        ->and($newFormat->getQualityTier())->toBe(QualityTier::FULL_HD);
});
