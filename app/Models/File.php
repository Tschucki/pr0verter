<?php

namespace App\Models;

use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    /** @use HasFactory<FileFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function getFullPathAttribute(): string
    {
        return Storage::disk($this->disk)->path($this->filename);
    }

    public function conversion(): HasOne
    {
        return $this->hasOne(Conversion::class);
    }
}
