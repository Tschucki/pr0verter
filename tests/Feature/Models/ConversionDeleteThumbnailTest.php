<?php

declare(strict_types=1);

use App\Events\ConversionFinished;
use App\Events\ConversionUpdated;
use App\Models\Conversion;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Event::fake([ConversionUpdated::class, ConversionFinished::class]);
    Storage::fake('conversions');
});

it('removes thumbnail file when conversion is deleted', function (): void {
    $conversion = Conversion::factory()->create([
        'thumbnail_path' => 'thumbnails/abc.jpg',
    ]);
    Storage::disk('conversions')->put('thumbnails/abc.jpg', 'fake');

    $conversion->delete();

    Storage::disk('conversions')->assertMissing('thumbnails/abc.jpg');
});
