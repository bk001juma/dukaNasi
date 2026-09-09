<?php

return [
    'channel_token' => env('MAUZO360_CHANNEL_TOKEN', env('CHANNEL_TOKEN')),

    'legacy_app_url' => env(
        'MAUZO360_LEGACY_APP_URL',
        'http://localhost:8080/api/channel/dotransaction',
    ),
];
