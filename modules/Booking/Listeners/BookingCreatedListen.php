<?php

namespace Modules\Booking\Listeners;

use Modules\Booking\Events\BookingCreatedEvent;
use App\Models\User;
use App\Notifications\AdminChannelServices;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class BookingCreatedListen
{
    public function handle(BookingCreatedEvent $event)
    {
        $booking = $event->booking;

        $name = Auth::check() ? (Auth::user()->name ?? Auth::user()->first_name ?? 'User') : 'Guest';
        $avatar = Auth::check() ? (Auth::user()->avatar_url ?? '') : '';

        $data = [
            'id'      => $booking->id,
            'event'   => 'BookingCreatedEvent',
            'to'      => 'admin',
            'name'    => $name,
            'avatar'  => $avatar,
            'link'    => url('/admin/module/booking'),
            'type'    => 'booking',
            'message' => __(":name has created a new Booking", ['name' => $name]),
        ];

        $adminUser = User::where('role_id', 1)->first();

        if ($adminUser) {
            $adminUser->notify(new AdminChannelServices($data));
            Log::info('📬 Sent Booking Notification to Admin', ['admin_id' => $adminUser->id]);
        } else {
            Log::warning('⚠️ No Admin user found for Booking notification.');
        }
    }
}
