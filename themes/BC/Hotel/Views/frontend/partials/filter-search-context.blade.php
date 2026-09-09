@php
    $idPrefix = $idPrefix ?? 'filter';
    $checkinDisplay = request('checkin') ? \Carbon\Carbon::parse(request('checkin'))->format('d M Y') : null;
    $checkoutDisplay = request('checkout') ? \Carbon\Carbon::parse(request('checkout'))->format('d M Y') : null;
    $adultsCount = (int) request('adults', 1);
    $roomsCount = (int) request('rooms', 1);
    $childrenCount = (int) request('children_count', 0);
    $hotelNameFilter = trim((string) request('hotel_name', ''));
    $locationDisplay = trim((string) request('location', ''));
    $hasSearchSummary = ($checkinDisplay && $checkoutDisplay) || $hotelNameFilter !== '' || $locationDisplay !== '';
@endphp

@if ($hasSearchSummary)
    <div class="search-summary">
        @if ($hotelNameFilter !== '')
            <div class="search-summary__hotel">{{ $hotelNameFilter }}</div>
        @endif
        @if ($locationDisplay !== '')
            <div class="search-summary__location">{{ $locationDisplay }}</div>
        @endif
        @if ($checkinDisplay && $checkoutDisplay)
            <div class="search-summary__dates">{{ $checkinDisplay }} – {{ $checkoutDisplay }}</div>
            <div class="search-summary__occupancy">
                {{ $adultsCount }} {{ $adultsCount === 1 ? 'adult' : 'adults' }}
                · {{ $roomsCount }} {{ $roomsCount === 1 ? 'room' : 'rooms' }}
                · {{ $childrenCount }} {{ $childrenCount === 1 ? 'child' : 'children' }}
            </div>
        @endif
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
