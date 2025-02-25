<!-- location.blade.php (or wherever your location input is included) -->
<div class="form-group">
    <i class="field-icon fa icofont-map"></i>
    <div class="form-content">
        <label>{{ $field['title'] }}</label>
        <input type="text" id="location" name="location" class="form-control" placeholder="Where are you going?"
            autocomplete="off" required>

        <!-- Add these two hidden fields -->
        <input type="hidden" id="latitude" name="map_lat" value="{{ request()->input('map_lat') }}">
        <input type="hidden" id="longitude" name="map_lng" value="{{ request()->input('map_lng') }}">
    </div>
</div>

<!-- Suggestions list (absolute-positioned) -->
<ul id="suggestions" class="list-group position-absolute w-100 mt-1"
    style="max-height: 200px; overflow-y: auto; display: none; z-index: 1000;">
</ul>
