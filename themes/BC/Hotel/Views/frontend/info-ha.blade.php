@extends('layouts.app')

@section('content')
    <div class="container mt-5">
        <div class="hotel-header d-flex justify-content-between align-items-center">
            <div>
                <h1 class="fw-bold">{{ $hotel['name'] ?? 'Hotel Name Not Available' }}</h1>
                <p class="text-muted"><i class="fa fa-map-marker"></i> {{ $hotel['address'] ?? 'Location Not Available' }}</p>
                <p class="star-rating">
                    @for ($star = 1; $star <= ($hotel['star_rating'] ?? 0); $star++)
                        <i class="fa fa-star" style="color: #FFD700;"></i>
                    @endfor
                </p>
            </div>
        </div>

        <!-- ✅ Carousel with Circle Indicators -->
        @if (!empty($hotel['images_ext']))
            <div id="hotelImageCarousel" class="carousel slide carousel-fade mt-4" data-bs-ride="carousel">
                <div class="carousel-inner">
                    @foreach ($hotel['images_ext'] as $index => $image)
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                            <img src="{{ $image }}" class="d-block w-100 img-fluid rounded shadow-sm"
                                alt="Hotel Image {{ $index + 1 }}" style="height: 600px; object-fit: cover;">
                        </div>
                    @endforeach
                </div>

                <!-- ✅ Circle Indicators -->
                <div class="carousel-indicators">
                    @foreach ($hotel['images_ext'] as $index => $image)
                        <button type="button" data-bs-target="#hotelImageCarousel" data-bs-slide-to="{{ $index }}"
                            class="{{ $index === 0 ? 'active' : '' }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                            aria-label="Slide {{ $index + 1 }}"></button>
                    @endforeach
                </div>
            </div>
        @else
            <p>No images available for this hotel.</p>
        @endif

        <!-- ✅ Room Selection -->
        @if (!empty($roomRates))
            <h3 class="mt-5">Available Rooms</h3>
            <div class="room-list mt-3">
                @foreach ($roomRates as $rate)
                    <div class="room-card card p-3 mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="fw-bold">{{ $rate['room_name'] ?? 'N/A' }}</h5>
                                <p class="text-muted">{{ ucfirst($rate['meal'] ?? 'No meal included') }}</p>
                                <p class="text-success">Breakfast Included:
                                    {{ $rate['meal_data']['has_breakfast'] ? 'Yes' : 'No' }}</p>
                            </div>
                            <div class="col-md-6 text-end">
                                <h4 class="text-primary">
                                    {{ $rate['payment_options']['payment_types'][0]['amount'] ?? 'N/A' }}
                                    {{ $rate['payment_options']['payment_types'][0]['currency_code'] ?? 'EUR' }}
                                </h4>
                                <form method="POST" action="{{ route('hotel.prebook') }}">
                                    @csrf
                                    <input type="hidden" name="book_hash" value="{{ $rate['book_hash'] }}">
                                    <input type="hidden" name="room_name" value="{{ $rate['room_name'] }}">
                                    <input type="hidden" name="checkin" value="{{ $checkin }}">
                                    <input type="hidden" name="checkout" value="{{ $checkout }}">
                                    <input type="hidden" name="adults" value="{{ $adults }}">
                                    <input type="hidden" name="children" value="{{ json_encode($children) }}">
                                    <input type="hidden" name="currency" value="{{ $currency }}">
                                    <button type="submit" class="btn btn-primary">Choose</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p>No specific room rates available.</p>
        @endif
    </div>
@endsection

<!-- ✅ Styling -->
<style>
    .room-card {
        border-radius: 10px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    }

    .star-rating i {
        font-size: 18px;
    }

    /* ✅ Carousel Indicators */
    .carousel-indicators {
        bottom: -30px;
    }

    .carousel-indicators button {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.7);
        border: none;
        margin: 0 5px;
        transition: background-color 0.3s ease;
    }

    .carousel-indicators .active {
        background-color: #007bff;
    }

    /* ✅ Carousel Image */
    .carousel-item img {
        border-radius: 10px;
        transition: transform 0.5s ease;
    }

    .carousel-item img:hover {
        transform: scale(1.03);
    }
</style>

<!-- ✅ Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
