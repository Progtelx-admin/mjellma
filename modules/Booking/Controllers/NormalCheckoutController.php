<?php


namespace Modules\Booking\Controllers;


use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;

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

        if (in_array($status, ['success', 'fullypaid', 'paid'])) {
            \Log::info('[PCB] Payment marked as successful');
            $booking->paid += (float) $booking->pay_now;
            $booking->markAsPaid();

            $payment->status = 'completed';
            $payment->logs = json_encode($details);
            $payment->save();
        } elseif (in_array($status, ['cancelled', 'declined', 'failed'])) {
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
