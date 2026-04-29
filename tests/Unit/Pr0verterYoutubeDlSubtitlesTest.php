<?php

declare(strict_types=1);

use App\Services\Pr0verterYoutubeDl;

beforeEach(function () {
    $this->tmpDir = sys_get_temp_dir() . '/p0v_subs_' . uniqid();
    mkdir($this->tmpDir);
});

afterEach(function () {
    array_map('unlink', glob($this->tmpDir . '/*') ?: []);
    rmdir($this->tmpDir);
});

it('prefers manual de subtitle over manual en', function () {
    $base = $this->tmpDir . '/clip';
    file_put_contents($base . '.mp4', 'fake');
    file_put_contents($base . '.de.srt', '1');
    file_put_contents($base . '.en.srt', '1');

    $svc = app(Pr0verterYoutubeDl::class);

    expect($svc->resolveSubtitlePath($base . '.mp4'))->toBe($base . '.de.srt');
});

it('falls back to manual en when de manual missing', function () {
    $base = $this->tmpDir . '/clip';
    file_put_contents($base . '.mp4', 'fake');
    file_put_contents($base . '.en.srt', '1');

    $svc = app(Pr0verterYoutubeDl::class);

    expect($svc->resolveSubtitlePath($base . '.mp4'))->toBe($base . '.en.srt');
});

it('falls back to auto-caption when no manual sub exists', function () {
    $base = $this->tmpDir . '/clip';
    file_put_contents($base . '.mp4', 'fake');
    file_put_contents($base . '.de-orig.srt', '1');

    $svc = app(Pr0verterYoutubeDl::class);

    expect($svc->resolveSubtitlePath($base . '.mp4'))->toBe($base . '.de-orig.srt');
});

it('returns null when no subtitle file exists', function () {
    $base = $this->tmpDir . '/clip';
    file_put_contents($base . '.mp4', 'fake');

    $svc = app(Pr0verterYoutubeDl::class);

    expect($svc->resolveSubtitlePath($base . '.mp4'))->toBeNull();
});

it('accumulates extra args via withExtraArgs (chainable)', function () {
    $svc = app(Pr0verterYoutubeDl::class);
    $result = $svc->withExtraArgs(['--write-sub']);

    expect($result)->toBe($svc);

    // Reflection check that args are stored
    $ref = new ReflectionClass($svc);
    $prop = $ref->getProperty('extraArgs');
    $prop->setAccessible(true);

    expect($prop->getValue($svc))->toBe(['--write-sub']);
});
