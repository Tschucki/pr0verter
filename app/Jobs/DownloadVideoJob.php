<?php

namespace App\Jobs;

use App\Enums\ConversionStatus;
use App\Enums\SubtitleMode;
use App\Enums\SubtitleStatus;
use App\Events\DownloadProgress;
use App\Models\Conversion;
use App\Models\File;
use App\Services\Pr0verterYoutubeDl;
use App\Services\ThumbnailService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use YoutubeDl\Options;

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

            $youtubeDl = app('pr0verter-yt-dlp');

            $youtubeDl->onProgress(function (?string $progressTarget, ?string $percentage = null, ?string $size = null, ?string $speed = null, ?string $eta = null, ?string $totalTime = null) use ($conversion): void {
                $iPercentage = $percentage !== null ? (int) str_replace('%', '', $percentage) : null;

                if ($iPercentage % 5 !== 0) {
                    return;
                }

                DownloadProgress::dispatch($conversion->id, $progressTarget, $percentage, $size, $speed, $eta, $totalTime);
            });

            $wantsSubs = $conversion->subtitle_mode !== SubtitleMode::None
                && $conversion->audio_only !== true;

            if ($wantsSubs) {
                $youtubeDl->withExtraArgs([
                    '--write-sub',
                    '--write-auto-sub',
                    '--sub-langs', 'de,en',
                    '--convert-subs', 'srt',
                    '--sub-format', 'best',
                ]);
            }

            // only supporting one video for now
            $options = Options::create()
                ->downloadPath(Storage::disk('conversions')->path('/'))
                ->restrictFileNames(true)
                ->continue(true)
                ->noPlaylist()
                ->ffmpegLocation(config('laravel-ffmpeg.binaries'))
                ->cookies(config('converter.cookies.file'))
                ->cleanupMetadata(true)
                ->maxDownloads(1)
                ->url($conversion->url);

            if ($conversion->audio_only === true) {
                $options = $options->format('bestaudio/best')
                    ->extractAudio(true)
                    ->audioFormat('mp3')
                    ->audioQuality(0);
            }

            $video = $youtubeDl->download($options)->getVideos()[0] ?? null;

            if ($video === null || $video->getError() !== null) {
                $errorMessage = $video?->getError() ?? 'yt-dlp lieferte kein Video zurück.';

                $conversion->update([
                    'status' => ConversionStatus::FAILED,
                    'error_message' => Str::limit($errorMessage, 255),
                ]);

                Log::error('Failed to download video', [
                    'conversion_id' => $conversion->id,
                    'error' => $errorMessage,
                ]);

                return;
            }

            if ($wantsSubs && $video !== null) {
                $videoPath = $video->getFile()->getPathname();
                $subPath = $youtubeDl->resolveSubtitlePath($videoPath);

                $conversion->update([
                    'subtitle_path' => $subPath,
                    'subtitle_status' => match (true) {
                        $subPath === null => SubtitleStatus::Unavailable,
                        $conversion->subtitle_mode === SubtitleMode::Soft => SubtitleStatus::Embedded,
                        $conversion->subtitle_mode === SubtitleMode::Burn => SubtitleStatus::Burnt,
                    },
                ]);
            }

            $fileName = Str::uuid()->toString() . '.' . $video->getFile()->getExtension();
            $exists = Storage::disk('conversions')->exists($video->getFile()->getFilename());

            $videoFullPath = $video->getFile()->getPathname();
            $moved = \Illuminate\Support\Facades\File::move($videoFullPath, Storage::disk('conversions')->path($fileName));

            if (! $exists || ! $moved) {
                $conversion->update([
                    'status' => ConversionStatus::FAILED,
                    'error_message' => 'Die Datei konnte nicht verschoben werden.',
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

            if ($conversion->audio_only === false) {
                $thumbnailPath = app(Pr0verterYoutubeDl::class)->getThumbnailPath($videoFullPath);
                if ($thumbnailPath !== null) {
                    app(ThumbnailService::class)->storeFromYoutubeDl($conversion, $thumbnailPath);
                }
            }

            ConversionJob::dispatch($conversion->id)->onQueue('converter');
        } catch (Throwable $th) {
            $conversion->update([
                'status' => ConversionStatus::FAILED,
                'error_message' => Str::limit($th->getMessage(), 250),
            ]);

            Log::error('Failed to download video', [
                'conversion_id' => $conversion->id,
                'error' => $th->getMessage(),
            ]);
        }
    }
}
