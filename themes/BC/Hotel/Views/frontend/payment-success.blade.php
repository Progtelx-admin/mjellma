@extends('layouts.app')

@section('content')
    <div class="container text-center mt-5 mb-5">
        <div class="card shadow-sm p-5">
            <h1 class="fw-bold text-success">Payment Successful!</h1>
            <p class="mt-3">Your booking has been successfully completed.</p>

            <!-- Display booking details -->
            <div class="mt-4">
                <h4>Booking Details:</h4>
                <p><strong>Hotel Name:</strong> {{ $bookingDetails['hotel_name'] ?? 'N/A' }}</p>
                <p><strong>Check-In:</strong> {{ $bookingDetails['check_in'] ?? 'N/A' }}</p>
                <p><strong>Check-Out:</strong> {{ $bookingDetails['check_out'] ?? 'N/A' }}</p>
                <p><strong>Guests:</strong></p>
                <ul>
                    @foreach ($bookingDetails['guest_names'] as $guest)
                        <li>{{ $guest }}</li>
                    @endforeach
                </ul>
                <p><strong>Room:</strong> {{ $bookingDetails['room_name'] ?? 'Standard Room' }}</p>
            </div>

            <!-- Back to Search button -->
            <div class="mt-4">
                <a href="{{ route('hotel.show') }}" class="btn btn-primary">Back to Search</a>
            </div>
        </div>
    </div>
@endsection
