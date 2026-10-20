<?php

namespace App\Services;

use App\Models\CarPaymentOrder;
use Carbon\Carbon;

class CarOrderSummary
{
    public function local(array $filters): array
    {
        $filters += ['search' => '', 'dateFrom' => '', 'dateTo' => '', 'filter_date' => '', 'column' => ''];
        $from = Carbon::parse($filters['filter_date'] ?: ($filters['dateFrom'] ?: ($filters['dateTo'] ?: now()->toDateString())))->startOfDay();
        $to = Carbon::parse($filters['filter_date'] ?: ($filters['dateTo'] ?: ($filters['dateFrom'] ?: now()->toDateString())))->endOfDay();
        $base = CarPaymentOrder::where(function ($q) {
            $q->whereNull('bank_order_id')->orWhere('bank_order_id', 'not like', 'DEMO-%');
        });
        if (!empty($filters['search'])) {
            $base->dashboardFilters(['search' => $filters['search']]);
        }
        $confirmed = (clone $base)->where('payment_status', 'paid')->where('reservation_status', 'completed');
        $rentalFilters = array_merge($filters, ['dateFrom' => $from->toDateString(), 'dateTo' => $to->toDateString()]);
        $instant = $from->isToday() && $to->isToday() ? now() : $from;

        return [
            'source' => 'local',
            'filter_date' => $from->format('d.m.Y').($from->isSameDay($to) ? '' : ' - '.$to->format('d.m.Y')),
            'total_reservations' => (clone $confirmed)->dashboardFilters($rentalFilters)->count(),
            'active_reservations' => (clone $confirmed)->where('pickup_at', '<=', $instant)->where('return_at', '>', $instant)->count(),
            'overdue_reservations' => null,
            'total_revenue' => (clone $confirmed)->where('currency', 'EUR')->whereBetween('verified_at', [$from, $to])->sum('amount'),
            'review_amount' => (clone $base)->where('payment_status', 'paid')->where('reservation_status', '!=', 'completed')
                ->where('currency', 'EUR')->whereBetween('verified_at', [$from, $to])->sum('amount'),
            'revenue_period' => $from->format('d.m.Y').($from->isSameDay($to) ? '' : ' - '.$to->format('d.m.Y')),
        ];
    }
}
