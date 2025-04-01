<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PcbBankService
{
    protected $username = 'TerminalSys/ECOM_TEST22';
    protected $password = '1234';
    protected $apiUrl = 'https://3dss2test.quipu.de:8000';

    public function createOrder($amount, $description, $redirectUrl)
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->post("{$this->apiUrl}/order", [
                "order" => [
                    "typeRid" => "1",
                    "amount" => number_format($amount, 2, '.', ''),
                    "currency" => "EUR",
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
            ]);

        if ($response->successful()) {
            return $response->json('order');
        }

        Log::error('PCB Bank createOrder failed', ['response' => $response->body()]);
        return null;
    }

    public function getOrderDetails($orderId, $password)
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->get("{$this->apiUrl}/order/{$orderId}?password={$password}");

        if ($response->successful()) {
            return $response->json('order');
        }

        Log::error('PCB Bank getOrderDetails failed', ['response' => $response->body()]);
        return null;
    }
}
