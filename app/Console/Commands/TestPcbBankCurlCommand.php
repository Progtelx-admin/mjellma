<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestPcbBankCurlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pcb:test-curl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test PCB Bank using cURL directly';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🧪 Testing PCB Bank with cURL...');
        
        $certPath = str_replace('\\', '/', storage_path('certs/cert.pem'));
        $keyPath = str_replace('\\', '/', storage_path('certs/key.pem'));
        $caPath = str_replace('\\', '/', storage_path('certs/ca.pem'));
        
        $this->info('Certificate paths:');
        $this->info('Cert: ' . $certPath . ' (exists: ' . (file_exists($certPath) ? 'yes' : 'no') . ')');
        $this->info('Key: ' . $keyPath . ' (exists: ' . (file_exists($keyPath) ? 'yes' : 'no') . ')');
        $this->info('CA: ' . $caPath . ' (exists: ' . (file_exists($caPath) ? 'yes' : 'no') . ')');
        
        $payload = json_encode([
            "order" => [
                "typeRid" => "ORD1",
                "amount" => "10.00",
                "currency" => "EUR",
                "description" => "Test Order",
                "language" => "en",
                "hppRedirectUrl" => "https://mjellma.test/test-redirect",
                "initiationEnvKind" => "Browser",
                "consumerDevice" => [
                    "browser" => [
                        "javaEnabled" => false,
                        "jsEnabled" => true,
                        "acceptHeader" => "application/json,application/jose;charset=utf-8",
                        "ip" => "127.0.0.1",
                        "colorDepth" => "24",
                        "screenW" => "1920",
                        "screenH" => "1080",
                        "tzOffset" => "-120",
                        "language" => "en-EN",
                        "userAgent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
                    ]
                ]
            ]
        ]);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://3dss2test.quipu.de:8000/order',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ],
            CURLOPT_SSLCERT => $certPath,
            CURLOPT_SSLKEY => $keyPath,
            CURLOPT_CAINFO => $caPath,
            CURLOPT_SSL_VERIFYPEER => false, // Disable peer verification for self-signed certs
            CURLOPT_SSL_VERIFYHOST => false, // Disable host verification for self-signed certs
            CURLOPT_TIMEOUT => 30,
            CURLOPT_VERBOSE => true,
        ]);
        
        $this->info('📤 Sending request...');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        
        curl_close($ch);
        
        $this->info('📡 Response:');
        $this->info('HTTP Code: ' . $httpCode);
        $this->info('Response: ' . $response);
        
        if ($error) {
            $this->error('cURL Error: ' . $error);
        }
        
        $this->info('cURL Info:');
        $this->info('SSL Verify Result: ' . ($info['ssl_verify_result'] ?? 'N/A'));
        $this->info('Total Time: ' . ($info['total_time'] ?? 'N/A'));
        
        return 0;
    }
}
