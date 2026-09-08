{{-- Small map preview + "Show on the Map" button. Opens #mapModal; does not change search/API logic. --}}
<div class="hotel-map-preview {{ $wrapperClass ?? '' }}">
    <div class="hotel-map-preview__frame js-open-hotel-map" role="button" tabindex="0"
        aria-label="Show hotels on the map">
        <div id="{{ $mapId }}" class="hotel-map-preview__map"></div>
        <div class="hotel-map-preview__overlay">
            <span class="hotel-map-preview__count" data-map-count></span>
        </div>
    </div>
    <button type="button" class="btn btn-success w-100 hotel-map-preview__btn js-open-hotel-map">
        <i class="fa fa-map-marker me-2" aria-hidden="true"></i> Show on the Map
    </button>
</div>