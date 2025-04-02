@extends('layouts.app')

@section('content')
    <div class="container">
        <h3 class="text-center mt-5 fw-bold">
            @if (request('location'))
                Hotels near {{ request('location') }}
            @else
                Our Hotels
            @endif
        </h3>
        <hr class="w-25 mx-auto mb-4">

        <div class="row mt-4">
            <!-- Sidebar Filters -->
            <!-- NOTE: Use .sticky-top, and optional inline style for top offset -->
            <div class="col-md-3 mb-5 filter-section sticky-top" style="top: 1rem;">
                <h5 class="fw-bold">Filters</h5>
                <hr>
                <form method="GET" action="{{ route('hotel.search') }}">
                    <!-- Hidden inputs to preserve existing search parameters -->
                    <input type="hidden" name="hotel_name" value="{{ request('hotel_name') }}">
                    <input type="hidden" name="location" value="{{ request('location') }}">
                    <input type="hidden" name="checkin" value="{{ request('checkin') }}">
                    <input type="hidden" name="checkout" value="{{ request('checkout') }}">
                    <input type="hidden" name="adults" value="{{ request('adults') }}">
                    <input type="hidden" name="rooms" value="{{ request('rooms') }}">
                    <input type="hidden" name="latitude" value="{{ request('latitude') }}">
                    <input type="hidden" name="longitude" value="{{ request('longitude') }}">
                    @foreach (request('children', []) as $index => $child)
                        <input type="hidden" name="children[]" value="{{ $child }}">
                    @endforeach

                    <!-- Price Filter -->
                    <div class="mb-4">
                        <h6 class="fw-bold">Price Range</h6>
                        <div class="mb-2">
                            <label for="min_price" class="form-label">Min Price (€{{ $minPrice }})</label>
                            <input type="number" class="form-control" id="min_price" name="min_price"
                                value="{{ request('min_price') }}" placeholder="Min">
                        </div>
                        <div>
                            <label for="max_price" class="form-label">Max Price (€{{ $maxPrice }})</label>
                            <input type="number" class="form-control" id="max_price" name="max_price"
                                value="{{ request('max_price') }}" placeholder="Max">
                        </div>
                    </div>
                    <hr>

                    <!-- Star Rating Filter -->
                    <div class="mb-4">
                        <h6 class="fw-bold">Hotel Star</h6>
                        @for ($i = 5; $i >= 1; $i--)
                            <label class="custom-checkbox">
                                <input type="checkbox" name="star_rating[]" value="{{ $i }}"
                                    {{ is_array(request('star_rating')) && in_array($i, request('star_rating')) ? 'checked' : '' }}>
                                <span class="checkmark"></span>
                                <span class="star-icons">
                                    @for ($j = 1; $j <= $i; $j++)
                                        <i class="fa fa-star text-warning"></i>
                                    @endfor
                                </span>
                            </label>
                        @endfor
                    </div>
                    <hr>

                    <!-- Breakfast Included Filter -->
                    <div class="mb-4">
                        <h6 class="fw-bold">Hotel Service</h6>
                        <label class="custom-checkbox">
                            <input type="checkbox" id="breakfast_included" name="breakfast_included" value="1"
                                {{ request('breakfast_included') ? 'checked' : '' }}>
                            <span class="checkmark"></span>
                            Breakfast Included
                        </label>
                    </div>
                    <hr>

                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </form>

                <!-- ✅ Button to Open Map Modal -->
                <button type="button" class="btn btn-success w-100 mt-3" data-bs-toggle="modal" data-bs-target="#mapModal">
                    Show on the Map
                </button>
            </div>

            <!-- Hotel List Section -->
            <div class="col-md-9">
                @if ($hotels && $hotels->count() > 0)
                    <!-- Container where we append hotels -->
                    <div id="hotel-list">
                        @foreach ($hotels as $hotel)
                            <div class="card mb-4 hotel-list-item border-0 shadow-sm">
                                <div class="row g-0">
                                    <!-- Hotel Image -->
                                    <div class="col-md-4">
                                        <a
                                            href="{{ route('hotel.info', ['id' => $hotel->hotel_id, 'checkin' => $checkin, 'checkout' => $checkout]) }}">
                                            <div class="hotel-image">
                                                @if (!empty($hotel->image_url))
                                                    <img src="{{ $hotel->image_url }}"
                                                        alt="{{ $hotel->name ?? 'No name provided' }}" class="img-fluid"
                                                        onerror="this.onerror=null;this.src='{{ asset('images/default-image.jpg') }}';">
                                                @else
                                                    <div class="no-image-placeholder">No Image Available</div>
                                                @endif
                                            </div>
                                        </a>
                                    </div>

                                    <!-- Hotel Details -->
                                    <div class="col-md-8">
                                        <div class="card-body d-flex flex-column justify-content-between h-100">
                                            <h5 class="fw-bold text-primary">
                                                <a href="{{ route('hotel.info', ['id' => $hotel->hotel_id, 'checkin' => $checkin, 'checkout' => $checkout]) }}"
                                                    class="text-decoration-none">
                                                    {{ $hotel->name ?? 'No name available' }}
                                                </a>
                                            </h5>

                                            <p class="text-warning mb-2">
                                                @for ($i = 0; $i < floor($hotel->star_rating ?? 0); $i++)
                                                    <i class="fa fa-star" aria-hidden="true" style="color: #FCC737"></i>
                                                @endfor
                                            </p>

                                            <p class="text-muted">{{ $hotel->address ?? 'Address not available' }}</p>

                                            <p class="text-primary fw-semibold fs-5">
                                                Starting from <span
                                                    class="font-weight-bold">{{ $hotel->daily_price ?? __('Price not available') }}
                                                </span>
                                                / per night
                                            </p>

                                            <p class="text-success">
                                                Breakfast Included: {{ $hotel->has_breakfast ? 'Yes' : 'No' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Loading Spinner (hidden by default) -->
                    <div id="loading-spinner" class="my-3 text-center" style="display: none;">
                        <div class="spinner-border" role="status">
                        </div>
                    </div>

                    <!-- Sentinel element for infinite scroll -->
                    <div id="infinite-scroll-sentinel"></div>
                @else
                    <div class="col-12 text-center">
                        <p class="text-muted fs-5">{{ __('No hotels found.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ✅ Bootstrap Modal for Map -->
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

    <!-- Leaflet.js Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- ✅ Bootstrap JS + Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <script>
        /* -------------------------
         *  Map Modal Initialization
         * ------------------------- */
        let mapInitialized = false;

        document.addEventListener("DOMContentLoaded", function() {
            var mapModal = document.getElementById('mapModal');
            mapModal.addEventListener('shown.bs.modal', function() {
                if (!mapInitialized) {
                    initializeMap();
                    mapInitialized = true;
                }
            });
        });

        function initializeMap() {
            var map = L.map('hotelMap');

            // Load OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            var markers = [];
            var bounds = L.latLngBounds();

            @if ($hotels && $hotels->count() > 0)
                @foreach ($hotels as $hotel)
                    @if (!empty($hotel->latitude) && !empty($hotel->longitude))
                        var hotelUrl =
                            "{{ route('hotel.info', ['id' => $hotel->hotel_id, 'checkin' => $checkin, 'checkout' => $checkout]) }}";
                        var imageUrl = "{{ $hotel->image_url }}";
                        var defaultImage = "{{ asset('images/default-image.jpg') }}";

                        var popupContent = `
                            <div style="width: 200px; font-family: Arial, sans-serif; text-align: center;">
                                <img src="${imageUrl}" onerror="this.src='${defaultImage}'"
                                    style="width: 100%; height: 80px; object-fit: cover; border-radius: 5px;" />
                                <h6 style="margin: 5px 0;">
                                    <a href="${hotelUrl}" target="_blank" style="color: #007bff; font-weight: bold; text-decoration: none;">
                                        {{ $hotel->name }}
                                    </a>
                                </h6>
                                <p style="margin: 2px 0; font-size: 12px; color: #ffa500;">
                                    @for ($i = 0; $i < floor($hotel->star_rating ?? 0); $i++)
                                        <i class="fa fa-star" aria-hidden="true" style="color: #FCC737"></i>
                                    @endfor
                                </p>
                                <p style="margin: 2px 0; font-size: 13px; color: #28a745;">
                                    {{ $hotel->daily_price ?? 'N/A' }} EUR
                                </p>
                            </div>
                        `;

                        var marker = L.marker([{{ $hotel->latitude }}, {{ $hotel->longitude }}])
                            .addTo(map)
                            .bindPopup(popupContent);

                        markers.push(marker);
                        bounds.extend(marker.getLatLng());
                    @endif
                @endforeach

                if (markers.length > 0) {
                    map.fitBounds(bounds, {
                        padding: [40, 40]
                    });
                } else {
                    map.setView([42.6629, 21.1655], 13); // Default location if no markers
                }

                setTimeout(() => {
                    map.invalidateSize();
                }, 500);
            @endif
        }

        /* -------------------------
         *   Infinite Scrolling
         * ------------------------- */
        let currentPage = 1; // We assume page=1 loaded initially
        let isLoading = false; // Flag to prevent multiple simultaneous loads
        let hasMore = true; // We'll assume there's more until the server tells us otherwise

        const observerOptions = {
            root: null,
            rootMargin: "0px",
            threshold: 0.1
        };

        // Callback to load more hotels when the sentinel enters view
        const observerCallback = (entries) => {
            const sentinel = entries[0];
            if (sentinel.isIntersecting) {
                if (!isLoading && hasMore) {
                    loadMoreHotels();
                }
            }
        };

        // Create the intersection observer once DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            const sentinelElement = document.getElementById('infinite-scroll-sentinel');
            if (sentinelElement) {
                observer.observe(sentinelElement);
            }
        });

        function loadMoreHotels() {
            isLoading = true;
            currentPage += 1; // increment to next page

            // Show loading spinner
            document.getElementById('loading-spinner').style.display = 'block';

            // Build query from current URL (filters, etc.), set page=next
            const params = new URLSearchParams(window.location.search);
            params.set('page', currentPage);

            // Make AJAX request to the same route
            fetch("{{ route('hotel.search') }}?" + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Hide spinner
                    document.getElementById('loading-spinner').style.display = 'none';
                    isLoading = false;

                    // If the server returns hotels
                    if (data.hotels && data.hotels.length > 0) {
                        appendHotels(data.hotels);
                    } else {
                        // No more hotels
                        hasMore = false;
                    }
                })
                .catch(error => {
                    console.error("Error loading more hotels:", error);
                    // Hide spinner and stop further loads
                    document.getElementById('loading-spinner').style.display = 'none';
                    hasMore = false;
                    isLoading = false;
                });
        }

        function appendHotels(hotels) {
            const hotelList = document.getElementById('hotel-list');

            hotels.forEach(hotel => {
                const cardWrapper = document.createElement('div');
                cardWrapper.classList.add('card', 'mb-4', 'hotel-list-item', 'border-0', 'shadow-sm');

                cardWrapper.innerHTML = `
                    <div class="row g-0">
                        <div class="col-md-4">
                            <a href="{{ route('hotel.info', ['id' => '__ID__', 'checkin' => $checkin, 'checkout' => $checkout]) }}">
                                <div class="hotel-image">
                                    ${hotel.image_url ? `
                                                <img src="${hotel.image_url}" alt="${hotel.name || 'No name'}"
                                                     class="img-fluid"
                                                     onerror="this.onerror=null;this.src='{{ asset('images/default-image.jpg') }}';" />
                                            ` : `
                                                <div class="no-image-placeholder">No Image Available</div>
                                            `}
                                </div>
                            </a>
                        </div>
                        <div class="col-md-8">
                            <div class="card-body d-flex flex-column justify-content-between h-100">
                                <h5 class="fw-bold text-primary">
                                    <a href="{{ route('hotel.info', ['id' => '__ID__', 'checkin' => $checkin, 'checkout' => $checkout]) }}"
                                       class="text-decoration-none">
                                       ${hotel.name || 'No name available'}
                                    </a>
                                </h5>
                                <p class="text-warning mb-2">
                                    ${renderStars(hotel.star_rating)}
                                </p>
                                <p class="text-muted">${hotel.address ? hotel.address : 'Address not available'}</p>
                                <p class="text-primary fw-semibold fs-5">
                                    Starting from <span class="font-weight-bold">
                                        ${hotel.daily_price || 'Price not available'}
                                    </span> / per night
                                </p>
                                <p class="text-success">
                                    Breakfast Included: ${hotel.has_breakfast ? 'Yes' : 'No'}
                                </p>
                            </div>
                        </div>
                    </div>
                `;

                // Replace placeholder with actual hotel_id
                cardWrapper.innerHTML = cardWrapper.innerHTML.replaceAll('__ID__', hotel.hotel_id);

                hotelList.appendChild(cardWrapper);
            });
        }

        function renderStars(starRating) {
            let rating = starRating || 0;
            let stars = '';
            for (let i = 0; i < rating; i++) {
                stars += '<i class="fa fa-star" aria-hidden="true" style="color: #FCC737"></i>';
            }
            return stars;
        }
    </script>

    <style>
        /* Remove custom position: sticky from .filter-section
                   because .sticky-top does it automatically. */
        .filter-section {
            /* We only keep default styling; .sticky-top handles the sticky behavior. */
        }

        /* Example override: If you want more top offset (like 1rem)
                   you can add this:
                   .sticky-top {
                       top: 1rem !important;
                   }
                */

        /* Hotel List Item */
        .hotel-list-item {
            display: flex;
            border-radius: 8px;
        }

        /* Hotel Image */
        .hotel-image {
            height: 200px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hotel-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        /* Placeholder for missing images */
        .no-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: #6c757d;
            background-color: #e9ecef;
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        .filter-section .custom-checkbox {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 1rem;
            margin-bottom: 10px;
            user-select: none;
            position: relative;
        }

        /* Hide default checkbox */
        .filter-section .custom-checkbox input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        /* Custom checkmark */
        .filter-section .checkmark {
            height: 18px;
            width: 18px;
            background-color: #f0f0f0;
            border: 2px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
            position: relative;
            transition: all 0.2s ease;
        }

        .filter-section .custom-checkbox:hover .checkmark {
            background-color: #e6e6e6;
        }

        .filter-section .custom-checkbox input:checked~.checkmark {
            background-color: #007bff;
            border-color: #007bff;
        }

        .filter-section .checkmark::after {
            content: "";
            position: absolute;
            display: none;
            left: 5px;
            top: 1px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .filter-section .custom-checkbox input:checked~.checkmark::after {
            display: block;
        }

        .filter-section .star-icons {
            margin-left: 5px;
            display: flex;
        }

        /* Price Inputs */
        .filter-section .form-control {
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .filter-section .btn-primary {
            background-color: #0056b3;
            border: none;
        }

        .filter-section .btn-primary:hover {
            background-color: #004494;
        }

        @media (max-width: 768px) {
            .hotel-image {
                height: 150px;
            }
        }
    </style>
@endsection
