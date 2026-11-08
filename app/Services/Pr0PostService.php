<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;
use Tschucki\Pr0grammApi\Facades\Pr0grammApi;

class Pr0PostService
{
    /**
     * @throws RuntimeException when the session is not logged in or the upload is rejected,
     *                          so the report commands fail visibly instead of skipping the post
     */
    public function postImage(string $pathToImage, string $comment, array $tags = []): void
    {
        $this->ensureLoggedIn();

        $response = Pr0grammApi::Post()->upload($pathToImage);
        $key = $response->collect()->get('key');

        if ($response->status() !== 200 || ! is_string($key) || $key === '') {
            throw new RuntimeException('pr0gramm upload failed with status ' . $response->status() . ': ' . Str::limit($response->body(), 500));
        }

        Pr0grammApi::Post()->post($key, collect($tags)->implode(','), comment: $comment);
    }

    protected function ensureLoggedIn(): void
    {
        $loggedIn = Pr0grammApi::loggedIn();

        if (($loggedIn['loggedIn'] ?? false) !== true) {
            throw new RuntimeException('Not logged in to pr0gramm, the configured session cookie is missing or expired.');
        }
    }
}
