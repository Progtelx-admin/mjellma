<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestPcbBankSimpleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pcb:test-simple';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simple PCB Bank test without certificates';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🧪 Testing PCB Bank without TLS certificates...');
        
        $payload = [
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
        ];

        $this->info('📤 Sending request to PCB Bank...');
        
        try {
            $response = Http::timeout(30)
                ->post('https://3dss2test.quipu.de:8000/order', $payload);
                
            $this->info('📡 Response received:');
            $this->info('Status: ' . $response->status());
            $this->info('Body: ' . $response->body());
            
            if ($response->successful()) {
                $this->info('✅ Request successful!');
                $data = $response->json();
                if (isset($data['order'])) {
                    $this->info('Order ID: ' . ($data['order']['id'] ?? 'N/A'));
                    $this->info('Status: ' . ($data['order']['status'] ?? 'N/A'));
                }
            } else {
                $this->error('❌ Request failed with status: ' . $response->status());
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
        }
        
        return 0;
    }
}

