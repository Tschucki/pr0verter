<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use RuntimeException;
use Tschucki\Pr0grammApi\Facades\Pr0grammApi;

class Pr0PostService
{
    /**
     * There is no separate login check: pr0gramm removed the user/loggedin
     * action (404 "No such Action"), so the upload itself tells whether the
     * configured session cookie is still valid.
     *
     * @throws RuntimeException when no cookie is configured or pr0gramm rejects the upload,
     *                          so the report commands fail visibly instead of skipping the post
     */
    public function postImage(string $pathToImage, string $comment, array $tags = []): void
    {
        if (blank(config('services.pr0gramm.cookie'))) {
            throw new RuntimeException('No pr0gramm session cookie configured (PR0GRAMM_COOKIE).');
        }

        try {
            $response = Pr0grammApi::Post()->upload($pathToImage);
        } catch (RequestException $e) {
            if ($e->response->forbidden()) {
                throw new RuntimeException('pr0gramm rejected the upload as not logged in, the session cookie (PR0GRAMM_COOKIE) is probably expired.', previous: $e);
            }

            throw $e;
        }

        $key = $response->collect()->get('key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('pr0gramm upload returned no key: ' . Str::limit($response->body(), 500));
        }

        Pr0grammApi::Post()->post($key, collect($tags)->implode(','), comment: $comment);
    }
}
