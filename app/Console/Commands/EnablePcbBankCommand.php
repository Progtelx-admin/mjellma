<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnablePcbBankCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pcb:enable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable PCB Bank payment gateway';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔧 Enabling PCB Bank payment gateway...');
        
        try {
            // Enable the PCB Bank gateway
            DB::table('core_settings')->updateOrInsert(
                ['name' => 'g_pcb_bank_enable'],
                ['name' => 'g_pcb_bank_enable', 'val' => '1']
            );
            
            // Set default name
            DB::table('core_settings')->updateOrInsert(
                ['name' => 'g_pcb_bank_name'],
                ['name' => 'g_pcb_bank_name', 'val' => 'PCB Bank']
            );
            
            $this->info('✅ PCB Bank gateway enabled successfully!');
            $this->info('You can now see PCB Bank as a payment option when booking.');
            
            // Clear cache
            $this->call('cache:clear');
            $this->info('🔄 Cache cleared');
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to enable PCB Bank gateway: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}

