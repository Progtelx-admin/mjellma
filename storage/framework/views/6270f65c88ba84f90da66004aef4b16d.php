<div class="filter-item">
    <div class="form-group form-date-field form-date-search clearfix  has-icon is_single_picker">
        <i class="field-icon icofont-wall-clock"></i>
        <div class="date-wrapper clearfix">
            <div class="check-in-wrapper d-flex align-items-center">
                <div class="render check-in-render"><?php echo e(Request::query('start',display_date(strtotime("today")))); ?></div>
            </div>
        </div>
        <input type="hidden" class="check-in-input" value="<?php echo e(Request::query('start',display_date(strtotime("today")))); ?>" name="start">
        <input type="text" class="check-in-out input-filter" name="date" value="<?php echo e(Request::query('date')); ?>">
    </div>
</div><?php /**PATH /home/webdesig/amansite.com/themes/BC/Boat/Views/frontend/layouts/search-map/fields/date.blade.php ENDPATH**/ ?>