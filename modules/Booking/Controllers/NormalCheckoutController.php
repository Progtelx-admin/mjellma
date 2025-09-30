<?php


namespace Modules\Booking\Controllers;


use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use App\Services\InvoiceService;

class NormalCheckoutController extends BookingController
{
    public function showInfo()
    {
        return view("Booking::frontend.normal-checkout.info");
    }
    // public function confirmPayment(Request $request, $gateway)
    // {
    //     $gateways = get_payment_gateways();
    //     if (empty($gateways[$gateway]) or !class_exists($gateways[$gateway])) {
    //         return $this->sendError(__("Payment gateway not found"));
    //     }
    //     $gatewayObj = new $gateways[$gateway]($gateway);
    //     if (!$gatewayObj->isAvailable()) {
    //         return $this->sendError(__("Payment gateway is not available"));
    //     }
    //     $res = $gatewayObj->confirmNormalPayment($request);
    //     $status = $res[0] ?? null;
    //     $message = $res[1] ?? null;
    //     $redirect_url = $res[2] ?? null;

    //     if(empty($redirect_url)) $redirect_url = route('gateway.info');

    //     return redirect()->to($redirect_url)->with($status ? "success" : "error",$message);

    // }



    //new hana leart testim
    // public function confirmPayment(Request $request, $gateway)
    // {
    //     $gateways = get_payment_gateways();
    //     if (empty($gateways[$gateway]) || !class_exists($gateways[$gateway])) {
    //         return $this->sendError(__("Payment gateway not found"));
    //     }

    //     $gatewayObj = new $gateways[$gateway]($gateway);

    //     if (!$gatewayObj->isAvailable()) {
    //         return $this->sendError(__("Payment gateway is not available"));
    //     }

    //     // ✅ Try to detect booking or wallet
    //     if ($request->has('c')) {
    //         // This is a booking confirmation
    //         return $gatewayObj->confirmPayment($request);
    //     }

    //     // This is a wallet top-up
    //     $res = $gatewayObj->confirmNormalPayment($request);
    //     $status = $res[0] ?? null;
    //     $message = $res[1] ?? null;
    //     $redirect_url = $res[2] ?? null;

    //     if (empty($redirect_url)) {
    //         $redirect_url = route('gateway.info');
    //     }

    //     return redirect()->to($redirect_url)->with($status ? "success" : "error", $message);
    // }



    public function confirmPayment(Request $request, $gateway)
    {
        \Log::info('[PCB] confirmPayment called in Gateway');

        $c = $request->query('c');
        $booking = Booking::where('code', $c)->first();

        \Log::info('[PCB] Booking code received', ['code' => $c]);

        if (!$booking || $booking->status !== Booking::UNPAID) {
            return redirect($booking ? $booking->getDetailUrl(false) : url('/'));
        }

        $payment = $booking->payment;
        if (!$payment) {
            return redirect($booking->getDetailUrl(false));
        }

        $orderId = $payment->getMeta('pcb_order_id');
        $password = $payment->getMeta('pcb_order_password');

        \Log::info('[PCB] Checking order details', ['order_id' => $orderId, 'password' => $password]);

        $service = new \App\Services\PcbBankService();
        $details = $service->getOrderDetails($orderId, $password);

        \Log::info('[PCB] Order details response', $details ?? []);

        $status = strtolower($details['status'] ?? '');

        // Check for successful payment statuses using the same config as PcbBankGateway
        $successStatuses = config('pcb_bank.success_statuses');
        
        if (in_array($status, $successStatuses)) {
            \Log::info('[PCB] Payment marked as successful');
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
                'pcb_card_pan' => $extractedData['card_pan'] ?? null,
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
                
                \Log::info('📄 Invoice created and sent', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'booking_id' => $booking->id
                ]);
            } catch (\Exception $e) {
                \Log::warning('⚠️ Failed to create/send invoice', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);
            }
        } elseif (in_array($status, config('pcb_bank.failed_statuses'))) {
            \Log::warning('[PCB] Payment marked as failed or cancelled', ['status' => $status]);

            $payment->status = 'cancel';
            $payment->logs = json_encode($details);
            $payment->save();

            $booking->status = Booking::CANCELLED;
            $booking->save();
        } else {
            \Log::warning('[PCB] Payment failed or unknown status', ['details' => $details]);
            $payment->status = 'fail';
            $payment->logs = json_encode(['payment_failed' => true, 'details' => $details]);
            $payment->save();
        }

        return redirect($booking->getDetailUrl(false));
    }








    public function sendError($message, $data = [])
    {
        return redirect()->to(route('gateway.info'))->with('error', $message);
    }

    public function cancelPayment(Request $request, $gateway)
    {

        $gateways = get_payment_gateways();
        if (empty($gateways[$gateway]) or !class_exists($gateways[$gateway])) {
            return $this->sendError(__("Payment gateway not found"));
        }
        $gatewayObj = new $gateways[$gateway]($gateway);
        if (!$gatewayObj->isAvailable()) {
            return $this->sendError(__("Payment gateway is not available"));
        }
        $res = $gatewayObj->cancelNormalPayment($request);
        $status = $res[0] ?? null;
        $message = $res[1] ?? null;
        $redirect_url = $res[2] ?? null;

        if (empty($redirect_url))
            $redirect_url = route('gateway.info');

        return redirect()->to($redirect_url)->with($status ? "success" : "error", $message);
    }
}
