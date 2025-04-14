<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PcbBankService
{
    protected $username = 'TerminalSys/ECOM_TEST22';
    protected $password = '1234';
    protected $apiUrl = 'https://3dss2test.quipu.de:8000';

    /**
     * Create a payment order with PCB Bank
     *
     * @param float $amount
     * @param string $description
     * @param string $redirectUrl
     * @param string $currency (optional)
     * @return array|null
     */
    public function createOrder($amount, $description, $redirectUrl, $currency = 'EUR')
    {
        $payload = [
            "order" => [
                "typeRid" => "1",
                "amount" => number_format($amount, 2, '.', ''),
                "currency" => $currency,
                "description" => $description,
                "language" => "en",
                "hppRedirectUrl" => $redirectUrl,
                "initiationEnvKind" => "Browser",
                "consumerDevice" => [
                    "browser" => [
                        "javaEnabled" => false,
                        "jsEnabled" => true,
                        "acceptHeader" => "application/json",
                        "ip" => request()->ip(),
                        "colorDepth" => "24",
                        "screenW" => "1920",
                        "screenH" => "1080",
                        "tzOffset" => "-120",
                        "language" => "en-EN",
                        "userAgent" => request()->userAgent(),
                    ]
                ]
            ]
        ];

        Log::info('📤 Sending PCB createOrder request', ['payload' => $payload]);

        $response = Http::withBasicAuth($this->username, $this->password)
            ->post("{$this->apiUrl}/order", $payload);

        if ($response->successful()) {
            Log::info('✅ PCB createOrder success', ['response' => $response->json()]);
            return $response->json('order');
        }

        Log::error('❌ PCB Bank createOrder failed', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        return null;
    }

    /**
     * Get the order details from PCB after redirect
     *
     * @param string $orderId
     * @param string $password
     * @return array|null
     */
    public function getOrderDetails($orderId, $password)
    {
        Log::info('📥 Getting PCB order details', ['orderId' => $orderId]);

        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->apiUrl}/order/{$orderId}?password={$password}");

        if ($response->successful()) {
            Log::info('✅ PCB getOrderDetails success', ['response' => $response->json()]);
            return $response->json('order');
        }

        Log::error('❌ PCB getOrderDetails failed', [
            'orderId' => $orderId,
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        return null;
    }
}
