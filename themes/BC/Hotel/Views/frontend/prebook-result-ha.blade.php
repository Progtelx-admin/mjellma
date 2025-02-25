@extends('layouts.app')

@section('content')
    <div class="container mt-5 mb-5">
        <h1 class="fw-bold text-center">Prebooking Confirmation</h1>
        <hr class="w-50 mx-auto mb-4">

        @if (isset($prebookData['data']['hotels'][0]))
            @php
                $hotel = $prebookData['data']['hotels'][0];
                $rate = $hotel['rates'][0] ?? null;
            @endphp

            <div class="row align-items-stretch">
                <!-- Hotel Information -->
                <div class="col-md-6 d-flex">
                    <div class="card shadow-sm border-0 w-100 d-flex flex-column">
                        <img src="{{ $hotelImage ?? asset('images/default-hotel.jpg') }}" class="card-img-top"
                            alt="{{ $hotelDetails['name'] }}">

                        <div class="card-body d-flex flex-column">
                            <h4 class="fw-bold">{{ $hotelDetails['name'] }}</h4>
                            <p><i class="fa fa-map-marker-alt text-primary"></i>
                                {{ $hotelDetails['address'] }}
                            </p>

                            @if (!empty($hotelDetails['star_rating']) && $hotelDetails['star_rating'] > 0)
                                <div class="text-warning">
                                    @for ($star = 1; $star <= $hotelDetails['star_rating']; $star++)
                                        <i class="fa fa-star"></i>
                                    @endfor
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Booking Details -->
                <div class="col-md-6 d-flex">
                    <div class="card shadow-sm border-0 w-100 p-4 d-flex flex-column">
                        <h5 class="fw-bold">Booking Summary</h5>
                        <hr>
                        <p><strong>Check-in:</strong> {{ $checkin ?? 'N/A' }}</p>
                        <p><strong>Check-out:</strong> {{ $checkout ?? 'N/A' }}</p>

                        @if ($rate)
                            <p><strong>Room Type:</strong> {{ $rate['room_name'] ?? 'N/A' }}</p>
                            <p><strong>Meal Plan:</strong> {{ ucfirst($rate['meal'] ?? 'N/A') }}</p>
                            <p><strong>Breakfast Included:</strong> {{ $rate['meal_data']['has_breakfast'] ? 'Yes' : 'No' }}
                            </p>
                            <h4 class="fw-bold text-success">Total Price:
                                {{ $rate['payment_options']['payment_types'][0]['amount'] ?? 'N/A' }}
                                {{ $rate['payment_options']['payment_types'][0]['currency_code'] ?? 'EUR' }}</h4>

                            <!-- Book Now Button -->
                            <form method="POST" action="{{ route('hotel.book') }}" class="mt-auto">
                                @csrf
                                <input type="hidden" name="book_hash" value="{{ $rate['book_hash'] }}">
                                <input type="hidden" name="partner_order_id" value="order_{{ uniqid() }}">
                                <input type="hidden" name="user_ip" value="{{ request()->ip() }}">
                                <button type="submit" class="btn btn-primary w-100 py-2 mt-3">Confirm Booking</button>
                            </form>
                        @else
                            <p class="text-danger">No rate information available. Please select another room.</p>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-warning text-center">No prebooking data available. Please try again.</div>
        @endif
    </div>
@endsection
