@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Payment Success</h1>

        @if ($paymentResult)
            <p><strong>Status:</strong> {{ $paymentResult['status'] ?? 'N/A' }}</p>
            <p><strong>Message:</strong> {{ $paymentResult['message'] ?? 'Payment completed successfully.' }}</p>

            @if (isset($paymentResult['payment_type']))
                <p><strong>Payment Type:</strong> {{ ucfirst($paymentResult['payment_type']['type'] ?? 'N/A') }}</p>
                <p><strong>Amount:</strong> {{ $paymentResult['payment_type']['amount'] ?? 'N/A' }}
                    {{ $paymentResult['payment_type']['currency_code'] ?? 'N/A' }}</p>
            @endif

            @if (isset($paymentResult['order_id']))
                <p><strong>Order ID:</strong> {{ $paymentResult['order_id'] }}</p>
            @endif
        @else
            <p>No payment result available.</p>
        @endif

        <a href="{{ route('hotel.show') }}" class="btn btn-primary">Back to Hotels</a>
    </div>
@endsection
