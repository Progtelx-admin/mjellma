<?php
    $event_map_search_fields = setting_item_array('event_map_search_fields');
    $usedAttrs = [];
    foreach ($event_map_search_fields as $field){
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
<?php /**PATH /home/u983725807/domains/rezervo24.com/themes/BC/Event/Views/frontend/layouts/search-map/advance-filter.blade.php ENDPATH**/ ?>