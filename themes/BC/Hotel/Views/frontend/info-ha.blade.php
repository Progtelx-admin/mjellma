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
                ← Back to results
            </a>
        </div>

        {{-- Header --}}
        <div class="hotel-header d-flex align-items-start justify-content-between gap-4 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h1 class="fw-bold mb-0">{{ $hotel['name'] }}</h1>
                    <div class="star-wrap">
                        @for ($i = 0; $i < ($hotel['star_rating'] ?? 0); $i++)
                            <i class="fa fa-star text-warning"></i>
                        @endfor
                    </div>
                </div>
                <p class="text-muted mb-2"><i class="fa fa-map-marker"></i> {{ $hotel['address'] }}</p>
                <div class="d-flex flex-wrap gap-3 small text-muted">
                    <div><strong>Check-in</strong>: {{ $checkin }}</div>
                    <div><strong>Check-out</strong>: {{ $checkout }}</div>
                </div>
            </div>
        </div>

        {{-- ===== Two-column layout ===== --}}
        <div class="row g-4">
            {{-- LEFT: hero slider + policies --}}
            <div class="col-lg-6">
                {{-- Hero Images Slider --}}
                @if (!empty($hotel['images_ext']))
                    <div class="custom-slider mb-4" id="customImageSlider">
                        <div class="slides">
                            @foreach ($hotel['images_ext'] as $idx => $img)
                                <img src="{{ $img }}" alt="Hotel image {{ $idx + 1 }}"
                                    class="slide-img {{ $idx === 0 ? 'active' : '' }}">
                            @endforeach
                        </div>
                        <button class="slider-btn prev-btn" type="button" onclick="changeSlide(-1)">‹</button>
                        <button class="slider-btn next-btn" type="button" onclick="changeSlide(1)">›</button>
                        <div class="dots" id="heroDots"></div>
                    </div>
                @endif

                {{-- Policies --}}
                @php $policy = $hotel['metapolicy_struct'] ?? []; @endphp

                @if (count($policy))
                    <h4 class="mb-3">Hotel Policies</h4>

                    <div class="policy-grid">
                        <table class="table table-borderless mb-5 align-middle">
                            <tbody>
                                {{-- Check-in / Check-out --}}
                                @if (!empty($policy['check_in_check_out']))
                                    <tr>
                                        <th class="w-35">Check-in / Check-out</th>
                                        <td class="text-muted">
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
                                        <th>Additional fees</th>
                                        <td class="text-muted">
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
                                    <td class="text-muted">
                                        @if (!empty($policy['deposit']))
                                            <ul class="mb-0 ps-3">
                                                @foreach ($policy['deposit'] as $dep)
                                                    <li>
                                                        {{ $dep['deposit_type'] !== 'unspecified' ? ucfirst($dep['deposit_type']) : 'General' }}
                                                        —
                                                        {{ number_format((float) $dep['price'], 2) }}
                                                        {{ $dep['currency'] ?? '' }}
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

                                {{-- Children and beds --}}
                                <tr>
                                    <th>Children & beds</th>
                                    <td class="text-muted">
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

                                {{-- No age restriction --}}
                                <tr>
                                    <th>No age restriction</th>
                                    <td class="text-muted">There is no age requirement for check-in.</td>
                                </tr>

                                {{-- Accepted Payment Methods --}}
                                <tr>
                                    <th>Accepted payment methods</th>
                                    <td>
                                        @foreach ($hotel['payment_methods'] ?? ['visa', 'mastercard', 'cash'] as $method)
                                            <img src="{{ asset('icons/' . $method . '.svg') }}"
                                                alt="{{ ucfirst($method) }}" class="me-1" style="height:24px">
                                        @endforeach
                                    </td>
                                </tr>

                                {{-- Parties --}}
                                <tr>
                                    <th>Parties</th>
                                    <td class="text-muted">Parties/events are not allowed.</td>
                                </tr>

                                {{-- Pets --}}
                                <tr>
                                    <th>Pets</th>
                                    <td class="text-muted">
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
                                    <td class="text-muted">
                                        @foreach ($policy['internet'] ?? [] as $net)
                                            {{ ucfirst($net['work_area']) }} —
                                            {{ ucfirst(str_replace('_', ' ', $net['inclusion'])) }}
                                            @if ($net['price'] > 0)
                                                – {{ number_format((float) $net['price'], 2) }}
                                                {{ $net['currency'] ?? '' }}
                                                per {{ str_replace('_', ' ', $net['price_unit']) }}
                                            @endif
                                            <br>
                                        @endforeach
                                    </td>
                                </tr>

                                {{-- No-show --}}
                                @if (data_get($policy, 'no_show.availability') === 'available')
                                    @php
                                        $nsTime = data_get($policy, 'no_show.time'); // e.g. "18:00:00"
                                        $nsPeriod = data_get($policy, 'no_show.day_period'); // e.g. "check_in_day"
                                    @endphp
                                    <tr>
                                        <th>No-show policy</th>
                                        <td class="text-muted">
                                            No-show charge applies
                                            @if ($nsTime)
                                                after
                                                {{ \Carbon\Carbon::createFromFormat('H:i:s', $nsTime)->format('H:i') }}
                                            @endif
                                            @if ($nsPeriod)
                                                ({{ str_replace('_', ' ', $nsPeriod) }})
                                            @endif
                                            .
                                        </td>
                                    </tr>
                                @endif


                                {{-- Parking --}}
                                <tr>
                                    <th>Parking</th>
                                    <td class="text-muted">
                                        @foreach ($policy['parking'] ?? [] as $park)
                                            {{ $park['territory_type'] !== 'unspecified' ? ucfirst($park['territory_type']) : 'General' }}
                                            —
                                            {{ ucfirst(str_replace('_', ' ', $park['inclusion'])) }} –
                                            {{ number_format((float) $park['price'], 2) }} {{ $park['currency'] ?? '' }}
                                            per {{ str_replace('_', ' ', $park['price_unit']) }}<br>
                                        @endforeach
                                    </td>
                                </tr>

                                {{-- Shuttle --}}
                                <tr>
                                    <th>Shuttle</th>
                                    <td class="text-muted">
                                        @foreach ($policy['shuttle'] ?? [] as $shuttle)
                                            {{ ucfirst(str_replace('_', ' ', $shuttle['destination_type'])) }} —
                                            {{ ucfirst(str_replace('_', ' ', $shuttle['inclusion'])) }}
                                            @if ($shuttle['price'] > 0)
                                                – {{ number_format((float) $shuttle['price'], 2) }}
                                                {{ $shuttle['currency'] ?? '' }}
                                            @endif
                                            ({{ ucfirst(str_replace('_', ' ', $shuttle['shuttle_type'])) }})
                                            <br>
                                        @endforeach
                                    </td>
                                </tr>

                                {{-- Meals --}}
                                <tr>
                                    <th>Meals</th>
                                    <td class="text-muted">
                                        @foreach ($policy['meal'] ?? [] as $meal)
                                            {{ ucfirst($meal['meal_type']) }} —
                                            {{ ucfirst(str_replace('_', ' ', $meal['inclusion'])) }} –
                                            {{ number_format((float) $meal['price'], 2) }}
                                            {{ $meal['currency'] ?? '' }}<br>
                                        @endforeach
                                    </td>
                                </tr>

                                {{-- Children's Meals --}}
                                <tr>
                                    <th>Children's meals</th>
                                    <td class="text-muted">
                                        @foreach ($policy['children_meal'] ?? [] as $cm)
                                            Age {{ $cm['age_start'] }}–{{ $cm['age_end'] }}:
                                            {{ ucfirst($cm['meal_type']) }},
                                            {{ ucfirst(str_replace('_', ' ', $cm['inclusion'])) }} –
                                            {{ number_format((float) $cm['price'], 2) }} {{ $cm['currency'] ?? '' }}<br>
                                        @endforeach
                                    </td>
                                </tr>

                                {{-- Visa --}}
                                <tr>
                                    <th>Visa</th>
                                    <td class="text-muted">
                                        {{ data_get($policy, 'visa.visa_support') === 'support_enable' ? 'Visa support available' : 'No visa support' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- RIGHT: Additional Policy Information + Available Rooms --}}
            <div class="col-lg-6">

                {{-- Blue Additional Policy Information --}}
                @if (!empty($hotel['metapolicy_extra_info']))
                    <div class="policy-card mb-4">
                        <div class="policy-card__header">Additional Policy Information</div>
                        <div class="policy-card__body">
                            {!! nl2br(e(str_replace(["\\r\\n", "\\n"], "\n", $hotel['metapolicy_extra_info']))) !!}
                        </div>
                    </div>
                @endif

                {{-- Available Rooms --}}
                @php
                    $totalRooms = is_countable($roomRates) ? count($roomRates) : collect($roomRates)->count();
                @endphp
                {{-- <h3 class="mb-4">Available Rooms</h3> --}}
                <div id="roomsList">
                    @forelse($roomRates as $rate)
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
                            $finalPrice = $net + ($comm ?? 0);

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

                            $roomId = $loop->iteration; // used for slider + collapse IDs
                        @endphp

                        <div class="card mb-4 room-card shadow-sm" data-room-id="{{ $roomId }}">
                            <div class="row g-0 align-items-stretch">
                                {{-- LEFT: Image slider only --}}
                                <div class="col-md-6">
                                    @if (!empty($rate['room_images']))
                                        <div class="room-image-slider h-100">
                                            @foreach ($rate['room_images'] as $i => $image)
                                                <img src="{{ $image }}" alt="Room image {{ $i + 1 }}"
                                                    class="room-slide-img {{ $i === 0 ? 'active' : '' }}"
                                                    onclick="openFullImage('{{ $image }}')">
                                            @endforeach
                                            @if (count($rate['room_images']) > 1)
                                                <button class="slider-btn room-prev" type="button"
                                                    onclick="changeRoomSlide({{ $roomId }}, -1)">‹</button>
                                                <button class="slider-btn room-next" type="button"
                                                    onclick="changeRoomSlide({{ $roomId }}, 1)">›</button>
                                            @endif
                                        </div>
                                    @else
                                        <div
                                            class="room-image-slider d-flex align-items-center justify-content-center h-100">
                                            <span class="text-muted small">No room images available.</span>
                                        </div>
                                    @endif
                                </div>

                                {{-- RIGHT: Content --}}
                                <div class="col-md-6 d-flex flex-column p-3">
                                    <div>
                                        <h5 class="fw-bold mb-2">{{ $rate['room_name'] }}</h5>

                                        {{-- Compact cancellation summary (first/last rule) --}}
                                        @if (!empty($policies))
                                            <div class="small text-muted mb-2">
                                                <strong>Cancellation Policies (UTC+0):</strong><br>
                                                @php
                                                    $first = $policies[0] ?? null;
                                                    $last = last($policies) ?: null;
                                                    $summary = [];
                                                    if ($first) {
                                                        $end = $first['end_at']
                                                            ? \Carbon\Carbon::parse($first['end_at'])
                                                                ->utc()
                                                                ->format('Y-m-d H:i')
                                                            : null;
                                                        $summary[] = $end
                                                            ? "Free cancellation until $end UTC."
                                                            : 'No free cancellation.';
                                                    }
                                                    if ($last && $last !== $first) {
                                                        $start = $last['start_at']
                                                            ? \Carbon\Carbon::parse($last['start_at'])
                                                                ->utc()
                                                                ->format('Y-m-d H:i')
                                                            : null;
                                                        $fee = number_format(
                                                            $last['amount_show'] ?? ($last['amount_charge'] ?? 0),
                                                            2,
                                                        );
                                                        $summary[] = $start
                                                            ? "After $start UTC: Fee {$fee} {$currency}"
                                                            : 'Full charge applies.';
                                                    }
                                                @endphp
                                                {!! implode(' ', array_map('e', $summary)) !!}
                                            </div>
                                        @endif

                                        <p class="mb-1"><strong>Meals:</strong> {{ $mealLabel }}</p>
                                        @if (!is_null($noChildMeal))
                                            <p class="mb-0"><strong>Child meal:</strong>
                                                {{ $noChildMeal ? 'Not included' : 'Included' }}</p>
                                        @endif
                                    </div>
                                    {{-- Price + Choose --}}
                                    <div class="mt-auto ms-auto price-stack text-right" style="max-width: 220px;">
                                        <div class="label">Total price</div>
                                        <div class="amount">{{ number_format((float) $finalPrice, 2) }}
                                            {{ $currency }}
                                        </div>

                                        <form method="POST" action="{{ route('hotel.prebook') }}">
                                            @csrf
                                            <input type="hidden" name="book_hash" value="{{ $rate['book_hash'] }}">
                                            <input type="hidden" name="room_name" value="{{ $rate['room_name'] }}">
                                            <input type="hidden" name="display_final_price"
                                                value="{{ number_format((float) $finalPrice, 2, '.', '') }}">
                                            <input type="hidden" name="display_currency" value="{{ $currency }}">
                                            <input type="hidden" name="room_number" value="{{ $roomId }}">
                                            <input type="hidden" name="room_code"
                                                value="{{ $rate['room_code'] ?? '' }}">
                                            <input type="hidden" name="checkin" value="{{ $checkin }}">
                                            <input type="hidden" name="checkout" value="{{ $checkout }}">
                                            <input type="hidden" name="adults" value="{{ $adults }}">
                                            <input type="hidden" name="currency" value="{{ $currency }}">
                                            <input type="hidden" name="children_count" value="{{ count($children) }}">
                                            @foreach ($children as $age)
                                                <input type="hidden" name="children[]" value="{{ $age }}">
                                            @endforeach

                                            <button type="submit" class="btn btn-choose w-50 mt-1">Choose</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            {{-- SEE MORE: full-width under the row --}}
                            <div class="room-more-wrap p-3 pt-2">
                                <button class="btn btn-sm btn-outline-secondary see-more-btn" type="button"
                                    data-target="room-more-{{ $roomId }}" aria-expanded="false">
                                    See more
                                </button>

                                <div id="room-more-{{ $roomId }}" class="collapse-box mt-3">
                                    {{-- LEFT COLUMN ------------------------------------------------- --}}
                                    <div class="info-block">
                                        {{-- On-site Taxes --}}
                                        @if ($onSiteTaxes->isNotEmpty())
                                            <h6 class="info-title">On-site Taxes</h6>
                                            <ul class="mb-3">
                                                @foreach ($onSiteTaxes as $tax)
                                                    @php
                                                        $displayAmount =
                                                            $tax['amount_show'] ??
                                                            ($tax['amount_charge'] ?? ($tax['amount'] ?? 0));
                                                    @endphp
                                                    <li class="mb-1">
                                                        {{ ucwords(str_replace('_', ' ', $tax['name'])) }}:
                                                        {{ number_format((float) $displayAmount, 2) }}
                                                        {{ $tax['currency_code'] }}
                                                        <small class="text-muted">(to be paid at hotel)</small>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        {{-- Full Cancellation Policies --}}
                                        @if (!empty($policies))
                                            <h6 class="info-title">Full cancellation policy (UTC+0)</h6>
                                            <ul class="mb-0">
                                                @foreach ($policies as $p)
                                                    @php
                                                        $start = $p['start_at']
                                                            ? \Carbon\Carbon::parse($p['start_at'])->utc()
                                                            : null;
                                                        $end = $p['end_at']
                                                            ? \Carbon\Carbon::parse($p['end_at'])->utc()
                                                            : null;
                                                        $fee = number_format(
                                                            $p['amount_show'] ?? ($p['amount_charge'] ?? 0),
                                                            2,
                                                        );
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
                                                        <li>After <strong>{{ $start->format('Y-m-d H:i') }} UTC</strong>:
                                                            Fee {{ $fee }} {{ $currency }}</li>
                                                    @else
                                                        <li>No free cancellation available. Full charge applies.</li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>

                                    {{-- RIGHT COLUMN ------------------------------------------------ --}}
                                    <div class="info-block">
                                        {{-- Hotel Meal Policy (ETG) --}}
                                        @if (!empty($hotel['metapolicy_struct']['meal']))
                                            <h6 class="info-title">Hotel Meal Policy (ETG)</h6>
                                            <div class="mb-3">
                                                @foreach ($hotel['metapolicy_struct']['meal'] as $policyMeal)
                                                    @php
                                                        $policyType = $policyMeal['meal_type'] ?? '';
                                                        $includedByRate =
                                                            ($policyType === 'breakfast' && $hasBreakfast) ||
                                                            ($policyType === 'lunch' && $hasLunch) ||
                                                            ($policyType === 'dinner' && $hasDinner) ||
                                                            ($policyType === 'all_inclusive' &&
                                                                $hasBreakfast &&
                                                                $hasLunch &&
                                                                $hasDinner);

                                                        if ($includedByRate) {
                                                            $inclusionText = 'Included';
                                                            $priceText = '';
                                                        } else {
                                                            $inclusionText =
                                                                ($policyMeal['inclusion'] ?? '') === 'included'
                                                                    ? 'Included'
                                                                    : 'Not included';
                                                            $priceText = !empty($policyMeal['price'])
                                                                ? ' – Price: ' .
                                                                    $policyMeal['price'] .
                                                                    ' ' .
                                                                    ($policyMeal['currency'] ?? '')
                                                                : '';
                                                        }
                                                    @endphp
                                                    <div>{{ ucfirst($policyType) }}:
                                                        {{ $inclusionText }}{!! $priceText !!}</div>
                                                @endforeach
                                            </div>
                                        @endif

                                        {{-- Children’s Meal Policy --}}
                                        @if (!empty($hotel['metapolicy_struct']['children_meal']))
                                            <h6 class="info-title">Children's Meal Policy</h6>
                                            <div class="mb-3">
                                                @foreach ($hotel['metapolicy_struct']['children_meal'] as $childPolicy)
                                                    @php
                                                        $childMealIncludedByRate = !data_get(
                                                            $rate,
                                                            'meal_data.no_child_meal',
                                                        );
                                                        if ($childMealIncludedByRate) {
                                                            $childInclusionText = 'included';
                                                            $childPriceText = '';
                                                        } else {
                                                            $childInclusionText =
                                                                ($childPolicy['inclusion'] ?? '') === 'included'
                                                                    ? 'included'
                                                                    : 'not included';
                                                            $childPriceText = !empty($childPolicy['price'])
                                                                ? ' – Price: ' .
                                                                    $childPolicy['price'] .
                                                                    ' ' .
                                                                    ($childPolicy['currency'] ?? '')
                                                                : '';
                                                        }
                                                    @endphp
                                                    <div>
                                                        Age
                                                        {{ $childPolicy['age_start'] }}–{{ $childPolicy['age_end'] }}:
                                                        {{ ucfirst($childPolicy['meal_type']) }},
                                                        {{ $childInclusionText }}{!! $childPriceText !!}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        {{-- Net + Commission --}}
                                        @if (!is_null($comm))
                                            <div class="text-muted small">
                                                Net: {{ number_format((float) $net, 2) }} {{ $currency }} |
                                                Commission: {{ number_format((float) $comm, 2) }} {{ $currency }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    @empty
                        <p>No rooms available.</p>
                    @endforelse
                </div>
                @if ($totalRooms > 5)
                    <button id="loadMoreRooms" class="btn w-50 mt-3 text-center"
                        style="background: #F27625; color: white" type="button" data-step="5">
                        Load more rooms
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Fullscreen Image Modal --}}
    <div id="imageModal" class="modal-img-viewer" onclick="closeFullImage()">
        <button class="close-btn" type="button" onclick="closeFullImage()">×</button>
        <img id="modalImage" src="" alt="Full Room Image">
    </div>

    <div class="section-offers py-5">
        <div class="container">
            <h2 class="text-center mb-5">Hot Offers for Your Summer Getaway</h2>

            <div class="row g-5 justify-content-center">
                <!-- Card 1 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="#hotel-search-form"
                        class="deal-card d-block position-relative rounded-3 overflow-hidden shadow-sm">
                        <img src="{{ asset('uploads/milano.png') }}" alt="Milano, Italy" class="deal-img w-100">
                        <span class="deal-caption">Milano, Italy</span>
                    </a>
                </div>

                <!-- Card 2 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="#hotel-search-form"
                        class="deal-card d-block position-relative rounded-3 overflow-hidden shadow-sm">
                        <img src="{{ asset('uploads/santorini.png') }}" alt="Santorini, Greece" class="deal-img w-100">
                        <span class="deal-caption">Santorini, Greece</span>
                    </a>
                </div>

                <!-- Card 3 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="#hotel-search-form"
                        class="deal-card d-block position-relative rounded-3 overflow-hidden shadow-sm">
                        <img src="{{ asset('uploads/sea.png') }}" alt="Miami, USA" class="deal-img w-100">
                        <span class="deal-caption">Miami, USA</span>
                    </a>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="#hotel-search-form" class="btn btn-cta px-4 py-2">
                    Explore More –Book a Hotel Today
                </a>
            </div>
        </div>
    </div>
    <div class="section-offers py-5">
        <div class="container">
            <h2 class="text-center mb-5">Hot Offers for Your Summer Getaway</h2>

            <div class="row g-5 justify-content-center">
                <!-- Card 1 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="#hotel-search-form"
                        class="deal-card d-block position-relative rounded-3 overflow-hidden shadow-sm">
                        <img src="{{ asset('uploads/milano.png') }}" alt="Milano, Italy" class="deal-img w-100">
                        <span class="deal-caption">Milano, Italy</span>
                    </a>
                </div>

                <!-- Card 2 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="#hotel-search-form"
                        class="deal-card d-block position-relative rounded-3 overflow-hidden shadow-sm">
                        <img src="{{ asset('uploads/santorini.png') }}" alt="Santorini, Greece" class="deal-img w-100">
                        <span class="deal-caption">Santorini, Greece</span>
                    </a>
                </div>

                <!-- Card 3 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="#hotel-search-form"
                        class="deal-card d-block position-relative rounded-3 overflow-hidden shadow-sm">
                        <img src="{{ asset('uploads/sea.png') }}" alt="Miami, USA" class="deal-img w-100">
                        <span class="deal-caption">Miami, USA</span>
                    </a>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="#hotel-search-form" class="btn btn-cta px-4 py-2">
                    Explore More –Book a Hotel Today
                </a>
            </div>
        </div>
    </div>

    <style>
        :root {
            --brand-blue: #1e3a8a;
            --muted: #6b7280;
        }

        .star-wrap i {
            font-size: 1rem
        }

        /* Hero slider */
        .custom-slider {
            position: relative;
            width: 100%;
            aspect-ratio: 16/10;
            overflow: hidden;
            background: #f2f2f2
        }

        .custom-slider .slides {
            width: 100%;
            height: 100%
        }

        .custom-slider .slide-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none
        }

        .custom-slider .slide-img.active {
            display: block
        }

        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, .35);
            border: none;
            font-size: 2rem;
            line-height: 1;
            color: #fff;
            padding: 2px 10px;
            cursor: pointer;
            z-index: 5
        }

        .slider-btn:hover {
            background: rgba(0, 0, 0, .5)
        }

        .prev-btn {
            left: 10px
        }

        .next-btn {
            right: 10px
        }

        .dots {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 10px;
            display: flex;
            gap: 6px;
            justify-content: center
        }

        .dots button {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            border: 0;
            background: #fff;
            opacity: .6
        }

        .dots button.active {
            opacity: 1
        }

        /* Blue policy card */
        .policy-card {
            overflow: hidden;
        }

        .policy-card__header {
            color: #0B0B45;
            font-weight: 600;
            font-size: 18px;
        }

        .policy-card__body {
            padding: 1rem;
            font-size: .95rem;
            line-height: 1.5;
            background: #0B0B45;
            color: white;
        }

        /* Room cards aligned like screenshot */
        .room-card {
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .04)
        }

        .room-image-slider {
            position: relative;
            width: 100%;
            height: 220px !important;
            /* set the fixed height you want */
            background: #f6f6f6;
            overflow: hidden;
        }

        @media (min-width:768px) {
            .room-image-slider {
                height: 100%
            }
        }

        .room-slide-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
            cursor: pointer;
        }

        .room-slide-img.active {
            display: block
        }

        .room-image-slider .slider-btn {
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.6rem;
            padding: 0 8px
        }

        .room-image-slider .room-prev {
            left: 8px
        }

        .room-image-slider .room-next {
            right: 8px
        }

        .price-badge {
            min-width: 150px
        }

        .price-badge .price-amount {
            display: inline-block;
            background: #eef2ff;
            color: #111;
            padding: .25rem .6rem;
            font-weight: 600
        }

        /* Policy table tweaks */
        .table> :not(caption)>*>* {
            padding: .6rem .6rem
        }

        .table th {
            width: 35%;
            white-space: nowrap
        }

        .text-muted {
            color: var(--muted) !important
        }

        /* Image modal */
        .modal-img-viewer {
            display: none;
            position: fixed;
            z-index: 1000;
            inset: 0;
            background: rgba(0, 0, 0, .9);
            justify-content: center;
            align-items: center
        }

        .modal-img-viewer img {
            max-width: 90%;
            max-height: 90%;
        }

        .modal-img-viewer .close-btn {
            position: absolute;
            top: 30px;
            right: 30px;
            font-size: 2rem;
            color: #fff;
            background: transparent;
            border: none;
            cursor: pointer
        }

        /* Price and button styling to match screenshot vibe */
        .price-wrap {
            min-width: 150px;
        }

        .btn-choose {
            background: #f97316;
            /* orange */
            border-color: #f97316;
            color: #fff;
        }

        .btn-choose:hover {
            filter: brightness(0.95);
        }

        /* Make the right column breathe a bit */
        .room-card .col-md-6:last-child {
            background: #fff;
        }

        /* Keep the left image nice and tall */
        .room-image-slider {
            position: relative;
            width: 100%;
            height: 220px;
            background: #f6f6f6;
        }

        @media (min-width:768px) {
            .room-image-slider {
                height: 100%;
                min-height: 230px;
            }
        }

        /* ----- Static room image height for every card ----- */
        :root {
            --room-img-height: 220px;
            /* change this value to whatever height you want */
        }

        .room-image-slider {
            position: relative;
            width: 100%;
            height: var(--room-img-height) !important;
            background: #f6f6f6;
            overflow: hidden;
        }

        .room-slide-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
            cursor: pointer;
        }

        .room-slide-img.active {
            display: block;
        }

        /* keep slider arrows */
        .room-image-slider .slider-btn {
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.6rem;
            padding: 0 8px;
        }

        .room-image-slider .room-prev {
            left: 8px;
        }

        .room-image-slider .room-next {
            right: 8px;
        }

        /* Make right column at least as tall as image so the card looks even */
        .room-card .col-md-6:last-child {
            min-height: var(--room-img-height);
            background: #fff;
        }

        /* ----- Price stack: label -> amount -> button ----- */
        .price-stack .label {
            font-size: .875rem;
            font-weight: 600;
            color: #16a34a;
            /* green 'Total price' label */
        }

        .price-stack .amount {
            font-size: 1.9rem;
            /* big price */
            font-weight: 800;
            line-height: 1.1;
        }

        .btn-choose {
            background: #f97316;
            /* orange */
            border-color: #f97316;
            color: #fff;
            min-width: 140px;
        }

        .btn-choose:hover {
            filter: brightness(0.95);
        }

        /* smooth, framework-free collapse */
        .collapse-box {
            overflow: hidden;
            max-height: 0;
            opacity: 0;
            transition: max-height .3s ease, opacity .25s ease;
        }

        .collapse-box.is-open {
            opacity: 1;
        }


        /* responsive two-column grid; collapses to 1 col when narrow */
        .more-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
            padding: 14px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
        }

        /* cards inside the grid */
        .info-block {
            border-radius: 10px;
            padding: 12px;
            min-width: 0;
            word-break: break-word;
        }

        .info-title {
            font-weight: 600;
            font-size: .95rem;
            margin-bottom: .35rem;
        }

        .more-grid ul {
            padding-left: 1rem;
            margin-bottom: .75rem;
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

        /* Card images with fixed aspect & overlay caption */
        .deal-img {
            aspect-ratio: 4 / 3;
            /* keeps all cards same height */
            object-fit: cover;
            display: block;
        }

        .deal-card::after {
            /* bottom gradient like the mock */
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 42%;
            pointer-events: none;
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

        .section-title {
            position: relative;
            display: inline-block;
            color: #0B0B45;
        }

        .deal-card::after {
            /* bottom gradient like the mock */
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
    </style>

    <!-- Bootstrap 5 bundle (place before </body>) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">
    </script>


    <script>
        // ---- Hero slider ----
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide-img');
        const dotsWrap = document.getElementById('heroDots');

        function renderDots() {
            if (!dotsWrap || !slides.length) return;
            dotsWrap.innerHTML = '';
            slides.forEach((_, i) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = i === currentSlide ? 'active' : '';
                b.addEventListener('click', () => {
                    currentSlide = i;
                    showSlide(currentSlide);
                });
                dotsWrap.appendChild(b);
            });
        }

        function showSlide(i) {
            slides.forEach((s, idx) => s.classList.toggle('active', idx === i));
            [...(dotsWrap?.children || [])].forEach((d, idx) => d.classList.toggle('active', idx === i));
        }

        function changeSlide(step) {
            if (!slides.length) return;
            currentSlide = (currentSlide + step + slides.length) % slides.length;
            showSlide(currentSlide);
        }

        if (slides.length) {
            renderDots();
            showSlide(0);
            setInterval(() => changeSlide(1), 5000);
        }

        // ---- Room sliders (per-card) ----
        const roomSliderIndex = {};

        function changeRoomSlide(roomId, step) {
            const card = document.querySelector(`.room-card[data-room-id="${roomId}"]`);
            if (!card) return;
            const imgs = card.querySelectorAll('.room-slide-img');
            if (!imgs.length) return;
            roomSliderIndex[roomId] = ((roomSliderIndex[roomId] ?? 0) + step + imgs.length) % imgs.length;
            imgs.forEach((el, i) => el.classList.toggle('active', i === roomSliderIndex[roomId]));
        }

        // ---- Modal ----
        function openFullImage(url) {
            const m = document.getElementById('imageModal');
            const img = document.getElementById('modalImage');
            img.src = url;
            m.style.display = 'flex';
        }

        function closeFullImage() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // expose to global
        window.changeRoomSlide = changeRoomSlide;
        window.changeSlide = changeSlide;
        window.openFullImage = openFullImage;
        window.closeFullImage = closeFullImage;
    </script>

    <script>
        // Framework-free "See more" toggle with smooth height animation
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.see-more-btn');
            if (!btn) return;

            const id = btn.getAttribute('data-target');
            const panel = document.getElementById(id);
            if (!panel) return;

            const isOpen = panel.classList.toggle('is-open');

            // animate to content height
            if (isOpen) {
                panel.style.maxHeight = panel.scrollHeight + 'px';
            } else {
                // collapse back
                panel.style.maxHeight = panel.scrollHeight + 'px'; // set current first
                requestAnimationFrame(() => {
                    panel.style.maxHeight = '0px';
                });
            }

            btn.setAttribute('aria-expanded', String(isOpen));
            btn.textContent = isOpen ? 'See less' : 'See more';
        });

        // If content inside expands later (images load), keep height correct
        const ro = new ResizeObserver(entries => {
            entries.forEach(entry => {
                const el = entry.target;
                if (el.classList.contains('is-open')) {
                    el.style.maxHeight = el.scrollHeight + 'px';
                }
            });
        });
        document.querySelectorAll('.collapse-box').forEach(el => ro.observe(el));
    </script>
    <script>
        // Progressive "Load more" for room cards
        document.addEventListener('DOMContentLoaded', () => {
            const list = document.getElementById('roomsList');
            if (!list) return;

            const cards = Array.from(list.querySelectorAll('.room-card'));
            const btn = document.getElementById('loadMoreRooms');
            const STEP = btn ? parseInt(btn.getAttribute('data-step') || '5', 10) : 5;

            // Hide all beyond the first STEP
            let visibleCount = Math.min(STEP, cards.length);
            cards.forEach((card, idx) => {
                if (idx >= visibleCount) card.classList.add('d-none');
            });

            if (btn) {
                btn.addEventListener('click', () => {
                    const next = Math.min(visibleCount + STEP, cards.length);
                    for (let i = visibleCount; i < next; i++) {
                        cards[i].classList.remove('d-none');
                    }
                    visibleCount = next;

                    // Hide button when everything is shown
                    if (visibleCount >= cards.length) {
                        btn.classList.add('d-none');
                    }
                });
            }
        });
    </script>


@endsection
