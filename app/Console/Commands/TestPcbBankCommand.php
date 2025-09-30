<?php

namespace App\Console\Commands;

use App\Services\PcbBankService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestPcbBankCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pcb:test {--amount=10.00} {--description=Test Order}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test PCB Bank integration';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🧪 Testing PCB Bank Integration...');
        
        $service = new PcbBankService();
        
        // Check configuration
        if (!$service->isConfigured()) {
            $this->error('❌ PCB Bank certificates are not configured!');
            $this->info('Please place the certificates in storage/certs/ directory:');
            $this->info('- cert.pem (Client certificate)');
            $this->info('- key.pem (Private key)');
            $this->info('- ca.pem (CA certificate)');
            return 1;
        }
        
        $this->info('✅ Certificates are configured');
        
        // Test order creation
        $amount = $this->option('amount');
        $description = $this->option('description');
        $redirectUrl = url('/test-redirect');
        
        $this->info("📤 Creating test order: {$amount} EUR - {$description}");
        
        $order = $service->createOrder($amount, $description, $redirectUrl);
        
        if (!$order) {
            $this->error('❌ Failed to create order');
            $this->info('Check the logs for more details: storage/logs/laravel.log');
            return 1;
        }
        
        $this->info('✅ Order created successfully!');
        $this->table(
            ['Field', 'Value'],
            [
                ['Order ID', $order['id'] ?? 'N/A'],
                ['Status', $order['status'] ?? 'N/A'],
                ['HPP URL', $order['hppUrl'] ?? 'N/A'],
                ['Password', substr($order['password'] ?? 'N/A', 0, 10) . '...'],
            ]
        );
        
        // Test order details retrieval
        if (isset($order['id'], $order['password'])) {
            $this->info('📥 Testing order details retrieval...');
            
            $details = $service->getOrderDetails($order['id'], $order['password']);
            
            if ($details) {
                $this->info('✅ Order details retrieved successfully!');
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['ID', $details['id'] ?? 'N/A'],
                        ['Status', $details['status'] ?? 'N/A'],
                        ['Amount', $details['amount'] ?? 'N/A'],
                        ['Currency', $details['currency'] ?? 'N/A'],
                        ['Type', $details['type']['title'] ?? 'N/A'],
                    ]
                );
            } else {
                $this->warn('⚠️ Failed to retrieve order details');
            }
        }
        
        $this->info('🎉 PCB Bank integration test completed!');
        $this->info('Check the logs for detailed information: storage/logs/laravel.log');
        
        return 0;
    }
}

