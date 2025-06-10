@extends('layouts.app')

@section('content')
    <div id="carouselBackground"
        class="carousel slide carousel-fade container-fluid p-0 position-relative min-vh-100 d-flex justify-content-center align-items-center"
        data-bs-ride="carousel" data-bs-interval="4000">
        <div class="carousel-inner w-100 h-100">
            <div class="carousel-item active"
                style="background-image: url('https://images.bubbleup.com/width1920/quality35/mville2017/1-brand/1-margaritaville.com/gallery-media/220803-compasshotel-medford-pool-73868-1677873697-78625-1694019828.jpg');">
            </div>
            <div class="carousel-item"
                style="background-image: url('https://digital.ihg.com/is/image/ihg/ihgor-member-rate-web-offers-1440x720');">
            </div>
            <div class="carousel-item"
                style="background-image: url('https://hoteldel.com/wp-content/uploads/2021/01/hotel-del-coronado-views-suite-K1TOS1-K1TOJ1-1600x900-1.jpg');">
            </div>
        </div>

        <div class="carousel-indicators">
            <button type="button" data-bs-target="#carouselBackground" data-bs-slide-to="0" class="active"
                aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#carouselBackground" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#carouselBackground" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>

        <div class="w-75 position-absolute" style="z-index: 1">
            <h1 class="text-white fw-bold mb-4 text-center" style="text-shadow:2px 2px 5px rgba(0,0,0,0.7);">
                Escape To Paradise, Unwind In Luxury
            </h1>
            <div class="p-4 rounded shadow-sm bg-white">
                <ul class="nav nav-tabs mb-3" id="searchTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="flight-tab" data-bs-toggle="tab" href="#flight" role="tab"
                            aria-controls="flight" aria-selected="false"><i class="fa fa-plane"></i> Flights</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="hotel-tab" data-bs-toggle="tab" href="#hotel" role="tab"
                            aria-controls="hotel" aria-selected="true"><i class="fa fa-hotel"></i> Hotels</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-car"></i> Cars</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade" id="flight" role="tabpanel" aria-labelledby="flight-tab">
                        <div id="thomalex-widget"
                            data-widget="https://mjellmatravel.resvoyage.com/widget/index?widgetId=b6f09e37-6e72-43cc-9da6-583d693a12fb&lang="
                            style="height:400px;"></div>
                        <script src="https://mjellmatravel.resvoyage.com/scripts/thomalex-integration.js"></script>
                    </div>

                    <div class="tab-pane fade show active p-4 rounded shadow-sm bg-white" id="hotel" role="tabpanel"
                        aria-labelledby="hotel-tab">
                        <form method="GET" action="{{ route('hotel.search') }}">
                            @csrf

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
                            <div class="row g-3 mb-3">
                                <div class="col-md-6 position-relative">
                                    <label for="hotel_name" class="form-label">Hotel Name</label>
                                    <input type="text" id="hotel_name" name="hotel_name"
                                        class="form-control @error('hotel_name') is-invalid @enderror"
                                        placeholder="Enter hotel name" value="{{ old('hotel_name') }}">
                                    @error('hotel_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 position-relative">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" id="location" name="location"
                                        class="form-control @error('location') is-invalid @enderror"
                                        placeholder="Where are you going?" autocomplete="off">
                                    @error('location')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <ul id="suggestions" class="list-group position-absolute w-100 mt-1 d-none"
                                        style="max-height:200px;overflow-y:auto;z-index:1000;"></ul>
                                </div>
                            </div>

                            {{-- Row 2: Dates / Guests / Rooms / Children --}}
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label for="checkin" class="form-label">Check-in Date</label>
                                    <input type="date" id="checkin" name="checkin"
                                        class="form-control @error('checkin') is-invalid @enderror"
                                        value="{{ old('checkin') }}" required>
                                    @error('checkin')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label for="checkout" class="form-label">Check-out Date</label>
                                    <input type="date" id="checkout" name="checkout"
                                        class="form-control @error('checkout') is-invalid @enderror"
                                        value="{{ old('checkout') }}" required>
                                    @error('checkout')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-2">
                                    <label for="adults" class="form-label">Adults</label>
                                    <input type="number" id="adults" name="adults"
                                        class="form-control @error('adults') is-invalid @enderror"
                                        value="{{ old('adults', 1) }}" min="1" required>
                                    @error('adults')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-2">
                                    <label for="rooms" class="form-label">Rooms</label>
                                    <input type="number" id="rooms" name="rooms"
                                        class="form-control @error('rooms') is-invalid @enderror"
                                        value="{{ old('rooms', 1) }}" min="1" required>
                                    @error('rooms')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Children Input --}}
                                <div class="col-md-2">
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
                            <div id="children-ages-row" class="row g-3 mt-2" style="display:none;"></div>

                            {{-- Row 4: Full-width Search --}}
                            <div class="row mt-4">
                                <div class="col">
                                    <button type="submit" class="btn btn-primary w-100">Search</button>
                                </div>
                            </div>

                            <input type="hidden" id="latitude" name="latitude">
                            <input type="hidden" id="longitude" name="longitude">
                        </form>
                    </div>

                    <div class="tab-pane fade" id="car" role="tabpanel" aria-labelledby="car-tab">
                        <p>#</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Carousel init
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Carousel(document.getElementById('carouselBackground'), {
                interval: 2000,
                ride: 'carousel',
                pause: false,
                wrap: true
            });
        });

        // Location suggestions
        document.addEventListener("DOMContentLoaded", function() {
            const locationInput = document.getElementById("location");
            const suggestionsList = document.getElementById("suggestions");
            const latInput = document.getElementById("latitude");
            const lonInput = document.getElementById("longitude");

            locationInput.addEventListener("input", function() {
                const q = this.value;
                if (q.length < 3) {
                    suggestionsList.innerHTML = "";
                    suggestionsList.classList.add("d-none");
                    return;
                }
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${q}`)
                    .then(r => r.json()).then(data => {
                        suggestionsList.innerHTML = "";
                        suggestionsList.classList.toggle("d-none", data.length === 0);
                        data.forEach(loc => {
                            const li = document.createElement("li");
                            li.textContent = loc.display_name;
                            li.className = "list-group-item";
                            li.style.cursor = "pointer";
                            li.onclick = () => {
                                locationInput.value = loc.display_name;
                                latInput.value = loc.lat;
                                lonInput.value = loc.lon;
                                suggestionsList.innerHTML = "";
                                suggestionsList.classList.add("d-none");
                            };
                            suggestionsList.append(li);
                        });
                    }).catch(console.error);
            });
            document.addEventListener("click", e => {
                if (!suggestionsList.contains(e.target) && e.target !== locationInput) {
                    suggestionsList.innerHTML = "";
                    suggestionsList.classList.add("d-none");
                }
            });
        });

        // Children input & dynamic ages
        document.addEventListener('DOMContentLoaded', function() {
            const countInput = document.getElementById('children_count');
            const agesRow = document.getElementById('children-ages-row');

            function renderAges(n) {
                agesRow.innerHTML = '';
                if (n < 1) {
                    agesRow.style.display = 'none';
                    return;
                }
                agesRow.style.display = 'flex';
                for (let i = 1; i <= n; i++) {
                    const col = document.createElement('div');
                    col.className = 'col-md-2';
                    col.innerHTML = `
                      <div class="form-floating">
                        <select name="children[]" id="child_age_${i}"
                                class="form-select form-select-sm border border-2 border-danger" required>
                          <option value="" selected>Age needed</option>
                          ${[...Array(18).keys()].map(a => `<option value="${a}">${a}</option>`).join('')}
                        </select>
                        <label for="child_age_${i}" class="small">Child ${i}</label>
                      </div>`;
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

            renderAges(parseInt(countInput.value) || 0);
        });
    </script>

    <style>
        .carousel-item {
            background-size: cover;
            background-position: center;
            min-height: 100vh;
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
            background: #007bff;
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

        .btn-primary {
            background: #007bff;
            border: none;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .nav-tabs .nav-link.active {
            border-bottom: 3px solid #007bff;
            font-weight: bold;
        }

        .form-label {
            font-weight: bold;
        }
    </style>
@endsection
