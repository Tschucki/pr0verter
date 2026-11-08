<?php

declare(strict_types=1);

use App\Services\Pr0PostService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('weekly-reports/report.png', 'png-bytes');

    // The nonce is the first 16 chars of the session id inside the cookie.
    config(['services.pr0gramm.cookie' => 'me=' . rawurlencode('{"n":"pr0verter","id":"0123456789abcdef0123"}')]);

    $this->imagePath = Storage::disk('local')->path('weekly-reports/report.png');
});

it('uploads and posts without calling the removed user/loggedin action', function (): void {
    Http::fake([
        'pr0gramm.com/api/items/upload' => Http::response(['key' => 'upload-key']),
        'pr0gramm.com/api/items/post' => Http::response(['error' => null, 'item' => ['id' => 1]]),
        '*' => Http::response(['error' => 'notFound', 'code' => 404, 'msg' => 'No such Action'], 404),
    ]);

    app(Pr0PostService::class)->postImage($this->imagePath, 'Wöchentlicher Bericht', ['pr0verter', 'sfw']);

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'items/post')
        && $request['key'] === 'upload-key'
        && $request['tags'] === 'pr0verter,sfw');
    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'user/loggedin'));
});

it('fails with a clear message when the session cookie is expired', function (): void {
    Http::fake([
        'pr0gramm.com/api/items/upload' => Http::response(['error' => 'forbidden', 'code' => 403, 'msg' => 'Nicht eingeloggt'], 403),
    ]);

    app(Pr0PostService::class)->postImage($this->imagePath, 'comment');
})->throws(RuntimeException::class, 'pr0gramm rejected the upload as not logged in');

it('fails before calling pr0gramm when no cookie is configured', function (): void {
    config(['services.pr0gramm.cookie' => null]);
    Http::fake();

    try {
        app(Pr0PostService::class)->postImage($this->imagePath, 'comment');
    } finally {
        Http::assertNothingSent();
    }
})->throws(RuntimeException::class, 'No pr0gramm session cookie configured');
