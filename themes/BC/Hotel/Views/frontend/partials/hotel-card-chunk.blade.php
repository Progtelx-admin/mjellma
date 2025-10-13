@php
    $query = $query ?? array_merge(
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

    $currencySym = $currencySym ?? match (request('currency', 'EUR')) {
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
            @if ($hotel->daily_price && ($hotel->is_available ?? true))
                <span class="grid-price-badge">
                    {{ number_format($hotel->daily_price, 0) }}{{ $currencySym }}
                </span>
            @elseif (isset($hotel->is_available) && !$hotel->is_available)
                <span class="grid-price-badge bg-secondary">
                    N/A
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
                With a stay at {{ \Illuminate\Support\Str::limit($hotel->name, 28) }}, you'll
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
                        @if ($hotel->daily_price && ($hotel->is_available ?? true))
                            From {{ number_format($hotel->daily_price, 0) }}
                            {{ $currencySym }} / night
                        @elseif (isset($hotel->is_available) && !$hotel->is_available)
                            <span class="text-warning">No rooms available</span>
                        @else
                            <span class="text-muted small">
                                <i class="fa fa-spinner fa-spin"></i> Loading price...
                            </span>
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


