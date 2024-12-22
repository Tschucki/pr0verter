<?php

return [
    'default_operations' => [
        \App\Conversion\MediaOperations\FastStartFilterOperation::class,
        \App\Conversion\MediaOperations\RemoveExifDataFilterOperation::class,
    ],
    'default_format_operations' => [

    ],
];
