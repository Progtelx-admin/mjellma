@extends('layouts.app')

@push('css')
    <link href="{{ asset('dist/frontend/module/car/css/car.css?_ver=' . config('app.asset_version')) }}" rel="stylesheet">
    <style>
        /* === SEARCH PARAMS BAR === */
        .search-params-bar {
            background: linear-gradient(135deg, #0B0B45 0%, #1a1a6e 100%);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .search-param {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .search-param i {
            color: #F27625;
            font-size: 1.1rem;
            width: 20px;
        }

        .search-param-label {
            font-weight: 600;
            opacity: 0.9;
            min-width: 70px;
        }

        .search-param-value {
            font-weight: 400;
            opacity: 0.95;
        }

        /* === MOBILE FILTER TOGGLE === */
        .mobile-filter-toggle {
            display: none;
            background: linear-gradient(135deg, #0B0B45 0%, #1a1a6e 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            margin-bottom: 1rem;
            width: 100%;
            transition: all 0.3s ease;
        }

        .mobile-filter-toggle:hover {
            background: linear-gradient(135deg, #1a1a6e 0%, #0B0B45 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(11, 11, 69, 0.4);
        }

        .mobile-filter-toggle i {
            margin-right: 0.5rem;
        }

        @media (max-width: 991px) {
            .mobile-filter-toggle {
                display: block;
            }

            .filter-panel {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 9999;
                background: white;
                overflow-y: auto;
                animation: slideInLeft 0.3s ease;
            }

            .filter-panel.show {
                display: block;
            }

            .filter-close-btn {
                display: block !important;
                position: absolute;
                right: 1.5rem;
                top: 1.5rem;
                background: none;
                border: none;
                font-size: 1.5rem;
                color: #6b7280;
                cursor: pointer;
                padding: 0.5rem;
                line-height: 1;
            }

            .filter-close-btn:hover {
                color: #0B0B45;
            }

            @keyframes slideInLeft {
                from {
                    transform: translateX(-100%);
                }
                to {
                    transform: translateX(0);
                }
            }
        }

        @media (min-width: 992px) {
            .filter-close-btn {
                display: none !important;
            }
        }

        /* === VIEW SWITCHER === */
        .view-switcher {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .view-switcher .btn {
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
            background: white;
            color: #495057;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .view-switcher .btn.active {
            background: #0B0B45;
            color: white;
            border-color: #0B0B45;
        }

        .view-switcher .btn:hover:not(.active) {
            background: #f8f9fa;
        }

        @media (max-width: 767px) {
            .view-switcher {
                display: none;
            }
        }

        /* === RESULTS CONTAINER === */
        #search-results-container {
            min-height: 400px;
        }

        /* === FILTER PANEL === */
        .filter-panel {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .filter-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .filter-title {
            color: #1f2937;
            font-weight: 700;
            font-size: 1.125rem;
            margin: 0;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid #F27625;
            display: inline-block;
        }

        .filter-body {
            padding: 1.5rem;
        }

        .filter-item {
            margin-bottom: 1.5rem;
        }

        .filter-item:last-child {
            margin-bottom: 0;
        }

        .filter-label {
            display: block;
            color: #0B0B45;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .filter-label i {
            color: #0B0B45;
            font-size: 1rem;
            width: 18px;
            text-align: center;
        }

        .filter-select {
            width: 100%;
            padding: 0.625rem 1rem;
            font-size: 0.95rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background-color: #fff;
            color: #1f2937;
            transition: all 0.2s;
            cursor: pointer;
        }

        .filter-select:focus {
            border-color: #0B0B45;
            outline: none;
            box-shadow: 0 0 0 3px rgba(11, 11, 69, 0.1);
        }

        .filter-select:hover {
            border-color: #9ca3af;
        }

        .filter-actions {
            padding: 1.5rem;
            background: #f9fafb;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .btn-filter-apply {
            width: 100%;
            background: linear-gradient(135deg, #0B0B45 0%, #1a1a6e 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .btn-filter-apply:hover {
            background: linear-gradient(135deg, #1a1a6e 0%, #0B0B45 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(11, 11, 69, 0.4);
            color: white;
        }

        .btn-filter-clear {
            width: 100%;
            background: white;
            color: #6b7280;
            border: 1px solid #d1d5db;
            padding: 0.625rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .btn-filter-clear:hover {
            background: #f3f4f6;
            color: #374151;
            border-color: #9ca3af;
            text-decoration: none;
        }

        .filter-item:last-child {
            margin-bottom: 0;
        }

        .filter-select {
            height: 44px;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.95rem;
            transition: all 0.2s;
            background-color: white;
        }

        .filter-select:focus {
            border-color: #0B0B45;
            box-shadow: 0 0 0 0.2rem rgba(11, 11, 69, 0.25);
            outline: none;
        }

        .filter-item label {
            color: #0B0B45;
            font-size: 0.9rem;
        }

        .bravo-filter-search .btn {
            font-size: 0.9rem;
            padding: 0.65rem 1.5rem;
        }

        /* === TOPBAR === */
        .topbar-search {
            background: white;
            padding: 1rem 1.25rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .result-count {
            color: #0B0B45;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .pagination-info {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            background: #e7f3ff;
            border-radius: 0.25rem;
            font-size: 0.85rem;
            color: #0B0B45;
            margin-top: 0.5rem;
        }

        .pagination-info i {
            color: #0B0B45;
        }

        /* === PAGINATION === */
        .bravo-pagination {
            margin-top: 2rem;
        }

        .pagination {
            gap: 0.25rem;
        }

        .pagination .page-link {
            color: #0B0B45;
            border: 1px solid #dee2e6;
            padding: 0.5rem 0.85rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
            font-weight: 500;
        }

        .pagination .page-link:hover {
            background: #f8f9fa;
            border-color: #0B0B45;
            color: #0B0B45;
        }

        .pagination .page-item.active .page-link {
            background: #0B0B45;
            border-color: #0B0B45;
            color: white;
            z-index: 1;
        }

        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background: #fff;
            border-color: #dee2e6;
        }

        /* === LOAD MORE BUTTON === */
        .load-more-container {
            padding: 2rem 0;
        }

        .btn-load-more {
            background: linear-gradient(135deg, #0B0B45 0%, #1a1a6e 100%);
            color: white;
            border: none;
            padding: 1rem 3rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(11, 11, 69, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-load-more:hover {
            background: linear-gradient(135deg, #1a1a6e 0%, #0B0B45 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(11, 11, 69, 0.4);
            color: white;
        }

        .btn-load-more:active {
            transform: translateY(0);
        }

        .btn-load-more:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .load-more-info {
            font-size: 0.9rem;
        }

        .all-loaded-message {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 0.5rem;
            display: inline-block;
            font-size: 1rem;
        }

        .all-loaded-message i {
            font-size: 1.2rem;
        }

        /* Loading animation for new items */
        .car-item-wrapper.loading-new {
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* === RESPONSIVE === */
        @media (max-width: 767px) {
            .topbar-search {
                flex-direction: column !important;
                gap: 1rem;
            }

            .result-count {
                font-size: 1.25rem;
            }

            .view-switcher {
                width: 100%;
                justify-content: center;
            }

            .pagination {
                flex-wrap: wrap;
            }

            .pagination .page-link {
                padding: 0.4rem 0.65rem;
                font-size: 0.9rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="bravo_search_car">
        {{-- Search Parameters Display --}}
        <div class="search-params-bar">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-3">{{ __('Your Search') }}</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="search-param">
                                    <i class="fa fa-map-marker-alt"></i>
                                    <span class="search-param-label">{{ __('Pickup:') }}</span>
                                    <span class="search-param-value">
                                        {{ $pickup_location['address'] ?? 'N/A' }}
                                        @if (isset($pickup_location['location_extra_price']) && floatval($pickup_location['location_extra_price']) > 0)
                                            <span
                                                class="badge bg-warning text-dark ms-2">+€{{ number_format($pickup_location['location_extra_price'], 2) }}</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="search-param">
                                    <i class="fa fa-calendar"></i>
                                    <span class="search-param-label">{{ __('From:') }}</span>
                                    <span class="search-param-value">{{ $search_params['pickup_date'] ?? '' }}
                                        {{ $search_params['pickup_time'] ?? '' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="search-param">
                                    <i class="fa fa-map-marker-alt"></i>
                                    <span class="search-param-label">{{ __('Dropoff:') }}</span>
                                    <span class="search-param-value">
                                        {{ $dropoff_location['address'] ?? 'N/A' }}
                                        @if (isset($dropoff_location['location_extra_price']) && floatval($dropoff_location['location_extra_price']) > 0)
                                            <span
                                                class="badge bg-warning text-dark ms-2">+€{{ number_format($dropoff_location['location_extra_price'], 2) }}</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="search-param">
                                    <i class="fa fa-calendar"></i>
                                    <span class="search-param-label">{{ __('To:') }}</span>
                                    <span class="search-param-value">{{ $search_params['dropoff_date'] ?? '' }}
                                        {{ $search_params['return_time'] ?? '' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Extra Price Notice --}}
                        @if (isset($location_extra_price) && $location_extra_price > 0)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-info mb-0"
                                        style="background: rgba(255, 193, 7, 0.2); border: 2px solid #ffc107; color: #fff; border-radius: 0.5rem;">
                                        <div class="d-flex align-items-center">
                                            <i class="fa fa-exclamation-triangle me-3"
                                                style="font-size: 1.5rem; color: #ffc107;"></i>
                                            <div>
                                                <strong style="font-size: 1.1rem;">{{ __('Location Fee Notice') }}</strong>
                                                <p class="mb-0 mt-1" style="font-size: 0.95rem; opacity: 0.95;">
                                                    {{ __('An additional location fee of €:amount will be applied to your total cost.', ['amount' => number_format($location_extra_price, 2)]) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="row">
                {{-- Filters Sidebar --}}
                <div class="col-lg-3 col-md-12 mb-4">
                    {{-- Mobile Filter Toggle Button --}}
                    <button type="button" class="mobile-filter-toggle" id="mobile-filter-toggle">
                        <i class="fa fa-filter"></i>{{ __('Show Filters') }}
                    </button>

                    <div class="filter-panel" id="filter-panel">
                        {{-- Close Button for Mobile --}}
                        <button type="button" class="filter-close-btn" id="filter-close-btn">
                            <i class="fa fa-times"></i>
                        </button>

                        <div class="filter-header">
                            <h5 class="filter-title">{{ __('Filter Results') }}</h5>
                        </div>

                        <form id="filter-form" method="GET" action="{{ route('car.do_search') }}">
                            {{-- Preserve search parameters --}}
                            <input type="hidden" name="pickup_place" value="{{ request('pickup_place') }}">
                            <input type="hidden" name="return_place" value="{{ request('return_place') }}">
                            <input type="hidden" name="pickup_date" value="{{ request('pickup_date') }}">
                            <input type="hidden" name="pickup_time" value="{{ request('pickup_time') }}">
                            <input type="hidden" name="dropoff_date" value="{{ request('dropoff_date') }}">
                            <input type="hidden" name="return_time" value="{{ request('return_time') }}">

                            <div class="filter-body">
                                {{-- Vehicle Mark (Manufacturer) --}}
                                <div class="filter-item">
                                    <label for="car_manufacturer" class="filter-label">
                                        <i class="fa fa-car me-2"></i>{{ __('Vehicle Mark') }}
                                    </label>
                                    <select name="car_manufacturer" id="car_manufacturer" class="filter-select">
                                        <option value="">{{ __('All Manufacturers') }}</option>
                                        @if (isset($manufacturers) && is_array($manufacturers))
                                            @foreach ($manufacturers as $manufacturer)
                                                <option value="{{ $manufacturer['id'] ?? '' }}"
                                                    {{ request('car_manufacturer') == ($manufacturer['id'] ?? '') ? 'selected' : '' }}>
                                                    {{ $manufacturer['name'] ?? 'N/A' }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                {{-- Vehicle Type (Category) --}}
                                <div class="filter-item">
                                    <label for="car_type" class="filter-label">
                                        <i class="fa fa-tags me-2"></i>{{ __('Vehicle Type') }}
                                    </label>
                                    <select name="car_type" id="car_type" class="filter-select">
                                        <option value="">{{ __('All Types') }}</option>
                                        @if (isset($categories) && is_array($categories))
                                            @foreach ($categories as $category)
                                                <option value="{{ $category['id'] ?? '' }}"
                                                    {{ request('car_type') == ($category['id'] ?? '') ? 'selected' : '' }}>
                                                    {{ $category['name'] ?? 'N/A' }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                {{-- Transmission --}}
                                <div class="filter-item">
                                    <label for="car_transmission" class="filter-label">
                                        <i class="fa fa-cog me-2"></i>{{ __('Transmission') }}
                                    </label>
                                    <select name="car_transmission" id="car_transmission" class="filter-select">
                                        <option value="">{{ __('All Transmissions') }}</option>
                                        @if (isset($transmissions) && is_array($transmissions))
                                            @foreach ($transmissions as $transmission)
                                                <option value="{{ $transmission['id'] ?? '' }}"
                                                    {{ request('car_transmission') == ($transmission['id'] ?? '') ? 'selected' : '' }}>
                                                    {{ $transmission['name'] ?? 'N/A' }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                {{-- Sort By (Category) --}}
                                <div class="filter-item">
                                    <label for="category" class="filter-label">
                                        <i class="fa fa-sort me-2"></i>{{ __('Sort By') }}
                                    </label>
                                    <select name="category" id="category" class="filter-select">
                                        <option value="">{{ __('Default') }}</option>
                                        <option value="high" {{ request('category') == 'high' ? 'selected' : '' }}>
                                            {{ __('Highest price first') }}
                                        </option>
                                        <option value="low" {{ request('category') == 'low' ? 'selected' : '' }}>
                                            {{ __('Lowest price first') }}
                                        </option>
                                        <option value="popular" {{ request('category') == 'popular' ? 'selected' : '' }}>
                                            {{ __('Most popular') }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            {{-- Filter Buttons --}}
                            <div class="filter-actions">
                                <button type="submit" class="btn-filter-apply">
                                    <i class="fa fa-filter me-2"></i>{{ __('Apply Filters') }}
                                </button>
                                <a href="{{ route('car.do_search', [
                                    'pickup_place' => request('pickup_place'),
                                    'return_place' => request('return_place'),
                                    'pickup_date' => request('pickup_date'),
                                    'pickup_time' => request('pickup_time'),
                                    'dropoff_date' => request('dropoff_date'),
                                    'return_time' => request('return_time'),
                                ]) }}" class="btn-filter-clear">
                                    <i class="fa fa-times me-2"></i>{{ __('Clear Filters') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Results Area --}}
                <div class="col-lg-9 col-md-12">
                    <div class="bravo-list-item">
                        {{-- Top Bar with Count and View Switcher --}}
                        <div class="topbar-search d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="text result-count mb-0">
                                    @if (isset($pagination) && $pagination['total'] > 0)
                                        {{ __(':count cars available', ['count' => $pagination['total']]) }}
                                    @elseif (isset($reservations['cars']) && is_array($reservations['cars']))
                                        @php $total = count($reservations['cars']); @endphp
                                        @if ($total > 1)
                                            {{ __(':count cars available', ['count' => $total]) }}
                                        @else
                                            {{ __(':count car available', ['count' => $total]) }}
                                        @endif
                                    @else
                                        {{ __('Available Cars') }}
                                    @endif
                                </h2>
                                @if (isset($pagination) && $pagination['total'] > 0)
                                    <small class="text-muted">
                                        {{ __('Showing :from - :to of :total results', [
                                            'from' => $pagination['from'],
                                            'to' => min($pagination['to'], $pagination['total']),
                                            'total' => $pagination['total'],
                                        ]) }}
                                    </small>
                                @endif
                            </div>

                            <div class="view-switcher">
                                <button class="btn btn-sm view-btn active" data-view="grid">
                                    <i class="fa fa-th"></i> {{ __('Grid') }}
                                </button>
                                <button class="btn btn-sm view-btn" data-view="list">
                                    <i class="fa fa-list"></i> {{ __('List') }}
                                </button>
                            </div>
                        </div>

                        {{-- Results Container --}}
                        <div id="search-results-container" class="view-grid">
                            @if (isset($reservations['cars']) && count($reservations['cars']) > 0)
                                <div class="row" id="results-row">
                                    @foreach ($reservations['cars'] as $car)
                                        @include('Car::frontend.layouts.search.api-car-item', [
                                            'car' => $car,
                                        ])
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle me-2"></i>
                                    {{ __('No cars available for the selected dates and locations. Please try different search criteria.') }}
                                </div>
                            @endif
                        </div>

                        {{-- Load More Button --}}
                        @if (isset($pagination) && $pagination['current_page'] < $pagination['total_pages'])
                            <div class="load-more-container text-center mt-5">
                                <button type="button" id="load-more-btn" class="btn btn-load-more"
                                    data-current-page="{{ $pagination['current_page'] }}"
                                    data-total-pages="{{ $pagination['total_pages'] }}"
                                    data-pickup-place="{{ request('pickup_place') }}"
                                    data-return-place="{{ request('return_place') }}"
                                    data-pickup-date="{{ request('pickup_date') }}"
                                    data-pickup-time="{{ request('pickup_time') }}"
                                    data-dropoff-date="{{ request('dropoff_date') }}"
                                    data-return-time="{{ request('return_time') }}"
                                    data-car-manufacturer="{{ request('car_manufacturer', '') }}"
                                    data-car-type="{{ request('car_type', '') }}"
                                    data-car-transmission="{{ request('car_transmission', '') }}"
                                    data-category="{{ request('category', '') }}">
                                    <span class="load-more-text">
                                        <i class="fa fa-plus-circle me-2"></i>
                                        {{ __('Load More Cars') }}
                                    </span>
                                    <span class="load-more-spinner d-none">
                                        <i class="fa fa-spinner fa-spin me-2"></i>
                                        {{ __('Loading...') }}
                                    </span>
                                </button>
                                <div class="load-more-info mt-3">
                                    <small class="text-muted">
                                        {{ __('Loaded :current of :total cars', [
                                            'current' => $pagination['to'],
                                            'total' => $pagination['total'],
                                        ]) }}
                                    </small>
                                </div>
                            </div>
                        @elseif(isset($pagination) && $pagination['total'] > 0)
                            <div class="text-center mt-5">
                                <div class="all-loaded-message">
                                    <i class="fa fa-check-circle text-success me-2"></i>
                                    <span
                                        class="text-muted">{{ __('All :total cars loaded', ['total' => $pagination['total']]) }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        // Mobile Filter Toggle
        const filterToggle = document.getElementById('mobile-filter-toggle');
        const filterPanel = document.getElementById('filter-panel');
        const filterCloseBtn = document.getElementById('filter-close-btn');

        if (filterToggle) {
            filterToggle.addEventListener('click', function() {
                filterPanel.classList.add('show');
                document.body.style.overflow = 'hidden'; // Prevent scrolling when filter is open
            });
        }

        if (filterCloseBtn) {
            filterCloseBtn.addEventListener('click', function() {
                filterPanel.classList.remove('show');
                document.body.style.overflow = ''; // Restore scrolling
            });
        }

        // Close filter when clicking outside on mobile
        if (filterPanel) {
            filterPanel.addEventListener('click', function(e) {
                if (e.target === filterPanel) {
                    filterPanel.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
        }

        // View Switcher
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const view = this.dataset.view;
                const container = document.getElementById('search-results-container');
                const resultsRow = document.getElementById('results-row');

                // Update button states
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Update view
                if (view === 'grid') {
                    container.classList.remove('view-list');
                    container.classList.add('view-grid');
                    resultsRow.className = 'row';
                } else {
                    container.classList.remove('view-grid');
                    container.classList.add('view-list');
                    resultsRow.className = 'row flex-column';
                }
            });
        });

        // Initialize Bootstrap carousels
        $(document).ready(function() {
            $('.carousel').carousel({
                interval: false, // Don't auto-play
                ride: false,
                pause: 'hover'
            });

            // Load More functionality
            let currentPage = {{ $pagination['current_page'] ?? 1 }};
            let totalPages = {{ $pagination['total_pages'] ?? 1 }};
            let totalLoaded = {{ $pagination['to'] ?? 12 }}; // Track how many cars are currently loaded
            let isLoading = false;

            $('#load-more-btn').on('click', function() {
                if (isLoading) return;

                const btn = $(this);
                const nextPage = currentPage + 1;

                if (nextPage > totalPages) {
                    return;
                }

                isLoading = true;
                btn.prop('disabled', true);
                btn.find('.load-more-text').addClass('d-none');
                btn.find('.load-more-spinner').removeClass('d-none');

                // Build search params from data attributes
                const searchParams = {
                    pickup_place: btn.data('pickup-place'),
                    return_place: btn.data('return-place'),
                    pickup_date: btn.data('pickup-date'),
                    pickup_time: btn.data('pickup-time'),
                    dropoff_date: btn.data('dropoff-date'),
                    return_time: btn.data('return-time'),
                    page: nextPage,
                    total_loaded: totalLoaded // Tell API how many cars we already have
                };

                // Add filters if they exist
                if (btn.data('car-manufacturer')) {
                    searchParams.car_manufacturer = btn.data('car-manufacturer');
                }
                if (btn.data('car-type')) {
                    searchParams.car_type = btn.data('car-type');
                }
                if (btn.data('car-transmission')) {
                    searchParams.car_transmission = btn.data('car-transmission');
                }
                if (btn.data('category')) {
                    searchParams.category = btn.data('category');
                }

                // Make AJAX request
                $.ajax({
                    url: '{{ route('car.api.load_more') }}',
                    method: 'GET',
                    data: searchParams,
                    success: function(response) {
                        if (response.success && response.html) {
                            // Append new cars to results
                            const newItems = $(response.html);
                            newItems.addClass('loading-new');
                            $('#results-row').append(newItems);

                            // Initialize carousels for new items
                            newItems.find('.carousel').carousel({
                                interval: false,
                                ride: false,
                                pause: 'hover'
                            });

                            // Update current page and total loaded
                            currentPage = nextPage;
                            totalLoaded = response.total_loaded;
                            btn.data('current-page', currentPage);

                            // Update info text
                            $('.load-more-info small').text(response.loaded_text);

                            // Check if we've loaded all cars
                            if (!response.has_more || totalLoaded >= response.total_available) {
                                btn.closest('.load-more-container').html(
                                    '<div class="all-loaded-message">' +
                                    '<i class="fa fa-check-circle text-success me-2"></i>' +
                                    '<span class="text-muted">' + response.all_loaded_text +
                                    '</span>' +
                                    '</div>'
                                );
                            } else {
                                btn.prop('disabled', false);
                                btn.find('.load-more-text').removeClass('d-none');
                                btn.find('.load-more-spinner').addClass('d-none');
                            }
                        } else {
                            alert('{{ __('Failed to load more cars. Please try again.') }}');
                            btn.prop('disabled', false);
                            btn.find('.load-more-text').removeClass('d-none');
                            btn.find('.load-more-spinner').addClass('d-none');
                        }
                        isLoading = false;
                    },
                    error: function(xhr, status, error) {
                        console.error('Load more error:', error);
                        alert('{{ __('Failed to load more cars. Please try again.') }}');
                        btn.prop('disabled', false);
                        btn.find('.load-more-text').removeClass('d-none');
                        btn.find('.load-more-spinner').addClass('d-none');
                        isLoading = false;
                    }
                });
            });
        });
    </script>
@endpush
