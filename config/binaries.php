<?php

return [
    'node' => env('NODE_BIN_PATH', '/usr/bin/node'),
    'npm' => env('NPM_BIN_PATH', '/usr/bin/npm'),
    'node_modules' => env('NODE_MODULES_PATH', base_path('node_modules')),
];
