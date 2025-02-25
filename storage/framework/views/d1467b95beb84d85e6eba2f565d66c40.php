<form action="<?php echo e(route('hotel.search')); ?>" class="form bravo_form" method="get">
    <div class="g-field-search">
        <div class="row">
            <?php
                $hotel_search_fields = setting_item_array('hotel_search_fields');
                $hotel_search_fields = array_values(
                    \Illuminate\Support\Arr::sort($hotel_search_fields, fn($value) => $value['position'] ?? 0),
                );
            ?>
            <?php if(!empty($hotel_search_fields)): ?>
                <?php $__currentLoopData = $hotel_search_fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $field['title'] = $field['title_'.app()->getLocale()] ?? $field['title'] ?? "" ?>
                    <div class="col-md-<?php echo e($field['size'] ?? '6'); ?> border-right">
                        <?php switch($field['field']):
                            case ('service_name'): ?>
                                <?php echo $__env->make('Hotel::frontend.layouts.search.fields.service_name', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            <?php break; ?>

                            <?php case ('location'): ?>
                                <?php echo $__env->make('Hotel::frontend.layouts.search.fields.location', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            <?php break; ?>

                            <?php case ('date'): ?>
                                <?php echo $__env->make('Hotel::frontend.layouts.search.fields.date', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            <?php break; ?>

                            <?php case ('attr'): ?>
                                <?php echo $__env->make('Hotel::frontend.layouts.search.fields.attr', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            <?php break; ?>

                            <?php case ('guests'): ?>
                                <?php echo $__env->make('Hotel::frontend.layouts.search.fields.guests', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                            <?php break; ?>
                        <?php endswitch; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="g-button-submit">
        <button class="btn btn-primary btn-search" type="submit"><?php echo e(__('Search')); ?></button>
    </div>
</form>


<script>
    const locationInput = document.getElementById('location');
    const suggestionsList = document.getElementById('suggestions');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');

    locationInput.addEventListener('input', function() {
        const query = this.value;
        if (query.length < 3) {
            suggestionsList.innerHTML = "";
            suggestionsList.style.display = 'none';
            return;
        }

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}`)
            .then(response => response.json())
            .then(data => {
                suggestionsList.innerHTML = "";
                suggestionsList.style.display = data.length > 0 ? 'block' : 'none';

                data.forEach(location => {
                    const suggestion = document.createElement('li');
                    suggestion.classList.add('list-group-item');
                    suggestion.textContent = location.display_name;
                    suggestion.style.cursor = 'pointer';

                    suggestion.addEventListener('click', function() {
                        // Fill inputs with selected location data
                        locationInput.value = location.display_name;
                        latitudeInput.value = location.lat;
                        longitudeInput.value = location.lon;

                        // Clear suggestions
                        suggestionsList.innerHTML = "";
                        suggestionsList.style.display = 'none';
                    });

                    suggestionsList.appendChild(suggestion);
                });
            })
            .catch(error => console.error('Error fetching Nominatim API:', error));
    });

    // Hide the suggestions list if the click is outside
    document.addEventListener('click', function(e) {
        if (!suggestionsList.contains(e.target) && e.target !== locationInput) {
            suggestionsList.innerHTML = "";
            suggestionsList.style.display = 'none';
        }
    });
</script>
<?php /**PATH C:\laragon\www\mjellma\themes/BC/Hotel/Views/frontend/layouts/search/form-search.blade.php ENDPATH**/ ?>