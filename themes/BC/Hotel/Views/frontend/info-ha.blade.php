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

        {{-- HOTEL HEADER --}}
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

        {{-- IMAGE CAROUSEL --}}
        @if (!empty($hotel['images_ext']))
            <div id="hotelImageCarousel" class="carousel slide mb-5" data-bs-ride="carousel">
                <div class="carousel-inner">
                    @foreach ($hotel['images_ext'] as $idx => $img)
                        <div class="carousel-item {{ $idx === 0 ? 'active' : '' }}">
                            <img src="{{ $img }}" class="d-block w-100 rounded shadow-sm"
                                style="height:600px;object-fit:cover;" alt="Slide {{ $idx + 1 }}">
                        </div>
                    @endforeach
                </div>
                <button class="carousel-control-prev" data-bs-target="#hotelImageCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" data-bs-target="#hotelImageCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>
        @endif

        {{-- ADDITIONAL POLICY INFO --}}
        @if (!empty($hotel['metapolicy_extra_info']))
            <div class="mb-5">
                <h4>Additional Policy Information</h4>
                <p>{!! $hotel['metapolicy_extra_info'] !!}</p>
            </div>
        @endif

        {{-- AVAILABLE ROOMS --}}
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
                            $allTaxes = data_get($payment, 'tax_data.taxes', []);
                            $onSiteTaxes = collect($allTaxes)->where('included_by_supplier', false)->values();
                            $policies = data_get($payment, 'cancellation_penalties.policies', []);
                            $netAmount = data_get(
                                $payment,
                                'commission_info.charge.amount_net',
                                $payment['amount'] ?? 0,
                            );
                            $commissionAmount = data_get($payment, 'commission_info.charge.amount_commission', null);
                            $currency = $payment['currency_code'] ?? '';
                        @endphp

                        <h4 class="text-primary">
                            {{ number_format((float) $netAmount, 2) }} {{ $currency }}
                        </h4>

                        @if (!is_null($commissionAmount))
                            <p class="text-muted">
                                <small>Your commission: {{ number_format((float) $commissionAmount, 2) }}
                                    {{ $currency }}</small>
                            </p>
                        @endif

                        {{-- On-site Taxes --}}
                        @if ($onSiteTaxes->isNotEmpty())
                            <div class="mt-2 text-start">
                                <strong>On-site Taxes:</strong>
                                <ul class="list-unstyled mb-0">
                                    @foreach ($onSiteTaxes as $tax)
                                        <li>
                                            {{ ucwords(str_replace('_', ' ', $tax['name'])) }}:
                                            {{ number_format((float) $tax['amount'], 2) }} {{ $tax['currency_code'] }}
                                            <small class="text-muted">(to be paid at hotel)</small>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Cancellation Policies --}}
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

                                        @if (is_null($start) && !is_null($end))
                                            <li>
                                                Free cancellation until
                                                <strong>{{ $end->format('Y-m-d H:i') }} UTC</strong>.
                                            </li>
                                        @elseif (!is_null($start) && !is_null($end))
                                            <li>
                                                From <strong>{{ $start->format('Y-m-d H:i') }} UTC</strong>
                                                to <strong>{{ $end->format('Y-m-d H:i') }} UTC</strong>:
                                                Fee {{ $fee }} {{ $currency }}
                                            </li>
                                        @elseif (!is_null($start) && is_null($end))
                                            <li>
                                                After <strong>{{ $start->format('Y-m-d H:i') }} UTC</strong>:
                                                Fee {{ $fee }} {{ $currency }}
                                            </li>
                                        @else
                                            <li>
                                                No free cancellation available. Full charge applies.
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Choose Button --}}
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

    </div>
@endsection

@push('styles')
    <style>
        .room-card {
            border-radius: 10px;
        }

        ul.list-unstyled li {
            margin-bottom: 0.5rem;
        }

        ul.list-unstyled small {
            font-style: italic;
            color: #6c757d;
        }

        ul.list-unstyled strong {
            font-weight: 600;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endpush
