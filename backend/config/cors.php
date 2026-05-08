<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) Configuration
|--------------------------------------------------------------------------
|
| The Next.js admin panel runs on http://localhost:3000 in dev and on
| https://admin.live-events-map.tech in prod. Both need to be allowlisted
| so the browser can hit the Laravel API directly.
|
| Origins beyond these two come from the env var CORS_EXTRA_ORIGINS
| (comma-separated) so we don't have to redeploy to add a staging origin.
|
*/

$extra = array_filter(array_map('trim', explode(',', (string)env('CORS_EXTRA_ORIGINS', ''))));

return [

    // 'docs' / 'docs/*' covers the raw OpenAPI JSON that Swagger UI fetches.
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'docs', 'docs/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique(array_merge([
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'https://admin.live-events-map.tech',
        'https://live-events-map.tech',
    ], $extra))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // We use Bearer tokens (Sanctum personal access tokens), not cookies,
    // so credentials don't need to be true. Flip to true if you ever
    // switch to SPA cookie auth.
    'supports_credentials' => false,

];
