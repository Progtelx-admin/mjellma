@extends('admin.layouts.app')
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar">{{ __('Booking Details') }} – #{{ $booking['order_id'] }}</h1>
            <a href="{{ route('hotel.admin.booking.index') }}" class="btn btn-secondary">{{ __('Back to Bookings') }}</a>
        </div>

        @include('admin.message')

        <div class="panel">
            <div class="panel-body">
                <h4>{{ __('General Info') }}</h4>
                <table class="table table-bordered">
                    <tr>
                        <th>{{ __('Order ID') }}</th>
                        <td>{{ $booking['order_id'] }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Status') }}</th>
                        <td><span class="badge badge-success">{{ $booking['status'] }}</span></td>
                    </tr>
                    <tr>
                        <th>{{ __('Created At') }}</th>
                        <td>{{ \Carbon\Carbon::parse($booking['created_at'])->format('Y-m-d H:i') }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Check-in / Check-out') }}</th>
                        <td>{{ $booking['checkin_at'] }} → {{ $booking['checkout_at'] }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Agreement Number') }}</th>
                        <td>{{ $booking['agreement_number'] }}</td>
                    </tr>
                </table>

                <h4 class="mt-4">{{ __('Guest Info') }}</h4>
                <table class="table table-bordered">
                    @foreach ($booking['rooms_data'] as $room)
                        @foreach ($room['guest_data']['guests'] as $guest)
                            <tr>
                                <th>{{ __('Guest') }}</th>
                                <td>{{ $guest['first_name'] }} {{ $guest['last_name'] }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </table>

                <h4 class="mt-4">{{ __('Hotel Info') }}</h4>
                <table class="table table-bordered">
                    <tr>
                        <th>{{ __('Hotel ID') }}</th>
                        <td>{{ $booking['hotel_data']['id'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Room Name') }}</th>
                        <td>{{ $booking['rooms_data'][0]['room_name'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('Meal') }}</th>
                        <td>{{ $booking['rooms_data'][0]['meal_name'] ?? '-' }}</td>
                    </tr>
                </table>

                <h4 class="mt-4">{{ __('Pricing') }}</h4>
                <table class="table table-bordered">
                    <tr>
                        <th>{{ __('Amount Payable') }}</th>
                        <td>{{ $booking['amount_payable']['amount'] }} {{ $booking['amount_payable']['currency_code'] }}
                        </td>
                    </tr>
                    <tr>
                        <th>{{ __('Amount Refunded') }}</th>
                        <td>{{ $booking['amount_refunded']['amount'] }} {{ $booking['amount_refunded']['currency_code'] }}
                        </td>
                    </tr>
                    <tr>
                        <th>{{ __('Total Sell (B2B2C)') }}</th>
                        <td>{{ $booking['amount_sell_b2b2c']['amount'] }}
                            {{ $booking['amount_sell_b2b2c']['currency_code'] }}</td>
                    </tr>
                </table>

                <h4 class="mt-4">{{ __('Cancellation Policy') }}</h4>
                @foreach ($booking['cancellation_info']['policies'] as $policy)
                    <div class="mb-2">
                        <strong>{{ __('Penalty') }}:</strong> {{ $policy['penalty']['amount'] ?? '0' }}
                        {{ $policy['penalty']['currency_code'] ?? '' }}<br>
                        @if (!empty($policy['start_at']))
                            <strong>{{ __('Starts At') }}:</strong> {{ $policy['start_at'] }}<br>
                        @endif
                        @if (!empty($policy['end_at']))
                            <strong>{{ __('Ends At') }}:</strong> {{ $policy['end_at'] }}<br>
                        @endif
                    </div>
                @endforeach

                @if (strtolower($booking['status']) !== 'cancelled' && !empty($booking['partner_data']['order_id']))
                    <div class="mt-4">
                        <form method="POST"
                            action="{{ route('booking.admin.cancel', ['partnerOrderId' => $booking['partner_data']['order_id']]) }}"
                            onsubmit="return confirm('{{ __('Are you sure to cancel this booking?') }}');">
                            @csrf
                            <button class="btn btn-danger">{{ __('Cancel Booking') }}</button>
                        </form>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection
