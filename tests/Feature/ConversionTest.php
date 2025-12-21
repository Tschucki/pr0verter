<?php

namespace Tests\Browser;


use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Mockery;
use Mockery\MockInterface;
use function Pest\Laravel\partialMock;

dataset('conversion urls', [
    'pr0gramm' => ['https://vid.pr0gramm.com/2025/01/29/b9542c91cde48f99.mp4', 'pr0gramm'],
]);

it('successfully completes video conversion flow', function (string $url, string $type) {
    $sessionId = Session::getId();

    DB::table('sessions')->insert([
        'id' => $sessionId,
        'payload' => '',
        'last_activity' => time(),
    ]);

    $page = visit('/');
    $page->navigate('/');

    $page->assertSee('Konvertierung')
        ->click('.download-tab-trigger')
        ->assertSee('URL eingeben')
        ->type('[name="url"]', $url)
        ->click('#startConversionButton')
        ->wait(3);

    $page = $page->navigate('/conversions');

    $page->assertSee('Meine Konvertierungen');

    $page->wait(60);

    $page->navigate('/conversions')
        ->assertDontSee('Keine Konvertierungen vorhanden');

    $page->assertEnabled('#download-button');
})->with('conversion urls');
