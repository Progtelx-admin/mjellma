<div class="filter-item filter-simple dropdown">
    <div class="form-group dropdown-toggle" data-toggle="dropdown" >
        <h3 class="filter-title"><?php echo e(__('Price filter')); ?> <i class="fa fa-angle-down"></i></h3>
    </div>
    <div class="filter-dropdown dropdown-menu dropdown-menu-right">
        <div class="bravo-filter-price">
            <?php
            $price_min = $pri_from = floor ( App\Currency::convertPrice($car_min_max_price[0]) );
            $price_max = $pri_to = ceil ( App\Currency::convertPrice($car_min_max_price[1]) );
            if (!empty($price_range = Request::query('price_range'))) {
                $pri_from = explode(";", $price_range)[0];
                $pri_to = explode(";", $price_range)[1];
            }
            $currency = App\Currency::getCurrency( App\Currency::getCurrent() );
            ?>
            <input type="hidden" class="filter-price irs-hidden-input" name="price_range"
                   data-symbol=" <?php echo e($currency['symbol'] ?? ''); ?>"
                   data-min="<?php echo e($price_min); ?>"
                   data-max="<?php echo e($price_max); ?>"
                   data-from="<?php echo e($pri_from); ?>"
                   data-to="<?php echo e($pri_to); ?>"
                   readonly="" value="<?php echo e($price_range); ?>">
            <div class="text-right">
                <br>
                <a href="#" onclick="return false;" class="btn btn-primary btn-sm btn-apply-advances"><?php echo e(__("APPLY")); ?></a>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/u983725807/domains/rezervo24.com/themes/BC/Car/Views/frontend/layouts/search-map/fields/price.blade.php ENDPATH**/ ?>