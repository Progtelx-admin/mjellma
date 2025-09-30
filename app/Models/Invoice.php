<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'booking_id',
        'payment_id',
        'invoice_number',
        'status',
        'amount',
        'currency',
        'payment_gateway',
        'gateway_order_id',
        'transaction_id',
        'approval_code',
        'card_brand',
        'card_pan',
        'transaction_datetime',
        'invoice_data',
        'pdf_path',
        'sent_at',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'invoice_data' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the booking that owns the invoice
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(\Modules\Booking\Models\Booking::class);
    }

    /**
     * Get the payment that owns the invoice
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(\Modules\Booking\Models\Payment::class);
    }

    /**
     * Generate invoice number
     */
    public static function generateInvoiceNumber($gatewayOrderId = null)
    {
        $prefix = 'INV';
        $date = date('Ymd');
        $sequence = str_pad(Invoice::whereDate('created_at', today())->count() + 1, 3, '0', STR_PAD_LEFT);
        
        if ($gatewayOrderId) {
            return "{$prefix}-{$gatewayOrderId}-{$date}";
        }
        
        return "{$prefix}-{$date}-{$sequence}";
    }

    /**
     * Check if invoice PDF exists
     */
    public function hasPdf(): bool
    {
        return !empty($this->pdf_path) && file_exists(storage_path('app/' . $this->pdf_path));
    }

    /**
     * Get PDF path
     */
    public function getPdfPath(): ?string
    {
        return $this->hasPdf() ? storage_path('app/' . $this->pdf_path) : null;
    }

    /**
     * Mark invoice as sent
     */
    public function markAsSent(): void
    {
        $this->update(['sent_at' => now()]);
    }
}
