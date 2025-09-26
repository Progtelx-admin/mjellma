<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PcbBankService
{
    protected $merchantId;
    protected $apiUrl;
    protected $certPath;
    protected $keyPath;
    protected $caPath;
    protected $timeout;

    public function __construct()
    {
        $this->merchantId = config('pcb_bank.merchant_id');
        $this->apiUrl = config('pcb_bank.api_url');
        $this->certPath = config('pcb_bank.certificates.cert_path');
        $this->keyPath = config('pcb_bank.certificates.key_path');
        $this->caPath = config('pcb_bank.certificates.ca_path');
        $this->timeout = config('pcb_bank.timeout');
    }

    /**
     * Create a payment order with PCB Bank
     *
     * @param float $amount
     * @param string $description
     * @param string $redirectUrl
     * @param string $currency (optional)
     * @return array|null
     */
    public function createOrder($amount, $description, $redirectUrl, $currency = null)
    {
        $payload = [
            "order" => [
                "typeRid" => "1", // Use "1" for purchase as per documentation
                "amount" => number_format($amount, 2, '.', ''),
                "currency" => $currency ?: config('pcb_bank.default_currency'),
                "description" => $description,
                "language" => config('pcb_bank.default_language'),
                "hppRedirectUrl" => $redirectUrl,
                "initiationEnvKind" => "Browser",
                "consumerDevice" => [
                    "browser" => [
                        "javaEnabled" => false,
                        "jsEnabled" => true,
                        "acceptHeader" => "application/json,application/jose;charset=utf-8",
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

        $response = $this->makeAuthenticatedRequest('POST', '/order', $payload);

        if ($response) {
            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('✅ PCB createOrder success', ['response' => $responseData]);
                
                if (isset($responseData['order'])) {
                    return $responseData['order'];
                } else {
                    Log::warning('⚠️ PCB createOrder response missing order data', ['response' => $responseData]);
                    return null;
                }
            } else {
                Log::error('❌ PCB Bank createOrder failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'headers' => $response->headers()
                ]);
            }
        } else {
            Log::error('❌ PCB Bank createOrder - No response received');
        }

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

        $url = "/order/{$orderId}?password={$password}&tokenDetailLevel=2&tranDetailLevel=1";
        $response = $this->makeAuthenticatedRequest('GET', $url);

        if ($response && $response->successful()) {
            Log::info('✅ PCB getOrderDetails success', ['response' => $response->json()]);
            return $response->json('order');
        }

        Log::error('❌ PCB getOrderDetails failed', [
            'orderId' => $orderId,
            'status' => $response ? $response->status() : 'No response',
            'response' => $response ? $response->body() : 'Request failed'
        ]);

        return null;
    }

    /**
     * Make authenticated request using TLS certificate
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return \Illuminate\Http\Client\Response|null
     */
    private function makeAuthenticatedRequest($method, $endpoint, $data = null)
    {
        try {
            // Convert Windows paths to forward slashes for cURL
            $certPath = str_replace('\\', '/', $this->certPath);
            $keyPath = str_replace('\\', '/', $this->keyPath);
            $caPath = str_replace('\\', '/', $this->caPath);
            
            Log::info('🔧 PCB Bank TLS Configuration', [
                'cert_path' => $certPath,
                'key_path' => $keyPath,
                'ca_path' => $caPath,
                'cert_exists' => file_exists($certPath),
                'key_exists' => file_exists($keyPath),
                'ca_exists' => file_exists($caPath)
            ]);

            $client = Http::withOptions([
                'verify' => false, // Disable SSL verification for self-signed certificates
                'cert' => [$certPath, ''], // Certificate file and password (empty for no password)
                'ssl_key' => [$keyPath, ''], // Private key file and password (empty for no password)
                'timeout' => $this->timeout,
                'http_errors' => false, // Don't throw exceptions for HTTP errors
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false, // Disable peer verification for self-signed certs
                    CURLOPT_SSL_VERIFYHOST => false, // Disable host verification for self-signed certs
                    CURLOPT_SSLCERT => $certPath,
                    CURLOPT_SSLKEY => $keyPath,
                    CURLOPT_CAINFO => $caPath,
                ]
            ]);

            $url = $this->apiUrl . $endpoint;
            
            Log::info('🌐 PCB Bank Request', [
                'method' => $method,
                'url' => $url,
                'endpoint' => $endpoint
            ]);

            if ($method === 'GET') {
                $response = $client->get($url);
            } else {
                $response = $client->post($url, $data);
            }
            
            Log::info('📡 PCB Bank Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body()
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error('❌ PCB Bank TLS request failed', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Process a refund for an order
     *
     * @param string $orderId
     * @param string $password
     * @param float $amount
     * @return array|null
     */
    public function refundOrder($orderId, $password, $amount)
    {
        Log::info('💰 Processing PCB refund', ['orderId' => $orderId, 'amount' => $amount]);

        $payload = [
            "tran" => [
                "phase" => "Single",
                "amount" => number_format($amount, 2, '.', ''),
                "type" => "Refund"
            ]
        ];

        $endpoint = "/order/{$orderId}/exec-tran";
        $response = $this->makeAuthenticatedRequest('POST', $endpoint, $payload);

        if ($response && $response->successful()) {
            Log::info('✅ PCB refund success', ['response' => $response->json()]);
            return $response->json('tran');
        }

        Log::error('❌ PCB refund failed', [
            'orderId' => $orderId,
            'status' => $response ? $response->status() : 'No response',
            'response' => $response ? $response->body() : 'Request failed'
        ]);

        return null;
    }

    /**
     * Process a reverse/void for an order
     *
     * @param string $orderId
     * @param string $password
     * @param float $amount
     * @return array|null
     */
    public function reverseOrder($orderId, $password, $amount)
    {
        Log::info('🔄 Processing PCB reverse', ['orderId' => $orderId, 'amount' => $amount]);

        $payload = [
            "tran" => [
                "voidKind" => "Full",
                "amount" => number_format($amount, 2, '.', ''),
                "phase" => "Single"
            ]
        ];

        $endpoint = "/order/{$orderId}/exec-tran";
        $response = $this->makeAuthenticatedRequest('POST', $endpoint, $payload);

        if ($response && $response->successful()) {
            Log::info('✅ PCB reverse success', ['response' => $response->json()]);
            return $response->json('tran');
        }

        Log::error('❌ PCB reverse failed', [
            'orderId' => $orderId,
            'status' => $response ? $response->status() : 'No response',
            'response' => $response ? $response->body() : 'Request failed'
        ]);

        return null;
    }

    /**
     * Check if certificates are properly configured
     *
     * @return bool
     */
    public function isConfigured()
    {
        return file_exists($this->certPath) && 
               file_exists($this->keyPath) && 
               file_exists($this->caPath);
    }
}
