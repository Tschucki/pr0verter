<?php

namespace App\Jobs;

use App\Enums\ConversionStatus;
use App\Events\DownloadProgress;
use App\Models\Conversion;
use App\Models\File;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use YoutubeDl\Options;
use YoutubeDl\YoutubeDl;

class DownloadVideoJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(public string $conversionId) {}

    public function handle(): void
    {
        $conversion = Conversion::find($this->conversionId);

        if ($conversion === null) {
            return;
        }
        try {
            $conversion->update([
                'status' => ConversionStatus::DOWNLOADING,
            ]);

            $youtubeDl = app(YoutubeDl::class);

            $youtubeDl->onProgress(function (?string $progressTarget, ?string $percentage = null, ?string $size = null, ?string $speed = null, ?string $eta = null, ?string $totalTime = null) use ($conversion): void {
                $iPercentage = $percentage !== null ? (int) str_replace('%', '', $percentage) : null;

                if ($iPercentage % 5 !== 0) {
                    return;
                }

                DownloadProgress::dispatch($conversion->id, $progressTarget, $percentage, $size, $speed, $eta, $totalTime);
            });

            // only supporting one video for now
            $video = $youtubeDl->download(
                Options::create()
                    ->downloadPath(Storage::disk('conversions')->path('/'))
                    ->restrictFileNames(true)
                    ->continue(true)
                    ->noPlaylist()
                    ->cookies(config('converter.cookies.file'))
                    ->cleanupMetadata(true)
                    ->maxDownloads(1)
                    ->url($conversion->url)
            )->getVideos()[0] ?? null;

            if ($video === null || $video->getError() !== null) {
                $conversion->update([
                    'status' => ConversionStatus::FAILED,
                ]);

                Log::error('Failed to download video', [
                    'conversion_id' => $conversion->id,
                    'error' => $video->getError(),
                ]);

                return;
            }

            $fileName = Str::uuid()->toString() . '.' . $video->getFile()->getExtension();
            $exists = Storage::disk('conversions')->exists($video->getFile()->getFilename());

            $moved = \Illuminate\Support\Facades\File::move($video->getFile()->getPathname(), Storage::disk('conversions')->path($fileName));

            if (! $exists || ! $moved) {
                $conversion->update([
                    'status' => ConversionStatus::FAILED,
                ]);

                Log::error('Failed to download video', [
                    'conversion_id' => $conversion->id,
                ]);

                return;
            }

            $file = File::create([
                'filename' => $fileName,
                'disk' => 'conversions',
                'mime_type' => Storage::disk('conversions')->mimeType($fileName),
                'size' => Storage::disk('conversions')->size($fileName),
                'extension' => pathinfo($fileName, PATHINFO_EXTENSION),
                'session_id' => $conversion->session_id,
            ]);

            $conversion->update([
                'file_id' => $file->id,
                'status' => ConversionStatus::PREPARING,
            ]);

            ConversionJob::dispatch($conversion->id)->onQueue('converter');
        } catch (Throwable $th) {
            $conversion->update([
                'status' => ConversionStatus::FAILED,
            ]);

            Log::error('Failed to download video', [
                'conversion_id' => $conversion->id,
                'error' => $th->getMessage(),
            ]);
        }
    }
}
