<?php

declare(strict_types=1);

use App\Services\Pr0PostService;
use Tschucki\Pr0grammApi\Facades\Pr0grammApi;

it('fails loudly instead of skipping the post when not logged in', function (): void {
    Pr0grammApi::shouldReceive('loggedIn')->once()->andReturn(['loggedIn' => false]);
    Pr0grammApi::shouldReceive('Post')->never();

    app(Pr0PostService::class)->postImage('/tmp/report.png', 'comment', ['tag']);
})->throws(RuntimeException::class, 'Not logged in to pr0gramm');
