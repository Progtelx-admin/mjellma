<?php
return [
    'gateways' => [
        'offline_payment' => Modules\Booking\Gateways\OfflinePaymentGateway::class,
        'paypal' => Modules\Booking\Gateways\PaypalGateway::class,
        'stripe' => Modules\Booking\Gateways\StripeGateway::class,
        'payrexx' => Modules\Booking\Gateways\PayrexxGateway::class,
        'paystack' => Modules\Booking\Gateways\PaystackGateway::class,
        'pcb_bank' => Modules\Booking\Gateways\PcbBankGateway::class,
    ],
];
