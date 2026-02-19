<?php

namespace Modules\Hotel\Listeners;

use Modules\Hotel\Events\MjellmaBookingCreatedEvent;
use App\Models\User;
use App\Notifications\AdminChannelServices;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmationEmail;

class MjellmaBookingCreatedListen
{
    public function handle(MjellmaBookingCreatedEvent $event)
    {
        $booking = $event->booking;

        // Detect who is the actor
        $name = Auth::check() ? (Auth::user()->name ?? Auth::user()->first_name ?? 'User') : 'Guest';
        $avatar = Auth::check() ? (Auth::user()->avatar_url ?? '') : '';

        $adminData = [
            'id' => $booking->id,
            'event' => 'MjellmaBookingCreatedEvent',
            'to' => 'admin',
            'name' => $name,
            'avatar' => $avatar,
            'link' => url('booking'), // Admin Booking List URL
            'type' => 'hotel_booking',
            'message' => __(":name has created a new Hotel Booking", ['name' => $name]),
        ];

        $agentData = [
            'id' => $booking->id,
            'event' => 'MjellmaBookingCreatedEvent',
            'to' => 'agent',
            'name' => $name,
            'avatar' => $avatar,
            'link' => url('/user/module/hotel/booking'), // Example Agent Booking List URL
            'type' => 'hotel_booking',
            'message' => __("Your Hotel Booking has been successfully created!"),
        ];

        // Send to Admin
        $adminUser = User::where('role_id', 1)->first();
        if ($adminUser) {
            $adminUser->notify(new AdminChannelServices($adminData));
            Log::info('📬 Sent Hotel Booking Notification to Admin', ['admin_id' => $adminUser->id]);
        } else {
            Log::warning('⚠️ No Admin user found for Hotel Booking notification.');
        }

        // Send to Agent (if booking was made by agent_id)
        if (!empty($booking->agent_id)) {
            $agentUser = User::where('id', $booking->agent_id)->first();
            if ($agentUser) {
                $agentUser->notify(new AdminChannelServices($agentData));
                Log::info('📬 Sent Hotel Booking Notification to Agent', ['agent_id' => $agentUser->id]);
            } else {
                Log::warning('⚠️ No Agent user found with ID: ' . $booking->agent_id);
            }
        }

        // Send booking confirmation email to customer
        try {
            $customerEmail = $booking->user_email ?? $booking->email ?? $booking->customer_email;

            if ($customerEmail) {
                Mail::to($customerEmail)->send(new BookingConfirmationEmail($booking));

                Log::info('✉️ Sent Hotel Booking Confirmation Email to Customer', [
                    'booking_id' => $booking->id,
                    'customer_email' => $customerEmail,
                    'order_id' => $booking->order_id ?? 'N/A',
                    'partner_order_id' => $booking->partner_order_id ?? 'N/A'
                ]);
            } else {
                Log::warning('⚠️ No customer email found for booking', [
                    'booking_id' => $booking->id,
                    'partner_order_id' => $booking->partner_order_id ?? 'N/A'
                ]);
            }
        } catch (\Exception $exception) {
            Log::error('⚠️ Failed to send customer confirmation email', [
                'booking_id' => $booking->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
        }
    }
}

