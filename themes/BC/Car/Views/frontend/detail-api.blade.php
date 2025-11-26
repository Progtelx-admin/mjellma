@extends('layouts.app')

@push('css')
    <link href="{{ asset('dist/frontend/module/car/css/car.css?_ver=' . config('app.asset_version')) }}" rel="stylesheet">
    <style>
        /* === OVERALL PAGE === */
        .bravo_detail_car {
            background: #f5f7fa;
            min-height: 100vh;
            padding-bottom: 3rem;
        }

        /* === HEADER INFO BAR === */
        .rental-info-bar {
            background: linear-gradient(135deg, #0B0B45 0%, #1a1a6e 100%);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .info-item {
            text-align: center;
            padding: 0 1rem;
            border-right: 1px solid #e0e0e0;
            color: white;
        }

        .info-item:last-child {
            border-right: none;
        }

        .info-label {
            font-size: 0.85rem;
            color: #F27625;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1rem;
            color: white;
            font-weight: 500;
        }

        .info-total {
            font-size: 1.5rem;
            color: white;
            font-weight: 700;
        }

        /* === CAR DETAILS === */
        .car-detail-container {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .car-name-price {
            padding: 1.5rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .car-name-price h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0 0 0.75rem 0;
        }

        .price-display {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
        }

        .price-amount {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .price-unit {
            font-size: 1.25rem;
            color: #F27625;
            font-weight: 600;
        }

        /* === CAR IMAGES === */
        .car-images-section {
            padding: 1.5rem;
            background: white;
        }

        .main-car-image {
            width: 100%;
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 1rem;
            max-height: 350px;
            min-height: 350px;
        }

        @media (max-width: 767px) {
            .main-car-image {
                max-height: 250px;
                min-height: 250px;
            }
        }

        .main-car-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .thumbnail-images {
            display: flex;
            gap: 0.75rem;
        }

        .thumbnail-item {
            flex: 1;
            border-radius: 0.375rem;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s;
        }

        .thumbnail-item:hover {
            border-color: #F27625;
        }

        .thumbnail-item.active {
            border-color: #F27625;
        }

        .thumbnail-item img {
            width: 100%;
            height: 80px;
            object-fit: cover;
            display: block;
        }

        /* === SPECIFICATIONS === */
        .spec-section {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: white;
        }

        .spec-section:last-child {
            border-bottom: none;
        }

        .spec-section:first-child {
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .spec-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .spec-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 0.75rem;
        }

        .spec-item-detail {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.4rem 0.6rem;
            background: #f5f7fa;
            border-radius: 0.375rem;
            border: 1px solid #e5e7eb;
        }

        .spec-item-detail i {
            color: #F27625;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        .spec-item-detail span {
            color: #2c3e50;
            font-size: 0.95rem;
        }

        .feature-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .feature-tag {
            padding: 0.5rem 1rem;
            background: #f5f7fa;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 0.9rem;
            color: #2c3e50;
            font-weight: 500;
        }

        /* === EXTRAS SECTION === */
        .extras-section {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #F27625;
        }

        .extra-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            margin-bottom: 0.75rem;
            transition: all 0.2s;
        }

        .extra-option:hover {
            background: #f3f4f6;
            border-color: #d1d5db;
        }

        .extra-info {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .extra-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #F27625;
            font-size: 1.25rem;
            position: relative;
            cursor: help;
        }

        /* Franchise Info Tooltip */
        .franchise-info-icon {
            position: relative;
            cursor: help;
        }

        .franchise-tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(-10px);
            background: #2c3e50;
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            line-height: 1.4;
            white-space: normal;
            width: 280px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            pointer-events: none;
        }

        .franchise-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 8px solid transparent;
            border-top-color: #2c3e50;
        }

        .franchise-info-icon:hover .franchise-tooltip {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(-5px);
        }

        @media (max-width: 767px) {
            .franchise-tooltip {
                width: 220px;
                font-size: 0.8rem;
                padding: 0.6rem 0.8rem;
            }
        }

        .extra-details {
            flex: 1;
        }

        .extra-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .extra-price {
            font-weight: 600;
            color: #F27625;
            font-size: 1.1rem;
            margin-right: 1rem;
        }

        .extra-checkbox {
            width: 24px;
            height: 24px;
            cursor: pointer;
            accent-color: #F27625;
        }

        /* === PRICE SUMMARY === */
        .price-summary {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 1rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-row.highlight {
            background: linear-gradient(135deg, #F27625 0%, #ff8a45 100%);
            color: white;
            padding: 1rem;
            border-radius: 0.375rem;
            margin: 0.5rem 0;
            font-weight: 600;
        }

        .summary-row.total {
            background: linear-gradient(135deg, #0B0B45 0%, #1a1a6e 100%);
            color: white;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-top: 0.5rem;
            font-weight: 700;
        }

        .btn-continue {
            width: 100%;
            background: linear-gradient(135deg, #0B0B45 0%, #1a1a6e 100%);
            border: none;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 0.375rem;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-continue:hover {
            background: linear-gradient(135deg, #1a1a6e 0%, #0B0B45 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(11, 11, 69, 0.4);
        }

        .car-name-price {
            padding: 1.5rem 1.5rem 1rem 1.5rem;
            background: white;
            border-radius: 0.5rem 0.5rem 0 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .car-name-price h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 0.75rem 0;
        }

        .car-images-section {
            padding: 1.5rem;
            background: white;
            border-radius: 0 0 0.5rem 0.5rem;
        }

        /* === RESPONSIVE === */
        @media (max-width: 991px) {
            .info-item {
                margin-bottom: 1rem;
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
                padding-bottom: 1rem;
            }

            .info-item:last-child {
                border-bottom: none;
            }

            .spec-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="bravo_detail_car">
        {{-- Rental Info Header --}}
        <div class="rental-info-bar">
            <div class="container">
                <div class="row">
                    <div class="col-md-3 col-6 info-item">
                        <div class="info-label">{{ __('Pickup Location') }}</div>
                        <div class="info-value">{{ $pickup_location['address'] ?? 'N/A' }}</div>
                        <div class="info-value">{{ $pickup_datetime->format('d.m.Y H:i') }}</div>
                    </div>
                    <div class="col-md-3 col-6 info-item">
                        <div class="info-label">{{ __('Drop-off Location') }}</div>
                        <div class="info-value">{{ $dropoff_location['address'] ?? 'N/A' }}</div>
                        <div class="info-value">{{ $dropoff_datetime->format('d.m.Y H:i') }}</div>
                    </div>
                    <div class="col-md-3 col-6 info-item">
                        <div class="info-label">{{ __('Duration') }}</div>
                        <div class="info-value">{{ $car['days'] ?? $duration }} {{ __('Days') }}</div>
                    </div>
                    <div class="col-md-3 col-6 info-item">
                        <div class="info-label">{{ __('Total') }}</div>
                        <div class="info-total">{{ number_format($car['car']['calculation']['grand_total'] ?? 0, 2) }} €
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            @php
                $carData = $car['car'] ?? [];
                $carName = trim(
                    ($carData['car_manufacturer']['name'] ?? '') . ' ' . ($carData['car_model']['name'] ?? ''),
                );
            @endphp

            {{-- Top Section: Car Name, Image, and Basic Specs --}}
            <div class="row mb-4">
                {{-- Left: Car Name, Price, and Images --}}
                <div class="col-lg-6">
                    <div class="car-detail-container">
                        <div class="car-name-price">
                            <h2>{{ $carName }}</h2>
                            <div class="price-display">
                                <span
                                    class="price-amount">{{ number_format($carData['calculation']['day_price'] ?? 0, 2) }}
                                    €</span>
                                <span class="price-unit">{{ __('/ day') }}</span>
                            </div>
                        </div>

                        {{-- Car Images --}}
                        <div class="car-images-section">
                            @php
                                $imageBasePath = '/images/images/';
                                $imageBaseUrl = env('CAR_IMAGE_BASE_URL', rtrim(env('CAR_API_BASE', ''), '/api'));

                                $allImages = [];
                                if (isset($carData['image']['image_path'])) {
                                    $allImages[] = $imageBaseUrl . $imageBasePath . $carData['image']['image_path'];
                                }
                                if (isset($carData['car_images']) && is_array($carData['car_images'])) {
                                    foreach ($carData['car_images'] as $image) {
                                        if (isset($image['image_path'])) {
                                            $allImages[] = $imageBaseUrl . $imageBasePath . $image['image_path'];
                                        }
                                    }
                                }
                            @endphp

                            @if (count($allImages) > 0)
                                <div class="main-car-image">
                                    <img src="{{ $allImages[0] }}" alt="{{ $carName }}" id="mainImage">
                                </div>
                                @if (count($allImages) > 1)
                                    <div class="thumbnail-images">
                                        @foreach (array_slice($allImages, 0, 4) as $index => $image)
                                            <div class="thumbnail-item {{ $index == 0 ? 'active' : '' }}"
                                                onclick="changeMainImage('{{ $image }}', this)">
                                                <img src="{{ $image }}" alt="{{ $carName }}">
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                <div class="main-car-image">
                                    <div class="no-image-placeholder"
                                        style="height: 300px; display: flex; align-items: center; justify-content: center; background: #f0f0f0;">
                                        <div class="text-center">
                                            <i class="fa fa-car" style="font-size: 3rem; color: #ccc;"></i>
                                            <p class="mt-2" style="color: #999;">{{ __('No Image') }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>

                {{-- Right: Characteristics, Capacity, Features --}}
                <div class="col-lg-6">
                    <div class="car-detail-container">
                        {{-- Car Characteristics --}}
                        <div class="spec-section">
                            <h3 class="spec-title">{{ __('Car Characteristics') }}</h3>
                            <div class="spec-list">
                                @if (isset($carData['car_air_conditioning']) && $carData['car_air_conditioning'])
                                    <div class="spec-item-detail">
                                        <i class="fa fa-snowflake"></i>
                                        <span>{{ __('Air Conditioner') }}</span>
                                    </div>
                                @endif
                                @if (isset($carData['door_count']))
                                    <div class="spec-item-detail">
                                        <i class="fa fa-door-open"></i>
                                        <span>{{ $carData['door_count'] }} {{ __('Doors') }}</span>
                                    </div>
                                @endif
                                @if (isset($carData['car_transmission']['name']))
                                    <div class="spec-item-detail">
                                        <i class="fa fa-cog"></i>
                                        <span>{{ __('Transmission') }} {{ $carData['car_transmission']['name'] }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Capacity --}}
                        <div class="spec-section">
                            <h3 class="spec-title">{{ __('Capacity') }}</h3>
                            <div class="spec-list">
                                @if (isset($carData['passenger_nr']))
                                    <div class="spec-item-detail">
                                        <i class="fa fa-users"></i>
                                        <span>{{ $carData['passenger_nr'] }} {{ __('Passengers') }}</span>
                                    </div>
                                @endif
                                @if (isset($carData['suitcase_nr']))
                                    <div class="spec-item-detail">
                                        <i class="fa fa-suitcase"></i>
                                        <span>{{ $carData['suitcase_nr'] }} {{ __('Luggage') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Features/Extras --}}
                        @if (isset($carData['car_feature']) && is_array($carData['car_feature']) && count($carData['car_feature']) > 0)
                            <div class="spec-section">
                                <h3 class="spec-title">{{ __('Extras') }}</h3>
                                <div class="feature-tags">
                                    @foreach ($carData['car_feature'] as $feature)
                                        <div class="feature-tag">{{ $feature['name'] ?? '' }}</div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Bottom Section: Extra Options and Price Summary --}}
            <div class="row">
                {{-- Left: Extra Reservation Options --}}
                <div class="col-lg-6">

                    {{-- Extra Reservation Options --}}
                    @if (isset($car['extra_features']) && is_array($car['extra_features']) && count($car['extra_features']) > 0)
                        <div class="extras-section">
                            <h3 class="section-title">{{ __('Extra Reservation Options') }}</h3>
                            @foreach ($car['extra_features'] as $extra)
                                <div class="extra-option">
                                    <div class="extra-info">
                                        <div class="extra-icon">
                                            <i class="fa fa-plus-circle"></i>
                                        </div>
                                        <div class="extra-details">
                                            <div class="extra-name">{{ $extra['name_al'] ?? ($extra['name'] ?? 'N/A') }}
                                            </div>
                                        </div>
                                    </div>
                                    <span class="extra-price">{{ number_format($extra['price'] ?? 0, 2) }} €</span>
                                    <input type="checkbox" class="extra-checkbox" data-extra-id="{{ $extra['id'] ?? '' }}"
                                        data-extra-price="{{ $extra['price'] ?? 0 }}"
                                        data-extra-name="{{ $extra['name_al'] ?? ($extra['name'] ?? '') }}">
                                </div>
                            @endforeach
                        </div>
                    @endif

                </div>

                {{-- Right: Franchise and Final Account --}}
                <div class="col-lg-6">
                    {{-- Franchise Options --}}
                    @if (isset($carData['collision_damages']) &&
                            is_array($carData['collision_damages']) &&
                            count($carData['collision_damages']) > 0)
                        <div class="extras-section mb-3">
                            <h3 class="section-title">{{ __('Franchise / Sigurimi i veturës') }}</h3>
                            @foreach ($carData['collision_damages'] as $index => $damage)
                                @php
                                    $franchiseName = $damage['name'] ?? 'N/A';
                                    $franchisePrice = $damage['price'] ?? 0;
                                    
                                    // Extract franchise amount from name (e.g., "Franshizë_1000€" -> "1000€")
                                    $amount = '';
                                    if (preg_match('/(\d+[€]?)/', $franchiseName, $matches)) {
                                        $amount = $matches[1];
                                    }
                                    
                                    // Generate tooltip text based on franchise
                                    $tooltipText = '';
                                    if ($franchisePrice == 0) {
                                        $tooltipText = __('Standard coverage with ') . $amount . __(' deductible. You are responsible for damages up to ') . $amount . __(' in case of an accident.');
                                    } else {
                                        $tooltipText = __('Premium coverage reducing your deductible to ') . $amount . __('. Additional daily fee applies but offers better protection in case of damage.');
                                    }
                                    
                                    // If no amount found, provide generic description
                                    if (empty($amount)) {
                                        if ($franchisePrice == 0) {
                                            $tooltipText = __('Standard insurance coverage with deductible. You are responsible for damages up to the deductible amount in case of an accident.');
                                        } else {
                                            $tooltipText = __('Additional insurance coverage that reduces your financial responsibility in case of damage. Daily fee applies.');
                                        }
                                    }
                                @endphp
                                <div class="extra-option">
                                    <div class="extra-info">
                                        <div class="extra-icon franchise-info-icon">
                                            <i class="fa fa-info-circle"></i>
                                            <div class="franchise-tooltip">{{ $tooltipText }}</div>
                                        </div>
                                        <div class="extra-details">
                                            <div class="extra-name">{{ $franchiseName }}</div>
                                        </div>
                                    </div>
                                    <span class="extra-price">{{ number_format($franchisePrice, 2) }} €
                                        {{ __('per day') }}</span>
                                    <input type="radio" name="franchise" class="extra-checkbox"
                                        data-damage-id="{{ $damage['id'] ?? '' }}"
                                        data-damage-price="{{ $franchisePrice }}"
                                        data-damage-name="{{ $franchiseName }}" {{ $index == 0 ? 'checked' : '' }}>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Price Summary --}}
                    <div class="price-summary">
                        <h3 class="section-title">{{ __('Final Account') }}</h3>

                        {{-- Car Rental Base Price --}}
                        <div class="summary-row highlight">
                            <span>{{ $carName }}</span>
                            <span>{{ number_format($carData['calculation']['grand_total'] ?? 0, 2) }} €</span>
                        </div>
                        <div class="summary-row">
                            <small class="text-muted">{{ number_format($carData['calculation']['day_price'] ?? 0, 2) }} €
                                x
                                {{ $car['days'] ?? $duration }} {{ __('Days') }}</small>
                        </div>

                        {{-- Selected Extras (will be populated by JS) --}}
                        <div id="selected-extras-summary"></div>

                        {{-- Location Extra Price --}}
                        @if ($location_extra_price > 0)
                            <div class="summary-row">
                                <span>{{ __('Location Fee') }}</span>
                                <span>{{ number_format($location_extra_price, 2) }} €</span>
                            </div>
                        @endif

                        {{-- VAT Breakdown --}}
                        @php
                            $calculation = $carData['calculation'] ?? [];
                            $noTax = floatval($calculation['no_tax'] ?? 0);
                            $taxAmount = floatval($calculation['tax'] ?? 0);
                            $grandTotal = floatval($calculation['grand_total'] ?? 0);
                        @endphp
                        @if ($noTax > 0 && $taxAmount > 0)
                            <div class="summary-row">
                                <span>{{ __('Without VAT') }}</span>
                                <span>{{ number_format($noTax, 2) }} €</span>
                            </div>
                            <div class="summary-row">
                                <span>{{ __('VAT') }}</span>
                                <span>{{ number_format($taxAmount, 2) }} €</span>
                            </div>
                        @endif

                        {{-- Total --}}
                        <div class="summary-row total">
                            <span>{{ __('Total') }}</span>
                            <span id="final-total">{{ number_format($grandTotal, 2) }}
                                €</span>
                        </div>

                        {{-- Continue Button --}}
                        <button type="button" class="btn btn-continue" onclick="continueReservation()">
                            {{ __('Continue Reservation') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        // Change main image when clicking thumbnails
        function changeMainImage(imageUrl, element) {
            document.getElementById('mainImage').src = imageUrl;
            document.querySelectorAll('.thumbnail-item').forEach(item => item.classList.remove('active'));
            element.classList.add('active');
        }

        // Track selected extras and update price
        let baseTotal = {{ $carData['calculation']['grand_total'] ?? 0 }};
        let locationFee = {{ $location_extra_price }};
        let duration = {{ $car['days'] ?? $duration }};
        let selectedExtras = [];
        let selectedFranchise = null;

        // Restore previous selections from sessionStorage if available
        const carId = {{ $carData['id'] ?? 0 }};
        const storageKey = 'car_selections_' + carId;

        function loadSavedSelections() {
            try {
                const saved = sessionStorage.getItem(storageKey);
                if (saved) {
                    const data = JSON.parse(saved);

                    console.log('Restoring saved selections:', data);

                    // Clear current selections
                    selectedExtras = [];
                    selectedFranchise = null;

                    // Restore extra checkboxes and rebuild selectedExtras array
                    if (data.extras && Array.isArray(data.extras)) {
                        data.extras.forEach(extraId => {
                            const checkbox = document.querySelector(`.extra-checkbox[data-extra-id="${extraId}"]`);
                            if (checkbox && checkbox.type === 'checkbox') {
                                checkbox.checked = true;
                                
                                // Manually add to selectedExtras array
                                const extraPrice = parseFloat(checkbox.dataset.extraPrice);
                                const extraName = checkbox.dataset.extraName;
                                
                                selectedExtras.push({
                                    id: extraId,
                                    price: extraPrice,
                                    name: extraName
                                });
                                
                                console.log('Restored extra:', {id: extraId, name: extraName, price: extraPrice});
                            }
                        });
                    }

                    // Restore franchise selection
                    if (data.franchiseId) {
                        const radio = document.querySelector(
                            `input[name="franchise"][data-damage-id="${data.franchiseId}"]`);
                        if (radio) {
                            radio.checked = true;
                            
                            // Manually set selectedFranchise
                            selectedFranchise = {
                                id: radio.dataset.damageId,
                                price: parseFloat(radio.dataset.damagePrice),
                                name: radio.dataset.damageName
                            };
                            
                            console.log('Restored franchise:', selectedFranchise);
                        }
                    }

                    // Update the display and totals
                    setTimeout(() => {
                        // Update the extras summary display
                        const extrasSummary = document.getElementById('selected-extras-summary');
                        extrasSummary.innerHTML = '';
                        
                        selectedExtras.forEach(extra => {
                            const row = document.createElement('div');
                            row.className = 'summary-row';
                            row.innerHTML = `<span>${extra.name}</span><span>${extra.price.toFixed(2)} €</span>`;
                            extrasSummary.appendChild(row);
                        });
                        
                        // Update the total
                        updateTotal();

                        console.log('Selections restored. Selected extras:', selectedExtras);
                        console.log('Selected franchise:', selectedFranchise);
                        console.log('Final total element:', document.getElementById('final-total').textContent);
                    }, 50);
                }
            } catch (e) {
                console.error('Error loading saved selections:', e);
            }
        }

        function saveSelections() {
            try {
                const data = {
                    extras: selectedExtras.map(e => e.id),
                    franchiseId: selectedFranchise ? selectedFranchise.id : null
                };
                sessionStorage.setItem(storageKey, JSON.stringify(data));
            } catch (e) {
                console.error('Error saving selections:', e);
            }
        }

        // Handle extra checkboxes
        document.querySelectorAll('.extra-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.type === 'checkbox') {
                    // Handle extras
                    updateExtras();
                    saveSelections();
                } else {
                    // Handle franchise (radio)
                    updateFranchise();
                    saveSelections();
                }
            });
        });

        function updateExtras() {
            selectedExtras = [];
            const extrasSummary = document.getElementById('selected-extras-summary');
            extrasSummary.innerHTML = '';

            const checkedBoxes = document.querySelectorAll('.extra-checkbox[type="checkbox"]:checked');
            console.log('updateExtras called, found checked boxes:', checkedBoxes.length);

            checkedBoxes.forEach(checkbox => {
                const extraId = checkbox.dataset.extraId;
                const extraPrice = parseFloat(checkbox.dataset.extraPrice);
                const extraName = checkbox.dataset.extraName;

                console.log('Processing extra:', {
                    id: extraId,
                    name: extraName,
                    price: extraPrice,
                    element: checkbox
                });

                selectedExtras.push({
                    id: extraId,
                    price: extraPrice,
                    name: extraName
                });

                // Add to summary
                const row = document.createElement('div');
                row.className = 'summary-row';
                row.innerHTML = `<span>${extraName}</span><span>${extraPrice.toFixed(2)} €</span>`;
                extrasSummary.appendChild(row);
            });

            console.log('selectedExtras after update:', selectedExtras);
            updateTotal();
        }

        function updateFranchise() {
            const selectedRadio = document.querySelector('input[name="franchise"]:checked');
            if (selectedRadio) {
                selectedFranchise = {
                    id: selectedRadio.dataset.damageId,
                    price: parseFloat(selectedRadio.dataset.damagePrice),
                    name: selectedRadio.dataset.damageName
                };
            }
            updateTotal();
        }

        function updateTotal() {
            let total = baseTotal + locationFee;

            console.log('updateTotal called:', {
                baseTotal: baseTotal,
                locationFee: locationFee,
                duration: duration,
                selectedExtras: selectedExtras,
                selectedFranchise: selectedFranchise
            });

            // Add selected extras
            selectedExtras.forEach(extra => {
                console.log('Adding extra to total:', extra.name, extra.price);
                total += extra.price;
            });

            // Add selected franchise (if different from included)
            if (selectedFranchise && selectedFranchise.price > 0) {
                const franchiseTotal = selectedFranchise.price * duration;
                console.log('Adding franchise to total:', selectedFranchise.name, franchiseTotal);
                total += franchiseTotal;
            }

            console.log('Final calculated total:', total);
            document.getElementById('final-total').textContent = total.toFixed(2) + ' €';
        }

        function continueReservation() {
            // Disable button to prevent double submission
            const button = document.querySelector('.btn-continue');
            button.disabled = true;
            button.textContent = '{{ __('Processing...') }}';

            // Format datetime for API (YYYY-mm-dd HH:mm format)
            const pickupDatetime = '{{ $search_params['pickup_date'] ?? '' }} {{ $search_params['pickup_time'] ?? '' }}';
            const dropoffDatetime =
                '{{ $search_params['dropoff_date'] ?? '' }} {{ $search_params['return_time'] ?? '' }}';

            // Prepare checkout data
            const checkoutData = {
                car_id: {{ $carData['id'] ?? 0 }},
                pickup_place: {{ $search_params['pickup_place'] ?? 0 }},
                dropoff_location: {{ $search_params['return_place'] ?? 0 }},
                pickup_datetime: pickupDatetime,
                dropoff_datetime: dropoffDatetime,
                franch: selectedFranchise ? selectedFranchise.id : {{ $carData['collision_damages'][0]['id'] ?? 0 }},
                extras: selectedExtras,
                franchise: selectedFranchise,
                _token: '{{ csrf_token() }}'
            };

            console.log('Checkout Data:', checkoutData);

            // Make AJAX request to checkout endpoint
            fetch('{{ route('car.checkout') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(checkoutData)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Checkout Response:', data);

                    if (data.success) {
                        // Clear saved selections on successful checkout
                        try {
                            sessionStorage.removeItem(storageKey);
                        } catch (e) {
                            console.error('Error clearing selections:', e);
                        }

                        // Redirect to confirmation page
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            alert('{{ __('Checkout successful!') }}');
                            button.disabled = false;
                            button.textContent = '{{ __('Continue Reservation') }}';
                        }
                    } else {
                        // Show error message
                        alert(data.message || '{{ __('Checkout failed. Please try again.') }}');
                        button.disabled = false;
                        button.textContent = '{{ __('Continue Reservation') }}';
                    }
                })
                .catch(error => {
                    console.error('Checkout Error:', error);
                    alert('{{ __('An error occurred. Please try again.') }}');
                    button.disabled = false;
                    button.textContent = '{{ __('Continue Reservation') }}';
                });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Try to load previously saved selections first
            loadSavedSelections();

            // If no selections were loaded, initialize with default franchise
            if (!selectedFranchise) {
                updateFranchise(); // Initialize with default franchise
            }
        });
    </script>
@endpush
