<?php
namespace Modules\Hotel\Models;

use Modules\Booking\Models\Bookable;

class MjellmaBooking  extends Bookable
{
    protected $table = 'mjellma_bookings';

    protected $fillable = [
        'order_id',
        'partner_order_id',
        'booked_by',
        'admin_id',
        'agent_id',
        'user_id',
        'user_email',
        'user_phone',
        'payment_type',
        'payment_amount',
        'currency_code',
        'pcb_status',
        'api_status',
        'api_error',
    ];

    public function save(array $options = [])
    {
        // Prevent create_user from being set if the column does not exist
        if (isset($this->create_user)) {
            unset($this->create_user);
        }

        return parent::save($options);
    }

}
