<?php

declare(strict_types=1);

use App\Enums\ConversionStatus;
use App\Models\Conversion;
use App\Models\File;
use App\Models\Statistic;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('conversions');
    Storage::fake('local');
});

it('deletes old conversions and their files without orphaning statistics', function (): void {
    $this->travel(-3)->hours();
    $conversion = Conversion::factory()->create(['status' => ConversionStatus::FINISHED]);
    $this->travelBack();

    expect(Statistic::where('conversion_id', $conversion->id)->exists())->toBeTrue();

    $this->artisan('app:cleanup')->assertSuccessful();

    expect(Conversion::find($conversion->id))->toBeNull()
        ->and(File::find($conversion->file_id))->toBeNull()
        ->and(Statistic::count())->toBe(1)
        ->and(Statistic::first()->conversion_id)->toBeNull();
});

it('deletes recent conversions whose file is being cleaned up', function (): void {
    $this->travel(-3)->hours();
    $file = File::factory()->create();
    $this->travelBack();

    $conversion = Conversion::factory()->create([
        'file_id' => $file->id,
        'session_id' => $file->session_id,
    ]);

    $this->artisan('app:cleanup')->assertSuccessful();

    expect(Conversion::find($conversion->id))->toBeNull()
        ->and(Statistic::count())->toBe(1);
});
