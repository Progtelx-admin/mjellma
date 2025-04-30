@extends('Layout::empty')

@push('css')
    <style type="text/css">
        html,
        body {
            background: #f0f0f0;
            font-family: 'Segoe UI', sans-serif;
        }

        .bravo_topbar,
        .bravo_header,
        .bravo_footer {
            display: none;
        }

        #invoice-print-zone {
            background: white;
            padding: 40px;
            margin: 80px auto;
            max-width: 800px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            font-size: 14px;
            color: #333;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .company-info p {
            margin: 2px 0;
            font-size: 13px;
        }

        .invoice-details {
            text-align: left;
            font-size: 13px;
        }

        .invoice-title {
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .invoice-meta div {
            margin-bottom: 4px;
        }

        .amount-due {
            font-size: 20px;
            font-weight: bold;
            color: #28a745;
            margin-top: 10px;
        }

        .section {
            margin-top: 30px;
        }

        .section h4 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .info-line {
            margin-bottom: 6px;
        }

        .info-line strong {
            display: inline-block;
            min-width: 140px;
        }
    </style>
@endpush

@section('content')
    <script>
        window.print();
    </script>

    <div id="invoice-print-zone">
        {{-- Header --}}
        <div class="invoice-header">
            <div class="company-info">
                @if (!empty(($logo = setting_item('logo_invoice_id') ?? setting_item('logo_id'))))
                    <img style="max-width: 160px;" src="{{ get_file_url($logo, 'full') }}"
                        alt="{{ setting_item('site_title') }}">
                @endif
                <div class="mt-2">
                    {!! setting_item_with_lang('invoice_company_info') !!}
                </div>
            </div>
            <div class="invoice-details">
                <div class="invoice-title">{{ __('INVOICE') }}</div>
                <div class="invoice-meta">
                    <div><strong>{{ __('Invoice #:') }}</strong> {{ $booking['order_id'] }}</div>
                    <div><strong>{{ __('Created:') }}</strong> {{ display_date($booking['created_at']) }}</div>
                </div>
                <div class="amount-due">{{ __('Amount Due:') }} {{ $booking['amount_payable']['amount'] }}
                    {{ $booking['amount_payable']['currency_code'] }}</div>
            </div>
        </div>

        {{-- Guest Info --}}
        <div class="section">
            <h4>{{ __('Guest Information') }}</h4>
            @php
                $firstGuest = $booking['rooms_data'][0]['guest_data']['guests'][0] ?? [];
            @endphp
            <div class="info-line"><strong>{{ __('Guest:') }}</strong> {{ $firstGuest['first_name'] ?? '' }}
                {{ $firstGuest['last_name'] ?? '' }}</div>
        </div>

        {{-- Hotel Info --}}
        <div class="section">
            <h4>{{ __('Hotel Information') }}</h4>
            <div class="info-line"><strong>{{ __('Hotel ID:') }}</strong> {{ $booking['hotel_data']['id'] ?? '-' }}</div>
            <div class="info-line"><strong>{{ __('Room Name:') }}</strong>
                {{ $booking['rooms_data'][0]['room_name'] ?? '-' }}</div>
            <div class="info-line"><strong>{{ __('Meal Plan:') }}</strong>
                {{ $booking['rooms_data'][0]['meal_name'] ?? '-' }}</div>
        </div>

        {{-- Payment Info --}}
        <div class="section">
            <h4>{{ __('Payment Details') }}</h4>
            <div class="info-line"><strong>{{ __('Amount Payable:') }}</strong> {{ $booking['amount_payable']['amount'] }}
                {{ $booking['amount_payable']['currency_code'] }}</div>
            <div class="info-line"><strong>{{ __('Amount Refunded:') }}</strong>
                {{ $booking['amount_refunded']['amount'] }} {{ $booking['amount_refunded']['currency_code'] }}</div>
        </div>

        {{-- Cancellation Policy --}}
        @if (!empty($booking['cancellation_info']['policies']))
            <div class="section">
                <h4>{{ __('Cancellation Policy') }}</h4>
                @foreach ($booking['cancellation_info']['policies'] as $policy)
                    <div class="info-line"><strong>{{ __('Penalty:') }}</strong> {{ $policy['penalty']['amount'] ?? '0' }}
                        {{ $policy['penalty']['currency_code'] ?? '' }}</div>
                    @if (!empty($policy['start_at']))
                        <div class="info-line"><strong>{{ __('Starts At:') }}</strong> {{ $policy['start_at'] }}</div>
                    @endif
                    @if (!empty($policy['end_at']))
                        <div class="info-line"><strong>{{ __('Ends At:') }}</strong> {{ $policy['end_at'] }}</div>
                    @endif
                    <br>
                @endforeach
            </div>
        @endif
    </div>
@endsection
