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
        'pickup_at' => 'datetime',
        'return_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function ($order) {
            $data = $order->booking_data ?? [];
            $order->pickup_at = data_get($data, 'reservation_data.pickup_datetime');
            $order->return_at = data_get($data, 'reservation_data.dropoff_datetime');
            $response = $order->reservation_response ?? [];
            $id = data_get($response, 'reservation_id') ?? data_get($response, 'reservation.id')
                ?? data_get($response, 'data.reservation_id') ?? data_get($response, 'data.id') ?? data_get($response, 'id');
            if (is_scalar($id)) {
                $order->external_reservation_id = (string) $id;
            }
        });
    }

    public function scopeDashboardFilters($query, array $filters)
    {
        $filters += ['search' => '', 'filter_date' => '', 'dateFrom' => '', 'dateTo' => '', 'column' => ''];
        $search = trim($filters['search'] ?? '');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('bank_order_id', $search)->orWhere('external_reservation_id', $search);
                if (ctype_digit(ltrim($search, '#'))) {
                    $q->orWhere('id', ltrim($search, '#'));
                }
            });
        }
        $from = $filters['filter_date'] ?: ($filters['dateFrom'] ?: ($filters['dateTo'] ?? null));
        $to = $filters['filter_date'] ?: ($filters['dateTo'] ?: ($filters['dateFrom'] ?? null));
        if ($from && $to) {
            $from = \Carbon\Carbon::parse($from)->startOfDay();
            $to = \Carbon\Carbon::parse($to)->endOfDay();
            $column = $filters['column'] ?? '';
            if (in_array($column, ['pickups', 'returns'], true)) {
                $query->whereBetween($column === 'pickups' ? 'pickup_at' : 'return_at', [$from, $to]);
            } else {
                $query->where(function ($q) use ($from, $to) {
                    $q->whereBetween('pickup_at', [$from, $to])->orWhereBetween('return_at', [$from, $to]);
                });
            }
        }
        return $query;
    }

    public function matchesBankPayment(?array $details): bool
    {
        return $details &&
            (string) ($details['id'] ?? '') === (string) $this->bank_order_id &&
            strtolower($details['status'] ?? '') === 'fullypaid' &&
            isset($details['amount'], $details['currency']) &&
            number_format((float) $details['amount'], 2, '.', '') === $this->amount &&
            strtoupper($details['currency']) === strtoupper($this->currency);
    }

    public function reservationDetails(): array
    {
        $booking = $this->booking_data ?? [];
        $request = $booking['reservation_data'] ?? [];
        $snapshot = $booking['api_response'] ?? [];
        $response = $this->reservation_response ?? [];
        $remote = data_get($response, 'reservation', data_get($response, 'data.reservation', []));
        $car = data_get($snapshot, 'car', []);
        $location = function ($kind, $id) use ($snapshot) {
            $value = data_get($snapshot, $kind.'_location');
            return is_string($value) ? $value : (data_get($value, 'address') ?? ($id ? __('Location').' #'.$id : null));
        };
        return array_filter(array_merge(is_array($remote) ? $remote : [], [
            'id' => $this->external_reservation_id ?? 'LOCAL-'.$this->id,
            'client_name' => trim(($booking['first_name'] ?? '').' '.($booking['last_name'] ?? '')),
            'email' => $booking['email'] ?? null, 'phone' => $booking['phone'] ?? null,
            'address' => $booking['address'] ?? null,
            'pickup_date' => optional($this->pickup_at)->toDateTimeString(),
            'dropoff_date' => optional($this->return_at)->toDateTimeString(),
            'pickup_location' => $location('pickup', $request['pickup_place'] ?? null),
            'dropoff_location' => $location('dropoff', $request['dropoff_location'] ?? null),
            'vehicle_manufacturer' => data_get($car, 'car_manufacturer.name'),
            'vehicle_model' => data_get($car, 'car_model.name') ?? data_get($car, 'title') ?? (isset($request['car_id']) ? __('Car').' #'.$request['car_id'] : null),
            'vehicle_plate' => data_get($car, 'licence_plate') ?? data_get($car, 'license_plate'),
            'days' => $this->pickup_at && $this->return_at ? max(1, $this->pickup_at->diffInDays($this->return_at)) : null,
            'rental_price_day' => data_get($snapshot, 'calculation.day_price'),
            'rental_price' => (float) $this->amount,
            'paid_amount' => $this->payment_status === 'paid' ? (float) $this->amount : 0,
            'status' => $this->reservation_status,
            'currency' => $this->currency,
        ]), fn ($value) => $value !== null && $value !== '');
    }

    public function claimReservation(): bool
    {
        // Never automatically repeat a POST whose outcome may be unknown.
        return static::whereKey($this->id)->where('payment_status', 'paid')
            ->where('reservation_status', 'pending')
            ->update(['reservation_status' => 'creating', 'updated_at' => now()]) === 1;
    }
}
