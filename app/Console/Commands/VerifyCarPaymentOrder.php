<?php

namespace App\Console\Commands;

use App\Models\CarPaymentOrder;
use App\Services\PcbBankService;
use Illuminate\Console\Command;

class VerifyCarPaymentOrder extends Command
{
    protected $signature = 'car:verify-payment {order : Bank order ID}';
    protected $description = 'Verify an existing car payment with the bank without charging or submitting a reservation';

    public function handle(PcbBankService $bank): int
    {
        $order = CarPaymentOrder::where('bank_order_id', $this->argument('order'))->first();
        if (!$order) {
            $this->error('Order is not recorded locally. Older payments require manual reconciliation with the bank and car provider.');
            return self::FAILURE;
        }
        $details = $bank->getOrderDetails($order->bank_order_id, $order->bank_password);
        if (!$order->matchesBankPayment($details)) {
            $this->error('Payment could not be confirmed with matching order, amount and currency. No reservation was submitted.');
            return self::FAILURE;
        }
        $order->update(['payment_status' => 'paid', 'verified_at' => now()]);
        $this->info('Payment verified. Reservation status: '.$order->fresh()->reservation_status);
        $this->line('No charge or reservation POST was made. Check the provider before any manual recovery.');
        return self::SUCCESS;
    }
}
