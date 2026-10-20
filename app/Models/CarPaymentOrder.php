<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarPaymentOrder extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['token_hash', 'bank_password', 'booking_data', 'reservation_response'];

    protected $casts = [
        'bank_password' => 'encrypted',
        'booking_data' => 'encrypted:array',
        'reservation_response' => 'encrypted:array',
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    public function matchesBankPayment(?array $details): bool
    {
        return $details &&
            (string) ($details['id'] ?? '') === (string) $this->bank_order_id &&
            strtolower($details['status'] ?? '') === 'fullypaid' &&
            isset($details['amount'], $details['currency']) &&
            number_format((float) $details['amount'], 2, '.', '') === $this->amount &&
            strtoupper($details['currency']) === strtoupper($this->currency);
    }

    public function claimReservation(): bool
    {
        // Never automatically repeat a POST whose outcome may be unknown.
        return static::whereKey($this->id)->where('payment_status', 'paid')
            ->where('reservation_status', 'pending')
            ->update(['reservation_status' => 'creating', 'updated_at' => now()]) === 1;
    }
}
