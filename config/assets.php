<?php

return [
    'watermark' => [
        'local_path' => public_path('assets/watermark.png'),
        'position' => 'absolute',
        'y' => 30,
        'x' => 30
    ],
    // Use `require` (not `require_once`) so config caching always gets the expected array.
    'fonts' => require var_path('config/assets/fonts.php'),
];
