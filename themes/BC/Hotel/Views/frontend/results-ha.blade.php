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
                 FILTERS
            ================================================================= --}}
            <div class="col-md-3 mb-5 filter-section sticky-top" style="top:1rem;">
                <h5 class="fw-bold">Filters</h5>
                <hr>

                <form method="GET" action="{{ route('hotel.search') }}">
                    {{-- Preserve query --}}
                    <input type="hidden" name="hotel_name" value="{{ request('hotel_name') }}">
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
                            <input type="checkbox" id="breakfast_included" name="breakfast_included" value="1"
                                @if (request('breakfast_included')) checked @endif>
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
                 RESULTS
            ================================================================= --}}
            <div class="col-md-9">
                {{-- Header --}}
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <span class="text-muted">Among <span
                                class="fw-semibold">{{ $totalHotels ?? $hotels->count() }}</span> accommodations</span>
                    </div>

                    {{-- View toggle --}}
                    <div class="view-toggle d-flex">
                        <button id="extended-view-btn" type="button" class="view-tab active">
                            <i class="fa fa-list me-2"></i> Extended
                        </button>
                        <button id="compact-view-btn" type="button" class="view-tab ms-4">
                            <i class="fa fa-th me-2"></i> Compact
                        </button>
                    </div>
                </div>

                @if ($hotels->count())
                    <div id="hotel-list" class="extended-view">
                        @foreach ($hotels as $hotel)
                            @php
                                $query = array_merge(
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
                                    ['children' => request('children', [])],
                                );

                                $currencySym = match (request('currency', 'EUR')) {
                                    'USD' => '$',
                                    'GBP' => '£',
                                    'EUR' => '€',
                                    default => request('currency', '€'),
                                };
                            @endphp

                            <a href="{{ route('hotel.info', $query) }}" class="text-decoration-none hotel-card-link">
                                <article class="hotel-listcard">
                                    {{-- IMAGE --}}
                                    <div class="hotel-listcard__media">
                                        @if ($hotel->image_url)
                                            <img src="{{ $hotel->image_url }}" alt="{{ $hotel->name }}">
                                        @else
                                            <span class="no-image">No image available</span>
                                        @endif

                                        {{-- grid-only price badge --}}
                                        @if ($hotel->daily_price)
                                            <span class="grid-price-badge">
                                                {{ number_format($hotel->daily_price, 0) }}{{ $currencySym }}
                                            </span>
                                        @endif
                                    </div>


                                    {{-- CONTENT --}}
                                    <div class="hotel-listcard__content">
                                        <div class="mb-2">
                                            <h4 class="hotel-listcard__title mb-1">{{ $hotel->name }}</h4>
                                            <div class="hotel-listcard__stars">
                                                @for ($i = 0; $i < floor($hotel->star_rating ?? 0); $i++)
                                                    <i class="fa fa-star"></i>
                                                @endfor
                                            </div>
                                        </div>



                                        <div class="hotel-listcard__location mb-3">
                                            {{ \Illuminate\Support\Str::before($hotel->address ?? '', ',') }}
                                            @if (!empty($hotel->distance_from_center))
                                                – <span>{{ $hotel->distance_from_center }} from Center</span>
                                            @endif
                                        </div>

                                        <p class="hotel-listcard__desc mb-4">
                                            With a stay at {{ \Illuminate\Support\Str::limit($hotel->name, 28) }}, you’ll
                                            be centrally located…
                                        </p>

                                        {{-- LIST-ONLY footer (left perks / right price+btn) --}}
                                        <div class="hotel-listcard__footer">
                                            <div class="hotel-listcard__perk">
                                                @if ($hotel->has_breakfast)
                                                    Breakfast included
                                                @endif
                                            </div>

                                            <div class="d-flex flex-column align-items-end gap-2">
                                                <div class="hotel-listcard__price">
                                                    @if ($hotel->daily_price)
                                                        From {{ number_format($hotel->daily_price, 0) }}
                                                        {{ $currencySym }} / night
                                                    @else
                                                        No rooms available
                                                    @endif
                                                </div>
                                                <span class="btn btn-cta">See options</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- GRID-ONLY footer bar --}}
                                    <div class="grid-see-footer">See options</div>
                                </article>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-center text-muted fs-5">No hotels found.</p>
                @endif
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
            const list = document.getElementById('hotel-list');
            const extendedBtn = document.getElementById('extended-view-btn');
            const compactBtn = document.getElementById('compact-view-btn');

            function setView(mode) {
                list.classList.toggle('extended-view', mode === 'extended');
                list.classList.toggle('compact-view', mode === 'compact');
                extendedBtn.classList.toggle('active', mode === 'extended');
                compactBtn.classList.toggle('active', mode === 'compact');
            }

            extendedBtn.addEventListener('click', () => setView('extended'));
            compactBtn.addEventListener('click', () => setView('compact'));
        });
    </script>

    {{-- Styles --}}
    <style>
        /* Toggle buttons */
        .view-tab {
            background: none;
            border: none;
            padding: 0;
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #0d1b50;
            display: flex;
            align-items: center;
            gap: .45rem;
            position: relative;
            cursor: pointer;
        }

        .view-tab i {
            color: #ff7a1a;
            font-size: 1.15rem;
        }

        .view-toggle .view-tab+.view-tab {
            margin-left: 2rem;
        }

        .view-tab.active::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: -6px;
            width: 100%;
            height: 3px;
            background: #ff7a1a;
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

        /* Compact grid */
        #hotel-list.compact-view {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 28px;

            /* Ensure grid items stretch */
            align-items: stretch;
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

        /* Responsive */
        @media (max-width: 1200px) {
            #hotel-list.compact-view {
                grid-template-columns: repeat(2, 1fr);
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

        @media (max-width: 575.98px) {
            #hotel-list.compact-view {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
