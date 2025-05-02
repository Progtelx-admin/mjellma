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
            <div class="col-md-3 mb-5 filter-section sticky-top" style="top: 1rem;">
                <h5 class="fw-bold">Filters</h5>
                <hr>
                <form method="GET" action="{{ route('hotel.search') }}">
                    <input type="hidden" name="hotel_name" value="{{ request('hotel_name') }}">
                    <input type="hidden" name="location" value="{{ request('location') }}">
                    <input type="hidden" name="checkin" value="{{ request('checkin') }}">
                    <input type="hidden" name="checkout" value="{{ request('checkout') }}">
                    <input type="hidden" name="adults" value="{{ request('adults') }}">
                    <input type="hidden" name="rooms" value="{{ request('rooms') }}">
                    <input type="hidden" name="latitude" value="{{ request('latitude') }}">
                    <input type="hidden" name="longitude" value="{{ request('longitude') }}">
                    <input type="hidden" name="children"
                        value="{{ is_array(request('children')) ? count(request('children')) : request('children', 0) }}">

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



                <!-- Button to Open Map Modal -->
                <button type="button" class="btn btn-success w-100 mt-3" data-bs-toggle="modal" data-bs-target="#mapModal">
                    Show on the Map
                </button>
            </div>

            <!-- Hotel List Section -->
            <div class="col-md-9">
                <div class="view-toggle mb-3 text-end">
                    <button id="list-view-btn" class="btn btn-outline-primary active"><i class="fa fa-list"></i></button>
                    <button id="grid-view-btn" class="btn btn-outline-secondary"><i class="fa fa-th"></i></button>
                </div>

                @if ($hotels && $hotels->count() > 0)
                    <div id="hotel-list">
                        @foreach ($hotels as $hotel)
                            <div class="card mb-4 hotel-list-item border-0 shadow-sm">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <a
                                            href="{{ route('hotel.info', ['id' => $hotel->hotel_id, 'checkin' => $checkin, 'checkout' => $checkout]) }}">
                                            <div class="hotel-image">
                                                <img src="{{ $hotel->image_url }}"
                                                    alt="{{ $hotel->name ?? 'No name provided' }}" class="img-fluid"
                                                    onerror="this.onerror=null;this.src='{{ asset('images/default-image.jpg') }}';">
                                            </div>
                                        </a>
                                    </div>
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
                                                    <i class="fa fa-star" style="color: #FCC737"></i>
                                                @endfor
                                            </p>
                                            <p class="text-muted">{{ $hotel->address ?? 'Address not available' }}</p>
                                            <p class="text-primary fw-semibold fs-5">
                                                Starting from <span
                                                    class="font-weight-bold">{{ $hotel->daily_price ?? __('Price not available') }}</span>
                                                / per night
                                            </p>
                                            <p class="text-success">Breakfast Included:
                                                {{ $hotel->has_breakfast ? 'Yes' : 'No' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div id="loading-spinner" class="my-3 text-center" style="display: none;">
                        <div class="spinner-border" role="status"></div>
                    </div>

                    <div class="text-center mb-5">
                        <button id="load-more-btn" class="btn btn-outline-primary">Load More</button>
                    </div>
                @else
                    <div class="col-12 text-center">
                        <p class="text-muted fs-5">{{ __('No hotels found.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hotelList = document.getElementById('hotel-list');
            const listBtn = document.getElementById('list-view-btn');
            const gridBtn = document.getElementById('grid-view-btn');

            listBtn.addEventListener('click', () => {
                hotelList.classList.remove('grid-view');
                listBtn.classList.add('active');
                gridBtn.classList.remove('active');
            });

            gridBtn.addEventListener('click', () => {
                hotelList.classList.add('grid-view');
                gridBtn.classList.add('active');
                listBtn.classList.remove('active');
            });
        });

        let page = 1;
        let loading = false;
        let hasMore = true;

        document.getElementById('load-more-btn')?.addEventListener('click', function() {
            if (loading || !hasMore) return;
            loading = true;
            page++;

            const url = new URL(window.location.href);
            url.searchParams.set('page', page);

            document.getElementById('loading-spinner').style.display = 'block';

            fetch(url.toString(), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.html) {
                        document.getElementById('hotel-list').insertAdjacentHTML('beforeend', data.html);
                    }
                    if (!data.hasMore) {
                        document.getElementById('load-more-btn')?.remove();
                    }
                })
                .catch(error => console.error('Load More Failed', error))
                .finally(() => {
                    loading = false;
                    document.getElementById('loading-spinner').style.display = 'none';
                });
        });
    </script>

    <style>
        .hotel-list-item {
            display: flex;
            border-radius: 8px;
        }

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

        #hotel-list.grid-view {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        #hotel-list.grid-view .hotel-list-item {
            flex-direction: column;
            flex: 0 0 calc(33.3333% - 20px);
            max-width: calc(33.3333% - 20px);
            margin: 0;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        #hotel-list.grid-view .row.g-0 {
            display: flex;
            flex-direction: column;
            margin: 0;
        }

        #hotel-list.grid-view .col-md-4,
        #hotel-list.grid-view .col-md-8 {
            width: 100%;
            max-width: 100%;
            flex: none;
        }

        #hotel-list.grid-view .hotel-image {
            height: 200px;
            border-bottom: 1px solid #ddd;
        }

        #hotel-list.grid-view .card-body {
            padding: 15px;
            background-color: #fff;
        }

        @media (max-width: 992px) {
            #hotel-list.grid-view .hotel-list-item {
                flex: 0 0 calc(50% - 20px);
                max-width: calc(50% - 20px);
            }
        }

        @media (max-width: 576px) {
            #hotel-list.grid-view .hotel-list-item {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
    </style>
@endsection
