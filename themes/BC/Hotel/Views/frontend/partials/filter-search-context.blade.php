@php
    $idPrefix = $idPrefix ?? 'filter';
    $locationDisplay = trim((string) request('location', ''));
    $hotelNameFilter = trim((string) request('hotel_name', ''));
    $checkinDisplay = request('checkin') ? \Carbon\Carbon::parse(request('checkin'))->format('d M Y') : null;
    $checkoutDisplay = request('checkout') ? \Carbon\Carbon::parse(request('checkout'))->format('d M Y') : null;
    $adultsCount = (int) request('adults', 1);
    $roomsCount = (int) request('rooms', 1);
    $childrenCount = (int) request('children_count', 0);
    $hasSearchSummary = $locationDisplay !== '' || $hotelNameFilter !== '' || ($checkinDisplay && $checkoutDisplay);
    $hasAppliedFilters = $hotelNameFilter !== '' || request()->filled('min_price') || request()->filled('max_price') || request()->filled('star_rating');
    $resetQuery = request()->except(['hotel_name', 'min_price', 'max_price', 'star_rating', 'sort_by']);
    $resetUrl = route('hotel.search', $resetQuery);
@endphp

{{-- Home search shown as text, not inputs --}}
@if ($hasSearchSummary)
    <div class="search-summary">
        @if ($hotelNameFilter !== '')
            <div class="search-summary__item">
                Hotel Name: <strong>{{ $hotelNameFilter }}</strong>
            </div>
        @endif
        @if ($locationDisplay !== '')
            <div class="search-summary__location">{{ $locationDisplay }}</div>
        @endif
        @if ($checkinDisplay && $checkoutDisplay)
            <div class="search-summary__dates">{{ $checkinDisplay }} - {{ $checkoutDisplay }}</div>
        @endif
        <div class="search-summary__occupancy">
            <strong>{{ $adultsCount }} {{ $adultsCount === 1 ? 'adult' : 'adults' }}</strong>
            ·
            <strong>{{ $roomsCount }} {{ $roomsCount === 1 ? 'room' : 'rooms' }}</strong>
            ·
            <strong>{{ $childrenCount }} {{ $childrenCount === 1 ? 'child' : 'children' }}</strong>
        </div>
    </div>
    <hr class="filter-divider">
@endif

<div class="hotel-name-search">
    <label for="{{ $idPrefix }}_hotel_name" class="hotel-name-search__label">Search by hotel name</label>
    <div class="hotel-name-search__field">
        <i class="fa fa-search" aria-hidden="true"></i>
        <input type="text" id="{{ $idPrefix }}_hotel_name" name="hotel_name"
            class="form-control hotel-name-search__input" value="{{ request('hotel_name') }}"
            placeholder="eg. Beach Antalya" autocomplete="off">
        <button type="button" class="hotel-name-search__clear{{ $hotelNameFilter === '' ? ' d-none' : '' }}"
            aria-label="Clear hotel name">&times;</button>
    </div>
</div>

@if ($hasAppliedFilters)
    <div class="applied-filters">
        <div class="applied-filters__head">
            <h6 class="applied-filters__title">APPLIED FILTERS</h6>
            <a href="{{ $resetUrl }}" class="applied-filters__reset">Reset filters</a>
        </div>
        @if ($hotelNameFilter !== '')
            <div class="applied-filters__chip">Name: {{ $hotelNameFilter }}</div>
        @endif
        @if (is_array(request('star_rating')) && count(request('star_rating')))
            <div class="applied-filters__chip">Stars: {{ implode(', ', request('star_rating')) }}</div>
        @endif
        @if (request()->filled('min_price') || request()->filled('max_price'))
            <div class="applied-filters__chip">
                Price:
                @if (request()->filled('min_price'))
                    €{{ request('min_price') }}
                @endif
                @if (request()->filled('min_price') && request()->filled('max_price'))
                    –
                @endif
                @if (request()->filled('max_price'))
                    €{{ request('max_price') }}
                @endif
            </div>
        @endif
    </div>
@endif
