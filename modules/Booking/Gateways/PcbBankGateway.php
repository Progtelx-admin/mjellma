<?php

namespace Modules\Booking\Gateways;

use App\Services\PcbBankService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Booking\Events\BookingCreatedEvent;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Payment;

class PcbBankGateway extends BaseGateway
{
    protected $id = 'pcb_bank';
    public $name = 'PCB Bank';

    public function __construct($id = null)
    {
        $this->id = $id ?? $this->id;
    }

    public function getOptionsConfigs()
    {
        return [
            [
                'type' => 'checkbox',
                'id' => 'enable',
                'label' => __('Enable PCB Bank Gateway?')
            ],
            [
                'type' => 'input',
                'id' => 'name',
                'label' => __('Custom Name'),
                'std' => __("PCB Bank"),
                'multi_lang' => "1"
            ],
            [
                'type' => 'upload',
                'id' => 'logo_id',
                'label' => __('Custom Logo'),
            ],
            [
                'type' => 'editor',
                'id' => 'html',
                'label' => __('Custom HTML Description'),
                'multi_lang' => "1"
            ]
        ];
    }

    public function process(Request $request, $booking, $service)
    {
        if (
            in_array($booking->status, [
                    $booking::PAID,
                    $booking::COMPLETED,
                    $booking::CANCELLED
            ])
        ) {
            throw new \Exception(__("Booking is not payable."));
        }

        if (!$booking->pay_now) {
            throw new \Exception(__("Booking total is zero. Cannot process payment."));
        }

        $payment = new Payment();
        $payment->booking_id = $booking->id;
        $payment->payment_gateway = $this->id;
        $payment->status = 'draft';
        $payment->amount = (float) $booking->pay_now;
        $payment->save();

        // $service = new PcbBankService();
        // $redirectUrl = $this->getReturnUrl() . '?c=' . $booking->code;

        // $order = $service->createOrder($booking->pay_now, "Booking #{$booking->id}", $redirectUrl);


        $service = new PcbBankService();
        
        // Check if certificates are configured
        if (!$service->isConfigured()) {
            throw new \Exception(__('PCB Bank certificates are not configured. Please contact administrator.'));
        }
        
        $redirectUrl = route('gateway.confirm', ['gateway' => 'pcb_bank']) . '?c=' . $booking->code;

        $order = $service->createOrder($booking->pay_now, "Booking #{$booking->id}", $redirectUrl);




        if (!$order || !isset($order['id'], $order['password'], $order['hppUrl'])) {
            throw new \Exception(__('Failed to initiate PCB Bank payment.'));
        }

        $payment->addMeta('pcb_order_id', $order['id']);
        $payment->addMeta('pcb_order_password', $order['password']);
        $payment->save();

        $booking->status = $booking::UNPAID;
        $booking->payment_id = $payment->id;
        $booking->save();

        try {
            event(new BookingCreatedEvent($booking));
        } catch (\Swift_TransportException $e) {
            Log::warning($e->getMessage());
        }

        $url = $order['hppUrl'] . "?id={$order['id']}&password={$order['password']}";
        return response()->json(['url' => $url])->send();
    }

    public function cancelPayment(Request $request)
    {
        $c = $request->query('c');
        $booking = Booking::where('code', $c)->first();

        if (!empty($booking) && $booking->status === Booking::UNPAID) {
            $payment = $booking->payment;
            if ($payment) {
                $payment->status = 'cancel';
                $payment->logs = json_encode(['customer_cancel' => 1]);
                $payment->save();
            }

            $booking->tryRefundToWallet(false);

            return redirect($booking->getDetailUrl())->with("error", __("You cancelled the payment"));
        }

        return redirect($booking ? $booking->getDetailUrl() : url('/'));
    }

    // public function confirmPayment(Request $request)
    // {
    //     $c = $request->query('c');
    //     $booking = Booking::where('code', $c)->first();

    //     if (!$booking || $booking->status !== Booking::UNPAID) {
    //         return redirect($booking ? $booking->getDetailUrl(false) : url('/'));
    //     }

    //     $payment = $booking->payment;
    //     if (!$payment) {
    //         return redirect($booking->getDetailUrl(false));
    //     }

    //     $orderId = $payment->getMeta('pcb_order_id');
    //     $password = $payment->getMeta('pcb_order_password');

    //     $service = new PcbBankService();
    //     $details = $service->getOrderDetails($orderId, $password);

    //     if ($details && $details['status'] === 'Success') {
    //         $booking->paid += (float) $booking->pay_now;
    //         $booking->markAsPaid();
    //         $payment->status = 'completed';
    //         $payment->logs = json_encode($details);
    //         $payment->save();
    //     } else {
    //         $payment->status = 'fail';
    //         $payment->logs = json_encode(['payment_failed' => true]);
    //         $payment->save();
    //     }

    //     return redirect($booking->getDetailUrl(false));
    // }


    // public function confirmPayment(Request $request)
    // {
    //     $c = $request->query('c');
    //     $booking = Booking::where('code', $c)->first();

    //     if (!$booking || $booking->status !== Booking::UNPAID) {
    //         return redirect($booking ? $booking->getDetailUrl(false) : url('/'));
    //     }

    //     $payment = $booking->payment;
    //     if (!$payment) {
    //         return redirect($booking->getDetailUrl(false));
    //     }

    //     $orderId = $payment->getMeta('pcb_order_id');
    //     $password = $payment->getMeta('pcb_order_password');

    //     $service = new PcbBankService();
    //     $details = $service->getOrderDetails($orderId, $password);

    //     if ($details && isset($details['status']) && $details['status'] === 'Success') {
    //         $booking->paid += (float) $booking->pay_now;
    //         $booking->markAsPaid();
    //         $payment->status = 'completed';
    //         $payment->logs = json_encode($details);
    //         $payment->save();
    //     } else {
    //         $payment->status = 'fail';
    //         $payment->logs = json_encode(['payment_failed' => true, 'details' => $details]);
    //         $payment->save();
    //     }

    //     return redirect($booking->getDetailUrl(false));
    // }




    public function confirmPayment(Request $request)
    {
        $c = $request->query('c');
        $booking = Booking::where('code', $c)->first();

        if (!$booking || $booking->status !== Booking::UNPAID) {
            return redirect($booking ? $booking->getDetailUrl(false) : url('/'));
        }

        $payment = $booking->payment;
        if (!$payment) {
            return redirect($booking->getDetailUrl(false));
        }

        $orderId = $payment->getMeta('pcb_order_id');
        $password = $payment->getMeta('pcb_order_password');

        $service = new \App\Services\PcbBankService();
        
        // Check if certificates are configured
        if (!$service->isConfigured()) {
            Log::error('❌ PCB Bank certificates not configured during payment confirmation');
            $payment->status = 'fail';
            $payment->logs = json_encode(['error' => 'Certificates not configured']);
            $payment->save();
            return redirect($booking->getDetailUrl(false))->with('error', __('Payment system configuration error.'));
        }
        
        $details = $service->getOrderDetails($orderId, $password);

        // Handle different status formats from PCB Bank
        $status = $details['status'] ?? '';
        $statusLower = strtolower($status);

        Log::info('🔍 PCB Bank order status check', [
            'orderId' => $orderId,
            'status' => $status,
            'details' => $details
        ]);

        // Check for successful payment statuses
        $successStatuses = config('pcb_bank.success_statuses');
        
        if (in_array($statusLower, $successStatuses)) {
            $booking->paid += (float) $booking->pay_now;
            $booking->markAsPaid();

            // Store comprehensive payment data for invoice generation
            $extractedData = $details['invoice_data'] ?? [];
            
            $invoiceData = [
                // Basic Order Info
                'pcb_order_id' => $details['id'] ?? null,
                'pcb_type_rid' => $details['typeRid'] ?? null,
                'pcb_status' => $details['status'] ?? $status,
                'pcb_create_time' => $details['createTime'] ?? now()->toDateTimeString(),
                'pcb_last_status_login' => $details['lastStatusLogin'] ?? null,
                
                // Transaction Details (from XML response)
                'pcb_transaction_id' => $details['tranActionId'] ?? null,
                'pcb_approval_code' => $extractedData['approval_code'] ?? $details['approvalCode'] ?? null,
                'pcb_rid_by_pmo' => $details['ridByPmo'] ?? null,
                'pcb_approved_partial' => $details['approvedPartial'] ?? false,
                
                // Payment Method Info (formatted for invoice)
                'pcb_payment_method' => $details['paymentMethod'] ?? null,
                'pcb_card_last4' => $extractedData['card_last4'] ?? $details['cardLast4'] ?? null,
                'pcb_card_pan' => $extractedData['card_pan'] ?? null, // XXXXXXXXXXXX0814
                'pcb_card_brand' => $extractedData['card_brand'] ?? $details['cardType'] ?? null,
                'pcb_cvv2_auth_status' => $details['cvv2AuthStatus'] ?? null,
                
                // Amount & Currency (properly formatted)
                'pcb_amount_eur' => $extractedData['amount_eur'] ?? number_format($booking->pay_now, 2),
                'pcb_amount_cents' => $extractedData['amount_cents'] ?? ($booking->pay_now * 100),
                'pcb_currency' => $extractedData['currency'] ?? 'EUR',
                'pcb_currency_code' => $extractedData['currency_code'] ?? '978',
                
                // Transaction DateTime (from XML)
                'pcb_transaction_datetime' => $extractedData['transaction_datetime'] ?? $details['createTime'] ?? now()->toDateTimeString(),
                
                // Order Type Info
                'pcb_order_type_title' => $details['type']['title'] ?? null,
                'pcb_allow_void' => $details['type']['allowVoid'] ?? false,
                'pcb_allow_cvv2' => $details['type']['allowCVV2'] ?? false,
                
                // Additional Fields
                'pcb_payment_time' => $details['paymentTime'] ?? now()->toDateTimeString(),
                'pcb_processor_response' => $details['processorResponse'] ?? null,
                'pcb_gateway_response' => $details, // Store full response
            ];

            $payment->status = 'completed';
            $payment->logs = json_encode($invoiceData);
            $payment->save();

            // Store invoice data in booking meta for easy access
            $booking->addMeta('invoice_data', $invoiceData);
            $booking->addMeta('payment_completed_at', now()->toDateTimeString());
            $booking->save();

            // Create invoice automatically
            try {
                $invoiceService = new \App\Services\InvoiceService();
                $invoice = $invoiceService->createInvoice($payment);
                
                // Generate PDF
                $invoiceService->generatePdf($invoice);
                
                // Send invoice via email
                $invoiceService->sendInvoice($invoice);
                
                Log::info('📄 Invoice created and sent', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'booking_id' => $booking->id
                ]);
            } catch (\Exception $e) {
                Log::warning('⚠️ Failed to create/send invoice', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('✅ Payment completed successfully', [
                'booking_id' => $booking->id,
                'amount' => $booking->pay_now,
                'status' => $status,
                'pcb_order_id' => $details['id'] ?? null,
                'transaction_id' => $details['tranActionId'] ?? null
            ]);
        } else {
            $payment->status = 'fail';
            $payment->logs = json_encode([
                'payment_failed' => true, 
                'status' => $status,
                'details' => $details
            ]);
            $payment->save();

            Log::warning('❌ Payment failed', [
                'booking_id' => $booking->id,
                'status' => $status,
                'details' => $details
            ]);
        }

        return redirect($booking->getDetailUrl(false));
    }






    public function callbackPayment(Request $request)
    {
        return $this->confirmPayment($request);
    }

    public function processNormal($payment)
    {
        // Not implemented: Normal wallet top-up flow
        return [false, __("PCB Bank does not support normal wallet top-ups")];
    }

    public function confirmNormalPayment()
    {
        return [false, __("PCB Bank does not support wallet confirmation")];
    }

    /**
     * Get invoice data for a completed payment
     *
     * @param Payment $payment
     * @return array|null
     */
    public function getInvoiceData($payment)
    {
        if ($payment->status !== 'completed') {
            return null;
        }

        $logs = json_decode($payment->logs, true);
        
        if (!$logs) {
            return null;
        }

        // Handle both old and new data structures
        if (isset($logs['pcb_gateway_response'])) {
            // New structure (from NormalCheckoutController)
            $gatewayResponse = $logs['pcb_gateway_response'];
            $trans = $gatewayResponse['trans'][0] ?? [];
            $srcToken = $gatewayResponse['srcToken'] ?? [];
            $card = $srcToken['card'] ?? [];
            $invoiceData = $gatewayResponse['invoice_data'] ?? [];
            
            return [
                'payment_gateway' => 'PCB Bank',
                
                // Basic Order Info
                'gateway_order_id' => $gatewayResponse['id'] ?? $logs['pcb_order_id'],
                'type_rid' => $gatewayResponse['typeRid'] ?? $logs['pcb_type_rid'],
                'status' => $gatewayResponse['status'] ?? $logs['pcb_status'],
                'create_time' => $gatewayResponse['createTime'] ?? $logs['pcb_create_time'],
                'last_status_login' => $gatewayResponse['lastStatusLogin'] ?? $logs['pcb_last_status_login'],
                
                // Transaction Details
                'transaction_id' => $trans['actionId'] ?? $logs['pcb_transaction_id'],
                'approval_code' => $trans['approvalCode'] ?? $logs['pcb_approval_code'],
                'rid_by_pmo' => $trans['ridByPmo'] ?? $logs['pcb_rid_by_pmo'],
                'approved_partial' => $trans['approvedPartial'] ?? $logs['pcb_approved_partial'],
                
                // Payment Method Info
                'payment_method' => $srcToken['paymentMethod'] ?? $logs['pcb_payment_method'] ?? 'Card',
                'card_last4' => substr($srcToken['displayName'] ?? '', -4) ?: $logs['pcb_card_last4'],
                'card_pan' => 'XXXXXXXXXXXX' . substr($srcToken['displayName'] ?? '', -4) ?: $logs['pcb_card_pan'],
                'card_brand' => $card['brand'] ?? $logs['pcb_card_brand'] ?? 'Visa',
                'cvv2_auth_status' => $card['authentication']['needCvv2'] ?? $logs['pcb_cvv2_auth_status'],
                
                // Amount & Currency (use payment amount from database, not PCB Bank converted amount)
                'amount_eur' => number_format($payment->amount, 2),
                'amount_cents' => $logs['pcb_amount_cents'] ?? $gatewayResponse['amount'],
                'currency' => $logs['pcb_currency'] ?? $gatewayResponse['currency'] ?? 'EUR',
                'currency_code' => $logs['pcb_currency_code'] ?? '978',
                
                // Transaction DateTime
                'transaction_datetime' => $logs['pcb_transaction_datetime'] ?? $gatewayResponse['createTime'],
                
                // Order Type Info
                'order_type_title' => $gatewayResponse['type']['title'] ?? $logs['pcb_order_type_title'] ?? 'Purchase',
                'allow_void' => $logs['pcb_allow_void'] ?? false,
                'allow_cvv2' => $logs['pcb_allow_cvv2'] ?? $card['authentication']['needCvv2'] ?? false,
                
                // Additional Fields
                'payment_time' => $logs['pcb_payment_time'] ?? $gatewayResponse['createTime'],
                'processor_response' => $logs['pcb_processor_response'] ?? 'Approved',
            ];
        } else {
            // Old structure (direct PCB response)
            $trans = $logs['trans'][0] ?? [];
            $srcToken = $logs['srcToken'] ?? [];
            $card = $srcToken['card'] ?? [];
            $invoiceData = $logs['invoice_data'] ?? [];

            return [
                'payment_gateway' => 'PCB Bank',
                
                // Basic Order Info
                'gateway_order_id' => $logs['id'],
                'type_rid' => $logs['typeRid'],
                'status' => $logs['status'],
                'create_time' => $logs['createTime'],
                'last_status_login' => $logs['lastStatusLogin'] ?? null,
                
                // Transaction Details
                'transaction_id' => $trans['actionId'] ?? null,
                'approval_code' => $trans['approvalCode'] ?? null,
                'rid_by_pmo' => $trans['ridByPmo'] ?? null,
                'approved_partial' => $trans['approvedPartial'] ?? false,
                
                // Payment Method Info
                'payment_method' => $srcToken['paymentMethod'] ?? 'Card',
                'card_last4' => substr($srcToken['displayName'] ?? '', -4),
                'card_pan' => 'XXXXXXXXXXXX' . substr($srcToken['displayName'] ?? '', -4),
                'card_brand' => $card['brand'] ?? 'Visa',
                'cvv2_auth_status' => $card['authentication']['needCvv2'] ?? false,
                
                // Amount & Currency (use payment amount from database)
                'amount_eur' => number_format($payment->amount, 2),
                'amount_cents' => $logs['amount'],
                'currency' => $logs['currency'],
                'currency_code' => '978',
                
                // Transaction DateTime
                'transaction_datetime' => $logs['createTime'],
                
                // Order Type Info
                'order_type_title' => $logs['type']['title'] ?? 'Purchase',
                'allow_void' => false,
                'allow_cvv2' => $card['authentication']['needCvv2'] ?? false,
                
                // Additional Fields
                'payment_time' => $logs['createTime'],
                'processor_response' => 'Approved',
            ];
        }
    }

    /**
     * Generate invoice number based on PCB Bank order ID
     *
     * @param Payment $payment
     * @return string
     */
    public function generateInvoiceNumber($payment)
    {
        $invoiceData = $this->getInvoiceData($payment);
        
        if (!$invoiceData) {
            return 'INV-' . $payment->id . '-' . date('Ymd');
        }

        return 'PCB-' . $invoiceData['gateway_order_id'] . '-' . date('Ymd');
    }
}
