@extends('admin.layouts.app')
@push('css')
<style>
    .car-orders{container-type:inline-size;min-width:0;border:1px solid #ddd;border-radius:4px;background:#fff;margin-bottom:20px;overflow:hidden}
    .car-orders-header{padding:16px 20px;display:flex;justify-content:space-between;align-items:center;gap:12px;border-bottom:1px solid #ddd}
    .car-orders-header h4{font-size:17px;font-weight:600;margin:0}
    .car-orders-count{font-size:12px;color:#6c757d;white-space:nowrap}
    .car-orders .table{margin:0;width:100%;table-layout:fixed}
    .car-orders .table thead th{background:#f7f7f7;border-top:0;border-bottom:1px solid #ddd;padding:12px 16px;font-size:12px;white-space:nowrap}
    .car-orders .table tbody td{padding:12px 16px;vertical-align:middle;font-size:13px}
    .car-orders .table tbody tr:hover{background:#fafafa}
    .car-order-ref{display:block;font-size:12px;overflow-wrap:anywhere;max-width:350px}
    .car-order-amount{font-weight:600;white-space:nowrap}
    .car-order-currency{font-size:11px;color:#6c757d;margin-left:4px}
    .car-status{display:inline-block;max-width:100%;overflow-wrap:anywhere;border-radius:3px;padding:3px 7px;font-size:12px;white-space:normal;background:#eee;color:#555}
    .car-status-success{background:#e8f3eb;color:#28633a}
    .car-status-warning{background:#fff3d7;color:#795b18}
    .car-status-danger{background:#f8e5e5;color:#963838}
    .car-status-info{background:#e6eff8;color:#365f86}
    .car-demo{display:inline-block;font-size:10px;border:1px solid #ccc;color:#666;border-radius:3px;padding:1px 5px;margin-bottom:4px}
    .car-order-date{white-space:nowrap}
    .car-order-time{display:block;color:#6c757d;font-size:11px;margin-top:2px}
    .car-orders-footer{padding:12px 20px;border-top:1px solid #ddd;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;color:#6c757d;font-size:12px}
    .car-orders-footer .pagination{margin:0;flex-wrap:wrap}
    .car-api-notice{padding:12px 16px;background:#fff3cd;border:1px solid #ffeeba;border-radius:4px;margin-bottom:18px;color:#856404;font-size:13px}
    .car-api-notice details{margin-top:6px;font-size:12px;overflow-wrap:anywhere}
    .car-orders-pagination{display:flex;gap:4px;list-style:none;padding:0;margin:0;flex-wrap:wrap}
    .car-orders-pagination a,.car-orders-pagination span{display:block;padding:6px 10px;border:1px solid #ddd;border-radius:3px;line-height:1.3;background:#fff;color:#455a70}
    .car-orders-pagination .active span{background:#17a2b8;border-color:#17a2b8;color:white}
    .car-orders-pagination .disabled span{color:#aaa;background:#fafafa}
    .filter-form-right{flex-wrap:wrap;gap:8px;margin-bottom:16px}
    .filter-form-right .ml-2{margin-left:0!important}
    .filter-form-right .form-control{width:auto;min-width:150px}
    .car-orders .table td{overflow-wrap:anywhere}
    .car-orders .table th:nth-child(1){width:6%}
    .car-orders .table th:nth-child(2){width:17%}
    .car-orders .table th:nth-child(3){width:12%}
    .car-orders .table th:nth-child(4){width:11%}
    .car-orders .table th:nth-child(5),.car-orders .table th:nth-child(6){width:12%}
    .car-orders .table th:nth-child(7){width:19%}
    .car-orders .table th:nth-child(8){width:11%}
    .car-orders .table thead th,.car-orders .table tbody td{padding:12px 8px}
    .car-orders .table td:last-child .btn{white-space:normal}
    @media(max-width:767px){
        .car-orders .table-responsive{overflow:visible}
        .car-orders .table thead{display:none}
        .car-orders .table,.car-orders .table tbody{display:block;width:100%;min-width:0}
        .car-orders .table tbody tr{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;padding:16px;border-bottom:1px solid #ddd}
        .car-orders .table tbody td{display:flex;flex-direction:column;align-items:flex-start;gap:4px;min-width:0;padding:0;border:0;white-space:normal}
        .car-orders .table tbody td::before{content:attr(data-label);font-size:11px;font-weight:600;color:#6c757d}
        .car-orders .table td:nth-child(2),.car-orders .table td:nth-child(7),.car-orders .table td:last-child,.car-orders .table td.car-orders-empty{grid-column:1/-1}
        .car-orders .table td:last-child .btn{width:100%}
        .car-orders .table td.car-orders-empty::before{display:none}
    }
    @media(max-width:767px){.car-orders .table{min-width:0}.filter-div,.filter-div>.col-left{display:block;width:100%}.filter-form-right .form-control{width:100%;min-width:0}}
    .car-rent-summary-card{
        border:1px solid #e7eaed;
        border-radius:10px;
        padding:20px;
        background:#fff;
        box-shadow:0 1px 3px rgba(15,34,58,.12);
        margin-bottom:15px;
    }
    .car-rent-summary-card .label{
        font-size:13px;
        color:#6c757d;
        text-transform:uppercase;
        letter-spacing:.05em;
    }
    .car-rent-summary-card .value{
        font-size:28px;
        font-weight:700;
        margin-top:5px;
        color:#212529;
    }
    .car-rent-summary-card .muted{
        font-size:12px;
        color:#adb5bd;
    }
    .car-rent-raw pre{
        max-height:320px;
        overflow:auto;
        background:#0b1727;
        color:#d0d7e2;
        padding:15px;
        border-radius:6px;
        font-size:12px;
    }
    .car-rent-report-badges .badge{
        font-size:12px;
        text-transform:uppercase;
        letter-spacing:.05em;
    }
</style>
@endpush
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar">{{__("Car Rent Reservations")}}</h1>
        </div>
        @include('admin.message')

        @if(!empty($home_summary))
            <div class="text-muted mb-2" style="font-size:12px">
                {{ ($home_summary['source'] ?? '') === 'local' ? __('Local records only. Test orders excluded.') : __('Provider statistics') }}
            </div>
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="car-rent-summary-card">
                        <div class="label">{{__('Total Reservations')}}</div>
                        <div class="value">{{ number_format($home_summary['total_reservations'] ?? 0) }}</div>
                        <div class="muted">{{__('Selected date')}}: {{ $home_summary['filter_date'] ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="car-rent-summary-card">
                        <div class="label">{{__('Active')}}</div>
                        <div class="value text-success">{{ number_format($home_summary['active_reservations'] ?? 0) }}</div>
                        <div class="muted">{{ ($home_summary['source'] ?? '') === 'local' ? __('Scheduled by rental dates; return status not verified') : __('Currently in progress') }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="car-rent-summary-card">
                        <div class="label">{{__('Overdue')}}</div>
                        <div class="value text-danger">{{ isset($home_summary['overdue_reservations']) ? number_format($home_summary['overdue_reservations']) : '-' }}</div>
                        <div class="muted">{{ isset($home_summary['overdue_reservations']) ? __('Need attention') : __('Requires provider return status') }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="car-rent-summary-card">
                        <div class="label">{{__('Revenue')}}</div>
                        <div class="value">{{ number_format($home_summary['total_revenue'] ?? 0, 2) }} &euro;</div>
                        <div class="muted">{{ $home_summary['revenue_period'] ?? __('Today') }}</div>
                        @if(($home_summary['source'] ?? '') === 'local')
                            <div class="muted">{{__('EUR payments for confirmed reservations')}}</div>
                            @if(($home_summary['review_amount'] ?? 0) > 0)
                                <div class="text-warning" style="font-size:12px">{{__('Paid, awaiting reservation')}}: {{ number_format($home_summary['review_amount'], 2) }} &euro;</div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($home_reservations))
            <div class="panel mb-4">
                <div class="panel-body">
                    <h4>{{__('Today\'s Reservations (Home API)')}}</h4>
                    <div class="table-responsive mt-3">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>{{__('ID')}}</th>
                                    <th>{{__('Customer')}}</th>
                                    <th>{{__('Vehicle')}}</th>
                                    <th>{{__('Pickup')}}</th>
                                    <th>{{__('Dropoff')}}</th>
                                    <th>{{__('Status')}}</th>
                                    <th>{{__('Amount')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_slice($home_reservations, 0, 5) as $dailyReservation)
                                    <tr>
                                        <td>#{{ data_get($dailyReservation,'id','-') }}</td>
                                        <td>{{ data_get($dailyReservation,'client_name','-') }}</td>
                                        <td>{{ trim(data_get($dailyReservation,'vehicle_manufacturer','').' '.data_get($dailyReservation,'vehicle_model','')) ?: 'N/A' }}</td>
                                        <td>
                                            {{ data_get($dailyReservation,'pickup_location','N/A') }}<br>
                                            <small>{{ data_get($dailyReservation,'pickup_date') ? \Carbon\Carbon::parse(data_get($dailyReservation,'pickup_date'))->format('d.m.Y H:i') : '' }}</small>
                                        </td>
                                        <td>
                                            {{ data_get($dailyReservation,'dropoff_location','N/A') }}<br>
                                            <small>{{ data_get($dailyReservation,'dropoff_date') ? \Carbon\Carbon::parse(data_get($dailyReservation,'dropoff_date'))->format('d.m.Y H:i') : '' }}</small>
                                        </td>
                                        <td>
                                            @php
                                                $status = strtoupper(data_get($dailyReservation,'status','N/A'));
                                                $badge = 'secondary';
                                                if($status === 'ACTIVE') $badge = 'success';
                                                elseif($status === 'OVERDUE') $badge = 'danger';
                                                elseif(in_array($status,['PENDING','IN_PROGRESS'])) $badge = 'warning';
                                            @endphp
                                            <span class="badge badge-{{ $badge }}">{{ $status }}</span>
                                        </td>
                                        <td>
                                            {{ number_format(data_get($dailyReservation,'rental_price', data_get($dailyReservation,'total_price',0)),2) }} &euro;
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @elseif(!empty($home_raw))
            <div class="panel mb-4">
                <div class="panel-body car-rent-raw">
                    <h4>{{__('Daily Overview (Raw)')}}</h4>
                    <pre>{{ json_encode($home_raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        @endif
        
        <!-- Filters -->
        <div class="filter-div d-flex justify-content-between">
            <div class="col-left">
                <!-- Empty left column for consistency -->
            </div>
            <div class="col-left">
                <form method="get" action="{{ route('carrent.admin.reservations.index') }}" class="filter-form filter-form-right d-flex justify-content-end flex-column flex-sm-row" role="search">
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="{{__('Reservation, bank or local ID')}}" class="form-control" >
                    <input type="date" name="dateFrom" value="{{ $filters['dateFrom'] }}" placeholder="{{__('Date From')}}" class="form-control ml-2">
                    <input type="date" name="dateTo" value="{{ $filters['dateTo'] }}" placeholder="{{__('Date To')}}" class="form-control ml-2">
                    <select name="column" class="form-control ml-2">
                        <option value="">{{__('All Columns')}}</option>
                        <option value="pickups" {{ $filters['column'] == 'pickups' ? 'selected' : '' }}>{{__('Pickups')}}</option>
                        <option value="returns" {{ $filters['column'] == 'returns' ? 'selected' : '' }}>{{__('Returns')}}</option>
                    </select>
                    <button class="btn-info btn btn-icon btn_search ml-2" type="submit">{{__('Search')}}</button>
                    <a href="{{ route('carrent.admin.reservations.index') }}" class="btn btn-secondary ml-2">{{__('Reset')}}</a>
                </form>
            </div>
        </div>

        @if($error)
            <div class="car-api-notice" role="status">
                {{__('Unable to load reservations from the provider.')}}
                <details><summary>{{__('Details')}}</summary>{{ $error }}</details>
            </div>
        @endif

        @if(isset($local_orders))
            <div class="car-orders">
                <div class="car-orders-header">
                    <h4>{{__('Car payment orders')}}</h4>
                    <span class="car-orders-count">{{ number_format($local_orders->total()) }} {{__('orders')}}</span>
                </div>
                <div class="table-responsive"><table class="table">
                    <thead><tr>
                        <th>{{__('Local ID')}}</th><th>{{__('Customer')}}</th><th>{{__('Car')}}</th><th>{{__('Amount')}}</th>
                        <th>{{__('Pickup')}}</th><th>{{__('Return')}}</th><th>{{__('Status')}}</th><th>{{__('Actions')}}</th>
                    </tr></thead>
                    <tbody>
                    @forelse($local_orders as $localOrder)
                        @php
                            $details = $localOrder->reservationDetails();
                            $paymentLabels = ['paid' => __('Paid'), 'pending' => __('Pending'), 'initialization_failed' => __('Not initialized')];
                            $reservationLabels = ['completed' => __('Confirmed'), 'pending' => __('Pending'), 'creating' => __('Processing'), 'review_required' => __('Needs review')];
                            $paymentColor = $localOrder->payment_status === 'paid' ? 'success' : ($localOrder->payment_status === 'initialization_failed' ? 'danger' : 'warning');
                            $reservationColors = ['completed' => 'success', 'pending' => 'warning', 'creating' => 'info', 'review_required' => 'danger'];
                        @endphp
                        <tr>
                            <td data-label="{{__('Local ID')}}"><div><strong>#{{ $localOrder->id }}</strong>
                                @if(\Illuminate\Support\Str::startsWith($localOrder->bank_order_id ?? '', 'DEMO-'))
                                    <small class="d-block text-muted">{{__('Test')}}</small>
                                @endif
                            </div></td>
                            <td data-label="{{__('Customer')}}"><div>{{ $details['client_name'] ?? '-' }}</div></td>
                            <td data-label="{{__('Car')}}">{{ trim(($details['vehicle_manufacturer'] ?? '').' '.($details['vehicle_model'] ?? '')) ?: '-' }}</td>
                            <td data-label="{{__('Amount')}}"><span class="car-order-amount">{{ number_format((float) $localOrder->amount, 2) }}<span class="car-order-currency">{{ $localOrder->currency }}</span></span></td>
                            <td data-label="{{__('Pickup')}}" class="car-order-date">{{ optional($localOrder->pickup_at)->format('d.m.Y') ?? '-' }}<span class="car-order-time">{{ optional($localOrder->pickup_at)->format('H:i') }}</span></td>
                            <td data-label="{{__('Return')}}" class="car-order-date">{{ optional($localOrder->return_at)->format('d.m.Y') ?? '-' }}<span class="car-order-time">{{ optional($localOrder->return_at)->format('H:i') }}</span></td>
                            <td data-label="{{__('Status')}}"><div>
                                <div class="mb-1"><span class="car-status car-status-{{ $paymentColor }}">{{__('Payment')}}: {{ $paymentLabels[$localOrder->payment_status] ?? $localOrder->payment_status }}</span></div>
                                <div><span class="car-status car-status-{{ $reservationColors[$localOrder->reservation_status] ?? 'info' }}">{{__('Reservation')}}: {{ $reservationLabels[$localOrder->reservation_status] ?? $localOrder->reservation_status }}</span></div>
                            </div></td>
                            <td data-label="{{__('Actions')}}"><a href="{{ route('carrent.admin.orders.show', ['id' => $localOrder->id]) }}" class="btn btn-sm btn-info">{{__('View Details')}}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="car-orders-empty">{{__('No orders match the selected filters.')}}</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                <div class="car-orders-footer">
                    <span>{{ $local_orders->firstItem() ?? 0 }} - {{ $local_orders->lastItem() ?? 0 }} / {{ number_format($local_orders->total()) }}</span>
                    @if($local_orders->hasPages())
                        {{ $local_orders->onEachSide(1)->withQueryString()->links('CarRent::admin.orders-pagination') }}
                    @endif
                </div>
            </div>
        @endif

        @if(!$error || !empty($reservations))
        <div class="panel">
            <div class="panel-body">
                @if(empty($reservations) || count($reservations) == 0)
                    <div class="alert alert-info">
                        {{__('No reservations found')}}
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>{{__('Reservation ID')}}</th>
                                <th>{{__('Customer')}}</th>
                                <th>{{__('Car')}}</th>
                                <th>{{__('Pickup Date')}}</th>
                                <th>{{__('Return Date')}}</th>
                                <th>{{__('Pickup Location')}}</th>
                                <th>{{__('Return Location')}}</th>
                                <th>{{__('Status')}}</th>
                                <th>{{__('Total Amount')}}</th>
                                <th>{{__('Actions')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($reservations as $reservation)
                                <tr>
                                    <td>
                                        @if(isset($reservation['id']))
                                            #{{ $reservation['id'] }}
                                        @elseif(isset($reservation['reservation_id']))
                                            #{{ $reservation['reservation_id'] }}
                                        @elseif(isset($reservation['reservation_number']))
                                            #{{ $reservation['reservation_number'] }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($reservation['client_name']))
                                            {{ $reservation['client_name'] }}
                                        @elseif(isset($reservation['customer']))
                                            {{ is_array($reservation['customer']) ? ($reservation['customer']['name'] ?? $reservation['customer']['email'] ?? 'N/A') : $reservation['customer'] }}
                                        @elseif(isset($reservation['customer_name']))
                                            {{ $reservation['customer_name'] }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($reservation['vehicle_manufacturer']) && isset($reservation['vehicle_model']))
                                            {{ $reservation['vehicle_manufacturer'] }} {{ $reservation['vehicle_model'] }}
                                        @elseif(isset($reservation['vehicle_model']))
                                            {{ $reservation['vehicle_model'] }}
                                        @elseif(isset($reservation['vehicle_manufacturer']))
                                            {{ $reservation['vehicle_manufacturer'] }}
                                        @elseif(isset($reservation['car']))
                                            {{ is_array($reservation['car']) ? ($reservation['car']['name'] ?? $reservation['car']['model'] ?? 'N/A') : $reservation['car'] }}
                                        @elseif(isset($reservation['car_name']))
                                            {{ $reservation['car_name'] }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($reservation['pickup_date']))
                                            {{ date('Y-m-d H:i', strtotime($reservation['pickup_date'])) }}
                                        @elseif(isset($reservation['pickup']))
                                            {{ is_array($reservation['pickup']) ? ($reservation['pickup']['date'] ?? 'N/A') : $reservation['pickup'] }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($reservation['dropoff_date']))
                                            {{ date('Y-m-d H:i', strtotime($reservation['dropoff_date'])) }}
                                        @elseif(isset($reservation['return_date']))
                                            {{ date('Y-m-d H:i', strtotime($reservation['return_date'])) }}
                                        @elseif(isset($reservation['return']))
                                            {{ is_array($reservation['return']) ? ($reservation['return']['date'] ?? 'N/A') : $reservation['return'] }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($reservation['pickup_location']))
                                            {{ is_array($reservation['pickup_location']) ? ($reservation['pickup_location']['name'] ?? 'N/A') : $reservation['pickup_location'] }}
                                        @elseif(isset($reservation['pickup']))
                                            {{ is_array($reservation['pickup']) ? ($reservation['pickup']['location'] ?? 'N/A') : 'N/A' }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($reservation['dropoff_location']))
                                            {{ is_array($reservation['dropoff_location']) ? ($reservation['dropoff_location']['name'] ?? 'N/A') : $reservation['dropoff_location'] }}
                                        @elseif(isset($reservation['return_location']))
                                            {{ is_array($reservation['return_location']) ? ($reservation['return_location']['name'] ?? 'N/A') : $reservation['return_location'] }}
                                        @elseif(isset($reservation['return']))
                                            {{ is_array($reservation['return']) ? ($reservation['return']['location'] ?? 'N/A') : 'N/A' }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($reservation['status']))
                                            @php
                                                $status = strtoupper($reservation['status']);
                                                $badgeClass = 'secondary';
                                                if ($status == 'ACTIVE') {
                                                    $badgeClass = 'success';
                                                } elseif ($status == 'OVERDUE') {
                                                    $badgeClass = 'danger';
                                                } elseif (in_array($status, ['CONFIRMED', 'COMPLETED'])) {
                                                    $badgeClass = 'success';
                                                } elseif (in_array($status, ['PENDING', 'IN_PROGRESS'])) {
                                                    $badgeClass = 'warning';
                                                }
                                            @endphp
                                            <span class="badge badge-{{ $badgeClass }}">
                                                {{ $reservation['status'] }}
                                            </span>
                                        @elseif(isset($reservation['reservation_status']))
                                            <span class="badge badge-warning">
                                                {{ $reservation['reservation_status'] }}
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($reservation['rental_price']))
                                            {{ number_format($reservation['rental_price'], 2) }} {{ $reservation['currency'] ?? 'EUR' }}
                                        @elseif(isset($reservation['total_amount']))
                                            {{ number_format($reservation['total_amount'], 2) }} {{ $reservation['currency'] ?? 'EUR' }}
                                        @elseif(isset($reservation['amount']))
                                            {{ number_format($reservation['amount'], 2) }} {{ $reservation['currency'] ?? 'EUR' }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $reservationId = $reservation['id'] ?? ($reservation['reservation_id'] ?? ($reservation['reservation_number'] ?? null));
                                        @endphp
                                        @if($reservationId)
                                            <a href="{{ route('carrent.admin.reservations.show', ['id' => $reservationId]) }}" class="btn btn-sm btn-info">
                                                {{__('View Details')}}
                                            </a>
                                        @else
                                            <span class="text-muted">{{__('N/A')}}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    @if(!empty($report_rows))
        <div class="panel mt-4">
            <div class="panel-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="mb-1">{{__('Performance Report')}}</h4>
                        <small class="text-muted">{{__('Range')}}: {{ $report_summary['range'] ?? '-' }}</small>
                    </div>
                    <div class="car-rent-report-badges">
                        <span class="badge badge-primary">{{__('Total')}}: {{ number_format($report_summary['total_reservations'] ?? 0) }}</span>
                        <span class="badge badge-success">{{__('Revenue')}}: {{ number_format($report_summary['total_revenue'] ?? 0, 2) }} &euro;</span>
                        <span class="badge badge-info">{{__('Avg.')}}: {{ number_format($report_summary['average_revenue'] ?? 0, 2) }} &euro;</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr>
                                <th>{{__('Reservation')}}</th>
                                <th>{{__('Customer')}}</th>
                                <th>{{__('Vehicle')}}</th>
                                <th>{{__('Pickup')}}</th>
                                <th>{{__('Dropoff')}}</th>
                                <th>{{__('Status')}}</th>
                                <th>{{__('Amount (&euro;)')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report_rows as $reportRow)
                                <tr>
                                    <td>#{{ data_get($reportRow,'id', data_get($reportRow,'reservation_id','-')) }}</td>
                                    <td>{{ data_get($reportRow,'client_name', data_get($reportRow,'customer','-')) }}</td>
                                    <td>{{ trim(data_get($reportRow,'vehicle_manufacturer','').' '.data_get($reportRow,'vehicle_model','')) ?: 'N/A' }}</td>
                                    <td>{{ data_get($reportRow,'pickup_location','N/A') }}</td>
                                    <td>{{ data_get($reportRow,'dropoff_location','N/A') }}</td>
                                    <td>{{ data_get($reportRow,'status','N/A') }}</td>
                                    <td>{{ number_format(data_get($reportRow,'rental_price', data_get($reportRow,'total_price',0)),2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @elseif(!empty($report_raw))
        <div class="panel mt-4">
            <div class="panel-body car-rent-raw">
                <h4>{{__('Performance Report (Raw)')}}</h4>
                <pre>{{ json_encode($report_raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    @endif
@endsection

