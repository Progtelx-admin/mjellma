@php
    $query =
        $query ??
        array_merge(
            ['id' => $hotel->hotel_id],
            request()->only([
                'hotel_name',
                'location',
                'hid',
                'etg_hotel_id',
                'hotel_region_id',
                'region_id',
                'region_type',
                'region_country_code',
                'checkin',
                'checkout',
                'adults',
                'rooms',
                'latitude',
                'longitude',
                'currency',
                'breakfast_included',
                'min_price',
                'max_price',
                'star_rating',
                'sort_by',
            ]),
            ['children_count' => request('children_count', 0)],
            ['children' => request('children', [])],
        );

    $currencySym =
        $currencySym ??
        match (request('currency', 'EUR')) {
            'USD' => '$',
            'GBP' => '£',
            'EUR' => '€',
            default => request('currency', '€'),
        };

    $cardImageUrl = $hotel->image_url ?? '';
    if ($cardImageUrl !== '' && str_contains($cardImageUrl, 'cdn.worldota.net')) {
        $cardImageUrl = preg_replace('#(/t/)\d+x\d+(/)#', '${1}640x400${2}', $cardImageUrl) ?: $cardImageUrl;
    }
    $imgLoading = (isset($loop) && $loop->index < 6) ? 'eager' : 'lazy';
@endphp

<a href="{{ route('hotel.info', $query) }}" class="text-decoration-none hotel-card-link"
    data-hotel-id="{{ $hotel->hotel_id }}"
    data-hotel-price="{{ $hotel->daily_price ?? '' }}"
    data-hotel-rating="{{ $hotel->star_rating ?? 0 }}"
    data-hotel-lat="{{ $hotel->latitude ?? '' }}"
    data-hotel-lng="{{ $hotel->longitude ?? '' }}">
    <article class="hotel-listcard">
        {{-- IMAGE --}}
        <div class="hotel-listcard__media">
            @if ($cardImageUrl)
                <img src="{{ $cardImageUrl }}" alt="{{ $hotel->name }}" width="640" height="400"
                    loading="{{ $imgLoading }}" decoding="async">
            @else
                <img src="{{ asset('uploads/no_img.jpeg') }}" alt="No image available" width="640" height="400"
                    loading="{{ $imgLoading }}" decoding="async">
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
