<div class="bravo_filter">
    <form action="<?php echo e(url(app_get_locale(false,false,'/').config('flight.flight_route_prefix'))); ?>" class="bravo_form_filter">
        <div class="filter-title">
            <?php echo e(__("FILTER BY")); ?>

        </div>
        <div class="g-filter-item">
            <div class="item-title">
                <h3><?php echo e(__("Filter Price")); ?></h3>
                <i class="fa fa-angle-up" aria-hidden="true"></i>
            </div>
            <div class="item-content">
                <div class="bravo-filter-price">
                    <?php
                    $price_min = $pri_from = floor ( App\Currency::convertPrice($flight_min_max_price[0]) );
                    $price_max = $pri_to = ceil ( App\Currency::convertPrice($flight_min_max_price[1]) );
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
                    <button type="submit" class="btn btn-link btn-apply-price-range"><?php echo e(__("APPLY")); ?></button>
                </div>
            </div>
        </div>
        <?php echo $__env->make('Layout::global.search.filters.attrs', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </form>
</div>
<?php /**PATH /home/webdesig/amansite.com/themes/BC/Flight/Views/frontend/layouts/search/filter-search.blade.php ENDPATH**/ ?>