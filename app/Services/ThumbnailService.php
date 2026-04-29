<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Conversion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use RuntimeException;
use Throwable;

final class ThumbnailService
{
    private const string DISK = 'conversions';

    private const int QUALITY = 80;

    private const int LONG_EDGE = 480;

    public function storeFromYoutubeDl(Conversion $conversion, string $sourcePath): ?string
    {
        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            Log::warning('Thumbnail storeFromYoutubeDl source missing', [
                'conversion' => $conversion->id,
                'source' => $sourcePath,
            ]);

            return null;
        }

        try {
            $bytes = file_get_contents($sourcePath);
            if ($bytes === false) {
                throw new RuntimeException('Failed to read source thumbnail bytes');
            }

            $image = @imagecreatefromstring($bytes);
            if ($image === false) {
                throw new RuntimeException('Failed to decode source thumbnail');
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $longEdge = max($width, $height);

            $relative = 'thumbnails/' . $conversion->id . '.jpg';
            $absolute = Storage::disk(self::DISK)->path($relative);
            @mkdir(dirname($absolute), 0755, true);

            if ($longEdge > self::LONG_EDGE) {
                $scale = self::LONG_EDGE / $longEdge;
                $newWidth = (int) round($width * $scale);
                $newHeight = (int) round($height * $scale);

                $resized = imagecreatetruecolor($newWidth, $newHeight);
                if ($resized === false) {
                    imagedestroy($image);
                    throw new RuntimeException('Failed to allocate resized image');
                }

                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagejpeg($resized, $absolute, self::QUALITY);
                imagedestroy($resized);
            } else {
                imagejpeg($image, $absolute, self::QUALITY);
            }

            imagedestroy($image);

            $conversion->thumbnail_path = $relative;
            $conversion->save();

            @unlink($sourcePath);

            return $relative;
        } catch (Throwable $e) {
            Log::warning('Thumbnail storeFromYoutubeDl failed', [
                'conversion' => $conversion->id,
                'source' => $sourcePath,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function captureSourceFrame(Conversion $conversion, float $duration): ?string
    {
        $offset = max(0.0, $duration * 0.1);

        return $this->capture($conversion, $conversion->file->disk, $conversion->file->filename, $offset);
    }

    public function captureOutputFrame(Conversion $conversion, string $disk, string $filename, float $duration): ?string
    {
        $offset = max(0.0, $duration / 2);

        return $this->capture($conversion, $disk, $filename, $offset);
    }

    public function delete(Conversion $conversion): void
    {
        if ($conversion->thumbnail_path === null) {
            return;
        }

        try {
            Storage::disk(self::DISK)->delete($conversion->thumbnail_path);
        } catch (Throwable) {
            // never throw from delete
        }
    }

    private function capture(Conversion $conversion, string $sourceDisk, string $sourceFile, float $offsetSeconds): ?string
    {
        $relative = 'thumbnails/' . $conversion->id . '.jpg';
        $absolute = Storage::disk(self::DISK)->path($relative);
        @mkdir(dirname($absolute), 0755, true);

        try {
            FFMpeg::fromDisk($sourceDisk)
                ->open($sourceFile)
                ->getFrameFromSeconds($offsetSeconds)
                ->export()
                ->toDisk(self::DISK)
                ->save($relative);

            $this->resizeToLongEdge($absolute);

            $conversion->thumbnail_path = $relative;
            $conversion->save();

            return $relative;
        } catch (Throwable $e) {
            Log::warning('Thumbnail capture failed', [
                'conversion' => $conversion->id,
                'source' => "{$sourceDisk}:{$sourceFile}",
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function resizeToLongEdge(string $absolutePath): void
    {
        $info = getimagesize($absolutePath);
        if ($info === false) {
            throw new RuntimeException('Failed to read thumbnail dimensions');
        }

        [$width, $height] = $info;
        $longEdge = max($width, $height);

        if ($longEdge <= self::LONG_EDGE) {
            $image = imagecreatefromjpeg($absolutePath);
            if ($image === false) {
                throw new RuntimeException('Failed to load thumbnail for re-encode');
            }

            imagejpeg($image, $absolutePath, self::QUALITY);
            imagedestroy($image);

            return;
        }

        $scale = self::LONG_EDGE / $longEdge;
        $newWidth = (int) round($width * $scale);
        $newHeight = (int) round($height * $scale);

        $source = imagecreatefromjpeg($absolutePath);
        if ($source === false) {
            throw new RuntimeException('Failed to load thumbnail for resize');
        }

        $destination = imagecreatetruecolor($newWidth, $newHeight);
        if ($destination === false) {
            imagedestroy($source);
            throw new RuntimeException('Failed to allocate resized thumbnail');
        }

        imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagejpeg($destination, $absolutePath, self::QUALITY);
        imagedestroy($destination);
        imagedestroy($source);
    }
}
