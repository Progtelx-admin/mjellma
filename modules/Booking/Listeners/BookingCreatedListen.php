<?php

namespace Modules\Hotel\Listeners;

use Modules\Hotel\Events\MjellmaBookingCreatedEvent;
use App\Models\User;
use App\Notifications\AdminChannelServices;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class MjellmaBookingCreatedListen
{
    public function handle(MjellmaBookingCreatedEvent $event)
    {
        $booking = $event->booking;

        $name = Auth::check() ? (Auth::user()->name ?? Auth::user()->first_name ?? 'User') : 'Guest';
        $avatar = Auth::check() ? (Auth::user()->avatar_url ?? '') : '';

        $data = [
            'id'      => $booking->id,
            'event'   => 'MjellmaBookingCreatedEvent',
            'to'      => 'admin',
            'name'    => $name,
            'avatar'  => $avatar,
            'link'    => url('/admin/module/hotel/booking'),
            'type'    => 'hotel_booking',
            'message' => __(":name has created a new Hotel Booking", ['name' => $name]),
        ];

        $adminUser = User::where('role_id', 1)->first();

        if ($adminUser) {
            $adminUser->notify(new AdminChannelServices($data));
            Log::info('📬 Sent Hotel Booking Notification to Admin', ['admin_id' => $adminUser->id]);
        } else {
            Log::warning('⚠️ No Admin user found for Hotel Booking notification.');
        }
    }
}
