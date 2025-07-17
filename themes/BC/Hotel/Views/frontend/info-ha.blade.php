@extends('layouts.app')

@section('content')
    <div class="container mt-5">

        {{-- Back to Results --}}
        <div class="mb-4">
            <a href="{{ route(
                'hotel.search',
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
                    'children_count',
                    'children',
                ]),
            ) }}"
                class="btn btn-outline-secondary">
                ← Back to Results
            </a>
        </div>

        {{-- Hotel Header --}}
        <div class="hotel-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold">{{ $hotel['name'] }}</h1>
                <p class="text-muted"><i class="fa fa-map-marker"></i> {{ $hotel['address'] }}</p>
                <p class="star-rating">
                    @for ($i = 0; $i < ($hotel['star_rating'] ?? 0); $i++)
                        <i class="fa fa-star text-warning"></i>
                    @endfor
                </p>
                <p>
                    <strong>Check-in Date:</strong> {{ $checkin }}
                    &nbsp;|&nbsp;
                    <strong>Check-out Date:</strong> {{ $checkout }}
                </p>
            </div>
        </div>

        {{-- Custom Image Slider --}}
        @if (!empty($hotel['images_ext']))
            <div class="custom-slider mb-5" id="customImageSlider">
                <div class="slides">
                    @foreach ($hotel['images_ext'] as $idx => $img)
                        <img src="{{ $img }}" alt="Hotel image {{ $idx + 1 }}"
                            class="slide-img {{ $idx === 0 ? 'active' : '' }}">
                    @endforeach
                </div>
                <button class="slider-btn prev-btn" onclick="changeSlide(-1)">‹</button>
                <button class="slider-btn next-btn" onclick="changeSlide(1)">›</button>
            </div>
        @endif


        {{-- Additional Policy Information --}}
        @if (!empty($hotel['metapolicy_extra_info']))
            <div class="mb-5">
                <h4>Additional Policy Information</h4>
                <p>{!! nl2br(e(str_replace(["\\r\\n", "\\n"], "\n", $hotel['metapolicy_extra_info']))) !!}</p>
            </div>
        @endif


        {{-- Available Rooms --}}
        <h3 class="mb-4">Available Rooms</h3>
        @forelse($roomRates as $rate)
            <div class="card mb-4 room-card shadow-sm">
                <div class="row g-0">
                    <div class="col-md-6">
                        <div class="card-body">
                            <h5 class="fw-bold">{{ $rate['room_name'] }}</h5>
                            <p>Meal: {{ ucfirst($rate['meal'] ?? 'N/A') }}</p>
                            <p>Breakfast: {{ $rate['meal_data']['has_breakfast'] ? 'Yes' : 'No' }}</p>
                        </div>
                    </div>
                    <div class="col-md-6 text-end p-3">
                        @php
                            $payment = $rate['payment_options']['payment_types'][0] ?? [];
                            $onSiteTaxes = collect(data_get($payment, 'tax_data.taxes', []))->where(
                                'included_by_supplier',
                                false,
                            );
                            $policies = data_get($payment, 'cancellation_penalties.policies', []);
                            $net = data_get($payment, 'commission_info.charge.amount_net', $payment['amount'] ?? 0);
                            $comm = data_get($payment, 'commission_info.charge.amount_commission', null);
                            $currency = $payment['currency_code'] ?? '';
                        @endphp

                        @php
                            $finalPrice = $net + ($comm ?? 0);
                        @endphp

                        <h4 class="text-primary">Total: {{ number_format((float) $finalPrice, 2) }} {{ $currency }}
                        </h4>

                        @if (!is_null($comm))
                            <p class="text-muted mb-0"><small>Net: {{ number_format((float) $net, 2) }}
                                    {{ $currency }}</small></p>
                            <p class="text-muted"><small>Commission: {{ number_format((float) $comm, 2) }}
                                    {{ $currency }}</small></p>
                        @endif

                        @if ($onSiteTaxes->isNotEmpty())
                            <div class="mt-2 text-start">
                                <strong>On-site Taxes:</strong>
                                <ul class="list-unstyled mb-0">
                                    @foreach ($onSiteTaxes as $tax)
                                        <li>{{ ucwords(str_replace('_', ' ', $tax['name'])) }}:
                                            {{ number_format((float) $tax['amount'], 2) }} {{ $tax['currency_code'] }}
                                            <small class="text-muted">(to be paid at hotel)</small>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (!empty($policies))
                            <div class="mt-2 text-start">
                                <strong>Cancellation Policies (UTC+0):</strong>
                                <ul class="list-unstyled mb-0">
                                    @foreach ($policies as $p)
                                        @php
                                            $start = $p['start_at']
                                                ? \Carbon\Carbon::parse($p['start_at'])->utc()
                                                : null;
                                            $end = $p['end_at'] ? \Carbon\Carbon::parse($p['end_at'])->utc() : null;
                                            $fee = number_format($p['amount_show'] ?? ($p['amount_charge'] ?? 0), 2);
                                        @endphp
                                        @if (is_null($start) && $end)
                                            <li>Free cancellation until <strong>{{ $end->format('Y-m-d H:i') }}
                                                    UTC</strong>.</li>
                                        @elseif($start && $end)
                                            <li>From <strong>{{ $start->format('Y-m-d H:i') }} UTC</strong> to
                                                <strong>{{ $end->format('Y-m-d H:i') }} UTC</strong>: Fee
                                                {{ $fee }} {{ $currency }}
                                            </li>
                                        @elseif($start)
                                            <li>After <strong>{{ $start->format('Y-m-d H:i') }} UTC</strong>: Fee
                                                {{ $fee }} {{ $currency }}</li>
                                        @else
                                            <li>No free cancellation available. Full charge applies.</li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('hotel.prebook') }}">
                            @csrf
                            <input type="hidden" name="book_hash" value="{{ $rate['book_hash'] }}">
                            <input type="hidden" name="room_name" value="{{ $rate['room_name'] }}">
                            <input type="hidden" name="room_number" value="{{ $loop->iteration }}">
                            <input type="hidden" name="room_code" value="{{ $rate['room_code'] ?? '' }}">
                            <input type="hidden" name="checkin" value="{{ $checkin }}">
                            <input type="hidden" name="checkout" value="{{ $checkout }}">
                            <input type="hidden" name="adults" value="{{ $adults }}">
                            <input type="hidden" name="currency" value="{{ $currency }}">
                            <input type="hidden" name="children_count" value="{{ count($children) }}">
                            @foreach ($children as $age)
                                <input type="hidden" name="children[]" value="{{ $age }}">
                            @endforeach
                            <button type="submit" class="btn btn-primary mt-3">Choose</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <p>No rooms available.</p>
        @endforelse

        @php
            $policy = $hotel['metapolicy_struct'] ?? [];
        @endphp

        @if (count($policy))
            <table class="table table-borderless mb-5">
                <tbody>
                    {{-- Cancellation / prepayment --}}
                    <tr>
                        <th class="align-top">
                            <i class="fa fa-info-circle text-secondary me-2"></i>
                            Cancellation/prepayment
                        </th>
                        <td>
                            @if (!empty($policy['deposit']))
                                <ul class="mb-0 ps-3">
                                    @foreach ($policy['deposit'] as $dep)
                                        <li>
                                            {{ str_replace('_', ' ', $dep['deposit_type']) }} —
                                            {{ number_format((float) $dep['price'], 2) }} {{ $dep['currency'] ?? '' }}
                                            ({{ str_replace('_', ' ', $dep['payment_type']) }},
                                            {{ str_replace('_', ' ', $dep['pricing_method']) }})
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mb-0">
                                    Cancellation and prepayment policies vary according to accommodation type.
                                    Please check what <a href="#conditions">conditions</a> may apply to each option when
                                    making your selection.
                                </p>
                            @endif
                        </td>
                    </tr>

                    {{-- Children and beds --}}
                    <tr>
                        <th class="align-top">
                            <i class="fa fa-users text-secondary me-2"></i>
                            Children and beds
                        </th>
                        <td>
                            <strong>Child policies</strong><br>
                            @if (!empty($policy['children']))
                                @foreach ($policy['children'] as $ch)
                                    Age {{ $ch['age_start'] }}–{{ $ch['age_end'] }} —
                                    {{ number_format((float) $ch['price'], 2) }} {{ $ch['currency'] ?? '' }}
                                    (Extra bed: {{ str_replace('_', ' ', $ch['extra_bed']) }})
                                    @if (!$loop->last)
                                        <br>
                                    @endif
                                @endforeach
                            @else
                                Children of any age are welcome.
                            @endif

                            <br><br>
                            <strong>Cot &amp; extra bed policies</strong><br>
                            @if (!empty($policy['cot']))
                                @foreach ($policy['cot'] as $cot)
                                    {{ $cot['amount'] }} cot(s) —
                                    {{ number_format((float) $cot['price'], 2) }} {{ $cot['currency'] ?? '' }}
                                    per {{ str_replace('_', ' ', $cot['price_unit']) }}
                                    @if (!$loop->last)
                                        <br>
                                    @endif
                                @endforeach
                            @elseif(!empty($policy['extra_bed']))
                                @foreach ($policy['extra_bed'] as $eb)
                                    {{ $eb['amount'] }} extra bed(s) —
                                    {{ number_format((float) $eb['price'], 2) }} {{ $eb['currency'] ?? '' }}
                                    per {{ str_replace('_', ' ', $eb['price_unit']) }}
                                    @if (!$loop->last)
                                        <br>
                                    @endif
                                @endforeach
                            @else
                                Cots and extra beds are not available at this property.
                            @endif
                        </td>
                    </tr>

                    {{-- No age restriction --}}
                    <tr>
                        <th>
                            <i class="fa fa-user-check text-secondary me-2"></i>
                            No age restriction
                        </th>
                        <td>There is no age requirement for check-in</td>
                    </tr>

                    {{-- Accepted payment methods --}}
                    <tr>
                        <th>
                            <i class="fa fa-credit-card text-secondary me-2"></i>
                            Accepted payment methods
                        </th>
                        <td>
                            @foreach ($policy['payment_methods'] ?? ['visa', 'mastercard', 'cash'] as $m)
                                <img src="{{ asset('icons/' . $m . '.svg') }}" alt="{{ ucfirst($m) }}" class="me-1"
                                    style="height:24px;">
                            @endforeach
                        </td>
                    </tr>

                    {{-- Parties/events --}}
                    <tr>
                        <th>
                            <i class="fa fa-ban text-secondary me-2"></i>
                            Parties
                        </th>
                        <td>Parties/events are not allowed</td>
                    </tr>

                    {{-- Pets --}}
                    <tr>
                        <th>
                            <i class="fa fa-paw text-secondary me-2"></i>
                            Pets
                        </th>
                        <td>
                            @if (!empty($policy['pets']))
                                @foreach ($policy['pets'] as $pet)
                                    {{ ucfirst($pet['pets_type']) }} —
                                    {{ number_format((float) $pet['price'], 2) }} {{ $pet['currency'] ?? '' }}
                                    ({{ str_replace('_', ' ', $pet['inclusion']) }})
                                    @if (!$loop->last)
                                        <br>
                                    @endif
                                @endforeach
                            @else
                                Pets are not allowed.
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        @endif



    </div>


    <style>
        .room-card {
            border-radius: 10px;
        }

        ul.list-unstyled li {
            margin-bottom: 0.5rem;
        }

        ul.list-unstyled small {
            font-style: italic;
        }

        ul.list-unstyled strong {
            font-weight: 600;
        }

        /* Custom Slider Styles */
        .custom-slider {
            position: relative;
            max-width: 100%;
            height: 600px;
            overflow: hidden;
            border-radius: 10px;
        }

        .custom-slider .slides {
            display: flex;
            width: 100%;
            height: 100%;
        }

        .custom-slider .slide-img {
            min-width: 100%;
            object-fit: cover;
            display: none;
            height: 600px;
            border-radius: 10px;
        }

        .custom-slider .slide-img.active {
            display: block;
        }

        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            /* remove background */
            border: none;
            font-size: 3rem;
            color: #fff;
            /* or #000 depending on your image */
            padding: 0;
            cursor: pointer;
            z-index: 10;
            transition: color 0.3s;
        }



        .prev-btn {
            left: 15px;
        }

        .next-btn {
            right: 15px;
        }
    </style>


    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide-img');

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.remove('active');
                if (i === index) {
                    slide.classList.add('active');
                }
            });
        }

        function changeSlide(step) {
            currentSlide = (currentSlide + step + slides.length) % slides.length;
            showSlide(currentSlide);
        }

        // Optional: Auto play every 5 seconds
        setInterval(() => changeSlide(1), 5000);
    </script>
@endsection
