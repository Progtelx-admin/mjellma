<div class="filter-item">
    <div class="form-group">
        <i class="field-icon icofont-beach"></i>
        <div class="form-content">
            <?php
            $cat_ids = Request::query('cat_id');
            $cat_name = "";
            $list_cat_json = [];
            $traverse = function ($categories, $prefix = '') use (&$traverse, &$list_cat_json , &$cat_name , $cat_ids) {
                foreach ($categories as $category) {
                    $translate = $category->translate();
                    if (!empty($cat_ids[0]) and $cat_ids[0] == $category->id){
                        $cat_name = $translate->name;
                    }
                    $list_cat_json[] = [
                        'id' => $category->id,
                        'title' => $prefix . ' ' . $translate->name,
                    ];
                    $traverse($category->children, $prefix . '-');
                }
            };
            $traverse($tour_category);
            ?>
            <div class="smart-search">
                <input type="text" class="smart-select parent_text form-control" readonly placeholder="<?php echo e(__("All Category")); ?>" value="<?php echo e($cat_name); ?>" data-default="<?php echo e(json_encode($list_cat_json)); ?>">
                <input type="hidden" class="child_id" name="cat_id[]" value="<?php echo e($cat_ids[0] ?? ""); ?>">
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/webdesig/amansite.com/themes/BC/Tour/Views/frontend/layouts/search-map/fields/category.blade.php ENDPATH**/ ?>