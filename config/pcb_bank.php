<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PCB Bank Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for PCB Bank payment gateway integration
    |
    */

    'merchant_id' => env('PCB_BANK_MERCHANT_ID', 'ECOM_TEST157'),
    
    'api_url' => env('PCB_BANK_API_URL', 'https://3dss2test.quipu.de:8000'),
    
    'portal_url' => env('PCB_BANK_PORTAL_URL', 'https://3dss2test.quipu.de:8004/'),
    
    'certificates' => [
        'cert_path' => env('PCB_BANK_CERT_PATH', storage_path('certs/cert.pem')),
        'key_path' => env('PCB_BANK_KEY_PATH', storage_path('certs/key.pem')),
        'ca_path' => env('PCB_BANK_CA_PATH', storage_path('certs/ca.pem')),
    ],
    
    'timeout' => env('PCB_BANK_TIMEOUT', 30),
    
    'default_currency' => env('PCB_BANK_DEFAULT_CURRENCY', 'EUR'),
    
    'default_language' => env('PCB_BANK_DEFAULT_LANGUAGE', 'en'),
    
    'supported_currencies' => ['EUR', 'USD', 'ALL'],
    
    'supported_languages' => ['en', 'sq', 'de'],
    
    'order_types' => [
        'purchase' => 'ORD1',
        'preauth' => 'ORD2',
        'preauth_completion' => 'ORD3',
    ],
    
    'success_statuses' => [
        'success',
        'fullypaid', 
        'paid',
        'completed',
        'approved'
    ],
    
    'pending_statuses' => [
        'preparing',
        'pending',
        'processing'
    ],
    
    'failed_statuses' => [
        'failed',
        'declined',
        'cancelled',
        'error'
    ],
];

