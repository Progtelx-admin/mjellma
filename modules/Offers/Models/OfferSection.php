<?php

namespace Modules\Offers\Models;

use Illuminate\Database\Eloquent\Model;

class OfferSection extends Model
{
    protected $table = 'offer_sections';

    protected $fillable = [
        'title','slug','cta_text','cta_link','sort_order','is_active',
    ];

    public function cards()
    {
        return $this->hasMany(OfferCard::class, 'offer_section_id')->orderBy('sort_order');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', 1);
    }
}
