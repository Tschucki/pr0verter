<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DuskTestCase;

class ConversionTest extends DuskTestCase
{
    use RefreshDatabase;

    public static function conversionUrlProvider(): array
    {
        return [
            'pr0gramm' => ['https://vid.pr0gramm.com/2025/01/29/b9542c91cde48f99.mp4', 'pr0gramm'],
        ];
    }

    #[DataProvider('conversionUrlProvider')]
    public function test_video_conversion_flow(string $url, string $type): void
    {
        $this->browse(function (Browser $browser) use ($url, $type) {
            $browser->visit('/')
                ->assertSee('Konvertierung')
                ->click('.download-tab-trigger')
                ->assertSee('URL eingeben')
                ->assertInputPresent('url')
                ->type('url', $url)
                ->click('#startConversionButton')
                ->waitForRoute('conversions.list', seconds: 3)
                ->assertSee('Meine Konvertierungen')
                ->screenshot("conversion-start-{$type}");

            $this->assertDatabaseHas('conversions', [
                'url' => $url,
            ]);

            sleep(60);

            $browser->refresh()
                ->assertSee('Konvertierung')
                ->assertDontSee('Keine Konvertierungen vorhanden')
                ->assertButtonEnabled('#download-button')
                ->screenshot("conversion-ready-to-download-{$type}");
        });
    }
}
