<?php

namespace App\Models;

use Database\Factories\ConversionPresetFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversionPreset extends Model
{
    /** @use HasFactory<ConversionPresetFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];
}
