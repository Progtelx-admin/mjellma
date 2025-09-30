<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Invoice Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for invoice generation and email sending
    |
    */

    'merchant' => [
        'name' => env('INVOICE_MERCHANT_NAME', 'Mjellma Travel'),
        'address' => env('INVOICE_MERCHANT_ADDRESS', 'Rr. Nëna Terezë, Nr. 1'),
        'city' => env('INVOICE_MERCHANT_CITY', '10 000 Pristina, Kosovo'),
        'website' => env('INVOICE_MERCHANT_WEBSITE', 'www.mjellmatravel.com'),
        'email' => env('INVOICE_MERCHANT_EMAIL', 'info@mjellmatravel.com'),
        'phone' => env('INVOICE_MERCHANT_PHONE', '+383 38 123 123'),
    ],

    'email' => [
        'from_name' => env('INVOICE_EMAIL_FROM_NAME', 'Mjellma Travel'),
        'from_email' => env('INVOICE_EMAIL_FROM', 'info@mjellmatravel.com'),
        'subject_prefix' => env('INVOICE_EMAIL_SUBJECT_PREFIX', 'Invoice'),
    ],

    'pdf' => [
        'storage_path' => 'invoices',
        'disk' => 'local',
    ],
];
