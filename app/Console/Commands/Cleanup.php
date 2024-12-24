<?php

namespace App\Console\Commands;

use App\Models\Conversion;
use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class Cleanup extends Command
{
    protected $signature = 'app:cleanup';

    protected $description = 'Command description';

    public function handle(): void
    {
        $this->info('Starting cleanup');

        $files = File::where('created_at', '<', now()->subHours(2))->get();
        $conversions = Conversion::where('created_at', '<', now()->subHours(2))->get();

        $this->info('Deleting ' . $files->count() . ' files');

        $this->withProgressBar($files, function ($file) {
            // local file deletion runs in the observer
            $file->delete();
        });

        $this->info("\nDeleting " . $conversions->count() . ' conversions');

        $this->withProgressBar($conversions, function ($conversion) {
            $conversion->delete();
        });

        $this->info("\nScanning local disk for orphaned files");

        $localFiles = collect(Storage::disk('conversions')->files());

        $this->info('Found ' . $localFiles->count() . ' files');

        $orphanedFiles = $localFiles->filter(function ($fileName) {
            $hash = Str::before($fileName, '.');
            if (Str::contains($hash, '-converted')) {
                $hash = Str::before($hash, '-converted');
            }

            return ! File::where('filename', $fileName)->exists() && ! File::where('filename', $hash)->exists();
        });

        $this->info('Deleting ' . $orphanedFiles->count() . ' orphaned files');

        $this->withProgressBar($orphanedFiles, function ($fileName) {
            Storage::disk('conversions')->delete($fileName);
        });

        $this->info("\nCleanup FFmpeg temp files");

        FFMpeg::cleanupTemporaryFiles();

        $this->info('Cleanup done');
    }
}
