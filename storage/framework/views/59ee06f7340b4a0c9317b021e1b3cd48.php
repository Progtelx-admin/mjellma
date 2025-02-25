<div class="form-group">
    <i class="field-icon fa icofont-map"></i>
    <div class="form-content">
        <label><?php echo e($field['title']); ?></label>
        <input type="text" id="location" name="location" class="form-control" placeholder="Where are you going?"
            autocomplete="off" required>

        <input type="hidden" id="latitude" name="map_lat" value="<?php echo e(request()->input('map_lat')); ?>">
        <input type="hidden" id="longitude" name="map_lng" value="<?php echo e(request()->input('map_lng')); ?>">
    </div>
</div>

<ul id="suggestions" class="list-group position-absolute w-100 mt-1"
    style="max-height: 200px; overflow-y: auto; display: none; z-index: 1000;">
</ul>
<?php /**PATH C:\laragon\www\mjellma\themes/BC/Hotel/Views/frontend/layouts/search/fields/location.blade.php ENDPATH**/ ?>