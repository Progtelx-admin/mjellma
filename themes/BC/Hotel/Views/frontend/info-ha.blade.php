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
                            @if (!empty($rate['room_images']))
                                <div id="room-slider-{{ $loop->iteration }}"
                                    class="room-image-slider mb-3 position-relative">
                                    @foreach ($rate['room_images'] as $i => $image)
                                        <img src="{{ $image }}" alt="Room Image {{ $i + 1 }}"
                                            class="room-slide-img {{ $i === 0 ? 'active' : '' }}"
                                            onclick="openFullImage('{{ $image }}')">
                                    @endforeach

                                    @if (count($rate['room_images']) > 1)
                                        <button class="slider-btn room-prev"
                                            onclick="changeRoomSlide({{ $loop->iteration }}, -1)">‹</button>
                                        <button class="slider-btn room-next"
                                            onclick="changeRoomSlide({{ $loop->iteration }}, 1)">›</button>
                                    @endif
                                </div>
                            @else
                                <p>No room images available.</p>
                            @endif

                            @php
                                $mealData = data_get($rate, 'meal_data', []);
                                $mealValue = $mealData['value'] ?? null;
                                $hasBreakfast = $mealData['has_breakfast'] ?? false;
                                $hasLunch = $mealData['has_lunch'] ?? false;
                                $hasDinner = $mealData['has_dinner'] ?? false;
                                $noChildMeal = $mealData['no_child_meal'] ?? null;

                                $mealParts = collect([
                                    $hasBreakfast ? 'Breakfast' : null,
                                    $hasLunch ? 'Lunch' : null,
                                    $hasDinner ? 'Dinner' : null,
                                ])->filter();

                                $mealLabel =
                                    $mealValue === 'nomeal' || $mealParts->isEmpty()
                                        ? 'No meal included'
                                        : $mealParts->implode(', ');
                            @endphp

                            <p><strong>Meals:</strong> {{ $mealLabel }}</p>

                            @if (!is_null($noChildMeal))
                                <p><strong>Child Meal:</strong> {{ $noChildMeal ? 'Not included' : 'Included' }}</p>
                            @endif

                            @if (!empty($hotel['metapolicy_struct']['meal']))
                                <h5>Hotel Meal Policy (ETG)</h5>
                                @foreach ($hotel['metapolicy_struct']['meal'] as $policyMeal)
                                    @php
                                        // Determine if this meal type is included in the current rate.
                                        $policyType = $policyMeal['meal_type'] ?? '';
                                        $includedByRate = false;
                                        // Use meal_data.has_* flags to understand what the rate includes
                                        if ($policyType === 'breakfast' && data_get($rate, 'meal_data.has_breakfast')) {
                                            $includedByRate = true;
                                        }
                                        if ($policyType === 'lunch' && data_get($rate, 'meal_data.has_lunch')) {
                                            $includedByRate = true;
                                        }
                                        if ($policyType === 'dinner' && data_get($rate, 'meal_data.has_dinner')) {
                                            $includedByRate = true;
                                        }
                                        // If the policy lists all-inclusive, consider it included when breakfast, lunch and dinner are all included
                                        if ($policyType === 'all_inclusive') {
                                            $hasBreakfast = data_get($rate, 'meal_data.has_breakfast');
                                            $hasLunch = data_get($rate, 'meal_data.has_lunch');
                                            $hasDinner = data_get($rate, 'meal_data.has_dinner');
                                            $includedByRate = $hasBreakfast && $hasLunch && $hasDinner;
                                        }

                                        // Determine the final inclusion status and whether to show the price
                                        if ($includedByRate) {
                                            $inclusionText = 'Included';
                                            $priceText = '';
                                        } else {
                                            $inclusionText =
                                                ($policyMeal['inclusion'] ?? '') === 'included'
                                                    ? 'Included'
                                                    : 'Not included';
                                            // Only show a price if the meal is not included; otherwise omit
                                            $policyPrice = $policyMeal['price'] ?? '';
                                            $policyCurrency = $policyMeal['currency'] ?? '';
                                            $priceText =
                                                $policyPrice !== ''
                                                    ? ' – Price: ' . $policyPrice . ' ' . $policyCurrency
                                                    : '';
                                        }
                                    @endphp
                                    <p>{{ ucfirst($policyType) }}: {{ $inclusionText }}{{ $priceText }}</p>
                                @endforeach
                            @endif

                            @if (!empty($hotel['metapolicy_struct']['children_meal']))
                                <h5>Children's Meal Policy</h5>
                                @foreach ($hotel['metapolicy_struct']['children_meal'] as $childPolicy)
                                    @php
                                        // Determine if children’s meal is included in this rate.  The API sets
                                        // meal_data.no_child_meal=true when there is no children meal.  If false, the rate
                                        // includes a children’s meal.
                                        $childMealIncludedByRate = !data_get($rate, 'meal_data.no_child_meal');
                                        if ($childMealIncludedByRate) {
                                            $childInclusionText = 'included';
                                            $childPriceText = '';
                                        } else {
                                            $childInclusionText =
                                                ($childPolicy['inclusion'] ?? '') === 'included'
                                                    ? 'included'
                                                    : 'not included';
                                            $childPolicyPrice = $childPolicy['price'] ?? '';
                                            $childPolicyCurrency = $childPolicy['currency'] ?? '';
                                            $childPriceText =
                                                $childPolicyPrice !== ''
                                                    ? ' – Price: ' . $childPolicyPrice . ' ' . $childPolicyCurrency
                                                    : '';
                                        }
                                    @endphp
                                    <p>
                                        Age {{ $childPolicy['age_start'] }}–{{ $childPolicy['age_end'] }}:
                                        {{ ucfirst($childPolicy['meal_type']) }},
                                        {{ $childInclusionText }}{{ $childPriceText }}
                                    </p>
                                @endforeach
                            @endif



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

                        <h4 class="text-primary">Total: {{ number_format((float) $finalPrice, 2) }} {{ $currency }}
                        </h4>
                        @if (!is_null($comm))
                            <p class="text-muted mb-0"><small>Net: {{ number_format((float) $net, 2) }}
                                    {{ $currency }}</small></p>
                            <p class="text-muted"><small>Commission: {{ number_format((float) $comm, 2) }}
                                    {{ $currency }}</small></p>
                        @endif

                        <form method="POST" action="{{ route('hotel.prebook') }}">
                            @csrf
                            <input type="hidden" name="book_hash" value="{{ $rate['book_hash'] }}">
                            <input type="hidden" name="room_name" value="{{ $rate['room_name'] }}">
                            <input type="hidden" name="display_final_price"
                                value="{{ number_format((float) $finalPrice, 2, '.', '') }}">
                            <input type="hidden" name="display_currency" value="{{ $currency }}">
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

        @if (count($hotel['metapolicy_struct'] ?? []))
            @php $policy = $hotel['metapolicy_struct']; @endphp

            <table class="table table-borderless mb-5">
                <tbody>

                    {{-- Check-in / Check-out --}}
                    @if (!empty($policy['check_in_check_out']))
                        <tr>
                            <th>Check-in / Check-out</th>
                            <td>
                                @foreach ($policy['check_in_check_out'] as $cico)
                                    @if (!empty($cico['check_in']))
                                        Check-in: {{ $cico['check_in'] }}<br>
                                    @endif
                                    @if (!empty($cico['check_out']))
                                        Check-out: {{ $cico['check_out'] }}<br>
                                    @endif
                                @endforeach
                            </td>
                        </tr>
                    @endif
                    {{-- Additional Fees --}}
                    @if (!empty($policy['add_fee']))
                        <tr>
                            <th>Additional Fees</th>
                            <td>
                                @foreach ($policy['add_fee'] as $fee)
                                    {{ ucfirst(str_replace('_', ' ', $fee['fee_type'] ?? '')) }} —
                                    {{ number_format((float) $fee['price'], 2) }} {{ $fee['currency'] ?? '' }}
                                    ({{ ucfirst(str_replace('_', ' ', $fee['inclusion'] ?? '')) }})
                                    <br>
                                @endforeach
                            </td>
                        </tr>
                    @endif

                    {{-- Deposits --}}
                    <tr>
                        <th>Deposits (Security / Other)</th>
                        <td>
                            @if (!empty($policy['deposit']))
                                <ul class="mb-0 ps-3">
                                    @foreach ($policy['deposit'] as $dep)
                                        <li>
                                            {{ $dep['deposit_type'] !== 'unspecified' ? ucfirst($dep['deposit_type']) : 'General' }}
                                            —
                                            {{ number_format((float) $dep['price'], 2) }} {{ $dep['currency'] ?? '' }}
                                            ({{ ucfirst($dep['payment_type'] ?? 'N/A') }},
                                            {{ ucfirst($dep['pricing_method'] ?? 'N/A') }})
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                No deposit information provided.
                            @endif
                        </td>
                    </tr>

                    {{-- Children and Beds --}}
                    <tr>
                        <th>Children and beds</th>
                        <td>
                            <strong>Child policies</strong><br>
                            @if (!empty($policy['children']))
                                @foreach ($policy['children'] as $ch)
                                    Age {{ $ch['age_start'] }}–{{ $ch['age_end'] }} —
                                    {{ number_format((float) $ch['price'], 2) }} {{ $ch['currency'] ?? '' }}
                                    (Extra bed: {{ str_replace('_', ' ', $ch['extra_bed']) }})
                                    <br>
                                @endforeach
                            @else
                                Children of any age are welcome.<br>
                            @endif

                            <br><strong>Cot policies</strong><br>
                            @if (!empty($policy['cot']))
                                @foreach ($policy['cot'] as $cot)
                                    {{ $cot['amount'] }} cot(s) —
                                    {{ number_format((float) $cot['price'], 2) }} {{ $cot['currency'] ?? '' }}
                                    per {{ str_replace('_', ' ', $cot['price_unit']) }}
                                    ({{ ucfirst(str_replace('_', ' ', $cot['inclusion'] ?? 'unspecified')) }})
                                    <br>
                                @endforeach
                            @else
                                No cot policies available.<br>
                            @endif

                            <br><strong>Extra bed policies</strong><br>
                            @if (!empty($policy['extra_bed']))
                                @foreach ($policy['extra_bed'] as $eb)
                                    {{ $eb['amount'] }} extra bed(s) —
                                    {{ number_format((float) $eb['price'], 2) }} {{ $eb['currency'] ?? '' }}
                                    per {{ str_replace('_', ' ', $eb['price_unit']) }}
                                    ({{ ucfirst(str_replace('_', ' ', $eb['inclusion'] ?? 'unspecified')) }})
                                    <br>
                                @endforeach
                            @else
                                No extra bed policies available.
                            @endif
                        </td>
                    </tr>

                    {{-- No Age Restriction --}}
                    <tr>
                        <th>No age restriction</th>
                        <td>There is no age requirement for check-in</td>
                    </tr>

                    {{-- Accepted Payment Methods --}}
                    <tr>
                        <th>Accepted payment methods</th>
                        <td>
                            @foreach ($hotel['payment_methods'] ?? ['visa', 'mastercard', 'cash'] as $method)
                                <img src="{{ asset('icons/' . $method . '.svg') }}" alt="{{ ucfirst($method) }}"
                                    class="me-1" style="height:24px;">
                            @endforeach
                        </td>
                    </tr>

                    {{-- Parties --}}
                    <tr>
                        <th>Parties</th>
                        <td>Parties/events are not allowed</td>
                    </tr>

                    {{-- Pets --}}
                    <tr>
                        <th>Pets</th>
                        <td>
                            @if (!empty($policy['pets']))
                                @foreach ($policy['pets'] as $pet)
                                    {{ ucfirst($pet['pets_type'] ?? 'Pet') }} —
                                    {{ number_format((float) $pet['price'], 2) }} {{ $pet['currency'] ?? '' }}
                                    ({{ ucfirst(str_replace('_', ' ', $pet['inclusion'])) }})
                                    <br>
                                @endforeach
                            @else
                                Pets are not allowed.
                            @endif
                        </td>
                    </tr>

                    {{-- Internet --}}
                    <tr>
                        <th>Internet</th>
                        <td>
                            @foreach ($policy['internet'] ?? [] as $net)
                                {{ ucfirst($net['work_area']) }} —
                                {{ ucfirst(str_replace('_', ' ', $net['inclusion'])) }}
                                @if ($net['price'] > 0)
                                    – {{ number_format((float) $net['price'], 2) }} {{ $net['currency'] ?? '' }}
                                    per {{ str_replace('_', ' ', $net['price_unit']) }}
                                @endif
                                <br>
                            @endforeach
                        </td>
                    </tr>

                    {{-- No-show --}}
                    @if (!empty($policy['no_show']) && $policy['no_show']['availability'] === 'available')
                        <tr>
                            <th>No-show Policy</th>
                            <td>
                                No-show charge applies after
                                {{ \Carbon\Carbon::createFromFormat('H:i:s', $policy['no_show']['time'])->format('H:i') }}
                                ({{ str_replace('_', ' ', $policy['no_show']['day_period']) }})
                            </td>
                        </tr>
                    @endif

                    {{-- Parking --}}
                    <tr>
                        <th>Parking</th>
                        <td>
                            @foreach ($policy['parking'] ?? [] as $park)
                                {{ $park['territory_type'] !== 'unspecified' ? ucfirst($park['territory_type']) : 'General' }}
                                —
                                {{ ucfirst(str_replace('_', ' ', $park['inclusion'])) }}
                                – {{ number_format((float) $park['price'], 2) }} {{ $park['currency'] ?? '' }}
                                per {{ str_replace('_', ' ', $park['price_unit']) }}<br>
                            @endforeach
                        </td>
                    </tr>

                    {{-- Shuttle --}}
                    <tr>
                        <th>Shuttle</th>
                        <td>
                            @foreach ($policy['shuttle'] ?? [] as $shuttle)
                                {{ ucfirst(str_replace('_', ' ', $shuttle['destination_type'])) }} —
                                {{ ucfirst(str_replace('_', ' ', $shuttle['inclusion'])) }}
                                @if ($shuttle['price'] > 0)
                                    – {{ number_format((float) $shuttle['price'], 2) }} {{ $shuttle['currency'] ?? '' }}
                                @endif
                                ({{ ucfirst(str_replace('_', ' ', $shuttle['shuttle_type'])) }})
                                <br>
                            @endforeach
                        </td>
                    </tr>

                    {{-- Meals --}}
                    <tr>
                        <th>Meals</th>
                        <td>
                            @foreach ($policy['meal'] ?? [] as $meal)
                                {{ ucfirst($meal['meal_type']) }} —
                                {{ ucfirst(str_replace('_', ' ', $meal['inclusion'])) }} –
                                {{ number_format((float) $meal['price'], 2) }} {{ $meal['currency'] ?? '' }}<br>
                            @endforeach
                        </td>
                    </tr>

                    {{-- Children's Meals --}}
                    <tr>
                        <th>Children's Meals</th>
                        <td>
                            @foreach ($policy['children_meal'] ?? [] as $cm)
                                Age {{ $cm['age_start'] }}–{{ $cm['age_end'] }}:
                                {{ ucfirst($cm['meal_type']) }},
                                {{ ucfirst(str_replace('_', ' ', $cm['inclusion'])) }} –
                                {{ number_format((float) $cm['price'], 2) }} {{ $cm['currency'] ?? '' }}<br>
                            @endforeach
                        </td>
                    </tr>

                    {{-- Visa Support --}}
                    <tr>
                        <th>Visa</th>
                        <td>
                            {{ $policy['visa']['visa_support'] === 'support_enable' ? 'Visa support available' : 'No visa support' }}
                        </td>
                    </tr>

                </tbody>
            </table>
        @endif

    </div>

    <div id="imageModal" class="modal-img-viewer" onclick="closeFullImage()">
        <button class="close-btn" onclick="closeFullImage()">×</button>
        <img id="modalImage" src="" alt="Full Room Image">
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

        .room-image-slider {
            position: relative;
            max-width: 100%;
            height: 300px;
            overflow: hidden;
            border-radius: 8px;
        }

        .room-slide-img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            display: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .room-slide-img.active {
            display: block;
        }

        .room-image-slider .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.4);
            color: white;
            border: none;
            font-size: 2rem;
            padding: 0 10px;
            z-index: 10;
            cursor: pointer;
            transition: background 0.2s;
        }

        .room-image-slider .room-prev {
            left: 10px;
        }

        .room-image-slider .room-next {
            right: 10px;
        }

        /* Fullscreen Modal */
        .modal-img-viewer {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
        }

        .modal-img-viewer img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 8px;
        }

        .modal-img-viewer .close-btn {
            position: absolute;
            top: 30px;
            right: 30px;
            font-size: 2rem;
            color: white;
            cursor: pointer;
            background: transparent;
            border: none;
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

    <script>
        // Room Sliders
        const roomSliders = {};

        function changeRoomSlide(roomId, step) {
            const slides = document.querySelectorAll(`#room-slider-${roomId} .room-slide-img`);
            if (!roomSliders[roomId]) roomSliders[roomId] = 0;

            roomSliders[roomId] = (roomSliders[roomId] + step + slides.length) % slides.length;

            slides.forEach((s, i) => s.classList.toggle('active', i === roomSliders[roomId]));
        }

        // Modal full image viewer
        function openFullImage(url) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'flex';
            modalImg.src = url;
        }

        function closeFullImage() {
            document.getElementById('imageModal').style.display = 'none';
        }
    </script>

@endsection
