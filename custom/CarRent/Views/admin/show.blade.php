@extends('admin.layouts.app')
@section('content')
@push('css')
<style>
    .invoice-container {
        background: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .invoice-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f0f0f0;
    }
    .invoice-title {
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }
    .invoice-actions {
        display: flex;
        gap: 10px;
    }
    .reservation-overview {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 6px;
        margin-bottom: 30px;
    }
    .overview-row {
        display: flex;
        margin-bottom: 15px;
        align-items: flex-start;
    }
    .overview-row:last-child {
        margin-bottom: 0;
    }
    .overview-label {
        font-weight: 600;
        min-width: 180px;
        color: #555;
    }
    .overview-value {
        flex: 1;
        color: #333;
    }
    .vehicle-pricing-section {
        margin-bottom: 30px;
    }
    .vehicle-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .vehicle-table th,
    .vehicle-table td {
        padding: 12px;
        text-align: left;
        border: 1px solid #dee2e6;
    }
    .vehicle-table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    .vehicle-photo {
        width: 80px;
        height: 60px;
        object-fit: cover;
        border-radius: 4px;
    }
    .pricing-breakdown {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
    }
    .pricing-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #dee2e6;
    }
    .pricing-row:last-child {
        border-bottom: none;
        font-weight: bold;
        font-size: 16px;
        margin-top: 10px;
        padding-top: 15px;
        border-top: 2px solid #333;
    }
    .details-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-top: 30px;
    }
    .details-column {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 6px;
    }
    .details-column h5 {
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
    }
    .detail-item {
        margin-bottom: 12px;
    }
    .detail-label {
        font-weight: 600;
        color: #555;
        margin-bottom: 4px;
    }
    .detail-value {
        color: #333;
    }
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 14px;
    }
    .status-active {
        background: #28a745;
        color: white;
    }
    .status-overdue {
        background: #dc3545;
        color: white;
    }
    .status-pending {
        background: #ffc107;
        color: #333;
    }
    .comments-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 6px;
        margin-top: 30px;
    }
    .comments-textarea {
        width: 100%;
        min-height: 120px;
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        margin-bottom: 10px;
    }
    .invoice-preview {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        margin-top: 30px;
        background: #fff;
    }
    .invoice-preview-header {
        padding: 20px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .invoice-preview-body {
        padding: 20px;
        background: #fafbfc;
    }
    .invoice-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    .invoice-meta-card {
        background: #fff;
        border: 1px solid #edf1f5;
        border-radius: 6px;
        padding: 15px;
    }
    .invoice-meta-card h6 {
        text-transform: uppercase;
        font-size: 12px;
        color: #6c757d;
        letter-spacing: .05em;
    }
    .invoice-meta-card .value {
        font-size: 15px;
        font-weight: 600;
        margin-top: 6px;
        color: #111;
    }
    .invoice-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .invoice-table th,
    .invoice-table td {
        border: 1px solid #e1e5eb;
        padding: 10px;
    }
    .invoice-table th {
        background: #f1f3f5;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: .05em;
    }
    .invoice-branding {
        text-align: center;
        margin-bottom: 20px;
    }
    .invoice-branding img {
        max-width: 100%;
        height: auto;
    }
    .terms-list {
        margin: 15px 0 0;
        padding-left: 18px;
    }
    .terms-list li {
        margin-bottom: 6px;
        font-size: 13px;
    }
    @media (max-width: 768px) {
        .details-section {
            grid-template-columns: 1fr;
        }
        .invoice-header {
            flex-direction: column;
            align-items: flex-start;
        }
        .invoice-actions {
            margin-top: 15px;
            width: 100%;
        }
    }
</style>
@endpush

<div class="container-fluid">
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div>
                <h1 class="invoice-title">{{__('Invoice')}}: #{{ $reservation['id'] ?? 'N/A' }}</h1>
                <nav aria-label="breadcrumb" class="mt-2">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{__('Dashboard')}}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('carrent.admin.reservations.index') }}">{{__('Car Rent Reservations')}}</a></li>
                        <li class="breadcrumb-item active">{{__('Invoice Management')}}</li>
                        <li class="breadcrumb-item active">{{__('Invoice')}}</li>
                    </ol>
                </nav>
            </div>
            <div class="invoice-actions">
                <a href="{{ route('carrent.admin.reservations.index') }}" class="btn btn-secondary">{{__('Back')}}</a>
                @if(!empty($invoice) && !empty($reservation_id))
                    <a href="{{ route('carrent.admin.reservations.invoice.download', ['id' => $reservation_id, 'preview' => 1]) }}" target="_blank" class="btn btn-outline-primary">
                        {{ __('Open PDF') }}
                    </a>
                    <a href="{{ route('carrent.admin.reservations.invoice.download', ['id' => $reservation_id]) }}" class="btn btn-success">
                        {{ __('Download PDF') }}
                    </a>
                @endif
            </div>
        </div>

        @include('admin.message')

        @if($error)
            <div class="alert alert-danger">
                {{ $error }}
            </div>
        @endif

        @if($reservation)
            <!-- Reservation Overview -->
            <div class="reservation-overview">
                <div class="overview-row">
                    <div class="overview-label">{{__('Reserved on')}}:</div>
                    <div class="overview-value">
                        @if(isset($reservation['pickup_date']))
                            {{ \Carbon\Carbon::parse($reservation['pickup_date'])->format('d.m.Y H:i') }}
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                <div class="overview-row">
                    <div class="overview-label">{{__('From')}}:</div>
                    <div class="overview-value">
                        @if(isset($reservation['pickup_date']))
                            {{ \Carbon\Carbon::parse($reservation['pickup_date'])->format('d.m.Y H:i') }}
                        @else
                            N/A
                        @endif
                        <br>
                        <strong>{{__('Pickup location')}}:</strong> {{ $reservation['pickup_location'] ?? 'N/A' }}
                    </div>
                </div>
                <div class="overview-row">
                    <div class="overview-label">{{__('Until')}}:</div>
                    <div class="overview-value">
                        @if(isset($reservation['dropoff_date']))
                            {{ \Carbon\Carbon::parse($reservation['dropoff_date'])->format('d.m.Y H:i') }}
                            @if(isset($reservation['days']))
                                ({{ $reservation['days'] }} {{__('Days')}})
                            @endif
                        @else
                            N/A
                        @endif
                        <br>
                        <strong>{{__('Drop-off location')}}:</strong> {{ $reservation['dropoff_location'] ?? 'N/A' }}
                    </div>
                </div>
                <div class="overview-row">
                    <div class="overview-label">{{__('Provider')}}:</div>
                    <div class="overview-value">
                        <strong>RENT A CAR ORANGE</strong><br>
                        10000 Prishtina International Airport<br>
                        {{__('Phone')}}: 044 240 383
                    </div>
                </div>
                <div class="overview-row">
                    <div class="overview-label">{{__('Customer')}}:</div>
                    <div class="overview-value">
                        <strong>{{ $reservation['client_name'] ?? 'N/A' }}</strong><br>
                        {{ $reservation['email'] ?? 'N/A' }}<br>
                        {{ $reservation['phone'] ?? 'N/A' }}
                    </div>
                </div>
            </div>

            <!-- Vehicle and Pricing -->
            <div class="vehicle-pricing-section">
                <h4 class="mb-3">{{__('Vehicle and Pricing')}}</h4>
                <table class="vehicle-table">
                    <thead>
                        <tr>
                            <th>{{__('Photo')}}</th>
                            <th>{{__('Vehicle')}}</th>
                            <th>{{__('License Plate')}}</th>
                            <th>{{__('Days')}}</th>
                            <th>{{__('Price')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                @if(isset($reservation['vehicle_image']))
                                    <img src="{{ $reservation['vehicle_image'] }}" alt="Vehicle" class="vehicle-photo">
                                @else
                                    <div class="vehicle-photo bg-light d-flex align-items-center justify-content-center">
                                        <i class="fa fa-car"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if(isset($reservation['vehicle_manufacturer']) && isset($reservation['vehicle_model']))
                                    <strong>{{ $reservation['vehicle_manufacturer'] }} {{ $reservation['vehicle_model'] }}</strong>
                                @elseif(isset($reservation['vehicle_model']))
                                    <strong>{{ $reservation['vehicle_model'] }}</strong>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>{{ $reservation['vehicle_plate'] ?? 'N/A' }}</td>
                            <td>{{ $reservation['days'] ?? 'N/A' }}</td>
                            <td>
                                @if(isset($reservation['rental_price_day']))
                                    {{ number_format($reservation['rental_price_day'], 2) }}€
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="pricing-breakdown">
                    <div class="pricing-row">
                        <span>{{__('Price')}}:</span>
                        <span>
                            @if(isset($reservation['rental_price']))
                                {{ number_format($reservation['rental_price'], 2) }} €
                            @else
                                0.00 €
                            @endif
                        </span>
                    </div>
                    <div class="pricing-row">
                        <span>{{__('Traffic Fine')}}:</span>
                        <span>0.00 €</span>
                    </div>
                    <div class="pricing-row">
                        <span>{{__('Franchise 1000€')}}:</span>
                        <span>0.00 €</span>
                    </div>
                    <div class="pricing-row">
                        <span><strong>{{__('Total')}}:</strong></span>
                        <span>
                            <strong>
                                @if(isset($reservation['rental_price']))
                                    {{ number_format($reservation['rental_price'], 2) }} €
                                @else
                                    0.00 €
                                @endif
                            </strong>
                        </span>
                    </div>
                    <div class="pricing-row">
                        <span>{{__('Paid')}}:</span>
                        <span>
                            @if(isset($reservation['paid_amount']))
                                {{ number_format($reservation['paid_amount'], 2) }} €
                            @else
                                0.00 €
                            @endif
                        </span>
                    </div>
                    <div class="pricing-row">
                        <span><strong>{{__('Remaining Payment')}}:</strong></span>
                        <span>
                            <strong>
                                @if(isset($reservation['remaining_debt']))
                                    {{ number_format($reservation['remaining_debt'], 2) }} €
                                @elseif(isset($reservation['rental_price']))
                                    {{ number_format($reservation['rental_price'] - ($reservation['paid_amount'] ?? 0), 2) }} €
                                @else
                                    0.00 €
                                @endif
                            </strong>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Details Section -->
            <div class="details-section">
                <div class="details-column">
                    <h5>{{__('Status')}}:</h5>
                    <div class="detail-item">
                        @if(isset($reservation['status']))
                            @php
                                $status = strtoupper($reservation['status']);
                                $statusClass = 'status-active';
                                if ($status == 'OVERDUE') {
                                    $statusClass = 'status-overdue';
                                } elseif (in_array($status, ['PENDING', 'IN_PROGRESS'])) {
                                    $statusClass = 'status-pending';
                                }
                            @endphp
                            <span class="status-badge {{ $statusClass }}">
                                {{ $reservation['status'] }}
                            </span>
                        @elseif(isset($reservation['reservation_status']))
                            <span class="status-badge status-pending">
                                {{ $reservation['reservation_status'] }}
                            </span>
                        @else
                            <span class="status-badge">N/A</span>
                        @endif
                    </div>

                    <h5 class="mt-4">{{__('Invoice Details')}}</h5>
                    <div class="detail-item">
                        <div class="detail-label">{{__('Name/Surname')}}:</div>
                        <div class="detail-value">{{ $reservation['client_name'] ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">{{__('Phone')}}:</div>
                        <div class="detail-value">{{ $reservation['phone'] ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">{{__('E-mail')}}:</div>
                        <div class="detail-value">{{ $reservation['email'] ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">{{__('Return Date')}}:</div>
                        <div class="detail-value">
                            @if(isset($reservation['dropoff_date']))
                                {{ \Carbon\Carbon::parse($reservation['dropoff_date'])->format('d.m.Y H:i') }}
                            @else
                                N/A
                            @endif
                        </div>
                    </div>
                </div>

                <div class="details-column">
                    <h5>{{__('Reservation Information')}}</h5>
                    <div class="detail-item">
                        <div class="detail-label">{{__('Reservation Date and Time')}}:</div>
                        <div class="detail-value">
                            @if(isset($reservation['pickup_date']))
                                {{ \Carbon\Carbon::parse($reservation['pickup_date'])->format('d.m.Y H:i') }}
                            @else
                                N/A
                            @endif
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">{{__('Address')}}:</div>
                        <div class="detail-value">{{ $reservation['address'] ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">{{__('Created by')}}:</div>
                        <div class="detail-value">{{ $reservation['created_by'] ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">{{__('Pickup Date and Time')}}:</div>
                        <div class="detail-value">
                            @if(isset($reservation['pickup_date']))
                                {{ \Carbon\Carbon::parse($reservation['pickup_date'])->format('d.m.Y H:i') }}
                            @else
                                N/A
                            @endif
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">{{__('Drop-off Date and Time')}}:</div>
                        <div class="detail-value">
                            @if(isset($reservation['dropoff_date']))
                                {{ \Carbon\Carbon::parse($reservation['dropoff_date'])->format('d.m.Y H:i') }}
                            @else
                                N/A
                            @endif
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">{{__('Pickup Location')}}:</div>
                        <div class="detail-value">{{ $reservation['pickup_location'] ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">{{__('Drop-off Location')}}:</div>
                        <div class="detail-value">{{ $reservation['dropoff_location'] ?? 'N/A' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">{{__('Cancelled by')}}:</div>
                        <div class="detail-value">{{ $reservation['cancelled_by'] ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            @if(!empty($invoice_error))
                <div class="alert alert-warning mt-4">
                    {{ $invoice_error }}
                </div>
            @endif

            @if(!empty($invoice))
                <div class="invoice-preview">
                    <div class="invoice-preview-header">
                        <div>
                            <h4 class="mb-0">{{ __('Invoice Preview') }}</h4>
                            <small class="text-muted">{{ __('Reservation ID') }}: #{{ data_get($invoice, 'reservation.id', $reservation_id) }}</small>
                        </div>
                        <div>
                            <span class="badge badge-primary">{{ __('Total') }}: {{ number_format((float) data_get($invoice, 'reservation.grand_total', 0), 2) }} €</span>
                        </div>
                    </div>
                    <div class="invoice-preview-body">
                        <div class="invoice-branding">
                            @if(data_get($invoice, 'images.header'))
                                <img src="{{ data_get($invoice, 'images.header') }}" alt="Invoice Header">
                            @endif
                        </div>

                        <div class="invoice-summary-grid">
                            <div class="invoice-meta-card">
                                <h6>{{ __('Client') }}</h6>
                                <div class="value">{{ data_get($invoice, 'client.full_name') }}</div>
                                <div>{{ data_get($invoice, 'client.email') }}</div>
                                <div>{{ data_get($invoice, 'client.phone') }}</div>
                            </div>
                            <div class="invoice-meta-card">
                                <h6>{{ __('Vehicle') }}</h6>
                                <div class="value">{{ data_get($invoice, 'car.manufacturer') }} {{ data_get($invoice, 'car.model') }}</div>
                                <div>{{ __('Plate') }}: {{ data_get($invoice, 'car.licence_plate') }}</div>
                                <div>{{ __('Fuel') }}: {{ data_get($invoice, 'car.fuel_type') }}</div>
                            </div>
                            <div class="invoice-meta-card">
                                <h6>{{ __('Rental Period') }}</h6>
                                <div>{{ data_get($invoice, 'reservation.pickup_datetime') }}</div>
                                <div>{{ data_get($invoice, 'reservation.dropoff_datetime') }}</div>
                                <div>{{ __('Days') }}: {{ data_get($invoice, 'reservation.days') }}</div>
                            </div>
                            <div class="invoice-meta-card">
                                <h6>{{ __('Locations') }}</h6>
                                <div>{{ __('Pickup') }}: {{ data_get($invoice, 'locations.pickup.full_address') }}</div>
                                <div>{{ __('Dropoff') }}: {{ data_get($invoice, 'locations.dropoff.full_address') }}</div>
                            </div>
                        </div>

                        <table class="invoice-table mt-4">
                            <thead>
                                <tr>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Days') }}</th>
                                    <th>{{ __('Price') }}</th>
                                    <th>{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ __('Vehicle Rental') }}</td>
                                    <td>{{ data_get($invoice, 'reservation.days') }}</td>
                                    <td>{{ number_format((float) data_get($invoice, 'reservation.gross_total', 0), 2) }} €</td>
                                    <td>{{ number_format((float) data_get($invoice, 'reservation.gross_total', 0), 2) }} €</td>
                                </tr>
                                @foreach(data_get($invoice, 'extra_equipments', []) as $extra)
                                    <tr>
                                        <td>{{ data_get($extra, 'name') }}</td>
                                        <td>{{ data_get($extra, 'days', '-') }}</td>
                                        <td>{{ number_format((float) data_get($extra, 'price', 0), 2) }} €</td>
                                        <td>{{ number_format((float) data_get($extra, 'total', 0), 2) }} €</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-right">{{ __('Extras Subtotal') }}</th>
                                    <th>{{ number_format((float) data_get($invoice, 'totals.extras_subtotal', 0), 2) }} €</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-right">{{ __('Grand Total') }}</th>
                                    <th>{{ number_format((float) data_get($invoice, 'totals.grand_total', data_get($invoice, 'reservation.grand_total', 0)), 2) }} €</th>
                                </tr>
                            </tfoot>
                        </table>

                        @if(!empty(data_get($invoice, 'collision_damages')))
                            <h5 class="mt-4">{{ __('Collision Damages') }}</h5>
                            <table class="invoice-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Price') }}</th>
                                        <th>{{ __('Description') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(data_get($invoice, 'collision_damages', []) as $damage)
                                        <tr>
                                            <td>{{ data_get($damage, 'name') }}</td>
                                            <td>{{ number_format((float) data_get($damage, 'price', 0), 2) }} €</td>
                                            <td>{{ data_get($damage, 'description_en', data_get($damage, 'description_al')) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                        @if(!empty(data_get($invoice, 'terms_and_conditions')))
                            <h5 class="mt-4">{{ __('Terms & Conditions') }}</h5>
                            <ol class="terms-list">
                                @foreach(data_get($invoice, 'terms_and_conditions', []) as $term)
                                    <li>{{ data_get($term, 'text') }}</li>
                                @endforeach
                            </ol>
                        @endif

                        <div class="invoice-branding mt-4">
                            @if(data_get($invoice, 'images.footer'))
                                <img src="{{ data_get($invoice, 'images.footer') }}" alt="Invoice Footer">
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Comments Section -->
            <div class="comments-section">
                <h5>{{__('Comments')}}</h5>
                <textarea class="comments-textarea" placeholder="{{__('Add comments here...')}}"></textarea>
                <button type="button" class="btn btn-primary btn-sm">{{__('Save Comment')}}</button>
            </div>
        @else
            <div class="alert alert-warning">
                {{__('Reservation not found')}}
            </div>
        @endif
    </div>
</div>
@endsection
