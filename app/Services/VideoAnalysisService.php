<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AudioCodec;
use App\Enums\VideoCodec;
use App\ValueObjects\VideoMetadata;
use FFMpeg\FFProbe;
use Illuminate\Support\Facades\Log;

class VideoAnalysisService
{
    public function __construct(private readonly FFProbe $probe) {}

    public function analyze(string $filePath): VideoMetadata
    {
        $format = $this->probe->format($filePath);
        $streams = $this->probe->streams($filePath);

        $videoStream = $streams->videos()->first();
        $audioStream = $streams->audios()->first();

        $width = $videoStream?->get('width') ?? 0;
        $height = $videoStream?->get('height') ?? 0;
        $duration = (float) ($format->get('duration') ?? 0);
        $framerate = $this->extractFramerate($videoStream);
        $rotation = $this->extractRotation($videoStream);

        $videoCodecName = $videoStream?->get('codec_name');
        $audioCodecName = $audioStream?->get('codec_name');
        $audioSampleRate = $audioStream?->get('sample_rate');

        $videoCodec = $this->mapVideoCodec($videoCodecName);
        $audioCodec = $this->mapAudioCodec($audioCodecName);

        Log::info('Video analysis completed', [
            'width' => $width,
            'height' => $height,
            'duration' => $duration,
            'videoCodec' => $videoCodec?->value,
            'audioCodec' => $audioCodec?->value,
            'framerate' => $framerate,
            'rotation' => $rotation,
            'audioSampleRate' => $audioSampleRate,
        ]);

        return new VideoMetadata(
            width: $width,
            height: $height,
            duration: $duration,
            videoCodec: $videoCodec,
            audioCodec: $audioCodec,
            framerate: $framerate,
            rotation: $rotation,
            audioSampleRate: $audioSampleRate ? (int) $audioSampleRate : null,
        );
    }

    private function extractFramerate($videoStream): float
    {
        if ($videoStream === null) {
            return 30.0;
        }

        $rFrameRate = $videoStream->get('r_frame_rate');
        if ($rFrameRate && str_contains($rFrameRate, '/')) {
            [$num, $den] = explode('/', $rFrameRate);
            if ($den > 0) {
                return (float) ($num / $den);
            }
        }

        $avgFrameRate = $videoStream->get('avg_frame_rate');
        if ($avgFrameRate && str_contains($avgFrameRate, '/')) {
            [$num, $den] = explode('/', $avgFrameRate);
            if ($den > 0) {
                return (float) ($num / $den);
            }
        }

        return 30.0;
    }

    private function extractRotation($videoStream): int
    {
        if ($videoStream === null) {
            return 0;
        }

        $rotation = $videoStream->get('rotation');
        if ($rotation !== null) {
            return abs((int) $rotation);
        }

        $tags = $videoStream->get('tags');
        if (is_array($tags) && isset($tags['rotate'])) {
            return abs((int) $tags['rotate']);
        }

        return 0;
    }

    private function mapVideoCodec(?string $codecName): ?VideoCodec
    {
        if ($codecName === null) {
            return null;
        }

        return match ($codecName) {
            'h264' => VideoCodec::H264,
            'vp9' => VideoCodec::VP9,
            'vp8' => VideoCodec::VP8,
            'hevc', 'h265' => VideoCodec::HEVC,
            'av1' => VideoCodec::AV1,
            default => null,
        };
    }

    private function mapAudioCodec(?string $codecName): ?AudioCodec
    {
        if ($codecName === null) {
            return null;
        }

        return match ($codecName) {
            'aac' => AudioCodec::AAC,
            'opus' => AudioCodec::OPUS,
            'vorbis' => AudioCodec::VORBIS,
            'mp3' => AudioCodec::MP3,
            default => null,
        };
    }
}
