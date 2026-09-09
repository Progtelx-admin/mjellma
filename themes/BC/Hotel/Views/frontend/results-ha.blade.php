@extends('layouts.app')

@section('content')
    <div class="container">
        {{-- Page heading --}}
        <h3 class="text-center mt-5 fw-bold">
            @if (request('location'))
                Hotels near {{ request('location') }}
            @else
                Our Hotels
            @endif
        </h3>
        <hr class="w-25 mx-auto mb-4">

        <div class="row mt-4">
            {{-- ================================================================
                 MOBILE FILTER BUTTON
            ================================================================= --}}
            <div class="col-12 d-md-none mb-3">
                <button class="btn mobile-filter-btn w-100" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mobileFilters" aria-expanded="false" aria-controls="mobileFilters">
                    <i class="fa fa-filter me-2"></i>Filters
                    <i class="fa fa-chevron-down ms-2" id="filterToggleIcon"></i>
                </button>
            </div>

            {{-- ================================================================
                 FILTERS
            ================================================================= --}}
            <div class="col-md-3 mb-5 filter-section sticky-top d-none d-md-block" style="top:1rem;">
                <h5 class="fw-bold filter-heading">Filters</h5>
                <hr class="filter-divider">

                <form method="GET" action="{{ route('hotel.search') }}">
                    {{-- Preserve home search query --}}
                    <input type="hidden" name="location" value="{{ request('location') }}">
                    <input type="hidden" name="checkin" value="{{ request('checkin') }}">
                    <input type="hidden" name="checkout" value="{{ request('checkout') }}">
                    <input type="hidden" name="adults" value="{{ request('adults') }}">
                    <input type="hidden" name="rooms" value="{{ request('rooms') }}">
                    <input type="hidden" name="latitude" value="{{ request('latitude') }}">
                    <input type="hidden" name="longitude" value="{{ request('longitude') }}">
                    <input type="hidden" name="currency" value="{{ request('currency', 'EUR') }}">
                    <input type="hidden" name="children_count" value="{{ request('children_count', 0) }}">
                    @foreach (request('children', []) as $age)
                        <input type="hidden" name="children[]" value="{{ $age }}">
                    @endforeach

                    @include('Hotel::frontend.partials.filter-search-context', ['idPrefix' => 'desktop'])
                    <hr class="filter-divider">

                    {{-- Price --}}
                    <div class="mb-4">
                        <h6 class="fw-bold">Price Range</h6>
                        <label for="min_price" class="form-label">Min Price (€{{ $minPrice }})</label>
                        <input type="number" class="form-control mb-2" id="min_price" name="min_price"
                            value="{{ request('min_price') }}" placeholder="Min">
                        <label for="max_price" class="form-label">Max Price (€{{ $maxPrice }})</label>
                        <input type="number" class="form-control" id="max_price" name="max_price"
                            value="{{ request('max_price') }}" placeholder="Max">
                    </div>
                    <hr>

                    {{-- Stars --}}
                    <div class="mb-4">
                        <h6 class="fw-bold">Hotel Star</h6>
                        @for ($i = 5; $i >= 1; $i--)
                            <label class="d-flex align-items-center mb-2" for="star_rating_{{ $i }}">
                                <input type="checkbox" id="star_rating_{{ $i }}" name="star_rating[]"
                                    value="{{ $i }}" class="me-2"
                                    @if (is_array(request('star_rating')) && in_array($i, request('star_rating'))) checked @endif>
                                @for ($j = 1; $j <= $i; $j++)
                                    <i class="fa fa-star text-warning me-1"></i>
                                @endfor
                            </label>
                        @endfor
                    </div>
                    <hr>

                    {{-- Breakfast --}}
                    <div class="mb-4">
                        <h6 class="fw-bold">Hotel Service</h6>
                        <label class="custom-checkbox">
                            <input type="hidden" name="breakfast_included" value="0">
                            <input type="checkbox" id="breakfast_included" name="breakfast_included" value="1"
                                @if (!request()->has('breakfast_included') || request('breakfast_included')) checked @endif>
                            <span class="checkmark"></span> Breakfast Included
                        </label>
                    </div>
                    <hr>

                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </form>

                <button type="button" class="btn btn-success w-100 mt-3" data-bs-toggle="modal" data-bs-target="#mapModal">
                    Show on the Map
                </button>
            </div>

            {{-- ================================================================
                 MOBILE FILTERS COLLAPSIBLE
            ================================================================= --}}
            <div class="col-12 d-md-none">
                <div class="collapse" id="mobileFilters">
                    <div class="card card-body mb-3 mobile-filters-card">
                        <h5 class="fw-bold mb-3 filter-heading">Filters</h5>
                        <form method="GET" action="{{ route('hotel.search') }}">
                            {{-- Preserve home search query --}}
                            <input type="hidden" name="location" value="{{ request('location') }}">
                            <input type="hidden" name="checkin" value="{{ request('checkin') }}">
                            <input type="hidden" name="checkout" value="{{ request('checkout') }}">
                            <input type="hidden" name="adults" value="{{ request('adults') }}">
                            <input type="hidden" name="rooms" value="{{ request('rooms') }}">
                            <input type="hidden" name="latitude" value="{{ request('latitude') }}">
                            <input type="hidden" name="longitude" value="{{ request('longitude') }}">
                            <input type="hidden" name="currency" value="{{ request('currency', 'EUR') }}">
                            <input type="hidden" name="children_count" value="{{ request('children_count', 0) }}">
                            @foreach (request('children', []) as $age)
                                <input type="hidden" name="children[]" value="{{ $age }}">
                            @endforeach

                            @include('Hotel::frontend.partials.filter-search-context', ['idPrefix' => 'mobile'])
                            <hr class="filter-divider">

                            {{-- Price --}}
                            <div class="mb-4">
                                <h6 class="fw-bold">Price Range</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label for="mobile_min_price" class="form-label">Min Price
                                            (€{{ $minPrice }})</label>
                                        <input type="number" class="form-control" id="mobile_min_price"
                                            name="min_price" value="{{ request('min_price') }}" placeholder="Min">
                                    </div>
                                    <div class="col-6">
                                        <label for="mobile_max_price" class="form-label">Max Price
                                            (€{{ $maxPrice }})</label>
                                        <input type="number" class="form-control" id="mobile_max_price"
                                            name="max_price" value="{{ request('max_price') }}" placeholder="Max">
                                    </div>
                                </div>
                            </div>
                            <hr>

                            {{-- Stars --}}
                            <div class="mb-4">
                                <h6 class="fw-bold">Hotel Star</h6>
                                @for ($i = 5; $i >= 1; $i--)
                                    <label class="d-flex align-items-center mb-2"
                                        for="mobile_star_rating_{{ $i }}">
                                        <input type="checkbox" id="mobile_star_rating_{{ $i }}"
                                            name="star_rating[]" value="{{ $i }}" class="me-2"
                                            @if (is_array(request('star_rating')) && in_array($i, request('star_rating'))) checked @endif>
                                        @for ($j = 1; $j <= $i; $j++)
                                            <i class="fa fa-star text-warning me-1"></i>
                                        @endfor
                                    </label>
                                @endfor
                            </div>
                            <hr>

                            {{-- Breakfast --}}
                            <div class="mb-4">
                                <h6 class="fw-bold">Hotel Service</h6>
                                <label class="custom-checkbox">
                                    <input type="hidden" name="breakfast_included" value="0">
                                    <input type="checkbox" id="mobile_breakfast_included" name="breakfast_included"
                                        value="1" @if (!request()->has('breakfast_included') || request('breakfast_included')) checked @endif>
                                    <span class="checkmark"></span> Breakfast Included
                                </label>
                            </div>
                            <hr>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                    data-bs-target="#mapModal">
                                    Show on the Map
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ================================================================
                 RESULTS
            ================================================================= --}}
            <div class="col-md-9">
                {{-- Header --}}
                <div class="results-toolbar mb-3">
                    <div class="results-toolbar__count">
                        <span class="text-muted" id="results-counter">
                            Among <span class="fw-semibold"
                                id="total-count">{{ $totalHotels ?? $hotels->count() }}</span> accommodations
                        </span>
                    </div>

                    <div class="results-toolbar__controls">
                        <div class="view-toggle d-none d-md-flex" role="group" aria-label="Result view">
                            <button id="extended-view-btn" type="button" class="view-tab" aria-label="Extended list view"
                                title="Extended">
                                <i class="fa fa-list-ul" aria-hidden="true"></i>
                            </button>
                            <button id="compact-view-btn" type="button" class="view-tab active" aria-label="Compact grid view"
                                title="Compact">
                                <i class="fa fa-th" aria-hidden="true"></i>
                            </button>
                        </div>

                        <div class="sort-by-wrap">
                            <button type="button" id="sort-by-btn" class="sort-by-btn" aria-expanded="false"
                                aria-haspopup="listbox">
                                <span>Sort by: <strong id="sort-by-label">Default</strong></span>
                                <i class="fa fa-chevron-down" aria-hidden="true"></i>
                            </button>
                            <ul id="sort-by-menu" class="sort-by-menu d-none" role="listbox">
                                <li role="option">
                                    <button type="button" class="sort-by-option active" data-sort="default">
                                        Default
                                    </button>
                                </li>
                                <li role="option">
                                    <button type="button" class="sort-by-option" data-sort="price_asc">
                                        Price (Low to High)
                                    </button>
                                </li>
                                <li role="option">
                                    <button type="button" class="sort-by-option" data-sort="price_desc">
                                        Price (High to Low)
                                    </button>
                                </li>
                                <li role="option">
                                    <button type="button" class="sort-by-option" data-sort="rating">
                                        Guest Rating (High to Low)
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div id="hotel-list" class="compact-view d-md-block d-none">
                    @foreach ($hotels as $hotel)
                        @include('Hotel::frontend.partials.hotel-card-chunk', [
                            'hotel' => $hotel,
                            'query' => array_merge(
                                ['id' => $hotel->hotel_id],
                                request()->only([
                                    'hotel_name',
                                    'location',
                                    'checkin',
                                    'checkout',
                                    'adults',
                                    'rooms',
                                    'latitude',
                                    'longitude',
                                    'currency',
                                ]),
                                ['children_count' => request('children_count', 0)],
                                ['children' => request('children', [])]),
                        ])
                    @endforeach
                </div>

                {{-- Mobile Compact View --}}
                <div id="hotel-list-mobile" class="compact-view d-md-none">
                    @foreach ($hotels as $hotel)
                        @include('Hotel::frontend.partials.hotel-card-chunk', [
                            'hotel' => $hotel,
                            'query' => array_merge(
                                ['id' => $hotel->hotel_id],
                                request()->only([
                                    'hotel_name',
                                    'location',
                                    'checkin',
                                    'checkout',
                                    'adults',
                                    'rooms',
                                    'latitude',
                                    'longitude',
                                    'currency',
                                ]),
                                ['children_count' => request('children_count', 0)],
                                ['children' => request('children', [])]),
                        ])
                    @endforeach
                </div>

                {{-- Loading more indicator --}}
                <div id="loading-more" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading more hotels...</p>
                </div>

                {{-- No results message --}}
                <div id="no-results" class="text-center text-muted fs-5 py-5 d-none">
                    No hotels found matching your criteria.
                </div>
            </div>
        </div>
    </div>

    {{-- Map modal --}}
    <div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mapModalLabel">Hotel Map</h5>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-close"></i>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <div id="hotelMap" style="height: 600px; width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Keep a global reference to all hotels currently loaded on the page.
            // Start with the initial collection passed from the backend.  As
            // additional chunks are loaded via AJAX this array will be
            // augmented so the map can reflect the hotels currently visible.
            window.mapHotels = @json($hotels);
            const list = document.getElementById('hotel-list');
            const extendedBtn = document.getElementById('extended-view-btn');
            const compactBtn = document.getElementById('compact-view-btn');
            let currentSort = 'default';

            function setView(mode) {
                if (!list) return;
                list.classList.toggle('extended-view', mode === 'extended');
                list.classList.toggle('compact-view', mode === 'compact');
                if (extendedBtn) extendedBtn.classList.toggle('active', mode === 'extended');
                if (compactBtn) compactBtn.classList.toggle('active', mode === 'compact');
            }

            if (extendedBtn && compactBtn) {
                extendedBtn.addEventListener('click', () => setView('extended'));
                compactBtn.addEventListener('click', () => setView('compact'));
            }

            function parseCardPrice(card) {
                const attr = card.getAttribute('data-hotel-price');
                if (attr !== null && attr !== '' && !Number.isNaN(parseFloat(attr))) {
                    return parseFloat(attr);
                }
                const badge = card.querySelector('.grid-price-badge');
                const priceEl = card.querySelector('.hotel-listcard__price');
                const text = ((badge && badge.textContent) || (priceEl && priceEl.textContent) || '').replace(/,/g, '');
                const match = text.match(/(\d+(\.\d+)?)/);
                return match ? parseFloat(match[1]) : null;
            }

            function isCardAvailable(card) {
                const price = parseCardPrice(card);
                const priceText = ((card.querySelector('.hotel-listcard__price') &&
                    card.querySelector('.hotel-listcard__price').textContent) || '').trim();
                if (priceText.includes('No rooms available')) return false;
                return price !== null;
            }

            function compareCards(a, b, sortKey) {
                if (sortKey === 'price_asc' || sortKey === 'price_desc') {
                    const aPrice = parseCardPrice(a);
                    const bPrice = parseCardPrice(b);
                    if (aPrice === null && bPrice === null) return 0;
                    if (aPrice === null) return 1;
                    if (bPrice === null) return -1;
                    return sortKey === 'price_asc' ? aPrice - bPrice : bPrice - aPrice;
                }

                if (sortKey === 'rating') {
                    const aRating = parseFloat(a.getAttribute('data-hotel-rating') || '0');
                    const bRating = parseFloat(b.getAttribute('data-hotel-rating') || '0');
                    return bRating - aRating;
                }

                // default: same as before — available hotels first, then original load order
                const aAvail = isCardAvailable(a);
                const bAvail = isCardAvailable(b);
                if (aAvail && !bAvail) return -1;
                if (!aAvail && bAvail) return 1;
                const aIndex = parseInt(a.getAttribute('data-load-index') || '0', 10);
                const bIndex = parseInt(b.getAttribute('data-load-index') || '0', 10);
                return aIndex - bIndex;
            }

            function ensureLoadIndexes(container) {
                if (!container) return;
                Array.from(container.children).forEach((card, index) => {
                    if (!card.hasAttribute('data-load-index')) {
                        card.setAttribute('data-load-index', String(index));
                    }
                });
            }

            function sortHotelLists(sortKey = currentSort) {
                currentSort = sortKey;
                const containers = [
                    document.getElementById('hotel-list'),
                    document.getElementById('hotel-list-mobile'),
                ];

                containers.forEach((container) => {
                    if (!container) return;
                    ensureLoadIndexes(container);
                    const cards = Array.from(container.children);
                    cards.sort((a, b) => compareCards(a, b, sortKey));
                    cards.forEach((card) => container.appendChild(card));
                });
            }

            function sortHotelsByAvailability() {
                sortHotelLists(currentSort);
            }

            const sortBtn = document.getElementById('sort-by-btn');
            const sortMenu = document.getElementById('sort-by-menu');
            const sortLabel = document.getElementById('sort-by-label');

            if (sortBtn && sortMenu) {
                sortBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const open = !sortMenu.classList.contains('d-none');
                    sortMenu.classList.toggle('d-none', open);
                    sortBtn.setAttribute('aria-expanded', open ? 'false' : 'true');
                });

                sortMenu.querySelectorAll('.sort-by-option').forEach((option) => {
                    option.addEventListener('click', function() {
                        const sortKey = this.getAttribute('data-sort');
                        const label = this.textContent.trim();
                        sortMenu.querySelectorAll('.sort-by-option').forEach((btn) => btn.classList.remove('active'));
                        this.classList.add('active');
                        if (sortLabel) sortLabel.textContent = label;
                        sortMenu.classList.add('d-none');
                        sortBtn.setAttribute('aria-expanded', 'false');
                        sortHotelLists(sortKey);
                    });
                });

                document.addEventListener('click', function(e) {
                    if (!sortMenu.contains(e.target) && e.target !== sortBtn && !sortBtn.contains(e.target)) {
                        sortMenu.classList.add('d-none');
                        sortBtn.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            ensureLoadIndexes(document.getElementById('hotel-list'));
            ensureLoadIndexes(document.getElementById('hotel-list-mobile'));

            // Progressive loading
            @if (isset($searchHash))
                const searchHash = '{{ $searchHash }}';
                let currentChunk = 1; // Start from chunk 1 (chunk 0 already loaded)
                let activeRequests = 0;
                let maxParallelRequests = 3; // Load 3 chunks in parallel
                let hasMoreHotels = {{ isset($loadMore) && $loadMore ? 'true' : 'false' }};
                let totalHotelsCount = {{ $totalHotels ?? 0 }};
                let loadedChunks = new Set([0]); // Chunk 0 already loaded
                let priceUpdateInterval;

                function loadChunk(chunkNumber) {
                    if (loadedChunks.has(chunkNumber)) return;
                    loadedChunks.add(chunkNumber);

                    activeRequests++;
                    if (activeRequests === 1) {
                        document.getElementById('loading-more').classList.remove('d-none');
                    }

                    // Get current search parameters
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.append('chunk', chunkNumber);

                    fetch('{{ route('hotel.search') }}?' + urlParams.toString(), {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            activeRequests--;

                            if (data.error) {
                                console.error('Error loading hotels:', data.error);
                                loadedChunks.delete(chunkNumber); // Allow retry
                                return;
                            }

                            // Hide skeleton on first load
                            if (chunkNumber === 0) {
                                const skeleton = document.getElementById('loading-skeleton');
                                if (skeleton) skeleton.remove();
                            }

                            // Append hotels
                            if (data.html) {
                                const hotelList = document.getElementById('hotel-list');
                                const hotelListMobile = document.getElementById('hotel-list-mobile');

                                // Append to desktop list
                                if (hotelList) {
                                    hotelList.insertAdjacentHTML('beforeend', data.html);
                                }

                                // Append to mobile list
                                if (hotelListMobile) {
                                    hotelListMobile.insertAdjacentHTML('beforeend', data.html);
                                }

                                // Sort hotels: available ones first
                                sortHotelsByAvailability();
                            }

                            // Augment mapHotels with any hotel objects returned in the response
                            // This allows the map to stay in sync with the hotels currently
                            // visible on the page.  Some APIs may return hotel data in
                            // different properties (e.g. `hotels`, `results`, or `data`).
                            if (Array.isArray(data.hotels)) {
                                window.mapHotels = window.mapHotels.concat(data.hotels);
                            } else if (Array.isArray(data.results)) {
                                window.mapHotels = window.mapHotels.concat(data.results);
                            } else if (Array.isArray(data.data)) {
                                window.mapHotels = window.mapHotels.concat(data.data);
                            }

                            // Update counter
                            totalHotelsCount = data.totalCount || 0;
                            const loadedCount = data.loadedCount || 0;

                            const resultsCounter = document.getElementById('results-counter');
                            if (totalHotelsCount > 0 && data.hasMore) {
                                resultsCounter.innerHTML =
                                    `Loaded <span class="fw-semibold">${loadedCount}</span> of <span class="fw-semibold" id="total-count">${totalHotelsCount}</span> accommodations`;
                            }

                            // Check if more to load
                            hasMoreHotels = data.hasMore;

                            if (activeRequests === 0) {
                                document.getElementById('loading-more').classList.add('d-none');
                            }

                            // Continue loading more chunks
                            if (hasMoreHotels) {
                                scheduleNextBatch();
                            } else {
                                // All loaded
                                if (totalHotelsCount === 0) {
                                    document.getElementById('no-results').classList.remove('d-none');
                                } else {
                                    resultsCounter.innerHTML =
                                        `Among <span class="fw-semibold" id="total-count">${totalHotelsCount}</span> accommodations`;
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error loading chunk ' + chunkNumber + ':', error);
                            activeRequests--;
                            loadedChunks.delete(chunkNumber); // Allow retry

                            if (activeRequests === 0) {
                                document.getElementById('loading-more').classList.add('d-none');
                            }

                            // Retry this chunk after delay
                            setTimeout(() => {
                                if (hasMoreHotels && !loadedChunks.has(chunkNumber)) {
                                    loadChunk(chunkNumber);
                                }
                            }, 2000);
                        });
                }

                function scheduleNextBatch() {
                    // Load multiple chunks in parallel
                    while (activeRequests < maxParallelRequests && hasMoreHotels) {
                        const nextChunk = currentChunk++;

                        // Quick successive loading for first few chunks
                        if (nextChunk < 5) {
                            loadChunk(nextChunk);
                        } else {
                            // Slight delay for subsequent chunks
                            setTimeout(() => loadChunk(nextChunk), 100 * (nextChunk - 4));
                            break;
                        }
                    }
                }

                // Function to update prices for existing hotels
                function updatePrices() {
                    const hotelElements = document.querySelectorAll('.hotel-listcard');
                    hotelElements.forEach(hotelEl => {
                        const priceEl = hotelEl.querySelector('.hotel-listcard__price');
                        if (priceEl && priceEl.textContent.includes('Loading price')) {
                            // Price is still loading, will be updated via chunk loading
                            return;
                        }
                    });
                }

                // Start loading immediately
                if (hasMoreHotels) {
                    scheduleNextBatch();
                }

                // Sort initial hotels after a short delay to allow prices to load
                setTimeout(() => {
                    sortHotelsByAvailability();
                }, 2000); // Wait 2 seconds for initial prices to load

                // Start updating prices for loaded hotels
                priceUpdateInterval = setInterval(() => {
                    updatePrices();
                }, 2000);

                // Clean up interval after 30 seconds
                setTimeout(() => {
                    if (priceUpdateInterval) {
                        clearInterval(priceUpdateInterval);
                    }
                }, 30000);
            @endif
        });
    </script>

    {{-- Styles --}}
    <style>
        /* Skeleton Loader */
        .skeleton-card {
            display: flex;
            background: #fff;
            border: 1px solid #eaeaea;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .04);
            animation: pulse 1.5s ease-in-out infinite;
        }

        .skeleton-image {
            width: 44%;
            min-width: 340px;
            max-width: 480px;
            height: 250px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        .skeleton-content {
            flex: 1;
            padding: 22px 28px;
        }

        .skeleton-title {
            height: 24px;
            width: 70%;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 4px;
            margin-bottom: 12px;
        }

        .skeleton-text {
            height: 16px;
            width: 90%;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .skeleton-text:last-child {
            width: 60%;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.8;
            }
        }

        /* Results toolbar */
        .results-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .results-toolbar__controls {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-left: auto;
        }

        .view-toggle {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .view-tab {
            background: none;
            border: none;
            padding: 0 0 6px;
            margin: 0;
            color: #ff7a1a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            line-height: 1;
        }

        .view-tab i {
            color: #ff7a1a;
            font-size: 1.25rem;
        }

        .view-tab.active::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 3px;
            background: #ff7a1a;
            border-radius: 2px;
        }

        .sort-by-wrap {
            position: relative;
        }

        .sort-by-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            border: 1px solid #c9a07a;
            border-radius: 8px;
            padding: 8px 14px;
            color: #222;
            font-size: 0.95rem;
            line-height: 1.2;
            cursor: pointer;
            white-space: nowrap;
        }

        .sort-by-btn strong {
            font-weight: 700;
        }

        .sort-by-btn .fa-chevron-down {
            font-size: 0.75rem;
            color: #666;
        }

        .sort-by-menu {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            min-width: 230px;
            margin: 0;
            padding: 8px 0;
            list-style: none;
            background: #fff;
            border: 1px solid #ececec;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            z-index: 20;
        }

        .sort-by-option {
            width: 100%;
            border: 0;
            background: transparent;
            text-align: left;
            padding: 10px 16px;
            color: #222;
            font-size: 0.95rem;
            cursor: pointer;
        }

        .sort-by-option:hover,
        .sort-by-option.active {
            color: #ff7a1a;
            background: #fff7f0;
        }

        @media (max-width: 767.98px) {
            .results-toolbar__controls {
                width: 100%;
                justify-content: flex-start;
            }

            .sort-by-btn {
                width: 100%;
                justify-content: space-between;
            }

            .sort-by-menu {
                left: 0;
                right: 0;
                min-width: 0;
            }
        }

        /* Extended card (list) */
        .hotel-listcard {
            display: flex;
            background: #fff;
            border: 1px solid #eaeaea;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .04);
            margin-bottom: 24px;
        }

        .hotel-listcard__media {
            width: 44%;
            min-width: 340px;
            max-width: 480px;
        }

        .hotel-listcard__media img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .hotel-listcard__content {
            flex: 1;
            padding: 22px 28px;
            display: flex;
            flex-direction: column;
        }

        .hotel-listcard__title {
            color: #0d1b50;
            font-weight: 800;
        }

        .hotel-listcard__stars {
            margin-top: 2px;
            line-height: 1;
        }

        .hotel-listcard__stars .fa {
            color: #ffb02e;
            margin-right: 2px;
        }

        .hotel-listcard__location {
            color: #0d1b50;
            font-weight: 600;
        }

        .hotel-listcard__desc {
            color: #5a6477;
        }

        .hotel-listcard__footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .hotel-listcard__perk {
            color: #1ca151;
            font-weight: 700;
        }

        .hotel-listcard__price {
            color: #0d1b50;
            font-weight: 800;
        }

        .btn-cta {
            background: #f47b2d;
            color: #fff;
            font-weight: 700;
            padding: .6rem 1.1rem;
            line-height: 1;
            box-shadow: 0 4px 12px rgba(244, 123, 45, .25);
        }

        .btn-cta:hover {
            color: #fff;
            filter: brightness(.95);
        }

        /* Compact grid - Force 3 columns by default */
        #hotel-list.compact-view {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 28px !important;
            align-items: stretch !important;
        }

        /* Ensure 3 columns on large screens */
        @media (min-width: 1001px) {
            #hotel-list.compact-view {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }
        }


        /* Make the anchor fill the grid cell so the card can be 100% height */
        #hotel-list.compact-view .hotel-card-link {
            display: block;
            height: 100%;
        }

        #hotel-list.compact-view .hotel-listcard {
            display: flex;
            flex-direction: column;
            margin: 0;
            border: 1px solid #e6e6e6;
            overflow: hidden;

            /* <-- equal heights */
            height: 100%;
        }

        #hotel-list.compact-view .hotel-listcard__media {
            position: relative;
            width: 100%;
            min-width: auto;
            max-width: none;
            height: 190px;
            border-bottom: 1px solid #ececec;
            flex: 0 0 190px;
            /* fixed image height for consistency */
        }

        #hotel-list.compact-view .hotel-listcard__media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* price badge on image (grid only) */
        #hotel-list.compact-view .grid-price-badge {
            position: absolute;
            top: 0;
            left: 0;
            background: #F27625A3;
            color: #0B0B45;
            font-weight: 800;
            padding: 6px 12px;
            font-size: 0.95rem;
        }

        #hotel-list.compact-view .hotel-listcard__content {
            display: flex;
            flex-direction: column;
            padding: 14px 14px 12px;

            /* grow to fill leftover space so footer stays bottom */
            flex: 1 1 auto;
            min-height: 0;
            /* allow flex to shrink if needed */
        }

        /* Clamp description lines to keep heights uniform */
        #hotel-list.compact-view .hotel-listcard__desc {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            /* maintains visual height approx 3 lines even if fonts differ slightly */
            min-height: calc(1.15em * 3);
        }

        /* hide list-only footer (price + button) in grid */
        #hotel-list.compact-view .hotel-listcard__footer,
        #hotel-list.compact-view .btn-cta {
            display: none !important;
        }

        /* grid "See options" bar pinned to bottom */
        #hotel-list.compact-view .grid-see-footer {
            margin-top: auto;
            background: #EF7F44;
            color: #fff;
            text-align: center;
            font-weight: 700;
            padding: 10px 0;
            width: 100%;
            flex: 0 0 auto;
        }

        /* keep badge/footer hidden in extended list */
        #hotel-list.extended-view .grid-price-badge,
        #hotel-list.extended-view .grid-see-footer {
            display: none !important;
        }

        /* Responsive - Only change to 2 columns on smaller screens */
        @media (max-width: 768px) {
            #hotel-list.compact-view {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }

        @media (max-width: 991.98px) {
            .hotel-listcard {
                flex-direction: column;
            }

            .hotel-listcard__media {
                width: 100%;
                min-width: auto;
                max-width: none;
                height: 210px;
            }
        }

        @media (max-width: 480px) {
            #hotel-list.compact-view {
                grid-template-columns: 1fr !important;
            }
        }

        /* Hide view toggle on mobile */
        @media (max-width: 767.98px) {
            .view-toggle {
                display: none !important;
            }
        }

        /* Mobile View Styles */
        #hotel-list-mobile {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            align-items: stretch;
        }

        #hotel-list-mobile .hotel-card-link {
            display: block;
            height: 100%;
        }

        #hotel-list-mobile .hotel-listcard {
            display: flex;
            flex-direction: column;
            margin: 0;
            border: 1px solid #e6e6e6;
            overflow: hidden;
            height: 100%;
        }

        #hotel-list-mobile .hotel-listcard__media {
            position: relative;
            width: 100%;
            min-width: auto;
            max-width: none;
            height: 200px;
            border-bottom: 1px solid #ececec;
            flex: 0 0 200px;
        }

        #hotel-list-mobile .hotel-listcard__media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Mobile Filter Styles */
        .mobile-filter-btn {
            border: 2px solid #0B0B45;
            color: #0B0B45;
            background: white;
            font-weight: 600;
            padding: 12px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .mobile-filter-btn:hover {
            background: #0B0B45;
            color: white;
        }

        .mobile-filter-btn:focus {
            box-shadow: 0 0 0 0.2rem rgba(11, 11, 69, 0.25);
        }

        #filterToggleIcon {
            transition: transform 0.3s ease;
        }

        #filterToggleIcon.rotated {
            transform: rotate(180deg);
        }

        .mobile-filters-card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .custom-checkbox {
            position: relative;
            padding-left: 30px;
            cursor: pointer;
            font-size: 14px;
            user-select: none;
        }

        .custom-checkbox input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 20px;
            width: 20px;
            background-color: #eee;
            border-radius: 4px;
            border: 2px solid #ddd;
        }

        .custom-checkbox:hover input~.checkmark {
            background-color: #ccc;
        }

        .custom-checkbox input:checked~.checkmark {
            background-color: #0B0B45;
            border-color: #0B0B45;
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }

        .custom-checkbox input:checked~.checkmark:after {
            display: block;
        }

        .custom-checkbox .checkmark:after {
            left: 6px;
            top: 2px;
            width: 6px;
            height: 10px;
            border: solid white;
            border-width: 0 3px 3px 0;
            transform: rotate(45deg);
        }

        .filter-heading {
            color: #0d1b50;
        }

        .filter-section {
            top: 1rem;
            max-height: none;
            overflow: visible;
        }

        .filter-section h6,
        .mobile-filters-card h6 {
            color: #0d1b50;
        }

        .filter-divider {
            border: 0;
            border-top: 1px solid #e6e6e6;
            margin: 14px 0;
            opacity: 1;
        }

        .search-summary {
            padding: 2px 0 4px;
        }

        .search-summary__hotel,
        .search-summary__location,
        .search-summary__occupancy {
            color: #0d1b50;
            font-size: 0.95rem;
            font-weight: 700;
            word-break: break-word;
        }

        .search-summary__hotel,
        .search-summary__location {
            margin-bottom: 6px;
        }

        .search-summary__dates {
            color: #555;
            font-size: 0.95rem;
            margin-bottom: 6px;
        }

        .hotel-name-search {
            background: #f3f3f3;
            border-radius: 10px;
            padding: 12px 12px 14px;
        }

        .hotel-name-search__label {
            display: block;
            color: #c47a3a;
            font-size: 0.92rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .hotel-name-search__field {
            position: relative;
        }

        .hotel-name-search__field .fa-search {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa0a6;
            font-size: 0.9rem;
            pointer-events: none;
        }

        .hotel-name-search__input {
            padding-left: 36px;
            padding-right: 32px;
            border-radius: 8px;
            border: 1px solid #d9d9d9;
            background: #fff;
            height: 42px;
        }

        .hotel-name-search__input:focus {
            border-color: #c47a3a;
            box-shadow: 0 0 0 0.15rem rgba(196, 122, 58, 0.15);
        }

        .hotel-name-search__clear {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #888;
            font-size: 1.25rem;
            line-height: 1;
            padding: 0 4px;
            cursor: pointer;
        }

        .hotel-name-search__clear:hover {
            color: #333;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterToggle = document.getElementById('filterToggleIcon');
            const mobileFilters = document.getElementById('mobileFilters');

            if (mobileFilters) {
                mobileFilters.addEventListener('show.bs.collapse', function() {
                    filterToggle.classList.add('rotated');
                });

                mobileFilters.addEventListener('hide.bs.collapse', function() {
                    filterToggle.classList.remove('rotated');
                });
            }

            document.querySelectorAll('.hotel-name-search').forEach(function(wrap) {
                const input = wrap.querySelector('.hotel-name-search__input');
                const clearBtn = wrap.querySelector('.hotel-name-search__clear');
                if (!input || !clearBtn) return;

                const toggleClear = function() {
                    clearBtn.classList.toggle('d-none', input.value.trim() === '');
                };

                input.addEventListener('input', toggleClear);
                clearBtn.addEventListener('click', function() {
                    input.value = '';
                    toggleClear();
                    input.focus();
                });
            });

            // Initialize map when modal is shown
            const mapModal = document.getElementById('mapModal');
            let map = null;
            let markers = [];

            if (mapModal) {
                mapModal.addEventListener('shown.bs.modal', function() {
                    if (!map) {
                        // Initialize map
                        map = L.map('hotelMap').setView([51.505, -0.09], 10);

                        // Add tile layer
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors'
                        }).addTo(map);

                        // Add hotel markers
                        // Use the globally accumulated mapHotels array if available;
                        // otherwise fall back to the initial hotels passed from the backend.
                        const hotels = (window.mapHotels && Array.isArray(window.mapHotels)) ? window
                            .mapHotels : @json($hotels);
                        const bounds = L.latLngBounds();

                        hotels.forEach(function(hotel) {
                            if (hotel.latitude && hotel.longitude) {
                                const marker = L.marker([hotel.latitude, hotel.longitude]).addTo(
                                    map);

                                // Build URL with search parameters
                                const urlParams = new URLSearchParams({
                                    id: hotel.hotel_id,
                                    checkin: '{{ request('checkin') }}',
                                    checkout: '{{ request('checkout') }}',
                                    adults: '{{ request('adults') }}',
                                    rooms: '{{ request('rooms') }}',
                                    currency: '{{ request('currency', 'EUR') }}',
                                    children_count: '{{ request('children_count', 0) }}'
                                });

                                // Add children ages if present
                                @if (request('children'))
                                    @foreach (request('children') as $age)
                                        urlParams.append('children[]', '{{ $age }}');
                                    @endforeach
                                @endif

                                // Add optional parameters if present
                                @if (request('hotel_name'))
                                    urlParams.set('hotel_name', '{{ request('hotel_name') }}');
                                @endif
                                @if (request('location'))
                                    urlParams.set('location', '{{ request('location') }}');
                                @endif
                                @if (request('latitude'))
                                    urlParams.set('latitude', '{{ request('latitude') }}');
                                @endif
                                @if (request('longitude'))
                                    urlParams.set('longitude', '{{ request('longitude') }}');
                                @endif

                                const hotelUrl = `/hotel/${hotel.hotel_id}?${urlParams.toString()}`;

                                // Create popup content
                                const popupContent = `
                                    <div style="min-width: 200px;">
                                        <h6 class="fw-bold mb-2">${hotel.name || hotel.title || 'Hotel'}</h6>
                                        <p class="mb-1"><strong>Address:</strong> ${hotel.address || 'N/A'}</p>
                                        <p class="mb-1"><strong>Star Rating:</strong> ${hotel.star_rating || 'N/A'}</p>
                                        <p class="mb-2"><strong>Price:</strong> ${hotel.daily_price ? '€' + hotel.daily_price : 'N/A'}</p>
                                        <a href="${hotelUrl}" class="btn btn-primary btn-sm">View Details</a>
                                    </div>
                                `;

                                marker.bindPopup(popupContent);
                                markers.push(marker);
                                bounds.extend([hotel.latitude, hotel.longitude]);
                            }
                        });

                        // Fit map to show all markers
                        if (markers.length > 0) {
                            map.fitBounds(bounds);
                        }
                    }
                });
            }
        });
    </script>
@endsection
