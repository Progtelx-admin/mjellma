<?php
    $tour_map_search_fields = setting_item_array('tour_map_search_fields');
    $usedAttrs = [];
    foreach ($tour_map_search_fields as $field){
        if($field['field'] == 'attr' and !empty($field['attr']))
        {
            $usedAttrs[] = $field['attr'];
        }
    }
    $selected = (array) request()->query('terms');
?>
<div id="advance_filters" class="d-none">
    <div class="ad-filter-b">
        <?php echo $__env->make('Layout::global.search.filters-map.attrs', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
    <div class="ad-filter-f text-right">
        <a href="#" onclick="return false" class="btn btn-primary btn-apply-advances"><?php echo e(__("Apply Filters")); ?></a>
    </div>
</div>
<?php /**PATH /home/webdesig/amansite.com/themes/BC/Tour/Views/frontend/layouts/search-map/advance-filter.blade.php ENDPATH**/ ?>