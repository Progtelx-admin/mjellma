<?php
namespace Modules\Hotel\Models;

use Modules\Booking\Models\Bookable;

class HotelH extends Bookable
{
    protected $table = 'hotels';
    protected $guarded = [];

    public function images()
    {
        return $this->hasMany(HotelImage::class, 'hotel_id', 'hotel_id');
    }

}
