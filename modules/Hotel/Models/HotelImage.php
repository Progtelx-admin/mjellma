<?php
namespace Modules\Hotel\Models;

use Modules\Booking\Models\Bookable;

class HotelImage extends Bookable
{
    protected $table = 'hotel_images';
    protected $guarded = [];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id', 'hotel_id');
    }

}
