<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckPcbBankStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pcb:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check PCB Bank gateway status';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔍 Checking PCB Bank Gateway Status...');
        
        // Check if gateway is enabled in database
        $enabled = DB::table('core_settings')
            ->where('name', 'g_pcb_bank_enable')
            ->value('val');
            
        $this->info('Database Status: ' . ($enabled ? '✅ Enabled' : '❌ Disabled'));
        
        // Check if gateway class exists
        $gatewayClass = 'Modules\Booking\Gateways\PcbBankGateway';
        $classExists = class_exists($gatewayClass);
        $this->info('Gateway Class: ' . ($classExists ? '✅ Exists' : '❌ Missing'));
        
        // Check if gateway is available
        if ($classExists) {
            $gateway = new $gatewayClass('pcb_bank');
            $isAvailable = $gateway->isAvailable();
            $this->info('Gateway Available: ' . ($isAvailable ? '✅ Yes' : '❌ No'));
        }
        
        // Check certificates
        $service = new \App\Services\PcbBankService();
        $configured = $service->isConfigured();
        $this->info('Certificates: ' . ($configured ? '✅ Configured' : '❌ Missing'));
        
        return 0;
    }
}

