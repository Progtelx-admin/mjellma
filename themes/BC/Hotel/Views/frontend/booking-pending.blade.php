@extends('layouts.app')

@section('content')
    <div class="container mx-auto p-6">
        @php
            $statusValue = $status['status'] ?? 'processing';
            $errorValue = $status['error'] ?? null;
            $isError = $statusValue === 'error' || !empty($errorValue);
            
            // Map error codes to user-friendly messages
            $errorMessages = [
                'timeout' => 'The booking request timed out. Please check your booking status or contact support.',
                'unknown' => 'An unknown error occurred. Please check your booking status or contact support.',
                'booking_finish_did_not_succeed' => 'The booking could not be completed. Please try again or contact support.',
                'soldout' => 'The selected room is no longer available.',
                'block' => 'The booking was blocked. Please contact support for assistance.',
                'charge' => 'Payment processing failed. Please check your payment method and try again.',
                'provider' => 'The hotel provider encountered an issue. Please contact support.',
                'order_not_found' => 'The booking order could not be found. Please contact support.',
                'invalid_params' => 'Invalid booking parameters were provided. Please contact support with your order ID.',
            ];
            
            $displayMessage = null;
            if ($isError && $errorValue) {
                $displayMessage = $errorMessages[$errorValue] ?? 'An error occurred while processing your booking. Please contact support for assistance.';
            }
        @endphp

        @if($isError && $displayMessage)
            <div class="alert alert-warning">
                <h4 class="alert-heading">Booking Status Update</h4>
                <p>{{ $displayMessage }}</p>
                @if(isset($order_id))
                    <p class="mb-0"><small>Order ID: <strong>{{ $order_id }}</strong></small></p>
                @endif
                <hr>
                <p class="mb-0">
                    <a href="{{ url('/hotels') }}" class="btn btn-secondary">Search Again</a>
                </p>
                
                {{-- Show booking details if available --}}
                @if(isset($booking_details))
                    <div class="mt-4">
                        <h5>Booking Information</h5>
                        <table class="table table-sm table-bordered mt-2">
                            <tr>
                                <th width="30%">Order ID:</th>
                                <td>{{ $booking_details['order_id'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge badge-{{ ($booking_details['status'] ?? '') === 'ok' ? 'success' : 'warning' }}">
                                        {{ ucfirst($booking_details['status'] ?? 'Unknown') }}
                                    </span>
                                </td>
                            </tr>
                            @if(isset($booking_details['hotel_data']) && isset($booking_details['hotel_data']['name']))
                            <tr>
                                <th>Hotel:</th>
                                <td>{{ $booking_details['hotel_data']['name'] ?? 'N/A' }}</td>
                            </tr>
                            @endif
                            @if(isset($booking_details['checkin']) && isset($booking_details['checkout']))
                            <tr>
                                <th>Check-in:</th>
                                <td>{{ $booking_details['checkin'] }}</td>
                            </tr>
                            <tr>
                                <th>Check-out:</th>
                                <td>{{ $booking_details['checkout'] }}</td>
                            </tr>
                            @endif
                            @if(isset($booking_details['client_price']))
                            <tr>
                                <th>Amount:</th>
                                <td>{{ $booking_details['client_price']['amount'] ?? '' }} {{ $booking_details['client_price']['currency_code'] ?? '' }}</td>
                            </tr>
                            @endif
                            @if(isset($booking_details['error']))
                            <tr>
                                <th>Error Details:</th>
                                <td class="text-danger">{{ $booking_details['error'] }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                @endif
            </div>
        @else
            <div class="alert alert-info">
                <h4 class="alert-heading">Your booking is being processed</h4>
                <p>Status: <strong>{{ ucfirst($statusValue) }}</strong></p>
                @if(isset($order_id))
                    <p class="mb-0"><small>Order ID: <strong>{{ $order_id }}</strong></small></p>
                @endif
                <hr>
                <p class="mb-0">Please wait while we process your booking. This page will automatically refresh to show the latest status.</p>
            </div>
        @endif

        {{-- Auto-refresh every 10 seconds if not a final error --}}
        @if(!$isError || in_array($errorValue, ['timeout', 'unknown']))
            <script>
                setTimeout(function() {
                    window.location.reload();
                }, 10000);
            </script>
        @endif
    </div>
@endsection
