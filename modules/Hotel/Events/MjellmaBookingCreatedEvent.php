<?php

namespace Modules\Hotel\Events;

use Modules\Hotel\Models\MjellmaBooking;
use Illuminate\Queue\SerializesModels;

class MjellmaBookingCreatedEvent
{
    use SerializesModels;

    public $booking;

    public function __construct(MjellmaBooking $booking)
    {
        $this->booking = $booking;
    }
}
