<form action="<?php echo e(url( app_get_locale(false,false,'/').config('car.car_route_prefix') )); ?>" class="form bravo_form d-flex justify-content-start" method="get" onsubmit="return false;">
    <input type="hidden" name="_layout" value="<?php echo e($layout ?? ''); ?>">
    <?php $car_map_search_fields = setting_item_array('car_map_search_fields');

    $car_map_search_fields = array_values(\Illuminate\Support\Arr::sort($car_map_search_fields, function ($value) {
        return $value['position'] ?? 0;
    }));

    ?>
    <?php if(!empty($car_map_search_fields)): ?>
        <?php $__currentLoopData = $car_map_search_fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php switch($field['field']):
                case ('location'): ?>
                    <?php echo $__env->make('Car::frontend.layouts.search-map.fields.location', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php break; ?>
                <?php case ('attr'): ?>
                    <?php echo $__env->make('Layout::global.search.map.fields.attr', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php break; ?>
                <?php case ('date'): ?>
                    <?php echo $__env->make('Car::frontend.layouts.search-map.fields.date', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php break; ?>
                <?php case ('price'): ?>
                    <?php echo $__env->make('Car::frontend.layouts.search-map.fields.price', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php break; ?>
                <?php case ('advance'): ?>
                    <div class="filter-item filter-simple">
                        <div class="form-group">
                            <span class="filter-title toggle-advance-filter" data-target="#advance_filters"><?php echo e(__('More filters')); ?> <i class="fa fa-angle-down"></i></span>
                        </div>
                    </div>
                <?php break; ?>

            <?php endswitch; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>



</form>
<?php /**PATH /home/u983725807/domains/rezervo24.com/themes/BC/Car/Views/frontend/layouts/search-map/form-search-map.blade.php ENDPATH**/ ?>