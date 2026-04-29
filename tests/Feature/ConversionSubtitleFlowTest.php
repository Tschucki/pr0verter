<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;

it('rejects invalid subtitleMode values', function () {
    Queue::fake();

    $response = $this->post(route('converter.start'), [
        'url' => 'https://example.com/video',
        'audio' => true,
        'audioQuality' => 1.0,
        'maxSize' => 2000,
        'audio_only' => false,
        'subtitleMode' => 'rainbow',
    ]);

    $response->assertSessionHasErrors('subtitleMode');
});
