<div class="b-panel-title">{{ __('Hotel information') }}</div>
<div class="b-table-wrap">
    <table class="b-table" cellspacing="0" cellpadding="0">
        <tr>
            <td class="label">{{ __('Booking Number') }}</td>
            <td class="val">#{{ $booking['order_id'] }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Booking Status') }}</td>
            <td class="val">{{ ucfirst($booking['status']) }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Hotel name') }}</td>
            <td class="val">
                {{ $booking['hotel_data']['name'] ?? '-' }}
            </td>
        </tr>
        <tr>
            <td class="label">{{ __('Address') }}</td>
            <td class="val">{{ $booking['hotel_data']['address'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Check in') }}</td>
            <td class="val">{{ $booking['checkin_at'] }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Check out') }}</td>
            <td class="val">{{ $booking['checkout_at'] }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Guests') }}</td>
            <td class="val">
                <ul>
                    @foreach ($booking['rooms_data'] as $room)
                        @foreach ($room['guest_data']['guests'] as $guest)
                            <li>{{ $guest['first_name'] }} {{ $guest['last_name'] }}</li>
                        @endforeach
                    @endforeach
                </ul>
            </td>
        </tr>
        <tr>
            <td class="label">{{ __('Room Name') }}</td>
            <td class="val">{{ $booking['rooms_data'][0]['room_name'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Meal') }}</td>
            <td class="val">{{ $booking['rooms_data'][0]['meal_name'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Amount Payable') }}</td>
            <td class="val">{{ $booking['amount_payable']['amount'] }}
                {{ $booking['amount_payable']['currency_code'] }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Amount Refunded') }}</td>
            <td class="val">{{ $booking['amount_refunded']['amount'] ?? 0 }}
                {{ $booking['amount_refunded']['currency_code'] ?? 'EUR' }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Total Sell (B2B2C)') }}</td>
            <td class="val">{{ $booking['amount_sell_b2b2c']['amount'] ?? 0 }}
                {{ $booking['amount_sell_b2b2c']['currency_code'] ?? 'EUR' }}</td>
        </tr>

        @if (!empty($booking['cancellation_info']['policies']))
            <tr>
                <td class="label">{{ __('Cancellation Policy') }}</td>
                <td class="val">
                    @foreach ($booking['cancellation_info']['policies'] as $policy)
                        <div>
                            <strong>{{ __('Penalty') }}:</strong> {{ $policy['penalty']['amount'] ?? 0 }}
                            {{ $policy['penalty']['currency_code'] ?? '' }}<br>
                            @if (!empty($policy['start_at']))
                                <strong>{{ __('Starts At') }}:</strong> {{ $policy['start_at'] }}<br>
                            @endif
                            @if (!empty($policy['end_at']))
                                <strong>{{ __('Ends At') }}:</strong> {{ $policy['end_at'] }}<br>
                            @endif
                        </div>
                        <hr>
                    @endforeach
                </td>
            </tr>
        @endif
    </table>
</div>
