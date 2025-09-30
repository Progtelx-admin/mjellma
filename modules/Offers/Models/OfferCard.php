<?php

namespace Modules\Offers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class OfferCard extends Model
{
    protected $table = 'offer_cards';

    protected $fillable = [
        'offer_section_id',
        'title',
        'link',
        'image_path',
        'sort_order',
        'show_caption',
        'is_active',
    ];

    protected $casts = [
        'show_caption' => 'boolean',
        'is_active'    => 'boolean',
        'sort_order'   => 'integer',
    ];

    protected $appends = ['image_url'];

    public function section()
    {
        return $this->belongsTo(OfferSection::class, 'offer_section_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', 1);
    }

    public function getImageUrlAttribute()
    {
        $p = $this->image_path;

        // Absolute URL (CDN/external)
        if ($p && filter_var($p, FILTER_VALIDATE_URL)) {
            return $p;
        }

        // ✅ Preferred: saved under /public/uploads/...
        if ($p && str_starts_with($p, 'uploads/') && file_exists(public_path($p))) {
            return asset($p); // => /uploads/...
        }

        // Back-compat: old records stored on 'public' disk
        if ($p && Storage::disk('public')->exists($p)) {
            $publicFile = public_path('storage/'.ltrim($p, '/'));
            if (file_exists($publicFile)) {
                return Storage::disk('public')->url($p); // => /storage/...
            }
        }

        // Legacy: raw path inside public/
        if ($p && file_exists(public_path($p))) {
            return asset($p);
        }

        return asset('images/placeholder.jpg');
    }
}
