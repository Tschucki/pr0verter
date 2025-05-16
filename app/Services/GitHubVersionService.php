<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GitHubVersionService
{
    public function getVersion(): string
    {
        return Cache::remember('github_version', 3600, function () {
            try {
                $response = Http::get('https://api.github.com/repos/Tschucki/pr0verter/releases/latest');

                if ($response->successful()) {
                    return $response->json('tag_name');
                }

                $tagsResponse = Http::get('https://api.github.com/repos/Tschucki/pr0verter/tags');

                if ($tagsResponse->successful() && ! empty($tagsResponse->json())) {
                    return $tagsResponse->json()[0]['name'];
                }

                return 'dev';
            } catch (Exception $e) {
                report($e);

                return 'dev';
            }
        });
    }
}
