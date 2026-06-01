<?php

return [
    'line_messaging' => [
        'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN', ''),
        'channel_secret' => env('LINE_CHANNEL_SECRET', ''),
    ],

    'paypay' => [
        'api_key'      => env('PAYPAY_API_KEY', ''),
        'api_secret'   => env('PAYPAY_API_SECRET', ''),
        'merchant_id'  => env('PAYPAY_MERCHANT_ID', ''),
        'api_base_url' => env('PAYPAY_API_BASE_URL', 'https://api.paypay.ne.jp'),
        'redirect_url' => env('PAYPAY_REDIRECT_URL', ''),
        'sandbox'      => env('PAYPAY_SANDBOX', true),
    ],
];