<div class="bravo_filter">
    <form action="<?php echo e(url(app_get_locale(false,false,'/').config('tour.tour_route_prefix'))); ?>" class="bravo_form_filter">
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
                    $price_min = $pri_from = floor ( App\Currency::convertPrice($tour_min_max_price[0]) );
                    $price_max = $pri_to = ceil ( App\Currency::convertPrice($tour_min_max_price[1]) );
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
        <div class="g-filter-item">
            <div class="item-title">
                <h3><?php echo e(__("Review Score")); ?></h3>
                <i class="fa fa-angle-up" aria-hidden="true"></i>
            </div>
            <div class="item-content">
                <ul>
                    <?php for($number = 5 ;$number >= 1 ; $number--): ?>
                        <li>
                            <div class="bravo-checkbox">
                                <label>
                                    <input name="review_score[]" type="checkbox" value="<?php echo e($number); ?>" <?php if(  in_array($number , request()->query('review_score',[])) ): ?>  checked <?php endif; ?>>
                                    <span class="checkmark"></span>
                                    <?php for($review_score = 1 ;$review_score <= $number ; $review_score++): ?>
                                        <i class="fa fa-star"></i>
                                    <?php endfor; ?>
                                </label>
                            </div>
                        </li>
                    <?php endfor; ?>
                </ul>
            </div>
        </div>
        <div class="g-filter-item">
            <div class="item-title">
                <h3><?php echo e(__("Tour Type")); ?></h3>
                <i class="fa fa-angle-up" aria-hidden="true"></i>
            </div>
            <div class="item-content">
                <ul>
                    <?php
                    $current_category_ids = Request::query('cat_id');
                    $traverse = function ($categories, $prefix = '') use (&$traverse, $current_category_ids) {
                    $i = 0;
                    foreach ($categories as $category) {
                        $checked = '';
                        if (!empty($current_category_ids)) {
                            foreach ($current_category_ids as $key => $current) {
                                if ($current == $category->slug)
                                    $checked = 'checked';
                            }
                        }
                        $traslate = $category->translate()
                        ?>
                        <li <?php if($i > 2 and empty($current_category_ids)): ?> class="hide" <?php endif; ?>>
                            <div class="bravo-checkbox">
                                <label>
                                    <input name="cat_id[]" <?php echo e($checked); ?> type="checkbox" value="<?php echo e($category->slug); ?>"> <?php echo e($prefix); ?> <?php echo e($traslate->name); ?>

                                    <span class="checkmark"></span>
                                </label>
                            </div>
                        </li>
                        <?php
                        $i++;
                        $traverse($category->children, $prefix . '-');
                        }
                    };
                    $traverse($tour_category);
                    ?>
                </ul>
                <?php if(count($tour_category) > 3 and empty($current_category_ids)): ?>
                    <button type="button" class="btn btn-link btn-more-item"><?php echo e(__("More")); ?> <i class="fa fa-caret-down"></i></button>
                <?php endif; ?>
            </div>
        </div>
        <?php echo $__env->make('Layout::global.search.filters.attrs', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </form>
</div>


<?php /**PATH /home/u983725807/domains/rezervo24.com/themes/BC/Tour/Views/frontend/layouts/search/filter-search.blade.php ENDPATH**/ ?>