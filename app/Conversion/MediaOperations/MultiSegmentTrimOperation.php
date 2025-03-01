<?php

namespace App\Conversion\MediaOperations;

use App\Contracts\MediaFilterOperation;
use App\Models\Conversion;
use ProtoneMedia\LaravelFFMpeg\MediaOpener;

class MultiSegmentTrimOperation implements MediaFilterOperation
{
    public Conversion $conversion;

    public array $segments;

    public function __construct(Conversion $conversion, array $segments)
    {
        $this->conversion = $conversion;
        $this->segments = $segments;
    }

    public function applyToMedia(MediaOpener $media): MediaOpener
    {
        if (empty($this->segments)) {
            return $media;
        }

        $filterComplex = [];
        $videoLabels = [];
        $audioLabels = [];

        foreach ($this->segments as $index => $segment) {
            $start = $segment['start'] ?? 0;
            $duration = $segment['duration'] ?? null;

            // Video segment with improved frame handling
            $videoFilter = "[0:v]trim=start={$start}";
            if ($duration !== null) {
                $videoFilter .= ":duration={$duration}";
            }
            $videoFilter .= ',setpts=PTS-STARTPTS';

            // Add framerate filter to ensure consistent frames
            $videoFilter .= ",fps=fps=25[v{$index}]";

            $filterComplex[] = $videoFilter;
            $videoLabels[] = "[v{$index}]";

            // Audio segment
            $audioFilter = "[0:a]atrim=start={$start}";
            if ($duration !== null) {
                $audioFilter .= ":duration={$duration}";
            }
            $audioFilter .= ",asetpts=PTS-STARTPTS[a{$index}]";
            $filterComplex[] = $audioFilter;
            $audioLabels[] = "[a{$index}]";
        }

        // Concatenate with format specification
        $filterComplex[] = implode('', $videoLabels) . 'concat=n=' . count($this->segments) . ':v=1:a=0:unsafe=1[outv]';
        $filterComplex[] = implode('', $audioLabels) . 'concat=n=' . count($this->segments) . ':v=0:a=1:unsafe=1[outa]';

        // Add the complex filter
        $media->addFilter(['-filter_complex', implode(';', $filterComplex)]);

        // Map the output streams
        $media->addFilter(['-map', '[outv]']);
        $media->addFilter(['-map', '[outa]']);

        // Ensure consistent encoding
        $media->addFilter(['-vsync', '1']);

        return $media;
    }
}
