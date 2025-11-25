@extends('admin.layouts.app')
@push('css')
<style>
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
                        <div class="muted">{{__('Currently in progress')}}</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="car-rent-summary-card">
                        <div class="label">{{__('Overdue')}}</div>
                        <div class="value text-danger">{{ number_format($home_summary['overdue_reservations'] ?? 0) }}</div>
                        <div class="muted">{{__('Need attention')}}</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="car-rent-summary-card">
                        <div class="label">{{__('Revenue')}}</div>
                        <div class="value">{{ number_format($home_summary['total_revenue'] ?? 0, 2) }} €</div>
                        <div class="muted">{{__('Today')}}</div>
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
                                            {{ number_format(data_get($dailyReservation,'rental_price', data_get($dailyReservation,'total_price',0)),2) }} €
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
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="{{__('Search by reservation ID (e.g., 81402)')}}" class="form-control" pattern="[0-9]*" inputmode="numeric">
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
            <div class="alert alert-danger">
                {{ $error }}
            </div>
        @endif

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
                        <span class="badge badge-success">{{__('Revenue')}}: {{ number_format($report_summary['total_revenue'] ?? 0, 2) }} €</span>
                        <span class="badge badge-info">{{__('Avg.')}}: {{ number_format($report_summary['average_revenue'] ?? 0, 2) }} €</span>
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
                                <th>{{__('Amount (€)')}}</th>
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

