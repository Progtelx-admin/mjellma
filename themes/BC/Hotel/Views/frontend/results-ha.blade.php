@extends('layouts.app')

@section('content')
    <div class="container">
        {{-- Page heading --}}
        <h3 class="text-center mt-5 fw-bold hotel-results-page-title">Our Hotels</h3>
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
            <div class="col-12 col-md-4 col-lg-3 mb-4 mb-md-5 filter-section sticky-top d-none d-md-block">
                <h5 class="fw-bold filter-heading">Filters</h5>
                <hr>

                <form method="GET" action="{{ route('hotel.search') }}">
                    <input type="hidden" name="location" value="{{ request('location') }}">
                    <input type="hidden" name="hid" value="{{ request('hid') }}">
                    <input type="hidden" name="etg_hotel_id" value="{{ request('etg_hotel_id') }}">
                    <input type="hidden" name="hotel_region_id" value="{{ request('hotel_region_id') }}">
                    <input type="hidden" name="region_id" value="{{ request('region_id') }}">
                    <input type="hidden" name="region_type" value="{{ request('region_type') }}">
                    <input type="hidden" name="region_country_code" value="{{ request('region_country_code') }}">
                    <input type="hidden" name="checkin" value="{{ request('checkin') }}">
                    <input type="hidden" name="checkout" value="{{ request('checkout') }}">
                    <input type="hidden" name="adults" value="{{ request('adults') }}">
                    <input type="hidden" name="rooms" value="{{ request('rooms') }}">
                    <input type="hidden" name="latitude" value="{{ request('latitude') }}">
                    <input type="hidden" name="longitude" value="{{ request('longitude') }}">
                    <input type="hidden" name="currency" value="{{ request('currency', 'EUR') }}">
                    <input type="hidden" name="children_count" value="{{ request('children_count', 0) }}">
                    <input type="hidden" name="sort_by" class="js-sort-by-input" value="{{ in_array(request('sort_by'), ['price_low', 'price_high', 'rating'], true) ? request('sort_by') : 'default' }}">
                    @foreach (request('children', []) as $age)
                        <input type="hidden" name="children[]" value="{{ $age }}">
                    @endforeach

                    @include('Hotel::frontend.partials.filter-search-context', ['idPrefix' => 'desktop'])
                    <hr>

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
                                @if (request()->boolean('breakfast_included')) checked @endif>
                            <span class="checkmark"></span> Breakfast Included
                        </label>
                    </div>
                    <hr>

                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </form>

                @include('Hotel::frontend.partials.hotel-map-preview', [
                    'mapId' => 'hotelMapPreviewDesktop',
                    'wrapperClass' => 'mt-3',
                ])
            </div>

            {{-- ================================================================
                 MOBILE FILTERS COLLAPSIBLE
            ================================================================= --}}
            <div class="col-12 d-md-none">
                <div class="collapse" id="mobileFilters">
                    <div class="card card-body mb-3 mobile-filters-card">
                        <h5 class="fw-bold filter-heading">Filters</h5>
                        <hr>
                        <form method="GET" action="{{ route('hotel.search') }}">
                            <input type="hidden" name="location" value="{{ request('location') }}">
                            <input type="hidden" name="hid" value="{{ request('hid') }}">
                            <input type="hidden" name="etg_hotel_id" value="{{ request('etg_hotel_id') }}">
                            <input type="hidden" name="hotel_region_id" value="{{ request('hotel_region_id') }}">
                            <input type="hidden" name="region_id" value="{{ request('region_id') }}">
                            <input type="hidden" name="region_type" value="{{ request('region_type') }}">
                            <input type="hidden" name="region_country_code" value="{{ request('region_country_code') }}">
                            <input type="hidden" name="checkin" value="{{ request('checkin') }}">
                            <input type="hidden" name="checkout" value="{{ request('checkout') }}">
                            <input type="hidden" name="adults" value="{{ request('adults') }}">
                            <input type="hidden" name="rooms" value="{{ request('rooms') }}">
                            <input type="hidden" name="latitude" value="{{ request('latitude') }}">
                            <input type="hidden" name="longitude" value="{{ request('longitude') }}">
                            <input type="hidden" name="currency" value="{{ request('currency', 'EUR') }}">
                            <input type="hidden" name="children_count" value="{{ request('children_count', 0) }}">
                            <input type="hidden" name="sort_by" class="js-sort-by-input" value="{{ in_array(request('sort_by'), ['price_low', 'price_high', 'rating'], true) ? request('sort_by') : 'default' }}">
                            @foreach (request('children', []) as $age)
                                <input type="hidden" name="children[]" value="{{ $age }}">
                            @endforeach

                            @include('Hotel::frontend.partials.filter-search-context', ['idPrefix' => 'mobile'])
                            <hr>

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
                                        value="1" @if (request()->boolean('breakfast_included')) checked @endif>
                                    <span class="checkmark"></span> Breakfast Included
                                </label>
                            </div>
                            <hr>

                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        </form>

                        @include('Hotel::frontend.partials.hotel-map-preview', [
                            'mapId' => 'hotelMapPreviewMobile',
                            'wrapperClass' => 'mt-3',
                        ])
                    </div>
                </div>
            </div>

            {{-- ================================================================
                 RESULTS
            ================================================================= --}}
            <div class="col-12 col-md-8 col-lg-9">
                @php
                    $locationShort = request('location')
                        ? trim(\Illuminate\Support\Str::before(request('location'), ','))
                        : '';
                    $pageHotelCount = $hotels->count();
                    $totalHotelCount = $totalHotels ?? $pageHotelCount;
                    $resultWord = $pageHotelCount === 1 ? 'result' : 'results';
                    $sortBy = request('sort_by', 'default');
                    $sortLabels = [
                        'default' => 'Default',
                        'price_low' => 'Price (Low to High)',
                        'price_high' => 'Price (High to Low)',
                        'rating' => 'Guest Rating (High to Low)',
                    ];
                    if (!isset($sortLabels[$sortBy]) || $sortBy === 'popularity') {
                        $sortBy = 'default';
                    }
                    $sortLabel = $sortLabels[$sortBy];
                @endphp
                {{-- Header --}}
                <div class="results-toolbar">
                    <div class="results-summary-row">
                        <span class="results-summary" id="results-counter"
                            data-location="{{ $locationShort }}">
                            @if ($locationShort)
                                {{ $locationShort }} : {{ $pageHotelCount }} {{ $resultWord }} on this page out of {{ $totalHotelCount }}
                            @else
                                {{ $pageHotelCount }} {{ $resultWord }} on this page out of {{ $totalHotelCount }}
                            @endif
                        </span>
                    </div>

                    <div class="results-controls">
                        <div class="view-toggle" role="group" aria-label="Results view">
                            <button id="extended-view-btn" type="button" class="view-tab" aria-label="Extended view" title="Extended">
                                <i class="fa fa-list" aria-hidden="true"></i>
                            </button>
                            <button id="compact-view-btn" type="button" class="view-tab active" aria-label="Compact view" title="Compact">
                                <i class="fa fa-th" aria-hidden="true"></i>
                            </button>
                        </div>

                        <div class="sort-by-wrap">
                            <button type="button" class="sort-by-btn" id="sort-by-toggle" aria-expanded="false">
                                <span class="sort-by-btn__text">
                                    <span class="sort-by-btn__prefix">Sort by:</span>
                                    <strong id="sort-by-label">{{ $sortLabel }}</strong>
                                </span>
                                <i class="fa fa-chevron-down" aria-hidden="true"></i>
                            </button>
                            <ul class="sort-by-menu d-none" id="sort-by-menu">
                                <li data-value="default" @class(['is-selected' => $sortBy === 'default'])>Default</li>
                                <li data-value="price_low" @class(['is-selected' => $sortBy === 'price_low'])>Price (Low to High)</li>
                                <li data-value="price_high" @class(['is-selected' => $sortBy === 'price_high'])>Price (High to Low)</li>
                                <li data-value="rating" @class(['is-selected' => $sortBy === 'rating'])>Guest Rating (High to Low)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div id="hotel-list" class="compact-view d-md-block d-none">
                    @foreach ($hotels as $hotel)
                        @include('Hotel::frontend.partials.hotel-card-chunk', [
                            'hotel' => $hotel,
                        ])
                    @endforeach
                </div>

                {{-- Mobile Compact View --}}
                <div id="hotel-list-mobile" class="compact-view d-md-none">
                    @foreach ($hotels as $hotel)
                        @include('Hotel::frontend.partials.hotel-card-chunk', [
                            'hotel' => $hotel,
                        ])
                    @endforeach
                </div>

                {{-- Loading more indicator --}}
                <div id="loading-more" class="hotel-page-loading text-center py-3 d-none">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 mb-0 text-muted" id="loading-more-text">Loading remaining hotels...</p>
                </div>

                <nav id="hotel-pagination" class="hotel-pagination d-none" aria-label="Hotel results pages"></nav>

                {{-- No results message --}}
                <div id="no-results" class="text-center text-muted fs-5 py-5 d-none">
                    No hotels found matching your criteria.
                </div>
            </div>
        </div>
    </div>

    {{-- Map modal --}}
    <div class="modal fade hotel-map-modal" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="mapModalLabel">{{ __('hotel.map') }}</h5>
                        <div class="hotel-map-modal__meta" id="hotelMapCount"></div>
                    </div>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" aria-label="{{ __('hotel.close') }}">
                        <i class="fa fa-close"></i>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <div class="hotel-map-canvas-wrap">
                        <div id="hotelMap" class="hotel-map-canvas"></div>
                        <div id="hotelMapLoading" class="hotel-map-loading d-none" aria-hidden="true">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                            <span>{{ __('hotel.loading_hotels') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
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

            function setView(mode) {
                if (list) {
                    list.classList.toggle('extended-view', mode === 'extended');
                    list.classList.toggle('compact-view', mode === 'compact');
                }
                if (extendedBtn) extendedBtn.classList.toggle('active', mode === 'extended');
                if (compactBtn) compactBtn.classList.toggle('active', mode === 'compact');
            }

            if (extendedBtn && compactBtn) {
                extendedBtn.addEventListener('click', () => setView('extended'));
                compactBtn.addEventListener('click', () => setView('compact'));
            }

            const searchLat = @json(request()->filled('latitude') ? (float) request('latitude') : null);
            const searchLng = @json(request()->filled('longitude') ? (float) request('longitude') : null);
            const sortLabels = {
                default: 'Default',
                popularity: 'Default',
                price_low: 'Price (Low to High)',
                price_high: 'Price (High to Low)',
                rating: 'Guest Rating (High to Low)'
            };
            let currentSort = @json(request('sort_by', 'default'));
            if (!sortLabels[currentSort] || currentSort === 'popularity') currentSort = 'default';

            function parseCardPrice(card) {
                const raw = card.getAttribute('data-hotel-price');
                if (raw === null || raw === '') return null;
                const n = parseFloat(raw);
                return Number.isFinite(n) ? n : null;
            }

            function parseCardRating(card) {
                const n = parseFloat(card.getAttribute('data-hotel-rating') || '0');
                return Number.isFinite(n) ? n : 0;
            }

            function distanceKm(lat1, lng1, lat2, lng2) {
                const toRad = (d) => d * Math.PI / 180;
                const R = 6371;
                const dLat = toRad(lat2 - lat1);
                const dLng = toRad(lng2 - lng1);
                const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
                return 2 * R * Math.asin(Math.sqrt(a));
            }

            function cardDistance(card) {
                const lat = parseFloat(card.getAttribute('data-hotel-lat'));
                const lng = parseFloat(card.getAttribute('data-hotel-lng'));
                if (!Number.isFinite(lat) || !Number.isFinite(lng) || searchLat === null || searchLng === null) {
                    return Number.POSITIVE_INFINITY;
                }
                return distanceKm(searchLat, searchLng, lat, lng);
            }

            function isCardAvailable(card) {
                const aPrice = card.querySelector('.hotel-listcard__price');
                if (!aPrice) return false;
                const aText = aPrice.textContent.trim();
                return aText.includes('From') && !aText.includes('No rooms available');
            }

            function compareCards(a, b, mode) {
                if (mode === 'price_low' || mode === 'price_high') {
                    const aPrice = parseCardPrice(a);
                    const bPrice = parseCardPrice(b);
                    if (aPrice === null && bPrice === null) return 0;
                    if (aPrice === null) return 1;
                    if (bPrice === null) return -1;
                    return mode === 'price_low' ? aPrice - bPrice : bPrice - aPrice;
                }
                if (mode === 'rating') {
                    return parseCardRating(b) - parseCardRating(a);
                }
                if (mode === 'distance') {
                    return cardDistance(a) - cardDistance(b);
                }
                if (mode === 'date') {
                    const aIdx = parseInt(a.dataset.hotelIndex || '0', 10);
                    const bIdx = parseInt(b.dataset.hotelIndex || '0', 10);
                    return aIdx - bIdx;
                }
                // Default: available hotels first (existing behaviour)
                const aAvailable = isCardAvailable(a);
                const bAvailable = isCardAvailable(b);
                if (aAvailable && !bAvailable) return -1;
                if (!aAvailable && bAvailable) return 1;
                return 0;
            }

            const PAGE_SIZE = 48;
            let currentPage = 1;
            let pendingPage = null;
            let allHotelCards = [];
            let hasMoreHotels = {{ isset($loadMore) && $loadMore ? 'true' : 'false' }};
            let totalHotelsCount = {{ (int) ($totalHotelCount ?? 0) }};
            let activeRequests = 0;

            function getCardId(card) {
                return card.getAttribute('data-hotel-id') || card.getAttribute('href') || '';
            }

            function toCardThumb(src) {
                if (!src || src.indexOf('cdn.worldota.net') === -1) return src;
                return src.replace(/\/t\/\d+x\d+\//, '/t/640x400/');
            }

            function prepareCardForDisplay(card, index) {
                const clone = card.cloneNode(true);
                const img = clone.querySelector('.hotel-listcard__media img');
                if (img) {
                    const thumb = toCardThumb(img.getAttribute('src') || '');
                    if (thumb && thumb !== img.getAttribute('src')) {
                        img.setAttribute('src', thumb);
                    }
                    img.setAttribute('decoding', 'async');
                    img.setAttribute('loading', index < 6 ? 'eager' : 'lazy');
                    img.setAttribute('width', '640');
                    img.setAttribute('height', '400');
                }
                return clone;
            }

            function snapshotInitialCards() {
                const hotelList = document.getElementById('hotel-list');
                if (!hotelList) return;
                allHotelCards = Array.from(hotelList.querySelectorAll(':scope > .hotel-card-link')).map((el, i) => {
                    const clone = el.cloneNode(true);
                    clone.dataset.hotelIndex = String(i);
                    return clone;
                });
            }

            function getSortedCards() {
                const cards = allHotelCards.slice();
                cards.forEach((el, i) => {
                    if (el.dataset.hotelIndex === undefined) {
                        el.dataset.hotelIndex = String(i);
                    }
                });
                cards.sort((a, b) => {
                    const sorted = compareCards(a, b, currentSort);
                    if (sorted !== 0) return sorted;
                    return parseInt(a.dataset.hotelIndex || '0', 10) - parseInt(b.dataset.hotelIndex || '0', 10);
                });
                return cards;
            }

            function getLoadedPageCount() {
                return Math.max(1, Math.ceil(allHotelCards.length / PAGE_SIZE) || 1);
            }

            function getTotalPages() {
                if (allHotelCards.length === 0) {
                    return Math.max(1, Math.ceil((totalHotelsCount || 0) / PAGE_SIZE));
                }
                const loadedPages = Math.ceil(allHotelCards.length / PAGE_SIZE);
                if (hasMoreHotels && totalHotelsCount > allHotelCards.length) {
                    return Math.max(loadedPages, Math.ceil(totalHotelsCount / PAGE_SIZE));
                }
                return Math.max(1, loadedPages);
            }

            function isPageReady(page) {
                return allHotelCards.length > (page - 1) * PAGE_SIZE;
            }

            function getPageButtonNumbers(current, total) {
                if (total <= 7) {
                    return Array.from({ length: total }, (_, i) => i + 1);
                }
                const pages = new Set([1, total, current, current - 1, current + 1]);
                if (current <= 3) {
                    [2, 3, 4].forEach((p) => pages.add(p));
                }
                if (current >= total - 2) {
                    [total - 1, total - 2, total - 3].forEach((p) => pages.add(p));
                }
                return Array.from(pages).filter((p) => p >= 1 && p <= total).sort((a, b) => a - b);
            }

            function updateLoadingIndicator() {
                const el = document.getElementById('loading-more');
                const text = document.getElementById('loading-more-text');
                if (!el) return;
                const waitingForPage = pendingPage !== null && !isPageReady(pendingPage);
                const backgroundLoading = activeRequests > 0;
                if (waitingForPage || backgroundLoading) {
                    el.classList.remove('d-none');
                    if (text) {
                        text.textContent = waitingForPage
                            ? ('Loading page ' + pendingPage + '...')
                            : 'Loading remaining hotels...';
                    }
                } else {
                    el.classList.add('d-none');
                }
            }

            function renderPagination() {
                const nav = document.getElementById('hotel-pagination');
                if (!nav) return;
                const totalPages = getTotalPages();
                if (totalPages <= 1 && !hasMoreHotels) {
                    nav.classList.add('d-none');
                    nav.innerHTML = '';
                    return;
                }

                nav.classList.remove('d-none');
                const loadedPages = getLoadedPageCount();
                const numbers = getPageButtonNumbers(currentPage, totalPages);
                let html = '';

                html += `<button type="button" class="hotel-page-btn hotel-page-btn--nav" data-page="prev" ${currentPage <= 1 ? 'disabled' : ''} aria-label="Previous page">&lt;</button>`;

                numbers.forEach((page, idx) => {
                    if (idx > 0 && page - numbers[idx - 1] > 1) {
                        html += '<span class="hotel-page-ellipsis">&hellip;</span>';
                    }
                    const isActive = page === currentPage;
                    const isPending = pendingPage === page && !isPageReady(page);
                    const isLoaded = page <= loadedPages || !hasMoreHotels;
                    html += `<button type="button" class="hotel-page-btn${isActive ? ' is-active' : ''}${isPending ? ' is-pending' : ''}${isLoaded ? '' : ' is-unloaded'}" data-page="${page}" ${isActive ? 'aria-current="page"' : ''}>${page}</button>`;
                });

                html += `<button type="button" class="hotel-page-btn hotel-page-btn--nav" data-page="next" ${currentPage >= totalPages ? 'disabled' : ''} aria-label="Next page">&gt;</button>`;
                nav.innerHTML = html;
            }

            function visibleCardIds() {
                const hotelList = document.getElementById('hotel-list');
                if (!hotelList) return [];
                return Array.from(hotelList.querySelectorAll(':scope > .hotel-card-link')).map(getCardId);
            }

            function sameIdList(a, b) {
                if (a.length !== b.length) return false;
                return a.every((id, i) => id === b[i]);
            }

            function paintCurrentPage(force) {
                const sorted = getSortedCards();
                if (pendingPage !== null && isPageReady(pendingPage)) {
                    currentPage = pendingPage;
                    pendingPage = null;
                    force = true;
                }
                const readyPages = Math.max(1, Math.ceil(sorted.length / PAGE_SIZE) || 1);
                if (!hasMoreHotels && currentPage > readyPages) {
                    currentPage = readyPages;
                    force = true;
                }

                const start = (currentPage - 1) * PAGE_SIZE;
                const pageCards = sorted.slice(start, start + PAGE_SIZE);
                const nextIds = pageCards.map(getCardId);

                if (!force && sameIdList(nextIds, visibleCardIds())) {
                    updateResultsSummary(totalHotelsCount);
                    renderPagination();
                    updateLoadingIndicator();
                    return;
                }

                ['hotel-list', 'hotel-list-mobile'].forEach((id) => {
                    const hotelList = document.getElementById(id);
                    if (!hotelList) return;
                    hotelList.replaceChildren();
                    pageCards.forEach((card, i) => hotelList.appendChild(prepareCardForDisplay(card, i)));
                });

                updateResultsSummary(totalHotelsCount);
                renderPagination();
                updateLoadingIndicator();
            }

            function goToPage(page, scrollToResults) {
                const totalPages = getTotalPages();
                const nextPage = Math.min(Math.max(1, page), totalPages);
                if (isPageReady(nextPage)) {
                    pendingPage = null;
                    currentPage = nextPage;
                    paintCurrentPage(true);
                    if (scrollToResults) {
                        const toolbar = document.querySelector('.results-toolbar');
                        if (toolbar) toolbar.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                    return;
                }
                pendingPage = nextPage;
                renderPagination();
                updateLoadingIndicator();
                if (scrollToResults) {
                    const toolbar = document.querySelector('.results-toolbar');
                    if (toolbar) toolbar.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            function applyHotelSort() {
                currentPage = 1;
                pendingPage = null;
                paintCurrentPage(true);
            }

            function sortHotelsByAvailability() {
                paintCurrentPage(false);
            }

            function updateResultsSummary(totalCount) {
                const resultsCounter = document.getElementById('results-counter');
                if (!resultsCounter) return;
                const pageCount = document.querySelectorAll('#hotel-list .hotel-card-link').length;
                const loc = resultsCounter.getAttribute('data-location') || '';
                const resultWord = pageCount === 1 ? 'result' : 'results';
                const loadedTotal = allHotelCards.length || pageCount;
                const total = typeof totalCount === 'number' && totalCount > 0
                    ? totalCount
                    : loadedTotal;
                resultsCounter.textContent = loc
                    ? `${loc} : ${pageCount} ${resultWord} on this page out of ${total}`
                    : `${pageCount} ${resultWord} on this page out of ${total}`;
            }

            snapshotInitialCards();
            renderPagination();

            const paginationNav = document.getElementById('hotel-pagination');
            if (paginationNav) {
                paginationNav.addEventListener('click', (e) => {
                    const btn = e.target.closest('[data-page]');
                    if (!btn || btn.disabled) return;
                    const value = btn.getAttribute('data-page');
                    if (value === 'prev') {
                        goToPage(currentPage - 1, true);
                    } else if (value === 'next') {
                        goToPage(currentPage + 1, true);
                    } else {
                        goToPage(parseInt(value, 10), true);
                    }
                });
            }

            const sortToggle = document.getElementById('sort-by-toggle');
            const sortMenu = document.getElementById('sort-by-menu');
            const sortLabelEl = document.getElementById('sort-by-label');

            function setCurrentSort(value) {
                if (!sortLabels[value]) return;
                currentSort = value;
                if (sortLabelEl) sortLabelEl.textContent = sortLabels[value];
                if (sortMenu) {
                    sortMenu.querySelectorAll('li').forEach((item) => {
                        item.classList.toggle('is-selected', item.getAttribute('data-value') === value);
                    });
                }
                document.querySelectorAll('.js-sort-by-input').forEach((input) => {
                    input.value = value;
                });
                applyHotelSort();
            }

            if (sortToggle && sortMenu) {
                sortToggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = !sortMenu.classList.contains('d-none');
                    sortMenu.classList.toggle('d-none', isOpen);
                    sortToggle.setAttribute('aria-expanded', String(!isOpen));
                });
                sortMenu.querySelectorAll('li').forEach((item) => {
                    item.addEventListener('click', () => {
                        setCurrentSort(item.getAttribute('data-value'));
                        sortMenu.classList.add('d-none');
                        sortToggle.setAttribute('aria-expanded', 'false');
                    });
                });
                sortMenu.addEventListener('click', (e) => e.stopPropagation());
                document.addEventListener('click', () => {
                    sortMenu.classList.add('d-none');
                    sortToggle.setAttribute('aria-expanded', 'false');
                });
            }

            document.querySelectorAll('.hotel-name-search__input').forEach((input) => {
                const clearBtn = input.parentElement.querySelector('.hotel-name-search__clear');
                const syncClear = () => {
                    if (!clearBtn) return;
                    clearBtn.classList.toggle('d-none', input.value.trim() === '');
                };
                input.addEventListener('input', syncClear);
                if (clearBtn) {
                    clearBtn.addEventListener('click', () => {
                        input.value = '';
                        syncClear();
                        input.focus();
                    });
                }
            });

            // Progressive loading: keep page 1 visible, prefetch remaining pages in the background
            @if (isset($searchHash))
                const searchHash = '{{ $searchHash }}';
                let currentChunk = 1; // Start from chunk 1 (chunk 0 already loaded)
                let maxParallelRequests = 3; // Load 3 chunks in parallel
                let loadedChunks = new Set([0]); // Chunk 0 already loaded

                function parseCardsFromHtml(html) {
                    const wrap = document.createElement('div');
                    wrap.innerHTML = html;
                    return Array.from(wrap.querySelectorAll('.hotel-card-link'));
                }

                function loadChunk(chunkNumber) {
                    if (loadedChunks.has(chunkNumber)) return;
                    loadedChunks.add(chunkNumber);

                    activeRequests++;
                    updateLoadingIndicator();

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
                                loadedChunks.delete(chunkNumber);
                                updateLoadingIndicator();
                                return;
                            }

                            if (chunkNumber === 0) {
                                const skeleton = document.getElementById('loading-skeleton');
                                if (skeleton) skeleton.remove();
                            }

                            if (data.html) {
                                const newCards = parseCardsFromHtml(data.html);
                                const startIndex = allHotelCards.length;
                                newCards.forEach((card, i) => {
                                    card.dataset.hotelIndex = String(startIndex + i);
                                });
                                allHotelCards = allHotelCards.concat(newCards);
                            }

                            if (Array.isArray(data.hotels)) {
                                window.mapHotels = window.mapHotels.concat(data.hotels);
                            } else if (Array.isArray(data.results)) {
                                window.mapHotels = window.mapHotels.concat(data.results);
                            } else if (Array.isArray(data.data)) {
                                window.mapHotels = window.mapHotels.concat(data.data);
                            }
                            if (typeof window.refreshHotelMapMarkers === 'function') {
                                window.refreshHotelMapMarkers();
                            }

                            totalHotelsCount = data.totalCount || totalHotelsCount;
                            hasMoreHotels = data.hasMore;

                            if (pendingPage !== null && isPageReady(pendingPage)) {
                                goToPage(pendingPage, false);
                            } else if (currentSort !== 'default') {
                                paintCurrentPage(false);
                            } else {
                                updateResultsSummary(totalHotelsCount);
                                renderPagination();
                                updateLoadingIndicator();
                            }

                            if (hasMoreHotels) {
                                scheduleNextBatch();
                            } else {
                                if (allHotelCards.length === 0 && totalHotelsCount === 0) {
                                    document.getElementById('no-results').classList.remove('d-none');
                                }
                                if (pendingPage !== null) {
                                    goToPage(pendingPage, false);
                                } else {
                                    paintCurrentPage(false);
                                    updateLoadingIndicator();
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error loading chunk ' + chunkNumber + ':', error);
                            activeRequests--;
                            loadedChunks.delete(chunkNumber);
                            updateLoadingIndicator();

                            setTimeout(() => {
                                if (hasMoreHotels && !loadedChunks.has(chunkNumber)) {
                                    loadChunk(chunkNumber);
                                }
                            }, 2000);
                        });
                }

                function scheduleNextBatch() {
                    while (activeRequests < maxParallelRequests && hasMoreHotels) {
                        const nextChunk = currentChunk++;

                        if (nextChunk < 5) {
                            loadChunk(nextChunk);
                        } else {
                            setTimeout(() => loadChunk(nextChunk), 100 * (nextChunk - 4));
                            break;
                        }
                    }
                }

                if (hasMoreHotels) {
                    scheduleNextBatch();
                }
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

        /* Toggle buttons */
        .view-tab {
            background: none;
            border: none;
            padding: 0 0 6px;
            margin: 0;
            color: #ff7a1a;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
            line-height: 1;
            min-width: 28px;
        }

        .view-tab i {
            color: #ff7a1a;
            font-size: 1.25rem;
        }

        .view-toggle {
            display: flex;
            align-items: flex-end;
            gap: 0.85rem;
            flex-shrink: 0;
        }

        .view-tab.active::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 3px;
            background: #ff7a1a;
            border-radius: 2px;
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
            background-color: #e8eef5;
        }

        .hotel-listcard__media img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background-color: #e8eef5;
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

        /* 2 columns on tablet so cards stay readable next to the filters */
        @media (max-width: 1199.98px) {
            #hotel-list.compact-view {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }

        /* Ensure 3 columns on large screens */
        @media (min-width: 1200px) {
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
            background-color: #e8eef5;
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
            background-color: #e8eef5;
        }

        #hotel-list-mobile .hotel-listcard__media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        #hotel-list-mobile.compact-view .hotel-listcard__footer,
        #hotel-list-mobile.compact-view .btn-cta {
            display: none !important;
        }

        #hotel-list-mobile.compact-view .grid-price-badge {
            position: absolute;
            top: 0;
            left: 0;
            background: #F27625A3;
            color: #0B0B45;
            font-weight: 800;
            padding: 6px 12px;
            font-size: 0.95rem;
        }

        #hotel-list-mobile.compact-view .grid-see-footer {
            margin-top: auto;
            background: #EF7F44;
            color: #fff;
            text-align: center;
            font-weight: 700;
            padding: 10px 0;
            width: 100%;
            flex: 0 0 auto;
        }

        #hotel-list-mobile.extended-view .grid-price-badge,
        #hotel-list-mobile.extended-view .grid-see-footer {
            display: none !important;
        }

        #hotel-list-mobile.extended-view .hotel-listcard__footer {
            display: flex;
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

        .hotel-results-page-title {
            color: #0d1b50;
        }

        .filter-heading {
            color: #0d1b50;
        }

        .filter-section {
            top: 1rem;
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

        .search-summary__location {
            color: #0d1b50;
            font-weight: 800;
            font-size: 1.05rem;
            line-height: 1.3;
            margin-bottom: 6px;
        }

        .search-summary__dates {
            color: #555;
            font-size: 0.95rem;
            margin-bottom: 6px;
        }

        .search-summary__item {
            color: #0d1b50;
            font-size: 0.95rem;
            margin-bottom: 6px;
        }

        .search-summary__occupancy {
            color: #0d1b50;
            font-size: 0.95rem;
        }

        .search-summary__occupancy strong {
            font-weight: 700;
            color: #0d1b50;
        }

        .applied-filters {
            padding: 4px 0 8px;
            margin: 8px 0 0;
            background: transparent;
            border: none;
        }

        .applied-filters__head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 8px;
        }

        .applied-filters__title {
            font-weight: 700;
            color: #0d1b50;
            font-size: 0.82rem;
            letter-spacing: 0.04em;
            margin: 0;
        }

        .applied-filters__reset {
            color: #b42318;
            font-size: 0.9rem;
            text-decoration: none;
            white-space: nowrap;
        }

        .applied-filters__reset:hover {
            color: #8f1d14;
            text-decoration: underline;
        }

        .applied-filters__chip {
            color: #5aa0d6;
            font-size: 0.95rem;
            margin-top: 2px;
        }

        .hotel-name-search {
            background: #f4f4f4;
            border-radius: 10px;
            padding: 14px;
            margin: 0 0 12px;
        }

        .hotel-name-search__label {
            font-weight: 700;
            color: #c47a2c;
            margin-bottom: 8px;
            display: block;
        }

        .hotel-name-search__field {
            position: relative;
        }

        .hotel-name-search__field .fa-search {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            pointer-events: none;
        }

        .hotel-name-search__input {
            padding-left: 36px;
            padding-right: 32px;
            border: 1px solid #d5d5d5;
            border-radius: 6px;
            background: #fff;
        }

        .hotel-name-search__input:focus {
            border-color: #c47a2c;
            box-shadow: 0 0 0 0.15rem rgba(196, 122, 44, 0.18);
        }

        .hotel-name-search__clear {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #888;
            font-size: 1.25rem;
            line-height: 1;
            padding: 0 4px;
            cursor: pointer;
        }

        .hotel-pagination {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin: 4px 0 28px;
        }

        .hotel-page-btn {
            min-width: 40px;
            height: 40px;
            padding: 0 12px;
            border: 1px solid #e6e6e6;
            background: #fff;
            color: #0d1b50;
            border-radius: 8px;
            font-weight: 700;
            line-height: 1;
            cursor: pointer;
        }

        .hotel-page-btn:hover:not(:disabled):not(.is-active) {
            border-color: #EF7F44;
            color: #EF7F44;
        }

        .hotel-page-btn.is-active {
            background: #0B0B45;
            color: #fff;
            border-color: #0B0B45;
        }

        .hotel-page-btn.is-pending {
            border-color: #EF7F44;
            color: #EF7F44;
        }

        .hotel-page-btn.is-unloaded {
            color: #8a93a6;
            background: #f7f8fa;
        }

        .hotel-page-btn:disabled {
            opacity: 0.45;
            cursor: default;
        }

        .hotel-page-ellipsis {
            color: #8a93a6;
            padding: 0 2px;
            font-weight: 700;
        }

        .hotel-page-loading {
            min-height: 0;
        }

        .results-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px 16px;
            margin-bottom: 18px;
        }

        .results-summary {
            color: #0d1b50;
            font-weight: 600;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .results-controls {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 14px;
            margin-left: auto;
        }

        .sort-by-wrap {
            position: relative;
            flex: 0 1 auto;
        }

        .sort-by-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            border: 1px solid #f0a060;
            border-radius: 6px;
            padding: 8px 14px;
            color: #333;
            font-size: 0.9rem;
            line-height: 1.2;
            white-space: nowrap;
        }

        .sort-by-btn__text {
            display: inline-flex;
            align-items: baseline;
            gap: 4px;
            min-width: 0;
        }

        .sort-by-btn__prefix {
            flex-shrink: 0;
        }

        .sort-by-btn strong {
            font-weight: 700;
            color: #222;
        }

        .sort-by-btn .fa-chevron-down {
            color: #888;
            font-size: 0.75rem;
            margin-left: 2px;
        }

        .sort-by-btn:hover,
        .sort-by-btn:focus {
            background: #fff8f3;
            border-color: #ff7a1a;
        }

        .sort-by-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 4px);
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
            list-style: none;
            padding: 8px 0;
            min-width: 240px;
            z-index: 30;
            margin: 0;
        }

        .sort-by-menu li {
            padding: 10px 18px;
            cursor: pointer;
            color: #333;
            font-size: 0.95rem;
        }

        .sort-by-menu li:hover {
            background: #f5f7fa;
        }

        .sort-by-menu li.is-selected {
            background: #fff4eb;
            color: #EF7F44;
            font-weight: 600;
        }

        @media (max-width: 991.98px) {
            .results-summary {
                font-size: 0.88rem;
            }
        }

        @media (max-width: 767.98px) {
            .results-toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .results-controls {
                order: 1;
                margin-left: 0;
                width: 100%;
            }

            .results-summary-row {
                order: 2;
                width: 100%;
            }

            .view-toggle {
                display: none;
            }

            .sort-by-wrap {
                width: 100%;
            }

            .sort-by-btn {
                width: 100%;
                max-width: 100%;
                justify-content: center;
                align-items: center;
                border: 2px solid #EF7F44;
                color: #EF7F44;
                background: #fff;
                font-weight: 600;
                padding: 10px 16px;
                border-radius: 8px;
                font-size: 1rem;
                white-space: nowrap;
                text-align: center;
            }

            .sort-by-btn__text {
                flex: 0 1 auto;
                min-width: 0;
                flex-direction: column;
                align-items: center;
                gap: 2px;
            }

            .sort-by-btn__prefix {
                font-size: 0.75rem;
                font-weight: 600;
                line-height: 1.2;
                color: #EF7F44;
            }

            .sort-by-btn strong {
                color: #EF7F44;
                font-size: 0.95rem;
                font-weight: 700;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 100%;
                display: block;
            }

            .sort-by-btn .fa-chevron-down {
                color: #EF7F44;
                font-size: 0.85rem;
                margin-left: 0;
                flex-shrink: 0;
            }

            .sort-by-btn:hover,
            .sort-by-btn:focus {
                background: #EF7F44;
                color: #fff;
                border-color: #EF7F44;
            }

            .sort-by-btn:hover .sort-by-btn__prefix,
            .sort-by-btn:focus .sort-by-btn__prefix,
            .sort-by-btn:hover strong,
            .sort-by-btn:focus strong,
            .sort-by-btn:hover .fa-chevron-down,
            .sort-by-btn:focus .fa-chevron-down {
                color: #fff;
            }

            .sort-by-menu {
                left: 0;
                right: 0;
                min-width: 100%;
            }

            .hotel-name-search {
                padding: 12px;
            }

            .applied-filters__head {
                flex-wrap: wrap;
            }

            .mobile-filters-card .hotel-name-search,
            .mobile-filters-card .search-summary,
            .mobile-filters-card .applied-filters {
                width: 100%;
            }
        }

        /* Hotel map preview (sidebar / mobile) */
        .hotel-map-preview {
            overflow: hidden;
        }

        .hotel-map-preview__frame {
            position: relative;
            z-index: 0;
            isolation: isolate;
            height: 180px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e4e7ec;
            box-shadow: 0 4px 14px rgba(11, 11, 69, 0.08);
            cursor: pointer;
            background: #e8eef5;
        }

        .hotel-map-preview__map {
            height: 100%;
            width: 100%;
        }

        .hotel-map-preview__overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(11, 11, 69, 0.28) 0%, transparent 42%);
            z-index: 5;
            pointer-events: none;
        }

        .hotel-map-preview__count {
            position: absolute;
            left: 10px;
            bottom: 10px;
            background: rgba(255, 255, 255, 0.94);
            color: #0B0B45;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
        }

        .hotel-map-preview__btn {
            margin-top: 10px;
            font-weight: 600;
        }

        .hotel-map-modal .modal-content {
            border: 0;
            overflow: hidden;
            border-radius: 12px;
        }

        .hotel-map-modal .modal-header {
            border-bottom: 1px solid #eef0f4;
            padding: 14px 18px;
        }

        .hotel-map-modal__meta {
            font-size: 13px;
            color: #667085;
            margin-top: 2px;
        }

        .hotel-map-canvas-wrap {
            position: relative;
        }

        .hotel-map-canvas {
            height: 70vh;
            min-height: 420px;
            width: 100%;
        }

        .hotel-map-loading {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.94);
            color: #0B0B45;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 999px;
            box-shadow: 0 2px 8px rgba(16, 24, 40, 0.14);
            pointer-events: none;
        }

        .hotel-map-pin {
            background: transparent;
            border: 0;
        }

        .hotel-map-pin__inner {
            display: block;
            width: 22px;
            height: 22px;
            background: #0B0B45;
            border: 2px solid #fff;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            box-shadow: 0 2px 6px rgba(11, 11, 69, 0.35);
        }

        .hotel-map-pin--preview .hotel-map-pin__inner {
            width: 16px;
            height: 16px;
        }

        .hotel-map-popup {
            min-width: 220px;
            max-width: 280px;
        }

        .hotel-map-popup h6 {
            color: #0B0B45;
            font-size: 15px;
        }

        .hotel-map-popup p {
            font-size: 13px;
            color: #475467;
        }

        .leaflet-popup-content-wrapper {
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(16, 24, 40, 0.16);
        }

        .marker-cluster-small,
        .marker-cluster-medium,
        .marker-cluster-large {
            background-color: rgba(11, 11, 69, 0.18);
        }

        .marker-cluster-small div,
        .marker-cluster-medium div,
        .marker-cluster-large div {
            background-color: #0B0B45;
            color: #fff;
            font-weight: 700;
        }

        @media (max-width: 767.98px) {
            .hotel-map-preview__frame {
                height: 150px;
            }

            .hotel-map-canvas {
                height: calc(100vh - 72px);
                min-height: 280px;
            }

            .hotel-map-modal .modal-content {
                border-radius: 0;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterToggle = document.getElementById('filterToggleIcon');
            const mobileFilters = document.getElementById('mobileFilters');

            if (mobileFilters) {
                mobileFilters.addEventListener('show.bs.collapse', function() {
                    if (filterToggle) filterToggle.classList.add('rotated');
                });

                mobileFilters.addEventListener('hide.bs.collapse', function() {
                    if (filterToggle) filterToggle.classList.remove('rotated');
                });
            }

            const mapModal = document.getElementById('mapModal');
            if (!mapModal || typeof L === 'undefined') {
                return;
            }

            const tileUrl = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}';
            const tileOptions = {
                attribution: 'Tiles &copy; Esri',
                maxZoom: 19
            };

            const hotelPinIcon = L.divIcon({
                className: 'hotel-map-pin',
                html: '<span class="hotel-map-pin__inner"></span>',
                iconSize: [22, 30],
                iconAnchor: [11, 28],
                popupAnchor: [0, -24]
            });

            const previewPinIcon = L.divIcon({
                className: 'hotel-map-pin hotel-map-pin--preview',
                html: '<span class="hotel-map-pin__inner"></span>',
                iconSize: [16, 22],
                iconAnchor: [8, 20]
            });

            const hotelMapI18n = {
                hotelFallback: @json(__('hotel.hotel')),
                address: @json(__('hotel.address')),
                starRating: @json(__('hotel.star_rating')),
                price: @json(__('hotel.price')),
                viewDetails: @json(__('hotel.view_details')),
                oneHotel: @json(__('hotel.one_hotel')),
                hotelsCount: @json(__('hotel.hotels_count')),
                noLocations: @json(__('hotel.no_locations')),
                zoomHint: @json(__('hotel.zoom_hint')),
                locationsAppear: @json(__('hotel.locations_appear')),
                na: @json(__('hotel.na'))
            };
            const hotelMapEndpoint = @json(route('hotel.map'));
            const modalMarkers = new Map();
            let initialMapPositionDone = false;
            let ignoreMoveEnd = false;
            let mapMoveTimer = null;
            let mapHotelsAbort = null;
            let mapHotelsRequestSeq = 0;

            function escapeHtml(value) {
                return String(value == null ? '' : value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function hotelMarkerId(hotel) {
                if (hotel.hotel_id) {
                    return String(hotel.hotel_id);
                }
                if (hotel.hid) {
                    return 'hid:' + String(hotel.hid);
                }
                return '';
            }

            function hotelsWithCoords() {
                const source = (window.mapHotels && Array.isArray(window.mapHotels))
                    ? window.mapHotels
                    : @json($hotels);
                const seen = new Set();
                const list = [];

                source.forEach(function(hotel) {
                    const lat = parseFloat(hotel.latitude);
                    const lng = parseFloat(hotel.longitude);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                        return;
                    }
                    const id = hotelMarkerId(hotel) || (lat + ',' + lng);
                    if (seen.has(id)) {
                        return;
                    }
                    seen.add(id);
                    list.push(hotel);
                });

                return list;
            }

            function hotelDetailUrl(hotel) {
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.delete('chunk');
                urlParams.set('id', hotel.hotel_id);

                return `/hotel/${hotel.hotel_id}?${urlParams.toString()}`;
            }

            function popupContent(hotel) {
                const name = escapeHtml(hotel.name || hotel.title || hotelMapI18n.hotelFallback);
                const address = escapeHtml(hotel.address || hotelMapI18n.na);
                const stars = escapeHtml(hotel.star_rating || hotelMapI18n.na);
                const price = hotel.daily_price ? ('€' + hotel.daily_price) : hotelMapI18n.na;
                const hotelUrl = hotelDetailUrl(hotel);

                return `
                    <div class="hotel-map-popup">
                        <h6 class="fw-bold mb-2">${name}</h6>
                        <p class="mb-1"><strong>${escapeHtml(hotelMapI18n.address)}:</strong> ${address}</p>
                        <p class="mb-1"><strong>${escapeHtml(hotelMapI18n.starRating)}:</strong> ${stars}</p>
                        <p class="mb-2"><strong>${escapeHtml(hotelMapI18n.price)}:</strong> ${price}</p>
                        <a href="${hotelUrl}" class="btn btn-primary btn-sm">${escapeHtml(hotelMapI18n.viewDetails)}</a>
                    </div>
                `;
            }

            function countLabel(count) {
                if (count === 1) {
                    return hotelMapI18n.oneHotel;
                }
                return hotelMapI18n.hotelsCount.replace(':count', count);
            }

            function createClusterGroup() {
                if (typeof L.markerClusterGroup === 'function') {
                    return L.markerClusterGroup({
                        showCoverageOnHover: false,
                        maxClusterRadius: function(zoom) {
                            return zoom < 8 ? 80 : zoom < 12 ? 55 : 40;
                        },
                        spiderfyOnMaxZoom: true,
                        disableClusteringAtZoom: 16
                    });
                }
                return L.layerGroup();
            }

            function addHotelTiles(map) {
                const layer = L.tileLayer(tileUrl, tileOptions);
                layer.on('tileerror', function() {
                    if (map._hotelTilesFallback) {
                        return;
                    }
                    map._hotelTilesFallback = true;
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                        maxZoom: 19
                    }).addTo(map);
                });
                layer.addTo(map);
            }

            function plotHotels(map, cluster, icon, withPopup, shouldFit) {
                cluster.clearLayers();
                const hotels = hotelsWithCoords();
                const bounds = L.latLngBounds();

                hotels.forEach(function(hotel) {
                    const lat = parseFloat(hotel.latitude);
                    const lng = parseFloat(hotel.longitude);
                    const marker = L.marker([lat, lng], { icon: icon });
                    if (withPopup) {
                        marker.bindPopup(popupContent(hotel));
                    }
                    cluster.addLayer(marker);
                    bounds.extend([lat, lng]);
                });

                if (shouldFit !== false) {
                    if (hotels.length === 1) {
                        map.setView(bounds.getCenter(), 13);
                    } else if (hotels.length > 1) {
                        map.fitBounds(bounds, { padding: [36, 36], maxZoom: 13 });
                    }
                }

                return hotels.length;
            }

            function updatePreviewCounts() {
                const count = hotelsWithCoords().length;
                const label = countLabel(count);
                document.querySelectorAll('[data-map-count]').forEach(function(el) {
                    el.textContent = count ? label : hotelMapI18n.noLocations;
                });
            }

            function updateModalCount(count) {
                const modalCount = document.getElementById('hotelMapCount');
                if (!modalCount) {
                    return;
                }
                modalCount.textContent = count
                    ? countLabel(count) + ' · ' + hotelMapI18n.zoomHint
                    : hotelMapI18n.locationsAppear;
            }

            function setMapLoading(isLoading) {
                const el = document.getElementById('hotelMapLoading');
                if (!el) {
                    return;
                }
                el.classList.toggle('d-none', !isLoading);
                el.setAttribute('aria-hidden', isLoading ? 'false' : 'true');
            }

            function upsertModalMarkers(hotels, pruneOutsideBounds) {
                if (!maps.modal.map || !maps.modal.cluster) {
                    return;
                }

                hotels.forEach(function(hotel) {
                    const id = hotelMarkerId(hotel);
                    if (!id || modalMarkers.has(id)) {
                        return;
                    }
                    const lat = parseFloat(hotel.latitude);
                    const lng = parseFloat(hotel.longitude);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                        return;
                    }
                    const marker = L.marker([lat, lng], { icon: hotelPinIcon });
                    marker.bindPopup(popupContent(hotel));
                    maps.modal.cluster.addLayer(marker);
                    modalMarkers.set(id, marker);
                });

                if (pruneOutsideBounds) {
                    const padded = maps.modal.map.getBounds().pad(0.2);
                    modalMarkers.forEach(function(marker, id) {
                        if (!padded.contains(marker.getLatLng())) {
                            maps.modal.cluster.removeLayer(marker);
                            modalMarkers.delete(id);
                        }
                    });
                }

                updateModalCount(modalMarkers.size);
            }

            const maps = {
                modal: { map: null, cluster: null },
                desktop: { map: null, cluster: null },
                mobile: { map: null, cluster: null }
            };

            function initMapInstance(key, elementId, options) {
                const el = document.getElementById(elementId);
                if (!el || maps[key].map) {
                    return maps[key];
                }
                if (!el.offsetWidth && !el.offsetHeight) {
                    return maps[key];
                }

                maps[key].map = L.map(elementId, options);
                addHotelTiles(maps[key].map);
                maps[key].cluster = createClusterGroup();
                maps[key].map.addLayer(maps[key].cluster);
                return maps[key];
            }

            const previewOptions = {
                zoomControl: false,
                attributionControl: false,
                dragging: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false
            };

            function refreshPreview(key, elementId, shouldFit) {
                const created = !maps[key].map;
                const instance = initMapInstance(key, elementId, previewOptions);
                if (!instance.map) {
                    return;
                }
                if (created || shouldFit) {
                    plotHotels(instance.map, instance.cluster, previewPinIcon, false, true);
                }
                instance.map.invalidateSize();
            }

            function refreshPreviewMaps(fitPreview) {
                refreshPreview('desktop', 'hotelMapPreviewDesktop', fitPreview);
                refreshPreview('mobile', 'hotelMapPreviewMobile', fitPreview);
                updatePreviewCounts();
            }

            function loadViewportHotels() {
                if (!maps.modal.map || ignoreMoveEnd || !initialMapPositionDone) {
                    return;
                }

                const bounds = maps.modal.map.getBounds();
                const params = new URLSearchParams({
                    north: bounds.getNorth(),
                    south: bounds.getSouth(),
                    east: bounds.getEast(),
                    west: bounds.getWest(),
                    zoom: maps.modal.map.getZoom()
                });

                if (mapHotelsAbort && typeof mapHotelsAbort.abort === 'function') {
                    mapHotelsAbort.abort();
                }

                const requestSeq = ++mapHotelsRequestSeq;
                const canAbort = typeof AbortController === 'function';
                const controller = canAbort ? new AbortController() : null;
                mapHotelsAbort = controller;

                setMapLoading(true);

                const fetchOptions = {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                };
                if (controller) {
                    fetchOptions.signal = controller.signal;
                }

                fetch(hotelMapEndpoint + '?' + params.toString(), fetchOptions)
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        if (requestSeq !== mapHotelsRequestSeq) {
                            return;
                        }
                        upsertModalMarkers(Array.isArray(data.hotels) ? data.hotels : [], true);
                    })
                    .catch(function(err) {
                        if (err && err.name === 'AbortError') {
                            return;
                        }
                    })
                    .finally(function() {
                        if (requestSeq === mapHotelsRequestSeq) {
                            setMapLoading(false);
                        }
                    });
            }

            function scheduleViewportLoad() {
                if (ignoreMoveEnd || !initialMapPositionDone) {
                    return;
                }
                if (mapMoveTimer) {
                    clearTimeout(mapMoveTimer);
                }
                mapMoveTimer = setTimeout(loadViewportHotels, 400);
            }

            function finishInitialMapPosition() {
                if (!ignoreMoveEnd) {
                    return;
                }
                ignoreMoveEnd = false;
                loadViewportHotels();
            }

            function applyInitialMapPosition(map) {
                const hotels = hotelsWithCoords();
                ignoreMoveEnd = true;
                upsertModalMarkers(hotels, false);

                if (hotels.length === 1) {
                    map.setView([
                        parseFloat(hotels[0].latitude),
                        parseFloat(hotels[0].longitude)
                    ], 13);
                } else if (hotels.length > 1) {
                    const bounds = L.latLngBounds();
                    hotels.forEach(function(hotel) {
                        bounds.extend([
                            parseFloat(hotel.latitude),
                            parseFloat(hotel.longitude)
                        ]);
                    });
                    map.fitBounds(bounds, { padding: [36, 36], maxZoom: 13 });
                } else {
                    map.setView([20, 0], 2);
                }

                initialMapPositionDone = true;
                map.once('moveend', finishInitialMapPosition);
                setTimeout(function() {
                    if (ignoreMoveEnd) {
                        finishInitialMapPosition();
                    }
                }, 500);
            }

            window.refreshHotelMapMarkers = function() {
                refreshPreviewMaps(true);
                if (maps.modal.map && initialMapPositionDone) {
                    upsertModalMarkers(hotelsWithCoords(), false);
                    maps.modal.map.invalidateSize();
                }
            };

            refreshPreviewMaps(true);
            setTimeout(function() {
                refreshPreviewMaps(true);
            }, 400);

            window.addEventListener('resize', function() {
                refreshPreview('desktop', 'hotelMapPreviewDesktop', false);
                refreshPreview('mobile', 'hotelMapPreviewMobile', false);
                if (maps.modal.map) {
                    maps.modal.map.invalidateSize();
                }
            });

            if (mobileFilters) {
                mobileFilters.addEventListener('shown.bs.collapse', function() {
                    refreshPreview('mobile', 'hotelMapPreviewMobile', true);
                });
            }

            document.querySelectorAll('.js-open-hotel-map').forEach(function(el) {
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (window.jQuery && typeof jQuery.fn.modal === 'function') {
                        jQuery('#mapModal').modal('show');
                    } else if (window.bootstrap && typeof bootstrap.Modal === 'function') {
                        bootstrap.Modal.getOrCreateInstance(mapModal).show();
                    }
                });
            });

            function onHotelMapModalShown() {
                if (!maps.modal.map) {
                    ignoreMoveEnd = true;
                    maps.modal.map = L.map('hotelMap', {
                        minZoom: 2,
                        maxZoom: 18,
                        zoomSnap: 0.5,
                        worldCopyJump: true
                    }).setView([20, 0], 2);
                    addHotelTiles(maps.modal.map);
                    maps.modal.cluster = createClusterGroup();
                    maps.modal.map.addLayer(maps.modal.cluster);
                    maps.modal.map.on('moveend', scheduleViewportLoad);
                }

                maps.modal.map.invalidateSize();

                if (!initialMapPositionDone) {
                    applyInitialMapPosition(maps.modal.map);
                }

                updateModalCount(modalMarkers.size || hotelsWithCoords().length);
            }

            mapModal.addEventListener('shown.bs.modal', onHotelMapModalShown);
            if (window.jQuery) {
                jQuery(mapModal).on('shown.bs.modal', onHotelMapModalShown);
            }
        });
    </script>
@endsection
