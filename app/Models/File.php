<?php

namespace App\Models;

use App\Observers\FileObserver;
use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[ObservedBy(FileObserver::class)]
class File extends Model
{
    /** @use HasFactory<FileFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['created_at_diff'] = $this->created_at->diffForHumans();
        $array['size_in_mb'] = number_format($this->size / 1024 / 1024, 2);

        return $array;
    }

    public function getFullPathAttribute(): string
    {
        return Storage::disk($this->disk)->path($this->filename);
    }

    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }

    public function convertedFileName(): string
    {
        return Str::rtrim($this->filename, '.' . $this->extension) . '-converted.mp4';
    }

    public function deleteLocalFile(): void
    {
        $convertedFileName = $this->convertedFileName();

        Storage::disk($this->disk)->delete($convertedFileName);
        Storage::disk($this->disk)->delete($this->filename);
    }

    public function isPublic(): bool
    {
        return $this->public;
    }
}
