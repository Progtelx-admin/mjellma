{{-- Display car from API response --}}
@php
    // Build car title from manufacturer and model
    $carName = ($car['car_manufacturer']['name'] ?? '') . ' ' . ($car['car_model']['name'] ?? '');
    $carName = trim($carName) ?: __('Car Rental');

    // Get image base URL
    $imageBaseUrl = env('CAR_IMAGE_BASE_URL');
    if (!$imageBaseUrl) {
        $apiBase = env('CAR_API_BASE', 'https://dev.rentacar-orange.com/api');
        $imageBaseUrl = str_replace('/api', '', $apiBase);
    }

    // Collect all images (main + additional)
    $allImages = [];

    // Add main image first
    if (isset($car['image']['image_path'])) {
        $allImages[] = $imageBaseUrl . '/images/images/' . $car['image']['image_path'];
    }

    // Add additional images
    if (isset($car['car_images']) && is_array($car['car_images'])) {
        foreach ($car['car_images'] as $img) {
            if (isset($img['image_path'])) {
                $imgUrl = $imageBaseUrl . '/images/images/' . $img['image_path'];
                // Avoid duplicates
                if (!in_array($imgUrl, $allImages)) {
                    $allImages[] = $imgUrl;
                }
            }
        }
    }

    // Generate unique ID for carousel
    $carouselId = 'car-carousel-' . $car['id'];

    // Get price from calculation
    $dayPrice = $car['calculation']['day_price'] ?? 0;
    $grandTotal = $car['calculation']['grand_total'] ?? 0;
@endphp

<div class="col-lg-4 col-md-6 car-item-wrapper mb-4">
    <div class="item-loop item-loop-wrap car-card">
        {{-- Popular Badge --}}
        @if (isset($car['popular']) && $car['popular'] == 1)
            <div class="featured">
                {{ __('Popular') }}
            </div>
        @endif

        {{-- Car Image/Carousel --}}
        <div class="thumb-images">
            @if (count($allImages) > 1)
                {{-- Multiple images - Show carousel --}}
                <div id="{{ $carouselId }}" class="carousel slide" data-ride="carousel">
                    <div class="carousel-inner">
                        @foreach ($allImages as $index => $imageUrl)
                            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                <div class="car-image-wrapper">
                                    <img src="{{ $imageUrl }}" class="img-responsive d-block w-100"
                                        alt="{{ $carName }}"
                                        onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'no-image-placeholder\'><i class=\'fa fa-car fa-3x text-muted\'></i><p class=\'text-muted mt-2\'>{{ __('Image unavailable') }}</p></div>';">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Carousel Controls --}}
                    <a class="carousel-control-prev" href="#{{ $carouselId }}" role="button" data-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    </a>
                    <a class="carousel-control-next" href="#{{ $carouselId }}" role="button" data-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    </a>

                    {{-- Image indicators --}}
                    <ol class="carousel-indicators">
                        @foreach ($allImages as $index => $imageUrl)
                            <li data-target="#{{ $carouselId }}" data-slide-to="{{ $index }}"
                                class="{{ $index === 0 ? 'active' : '' }}"></li>
                        @endforeach
                    </ol>
                </div>
            @elseif (count($allImages) === 1)
                {{-- Single image --}}
                <div class="car-image-wrapper">
                    <img src="{{ $allImages[0] }}" class="img-responsive" alt="{{ $carName }}"
                        onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'no-image-placeholder\'><i class=\'fa fa-car fa-3x text-muted\'></i><p class=\'text-muted mt-2\'>{{ __('Image unavailable') }}</p></div>';">
                </div>
            @else
                {{-- No images --}}
                <div class="car-image-wrapper">
                    <div class="no-image-placeholder">
                        <i class="fa fa-car fa-3x text-muted"></i>
                        <p class="text-muted mt-2">{{ __('No Image') }}</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Car Content Wrapper for List View --}}
        <div class="car-card-content">
            {{-- Car Title/Name --}}
            <div class="item-title">
                <h5>{{ $carName }}</h5>
            </div>

            {{-- Price --}}
            <div class="price-section">
                <div class="day-price">
                    <span class="price-amount">€{{ number_format($dayPrice, 2) }}</span>
                    <span class="price-unit">{{ __('/ day') }}</span>
                </div>
                @if ($grandTotal > 0 && isset($car['calculation']['days']) && $car['calculation']['days'] > 1)
                    <div class="total-price">
                        <small class="text-muted">{{ __('Total:') }} €{{ number_format($grandTotal, 2) }} /
                            {{ $car['calculation']['days'] }} {{ __('days') }}</small>
                    </div>
                @endif
            </div>

            {{-- Simple Car Details (Like in Image) --}}
            <div class="car-specs">
                @if (isset($car['car_model']['name']))
                    <span class="spec-item">
                        <i class="fa fa-car"></i>
                        <span class="text">{{ $car['car_model']['name'] }}</span>
                    </span>
                @endif

                @if (isset($car['car_transmission']['name']))
                    <span class="spec-item">
                        <i class="fa fa-cog"></i>
                        <span class="text">{{ $car['car_transmission']['name'] }}</span>
                    </span>
                @endif

                @if (isset($car['car_fuel_type']['name']))
                    <span class="spec-item">
                        <i class="fa fa-gas-pump"></i>
                        <span class="text">{{ $car['car_fuel_type']['name'] }}</span>
                    </span>
                @endif
            </div>

            {{-- Book Now Button --}}
            <div class="book-btn-wrapper">
                @php
                    $detailUrl =
                        route('car.detail', ['slug' => \Illuminate\Support\Str::slug($carName)]) .
                        '?' .
                        http_build_query([
                            'car_id' => $car['id'],
                            'pickup_place' => request('pickup_place'),
                            'return_place' => request('return_place'),
                            'pickup_date' => request('pickup_date'),
                            'pickup_time' => request('pickup_time'),
                            'dropoff_date' => request('dropoff_date'),
                            'return_time' => request('return_time'),
                        ]);
                @endphp
                <a href="{{ $detailUrl }}" class="btn btn-primary book-car-btn">
                    {{ __('Reserve') }}
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .car-item-wrapper {
        margin-bottom: 1.5rem;
    }

    .car-card {
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-radius: 0.5rem;
        overflow: hidden;
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border: 1px solid #e9ecef;
    }

    .car-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
    }

    .thumb-images {
        position: relative;
    }

    .car-image-wrapper {
        position: relative;
        width: 100%;
        padding-bottom: 66.67%;
        /* 3:2 aspect ratio */
        overflow: hidden;
        background: #f8f9fa;
    }

    .car-image-wrapper img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Carousel styling */
    .carousel {
        position: relative;
    }

    .carousel-inner {
        border-radius: 0.5rem 0.5rem 0 0;
        overflow: hidden;
    }

    .carousel-control-prev,
    .carousel-control-next {
        width: 40px;
        height: 40px;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(11, 11, 69, 0.7);
        border-radius: 50%;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .car-card:hover .carousel-control-prev,
    .car-card:hover .carousel-control-next {
        opacity: 1;
    }

    .carousel-control-prev {
        left: 10px;
    }

    .carousel-control-next {
        right: 10px;
    }

    .carousel-control-prev-icon,
    .carousel-control-next-icon {
        width: 20px;
        height: 20px;
    }

    .carousel-indicators {
        bottom: 10px;
        margin: 0;
    }

    .carousel-indicators li {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.5);
        border: none;
        margin: 0 3px;
        cursor: pointer;
    }

    .carousel-indicators li.active {
        background-color: #F27625;
        width: 24px;
        border-radius: 4px;
    }

    .no-image-placeholder {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #f1f3f5;
    }

    .no-image-placeholder p {
        margin: 0;
        font-size: 0.9rem;
    }

    .item-title {
        padding: 1rem 1rem 0.75rem 1rem;
    }

    .item-title h5 {
        color: #2c3e50;
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
    }

    .car-card-content {
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .price-section {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f0f0f0;
    }

    .day-price {
        display: flex;
        align-items: baseline;
        gap: 0.25rem;
    }

    .price-amount {
        font-size: 1.65rem;
        font-weight: 700;
        color: #2c3e50;
    }

    .price-unit {
        font-size: 1rem;
        color: #F27625;
        font-weight: 600;
    }

    .total-price {
        margin-top: 0.35rem;
    }

    .car-specs {
        display: flex;
        flex-wrap: wrap;
        gap: 1.25rem;
        padding: 1rem;
        border-bottom: 1px solid #f0f0f0;
    }

    .spec-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #555;
        font-size: 0.9rem;
    }

    .spec-item i {
        color: #666;
        font-size: 1rem;
    }

    .book-btn-wrapper {
        padding: 1rem;
        margin-top: auto;
    }

    .book-btn-wrapper .book-car-btn {
        width: 100%;
        background: linear-gradient(135deg, #0B0B45 0%, #1a1a6e 100%);
        border: none;
        color: white;
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
        font-weight: 600;
        border-radius: 0.375rem;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .book-btn-wrapper .book-car-btn:hover {
        background: linear-gradient(135deg, #1a1a6e 0%, #0B0B45 100%);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(11, 11, 69, 0.3);
    }

    .featured {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: #F27625;
        color: white;
        padding: 0.35rem 0.75rem;
        border-radius: 0.25rem;
        font-size: 0.8rem;
        font-weight: 600;
        z-index: 10;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    /* === LIST VIEW SPECIFIC === */
    .view-list .car-item-wrapper {
        margin-bottom: 1.5rem;
    }

    .view-list .car-item-wrapper {
        width: 100%;
        max-width: 100%;
        flex: 0 0 100%;
    }

    .view-list .car-card {
        flex-direction: row;
        align-items: stretch;
    }

    .view-list .thumb-images {
        flex: 0 0 320px;
        max-width: 320px;
        min-height: 240px;
    }

    .view-list .car-image-wrapper {
        padding-bottom: 0;
        height: 240px;
    }

    .view-list .carousel {
        height: 100%;
    }

    .view-list .carousel-inner,
    .view-list .carousel-item {
        height: 100%;
    }

    .view-list .car-card-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 1.25rem;
    }

    .view-list .item-title {
        border-bottom: none;
        padding: 0 0 0.75rem 0;
    }

    .view-list .price-section {
        padding: 0.5rem 0;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 0.75rem;
    }

    .view-list .car-specs {
        padding: 0.75rem 0;
        border: none;
        background: transparent;
    }

    .view-list .book-btn-wrapper {
        margin-top: auto;
        padding: 1rem 0 0 0;
        border-top: 2px solid #e9ecef;
    }

    .view-list .book-btn-wrapper .book-car-btn {
        width: auto;
        padding: 0.75rem 2.5rem;
        white-space: nowrap;
    }

    @media (max-width: 991px) {
        .view-list .car-card {
            flex-direction: column;
        }

        .view-list .thumb-images {
            flex: 1;
            max-width: 100%;
            min-height: auto;
        }

        .view-list .car-image-wrapper {
            padding-bottom: 66.67%;
            height: auto;
        }

        .view-list .car-card-content {
            padding: 1rem;
        }

        .view-list .g-price {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }

        .view-list .book-btn-wrapper .book-car-btn {
            width: 100%;
        }

        .view-list .item-title {
            padding: 0 0 0.5rem 0;
        }

        .view-list .price-section {
            padding: 0.5rem 0;
        }

        .view-list .car-specs {
            flex-direction: column;
            gap: 0.75rem;
        }
    }
</style>
