@extends('layouts.app')

@section('content')
    <div class="container mx-auto p-6">
        @php
            $statusValue = $status['status'] ?? 'processing';
            $errorValue = $status['error'] ?? null;
            $isError = !empty($errorValue);

            // Friendly messages for clients
            $errorMessages = [
                'timeout' => 'We are still processing your booking. Please be patient, this may take a few moments.',
                'unknown' => 'We are finalizing your booking. Please wait a moment before checking again.',
                'booking_finish_did_not_succeed' =>
                    'We could not complete your booking immediately. Our system is checking it for you.',
                'soldout' => 'The selected room is currently unavailable. Please try another option.',
                'block' => 'Your booking is temporarily blocked. Please contact our support if this persists.',
                'charge' => 'There was a payment issue. Please check your payment method.',
                'provider' => 'The hotel provider is processing your booking. Please wait a moment.',
                'order_not_found' => 'We could not locate your booking yet. Please wait or contact support.',
                'invalid_params' =>
                    'Some details were missing or incorrect. Please contact support with your order ID.',
            ];

            $displayMessage =
                $errorMessages[$errorValue] ??
                ($isError ? 'We are checking your booking. Please wait a moment.' : null);
        @endphp

        @if ($isError && $displayMessage)
            <div class="alert alert-warning">
                <h4 class="alert-heading">Booking Update</h4>
                <p>{{ $displayMessage }}</p>
                @if (isset($order_id))
                    <p><small>Order ID: <strong>{{ $order_id }}</strong></small></p>
                @endif

                @if (isset($booking_details))
                    <div class="mt-3">
                        <h5>Your Booking Details</h5>
                        <table class="table table-sm table-bordered mt-2">
                            <tr>
                                <th>Hotel:</th>
                                <td>{{ $booking_details['hotel_data']['name'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Check-in:</th>
                                <td>{{ $booking_details['checkin'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Check-out:</th>
                                <td>{{ $booking_details['checkout'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Amount:</th>
                                <td>{{ $booking_details['client_price']['amount'] ?? '' }}
                                    {{ $booking_details['client_price']['currency_code'] ?? '' }}</td>
                            </tr>
                        </table>
                    </div>
                @endif

                <hr>
                <p>
                    <a href="{{ url('/hotels') }}" class="btn btn-secondary">Search Again</a>
                    <a href="{{ url('/contact') }}" class="btn btn-primary">Contact Support</a>
                </p>
            </div>
        @else
            <div class="alert alert-info">
                <h4 class="alert-heading">Your booking is being processed</h4>
                <p>Status: <strong>{{ ucfirst($statusValue) }}</strong></p>
                @if (isset($order_id))
                    <p><small>Order ID: <strong>{{ $order_id }}</strong></small></p>
                @endif
                <hr>
                <p>Please wait while we finalize your booking. This page will refresh automatically.</p>
            </div>
        @endif

        {{-- Auto-refresh for temporary errors or processing --}}
        @if (!$isError || in_array($errorValue, ['timeout', 'unknown']))
            <script>
                setTimeout(function() {
                    window.location.reload();
                }, 10000);
            </script>
        @endif
    </div>
@endsection
