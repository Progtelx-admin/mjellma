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
        $details = $service->getOrderDetails($orderId, $password);

        $status = strtolower($details['status'] ?? '');

        if (in_array($status, ['success', 'fullypaid', 'paid'])) {
            $booking->paid += (float) $booking->pay_now;
            $booking->markAsPaid();

            $payment->status = 'completed';
            $payment->logs = json_encode($details);
            $payment->save();
        } else {
            $payment->status = 'fail';
            $payment->logs = json_encode(['payment_failed' => true, 'details' => $details]);
            $payment->save();
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
}
