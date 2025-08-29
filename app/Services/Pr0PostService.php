<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Tschucki\Pr0grammApi\Facades\Pr0grammApi;

class Pr0PostService
{
    public function postImage(string $pathToImage, string $comment, array $tags = []): void
    {
        $loggedIn = $this->loginToPr0gramm();

        if (! $loggedIn) {
            return;
        }

        $response = Pr0grammApi::Post()->upload($pathToImage);

        if ($response->status() === 200) {
            $tagString = collect($tags)->implode(',');
            Pr0grammApi::Post()->post($response->collect()->get('key'), $tagString, comment: $comment);
        }
    }

    protected function loginToPr0gramm(): bool
    {
        $loggedIn = Pr0grammApi::loggedIn();

        if (! $loggedIn['loggedIn']) {
            Log::error('Could not log in to pr0gramm');

            return false;
        }

        return $loggedIn['loggedIn'];
    }
}
