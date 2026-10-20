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

    'charge' => [
        'paypay_fee_rate' => (float) env('CHARGE_PAYPAY_FEE_RATE', 0.05),
        'frontend_allowed_origins' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('FRONTEND_ALLOWED_ORIGINS', ''))
        ))),
        'transfer_instructions' => [
            'bank_name' => env('CHARGE_BANK_NAME', ''),
            'branch_name' => env('CHARGE_BANK_BRANCH_NAME', ''),
            'account_type' => env('CHARGE_BANK_ACCOUNT_TYPE', '普通'),
            'account_number' => env('CHARGE_BANK_ACCOUNT_NUMBER', ''),
            'account_holder' => env('CHARGE_BANK_ACCOUNT_HOLDER', ''),
            'deadline' => env('CHARGE_BANK_TRANSFER_DEADLINE', ''),
        ],
    ],
];