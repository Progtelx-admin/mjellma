@extends('layouts.app')

@section('content')
    <div id="carouselBackground"
        class="carousel slide carousel-fade container-fluid p-0 position-relative min-vh-100 d-flex justify-content-center align-items-end"
        data-ride="carousel" data-interval="4000">
        <div class="carousel-inner w-100 h-100">
            <div class="carousel-item active" style="background-image: url('{{ asset('uploads/1.jpg') }}');">
            </div>
            <div class="carousel-item" style="background-image: url('{{ asset('uploads/2.jpg') }}');">
            </div>
            <div class="carousel-item" style="background-image: url('{{ asset('uploads/3.jpg') }}');">
            </div>
            <div class="carousel-item" style="background-image: url('{{ asset('uploads/4.jpg') }}');">
            </div>
            <div class="carousel-item" style="background-image: url('{{ asset('uploads/5.jpg') }}');">
            </div>
        </div>


        <div class="carousel-indicators">
            <button type="button" data-target="#carouselBackground" data-slide-to="0" class="active" aria-current="true"
                aria-label="Slide 1"></button>
            <button type="button" data-target="#carouselBackground" data-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-target="#carouselBackground" data-slide-to="2" aria-label="Slide 3"></button>
            <button type="button" data-target="#carouselBackground" data-slide-to="3" aria-label="Slide 4"></button>
            <button type="button" data-target="#carouselBackground" data-slide-to="4" aria-label="Slide 5"></button>
        </div>

        <div class="w-90 w-md-90 position-absolute px-3 px-md-0 pb-5" style="z-index: 1">
            <h1 class="text-white fw-bold mb-3 mb-md-4 text-center"
                style="text-shadow:2px 2px 5px rgba(0,0,0,0.7); font-size: clamp(1.5rem, 5vw, 2.5rem);">
                Rezervo Heret, Blej Lirë
            </h1>
            <div class="p-3 rounded shadow-sm bg-white" style="opacity: 92%;">
                <ul class="nav nav-tabs mb-3 flex-nowrap" id="searchTabs" role="tablist"
                    style="overflow-x: auto; overflow-y: hidden;">
                    <li class="nav-item flex-fill" role="presentation">
                        <a class="nav-link text-center" id="flight-tab" data-bs-toggle="tab" href="#flight" role="tab"
                            aria-controls="flight" aria-selected="false"><i class="fa fa-plane"></i><span
                                class="d-none d-sm-inline"> Flights</span></a>
                    </li>
                    <li class="nav-item flex-fill" role="presentation">
                        <a class="nav-link active text-center" id="hotel-tab" data-bs-toggle="tab" href="#hotel"
                            role="tab" aria-controls="hotel" aria-selected="true"><i class="fa fa-hotel"></i><span
                                class="d-none d-sm-inline"> Hotels</span></a>
                    </li>
                    <li class="nav-item flex-fill" role="presentation">
                        <a class="nav-link text-center" id="car-tab" data-bs-toggle="tab" href="#car" role="tab"
                            aria-controls="car" aria-selected="false"><i class="fa fa-car"></i><span
                                class="d-none d-sm-inline"> Cars</span></a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade" id="flight" role="tabpanel" aria-labelledby="flight-tab">
                        <!-- Thomalex flight widget embedded as an iframe. Embedding directly avoids any external
                                                                                                                                                                                     JavaScript sizing logic and ensures the full desktop layout of the booking form is displayed. -->
                        <iframe
                            src="https://MjellmaTravel.resvoyage.com/widget/index?widgetId=b6f09e37-6e72-43cc-9da6-583d693a12fb&lang=en-US"
                            class="flight-widget-iframe" allowfullscreen></iframe>
                    </div>

                    <div class="tab-pane fade show active rounded" id="hotel" role="tabpanel"
                        aria-labelledby="hotel-tab">
                        <div id="dateErrorAlert" class="alert alert-danger d-none" role="alert">
                        </div>

                        <form id="hotel-search-form" method="GET" action="{{ route('hotel.search') }}">

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            {{-- Row 1: Hotel Name + Location --}}
                            <div class="row g-2 g-md-3 mb-2 mb-md-3">
                                <div class="col-12 col-md-6 position-relative">
                                    <label for="hotel_name" class="form-label">Hotel Name</label>
                                    <input type="text" id="hotel_name" name="hotel_name"
                                        class="form-control @error('hotel_name') is-invalid @enderror"
                                        placeholder="Enter hotel name" value="{{ old('hotel_name') }}"
                                        autocomplete="off">
                                    @error('hotel_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <ul id="hotel_suggestions"
                                        class="list-group position-absolute w-100 mt-1 d-none etg-autocomplete-list"
                                        style="max-height:200px;overflow-y:auto;z-index:1000;"></ul>
                                </div>
                                <div class="col-12 col-md-6 position-relative">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" id="location" name="location"
                                        class="form-control @error('location') is-invalid @enderror"
                                        placeholder="Where are you going?" autocomplete="off"
                                        value="{{ old('location') }}">
                                    @error('location')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <ul id="suggestions"
                                        class="list-group position-absolute w-100 mt-1 d-none etg-autocomplete-list"
                                        style="max-height:200px;overflow-y:auto;z-index:1000;"></ul>
                                </div>
                            </div>

                            {{-- Row 2: Dates / Guests / Rooms / Children --}}
                            <div class="row g-2 g-md-3 align-items-end">
                                <div class="col-6 col-md-3">
                                    <label for="checkin" class="form-label">Check-in Date</label>
                                    <input type="date" id="checkin" name="checkin"
                                        class="form-control @error('checkin') is-invalid @enderror"
                                        value="{{ old('checkin') }}" min="{{ date('Y-m-d') }}" required>
                                    @error('checkin')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-6 col-md-3">
                                    <label for="checkout" class="form-label">Check-out Date</label>
                                    <input type="date" id="checkout" name="checkout"
                                        class="form-control @error('checkout') is-invalid @enderror"
                                        value="{{ old('checkout') }}" min="{{ date('Y-m-d') }}" required>
                                    @error('checkout')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-4 col-md-2">
                                    <label for="adults" class="form-label">Adults</label>
                                    <input type="number" id="adults" name="adults"
                                        class="form-control @error('adults') is-invalid @enderror"
                                        value="{{ old('adults', 1) }}" min="1" required>
                                    @error('adults')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-4 col-md-2">
                                    <label for="rooms" class="form-label">Rooms</label>
                                    <input type="number" id="rooms" name="rooms"
                                        class="form-control @error('rooms') is-invalid @enderror"
                                        value="{{ old('rooms', 1) }}" min="1" required>
                                    @error('rooms')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Children Input --}}
                                <div class="col-4 col-md-2">
                                    <label for="children_count" class="form-label">Children</label>
                                    <input type="number" id="children_count" name="children_count"
                                        class="form-control @error('children_count') is-invalid @enderror"
                                        value="{{ old('children_count', 0) }}" min="0" max="5" required>
                                    @error('children_count')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Row 3: Dynamic Child Ages --}}
                            <div id="children-ages-row" class="row g-2 g-md-3 mt-2 mt-md-3" style="display:none;"></div>

                            {{-- Row 4: Full-width Search --}}
                            <div class="row mt-3 mt-md-4">
                                <div class="col d-flex justify-content-center">
                                    <button type="submit" id="search-btn"
                                        class="btn btn-blue w-50 w-sm-100 w-md-25 w-lg-15">
                                        <span id="search-text">Search</span>
                                        <span id="search-spinner" class="spinner-border spinner-border-sm ms-2 d-none"
                                            role="status" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>

                            <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}">
                            <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude') }}">
                            <input type="hidden" id="locationSelected" name="locationSelected"
                                value="{{ old('region_id') ? 'true' : 'false' }}">
                            <input type="hidden" id="hid" name="hid" value="{{ old('hid') }}">
                            <input type="hidden" id="etg_hotel_id" name="etg_hotel_id"
                                value="{{ old('etg_hotel_id') }}">
                            <input type="hidden" id="hotel_region_id" name="hotel_region_id"
                                value="{{ old('hotel_region_id') }}">
                            <input type="hidden" id="region_id" name="region_id" value="{{ old('region_id') }}">
                            <input type="hidden" id="region_type" name="region_type"
                                value="{{ old('region_type') }}">
                            <input type="hidden" id="region_country_code" name="region_country_code"
                                value="{{ old('region_country_code') }}">
                        </form>
                    </div>

                    <div class="tab-pane fade" id="car" role="tabpanel" aria-labelledby="car-tab">
                        @include('Car.Views.frontend.layouts.car-rental-strip', [
                            'action' => route('car.search'),
                            'submitText' => __('Vazhdoje rezervimin'),
                        ])
                    </div>


                </div>
            </div>
        </div>
    </div>

    <!-- First Offer Section (before About Us) -->
    @include('offers::public.index', [
        'anchor' => '#hotel-search-form',
        'cols' => 3,
        'limit' => 0,
        'only_first' => true,
    ])

    <!-- About Us -->
    <div id="about-us" class="section-about py-5">
        <div class="text-center">
            <h2 class="section-title mb-4">About Us</h2>

            <div class="shadow p-3 mb-5 bg-body">
                <div class="container">
                    <p class="mb-5 px-2 px-md-5">
                        Rezervo24, powered by MJELLMA TRAVEL, makes booking flights, hotels and car rentals fast and simple.
                        With local expertise and global connections, we offer reliable service, competitive prices and
                        friendly
                        24/7
                        support
                        to help you plan your next trip with confidence.
                    </p>

                    <div class="row align-items-start justify-content-center g-5">
                        <!-- IATA -->
                        <div class="col-10 col-md-4 text-center">
                            <div class="logo-wrap d-flex align-items-end justify-content-center">
                                <img src="{{ asset('uploads/iata.png') }}" alt="IATA"
                                    class="partner-logo img-fluid">
                            </div>
                            <small class="text-muted d-inline-block mt-2">(Accreditation No: 95-21245-6)</small>
                        </div>

                        <!-- Amadeus -->
                        <div class="col-10 col-md-4 text-center">
                            <div class="logo-wrap d-flex align-items-end justify-content-center">
                                <img src="{{ asset('uploads/amadeus.png') }}" alt="Amadeus"
                                    class="partner-logo img-fluid" style="margin-bottom: 20px;">
                            </div>
                            <small class="text-muted d-inline-block mt-2">(Partner ID: PRNK03320)</small>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <div class="section-offers py-5">
        <div class="container text-center">
            <h2 class="section-title mb-4">Our Services</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <img src="{{ asset('uploads/flight.png') }}" class="img-fluid rounded shadow" alt="Flight">
                    <h5 class="mt-3">Book a Flight</h5>
                </div>
                <div class="col-md-4">
                    <img src="{{ asset('uploads/hotel.png') }}" class="img-fluid rounded shadow" alt="hotel">
                    <h5 class="mt-3">Book a Hotel</h5>
                </div>
                <div class="col-md-4">
                    <img src="{{ asset('uploads/car.png') }}" class="img-fluid rounded shadow" alt="Rent a Car">
                    <h5 class="mt-3">Rent a Car</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Remaining Offer Sections (after About Us) -->
    @php
        use Modules\Offers\Models\OfferSection;
        $allSections = OfferSection::active()
            ->orderBy('sort_order')
            ->with(['cards' => fn($q) => $q->active()->orderBy('sort_order')])
            ->get();

        // Remove the first section (already shown above)
        $remainingSections = $allSections->slice(1);
    @endphp

    @foreach ($remainingSections as $section)
        @include('offers::front.section', [
            'section' => $section,
            'heading' => $section->title,
            'anchor' => '#hotel-search-form',
            'cols' => 3,
            'limit' => 0,
        ])
    @endforeach

    <!-- Bootstrap 4.6 CSS (keep this for styling) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Carousel - Bootstrap 4 syntax
            $('#carouselBackground').carousel({
                interval: 4000,
                ride: 'carousel',
                pause: false,
                wrap: true
            });

            const etgSuggestUrl = @json(route('hotel.suggestions'));
            const etgCountryNames = @json(get_country_lists());
            const etgAutocompleteClosers = [];
            const hotelNameInput = document.getElementById('hotel_name');
            const hotelSuggestionsList = document.getElementById('hotel_suggestions');
            const hidInput = document.getElementById('hid');
            const etgHotelIdInput = document.getElementById('etg_hotel_id');
            const hotelRegionIdInput = document.getElementById('hotel_region_id');

            const locationInput = document.getElementById('location');
            const suggestionsList = document.getElementById('suggestions');
            const locationSelectedInput = document.getElementById('locationSelected');
            const regionIdInput = document.getElementById('region_id');
            const regionTypeInput = document.getElementById('region_type');
            const regionCountryInput = document.getElementById('region_country_code');

            function bindEtgAutocomplete(config) {
                const input = config.input;
                const list = config.list;
                if (!input || !list) {
                    return;
                }
                const minChars = 1;
                const debounceMs = 300;
                let timer = null;
                let abortController = null;
                let seq = 0;
                let selectedLabel = (config.hasSelection && input.value) ? input.value.trim() : '';

                function hideList() {
                    list.innerHTML = '';
                    list.classList.add('d-none');
                }
                etgAutocompleteClosers.push(hideList);

                input.addEventListener('focus', function() {
                    etgAutocompleteClosers.forEach(function(closeFn) {
                        if (closeFn !== hideList) {
                            closeFn();
                        }
                    });
                });

                function showMessage(text) {
                    list.innerHTML = '';
                    const li = document.createElement('li');
                    li.className = 'list-group-item text-muted';
                    li.textContent = text;
                    list.appendChild(li);
                    list.classList.remove('d-none');
                }

                function uniqueItems(items, keyFn) {
                    const seen = new Set();
                    const out = [];
                    (items || []).forEach(function(item) {
                        const key = keyFn(item);
                        if (key === '' || key === 'undefined' || key === 'null' || seen.has(key)) {
                            return;
                        }
                        seen.add(key);
                        out.push(item);
                    });
                    return out;
                }

                function render(items) {
                    list.innerHTML = '';
                    items.forEach(function(item) {
                        const li = document.createElement('li');
                        li.className = 'list-group-item';
                        li.style.cursor = 'pointer';
                        li.textContent = config.label(item);
                        li.setAttribute('data-key', config.key(item));
                        li.addEventListener('click', function() {
                            selectedLabel = config.value(item);
                            input.value = selectedLabel;
                            input.classList.remove('is-invalid');
                            config.onSelect(item);
                            hideList();
                        });
                        list.appendChild(li);
                    });
                    list.classList.remove('d-none');
                }

                function fetchSuggestions(q) {
                    const thisSeq = ++seq;
                    if (abortController) {
                        abortController.abort();
                    }
                    abortController = new AbortController();
                    showMessage('Searching...');

                    const url = etgSuggestUrl +
                        '?query=' + encodeURIComponent(q) +
                        '&type=' + encodeURIComponent(config.type);

                    fetch(url, {
                        signal: abortController.signal,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }).then(function(res) {
                        return res.text().then(function(text) {
                            var data = {};
                            try {
                                data = text ? JSON.parse(text) : {};
                            } catch (parseErr) {
                                data = { error: true };
                            }
                            return { ok: res.ok, data: data };
                        });
                    }).then(function(result) {
                        if (thisSeq !== seq) {
                            return;
                        }
                        const data = result.data;
                        if (!result.ok || data.error) {
                            showMessage('Unable to load suggestions');
                            return;
                        }
                        const items = uniqueItems(config.pickItems(data), config.key);
                        if (!items.length) {
                            showMessage(config.emptyText);
                            return;
                        }
                        render(items);
                    }).catch(function(err) {
                        if (err && err.name === 'AbortError') {
                            return;
                        }
                        if (thisSeq !== seq) {
                            return;
                        }
                        showMessage('Unable to load suggestions');
                    });
                }

                input.addEventListener('input', function() {
                    const q = this.value.trim();
                    if (q !== selectedLabel) {
                        config.onClear();
                        selectedLabel = '';
                    }
                    if (timer) {
                        clearTimeout(timer);
                    }
                    if (abortController) {
                        abortController.abort();
                    }
                    if (q.length < minChars) {
                        hideList();
                        return;
                    }
                    timer = setTimeout(function() {
                        fetchSuggestions(q);
                    }, debounceMs);
                });

                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        hideList();
                    }
                });

                list.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                });

                document.addEventListener('click', function(e) {
                    if (!list.contains(e.target) && e.target !== input) {
                        hideList();
                    }
                });
            }

            bindEtgAutocomplete({
                input: hotelNameInput,
                list: hotelSuggestionsList,
                type: 'hotels',
                emptyText: 'No hotels found',
                hasSelection: !!(hidInput && hidInput.value),
                pickItems: function(data) {
                    return Array.isArray(data.hotels) ? data.hotels : [];
                },
                key: function(item) {
                    return String(item.hid || '');
                },
                label: function(item) {
                    return item.name || '';
                },
                value: function(item) {
                    return item.name || '';
                },
                onSelect: function(item) {
                    hidInput.value = item.hid || '';
                    etgHotelIdInput.value = item.id || '';
                    hotelRegionIdInput.value = item.region_id || '';
                },
                onClear: function() {
                    hidInput.value = '';
                    etgHotelIdInput.value = '';
                    hotelRegionIdInput.value = '';
                }
            });

            bindEtgAutocomplete({
                input: locationInput,
                list: suggestionsList,
                type: 'cities',
                emptyText: 'No cities found',
                hasSelection: !!(regionIdInput && regionIdInput.value),
                pickItems: function(data) {
                    const regions = Array.isArray(data.regions) ? data.regions : [];
                    return regions.filter(function(region) {
                        return String(region.type || '') === 'City';
                    });
                },
                key: function(item) {
                    return String(item.id != null ? item.id : '');
                },
                label: function(item) {
                    const code = String(item.country_code || '').toUpperCase();
                    const country = etgCountryNames[code] || '';
                    return [item.name, country]
                        .filter(function(part) { return part; })
                        .join(', ');
                },
                value: function(item) {
                    return item.name || '';
                },
                onSelect: function(item) {
                    regionIdInput.value = item.id != null ? item.id : '';
                    regionTypeInput.value = item.type || '';
                    regionCountryInput.value = item.country_code || '';
                    locationSelectedInput.value = 'true';
                },
                onClear: function() {
                    regionIdInput.value = '';
                    regionTypeInput.value = '';
                    regionCountryInput.value = '';
                    locationSelectedInput.value = 'false';
                }
            });

            // Children age inputs
            const countInput = document.getElementById('children_count');
            const agesRow = document.getElementById('children-ages-row');
            const initialChildAges = @json(old('children', request('children', [])));

            function ageLabel(age) {
                if (age === 0) return 'Under 1';
                return age === 1 ? '1 year' : age + ' years';
            }

            function currentAgeValues() {
                return Array.from(agesRow.querySelectorAll('select[name="children[]"]'))
                    .map(function(sel) { return sel.value; });
            }

            function renderAges(n, presetAges) {
                const previous = presetAges || currentAgeValues();
                agesRow.innerHTML = '';
                if (n < 1) {
                    agesRow.style.display = 'none';
                    return;
                }
                agesRow.style.display = 'flex';

                const heading = document.createElement('div');
                heading.className = 'col-12';
                heading.innerHTML = '<p class="form-label mb-1">Ages of children</p>';
                agesRow.append(heading);

                for (let i = 1; i <= n; i++) {
                    const selected = previous[i - 1] !== undefined && previous[i - 1] !== ''
                        ? String(previous[i - 1])
                        : '';
                    const col = document.createElement('div');
                    col.className = 'col-6 col-sm-4 col-md-2';
                    col.innerHTML =
                        '<label for="child_age_' + i + '" class="form-label">Child ' + i + '</label>' +
                        '<select name="children[]" id="child_age_' + i + '"' +
                        ' class="form-control child-age-select' + (selected === '' ? ' is-empty' : '') + '" required>' +
                        '<option value="" disabled' + (selected === '' ? ' selected' : '') + '>Select age</option>' +
                        [...Array(18).keys()].map(function(a) {
                            const isSel = selected === String(a) ? ' selected' : '';
                            return '<option value="' + a + '"' + isSel + '>' + ageLabel(a) + '</option>';
                        }).join('') +
                        '</select>';
                    const select = col.querySelector('select');
                    select.addEventListener('change', function() {
                        this.classList.toggle('is-empty', this.value === '');
                        this.classList.remove('is-invalid');
                    });
                    agesRow.append(col);
                }
            }

            countInput.addEventListener('input', function() {
                let v = parseInt(this.value);
                if (isNaN(v) || v < 0) v = 0;
                if (v > 5) v = 5;
                this.value = v;
                renderAges(v);
            });
            renderAges(parseInt(countInput.value) || 0, initialChildAges);

            // Date validation
            const checkinInput = document.getElementById('checkin');
            const checkoutInput = document.getElementById('checkout');
            const errorAlert = document.getElementById('dateErrorAlert');
            const today = new Date().toISOString().split('T')[0];
            checkinInput.min = today;
            checkoutInput.min = today;

            function validateDates() {
                const checkin = checkinInput.value;
                const checkout = checkoutInput.value;
                if (checkin && checkout) {
                    if (checkin === checkout) {
                        errorAlert.textContent = "Check-in and check-out dates cannot be the same.";
                        errorAlert.classList.remove('d-none');
                        return false;
                    } else {
                        errorAlert.classList.add('d-none');
                        errorAlert.textContent = '';
                        return true;
                    }
                }
                return true;
            }

            checkinInput.addEventListener('change', function() {
                checkoutInput.min = this.value;
                if (checkoutInput.value < this.value) {
                    checkoutInput.value = this.value;
                }
                validateDates();
            });

            checkoutInput.addEventListener('change', validateDates);

            // Final form validation and loading state
            const form = document.querySelector('form');
            const searchBtn = document.getElementById('search-btn');
            const searchText = document.getElementById('search-text');
            const searchSpinner = document.getElementById('search-spinner');

            if (form) {
                form.addEventListener('submit', function(e) {
                    // Clear previous errors
                    errorAlert.classList.add("d-none");
                    errorAlert.textContent = "";

                    // Remove invalid classes from all inputs
                    const allInputs = form.querySelectorAll('.form-control');
                    allInputs.forEach(input => {
                        input.classList.remove('is-invalid');
                    });

                    // Remove invalid feedback
                    const invalidFeedbacks = form.querySelectorAll('.invalid-feedback');
                    invalidFeedbacks.forEach(feedback => {
                        feedback.style.display = 'none';
                    });

                    // Validate required fields
                    const checkinInput = document.getElementById('checkin');
                    const checkoutInput = document.getElementById('checkout');
                    const adultsInput = document.getElementById('adults');
                    const roomsInput = document.getElementById('rooms');
                    const childrenCountInput = document.getElementById('children_count');

                    let hasErrors = false;
                    const errors = [];

                    // Check required fields
                    if (!checkinInput.value) {
                        checkinInput.classList.add('is-invalid');
                        errors.push('Check-in date is required.');
                        hasErrors = true;
                    }

                    if (!checkoutInput.value) {
                        checkoutInput.classList.add('is-invalid');
                        errors.push('Check-out date is required.');
                        hasErrors = true;
                    }

                    if (!adultsInput.value || adultsInput.value < 1) {
                        adultsInput.classList.add('is-invalid');
                        errors.push('Number of adults is required.');
                        hasErrors = true;
                    }

                    if (!roomsInput.value || roomsInput.value < 1) {
                        roomsInput.classList.add('is-invalid');
                        errors.push('Number of rooms is required.');
                        hasErrors = true;
                    }

                    if (childrenCountInput.value === '' || childrenCountInput.value < 0) {
                        childrenCountInput.classList.add('is-invalid');
                        errors.push('Number of children is required.');
                        hasErrors = true;
                    }

                    const childAgeSelects = form.querySelectorAll('select[name="children[]"]');
                    childAgeSelects.forEach(function(select, index) {
                        if (select.value === '') {
                            select.classList.add('is-invalid');
                            errors.push('Please select the age for Child ' + (index + 1) + '.');
                            hasErrors = true;
                        }
                    });

                    // Validate location if provided
                    const locationValue = locationInput.value.trim();
                    const hasRegionId = regionIdInput && regionIdInput.value !== '';
                    const locationSelected = locationSelectedInput.value === "true" || hasRegionId;

                    if (locationValue !== "" && !locationSelected) {
                        locationInput.classList.add('is-invalid');
                        errors.push('Please select a location from the suggestions.');
                        hasErrors = true;
                    }

                    // Validate dates
                    const datesValid = validateDates();
                    if (!datesValid) {
                        hasErrors = true;
                    }

                    if (hasErrors) {
                        e.preventDefault();

                        // Show error message
                        if (errors.length > 0) {
                            errorAlert.innerHTML = '<ul class="mb-0"><li>' + errors.join('</li><li>') +
                                '</li></ul>';
                            errorAlert.classList.remove("d-none");
                        }

                        // Scroll to first error
                        const firstInvalid = form.querySelector('.is-invalid');
                        if (firstInvalid) {
                            firstInvalid.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                            firstInvalid.focus();
                        }

                        return;
                    }

                    // Show loading spinner
                    if (searchBtn && searchText && searchSpinner) {
                        searchBtn.disabled = true;
                        searchText.textContent = "Searching...";
                        searchSpinner.classList.remove("d-none");
                    }
                });
            }

            /*
             * Simple tab switching logic for the search form.
             *
             * Bootstrap's JavaScript may not be loaded in this environment, so we manually handle
             * the activation of the tab navigation. When a tab link is clicked, we prevent
             * the default anchor behaviour, update the active classes on both the navigation
             * link and the corresponding tab pane, and for the flight tab we dynamically
             * load the Thomalex widget script the first time it is opened.
             */
            const tabLinks = document.querySelectorAll('#searchTabs .nav-link[href^="#"]');
            const tabPanes = document.querySelectorAll('.tab-content .tab-pane');
            // No external script is needed when embedding the Thomalex widget directly as an iframe.
            tabLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    if (!href || href === '#' || href.length <= 1) return;
                    e.preventDefault();
                    // Update active state on nav links
                    tabLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    // Switch visible tab pane
                    tabPanes.forEach(pane => pane.classList.remove('show', 'active'));
                    const target = document.querySelector(href);
                    if (target) {
                        target.classList.add('show', 'active');
                    }
                    // No action required for the flight tab here because the widget is embedded as an iframe
                    // and does not rely on any external script to render.
                });
            });
            // If the page is loaded with a fragment identifier (#flight), automatically open the flight tab
            if (window.location.hash === '#flight') {
                const flightLink = document.querySelector('#searchTabs .nav-link[href="#flight"]');
                if (flightLink) {
                    flightLink.click();
                }
            }
        });
    </script>

    <script>
        // Smooth-scroll only for real in-page anchors, not tabs or "#"
        document.addEventListener("click", function(e) {
            const link = e.target.closest('a[href^="#"]');
            if (!link) return;

            // Ignore Bootstrap tabs and other BS toggles
            if (link.hasAttribute("data-bs-toggle")) return;

            const href = link.getAttribute("href");
            if (!href || href === "#" || href.length <= 1) return;

            const target = document.querySelector(href);
            if (!target) return; // no scroll; avoids TypeError

            e.preventDefault();
            target.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });
        });
    </script>



    <style>
        /* Responsive width utilities */
        .w-md-95 {
            width: 95% !important;
        }

        .w-lg-85 {
            width: 85% !important;
        }

        .w-sm-75 {
            width: 75% !important;
        }

        .w-md-50 {
            width: 50% !important;
        }

        /* Mobile adjustments */
        @media (max-width: 575.98px) {

            .w-sm-75,
            .w-md-50,
            .w-lg-25 {
                width: 100% !important;
            }

            .form-label {
                font-size: 0.9rem;
            }

            .nav-link {
                font-size: 0.85rem;
                padding: 0.5rem 0.80rem;
            }
        }

        /* Navigation tabs overflow fix */
        .nav-tabs {
            overflow-y: hidden !important;
            border-bottom: 1px solid #dee2e6;
        }

        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            white-space: nowrap;
        }

        .nav-tabs .nav-link:hover {
            border-color: transparent;
            border-bottom-color: #0B0B45;
        }

        @media (min-width: 768px) and (max-width: 991.98px) {
            .w-md-95 {
                width: 95% !important;
            }
        }

        @media (min-width: 992px) {
            .w-lg-85 {
                width: 85% !important;
            }
        }

        .carousel-item {
            background-size: cover;
            background-position: center;
            min-height: 100vh;
        }

        @media (max-width: 767.98px) {
            .carousel-item {
                background-position: center;
                min-height: 100vh;
            }

            #carouselBackground {
                align-items: flex-end !important;
                padding-bottom: 20px;
            }
        }

        .carousel-item img {
            display: none;
        }

        .carousel-indicators button {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.7);
        }

        .carousel-indicators .active {
            background: #F27625;
        }

        .carousel-indicators {
            bottom: 20px;
        }

        .bg-white {
            background: rgba(255, 255, 255, 0.9);
        }

        h1 {
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
        }

        .btn-color {
            background-color: #F27625;
            border: none;
            color: white;
        }

        .btn-blue {
            background-color: #0B0B45;
            color: white;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 0.375rem;
            transition: all 0.3s ease;
        }

        .btn-blue:hover {
            color: white;
            background-color: #060630;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 11, 69, 0.3);
        }

        .btn-blue:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-blue .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }

        .nav-tabs .nav-link.active {
            border-bottom: 3px solid #0B0B45F0;
            font-weight: bold;
            color: #0B0B45F0;
        }

        .form-label {
            font-weight: bold;
        }

        /* Child age selects — match other form-control fields */
        #children-ages-row .child-age-select {
            display: block;
            width: 100%;
            height: calc(1.5em + 0.75rem + 2px);
            padding: 0.375rem 2rem 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23495057' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 12px 8px;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        #children-ages-row .child-age-select.is-empty {
            color: #6c757d;
        }

        #children-ages-row .child-age-select:focus {
            color: #495057;
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        #children-ages-row .child-age-select:invalid {
            box-shadow: none;
            border-color: #ced4da;
        }

        #children-ages-row .child-age-select.is-invalid,
        #children-ages-row .child-age-select.is-invalid:invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        /* Form control responsiveness */
        @media (max-width: 767.98px) {

            .form-control,
            .form-select {
                font-size: 0.9rem;
            }

            input[type="date"] {
                font-size: 0.85rem;
            }
        }

        /* Tab content responsive padding */
        .tab-pane {
            overflow-x: hidden;
        }

        /* Suggestions list responsive */
        .etg-autocomplete-list,
        #suggestions,
        #hotel_suggestions {
            font-size: 0.9rem;
        }

        @media (max-width: 575.98px) {
            .etg-autocomplete-list,
            #suggestions,
            #hotel_suggestions {
                font-size: 0.85rem;
            }
        }

        .section-title {
            position: relative;
            display: inline-block;
            color: #0B0B45;
        }

        .section-title::after {
            content: "";
            display: block;
            width: 140px;
            height: 3px;
            background: #F27625;
            margin: 10px auto 0;
            border-radius: 2px;
        }

        .partner-logo {
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, .08));
        }

        .deals-title {
            color: #0B0B45;
            font-weight: 700;
            letter-spacing: .3px;
        }

        .deals-title::after {
            content: "";
            display: block;
            width: 260px;
            height: 3px;
            background: #F27625;
            margin: 12px auto 0;
            border-radius: 2px;
        }

        .deal-img {
            aspect-ratio: 4 / 3;
            object-fit: cover;
            display: block;
        }

        .deal-card::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 42%;
            pointer-events: none;
        }

        .deal-caption {
            position: absolute;
            left: 22px;
            bottom: 18px;
            color: #fff;
            font-weight: 700;
            font-size: clamp(1.1rem, 1.6vw + .6rem, 1.8rem);
            text-shadow: 0 2px 10px rgba(0, 0, 0, .45);
            z-index: 1;
        }

        .btn-cta {
            background: #F27625;
            color: #fff;
            border: none;
            border-radius: .35rem;
        }

        .btn-cta:hover {
            color: #fff;
            background: #d5651d;
        }

        .logo-wrap {
            height: 100px;
        }

        .partner-logo {
            max-height: 80px;
            width: auto;
        }

        /* Validation styles */
        .form-control.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
            border-radius: 0.375rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }

        .alert-danger ul {
            margin-bottom: 0;
        }

        /* Flight widget iframe responsive styles */
        .flight-widget-iframe {
            width: 100%;
            min-width: 780px;
            height: 550px;
            border: none;
        }

        @media (max-width: 767.98px) {
            .flight-widget-iframe {
                min-width: unset;
                width: 100%;
                height: 600px;
            }
        }
    </style>
@endsection
