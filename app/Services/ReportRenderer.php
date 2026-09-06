<?php

declare(strict_types=1);

namespace App\Services;

use Spatie\Browsershot\Browsershot;
use Spatie\Image\Image;

class WeeklyReportRenderer
{
    public function renderPng(string $html, string $targetPath): void
    {
        $browsershot = Browsershot::html($html)
            ->noSandbox()
            ->deviceScaleFactor(3)
            ->fullPage()
            ->disableCaptureURLs()
            ->preventUnsuccessfulResponse()
            ->delay(500)
            ->hideBrowserHeaderAndFooter()
            ->setOption('args', ['--disable-web-security', '--waitForFonts'])
            ->waitUntilNetworkIdle()
            ->windowSize(1052, 0)
            ->setNodeBinary(config('binaries.node'))
            ->setNpmBinary(config('binaries.npm'));

        if ($chromiumPath = config('binaries.chromium')) {
            $browsershot->setChromePath($chromiumPath);
        }

        $browsershot->save($targetPath);

        Image::load($targetPath)->width(1052)->save();
    }
}
