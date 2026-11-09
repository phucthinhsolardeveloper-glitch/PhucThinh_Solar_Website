<?php

// Uploaded files the Nuxt source links to directly (logo, fixed illustrations). The media inventory
// counts them as used even though no database row points at them. frontend/tests/media-pinned.test.mjs
// fails when the frontend links to an upload missing from this list.
return [
    'pinned' => [
        'news/svgviewer-png-output-1-2932.png',
        'news/svgviewer-png-output-2-4536.png',
        'news/svgviewer-png-output-3-5470.png',
        'photo/logo-phucthinh-solar-wordmark-2026.png',
        'product/t-bat-tsys-d53-2-4295.png',
        'product/x3-pro-20-2-2061.png',
        'product/x3-pro-30-2-9905.png',
    ],
];
