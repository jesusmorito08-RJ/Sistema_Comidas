<?php

return [
    'business_name' => env('PAYMENTS_BUSINESS_NAME') ?: env('APP_NAME'),
    'methods' => [
        'yape' => [
            'holder' => env('PAYMENTS_YAPE_HOLDER') ?: env('PAYMENTS_YAPE_RECIPIENT'),
            'phone' => env('PAYMENTS_YAPE_PHONE'),
            'qr_path' => env('PAYMENTS_YAPE_QR_PATH'),
        ],
        'plin' => [
            'holder' => env('PAYMENTS_PLIN_HOLDER') ?: env('PAYMENTS_PLIN_RECIPIENT'),
            'phone' => env('PAYMENTS_PLIN_PHONE'),
            'qr_path' => env('PAYMENTS_PLIN_QR_PATH'),
        ],
    ],
];
