<?php
    $selected = (array) Request::query('attrs',[]);
?>
<?php $__currentLoopData = $attributes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if(empty($item['hide_in_filter_search'])): ?>
        <?php
            if(in_array($item->id,$usedAttrs)) continue;
            $translate = $item->translate();
        ?>
        <div class="filter-item">
            <div class="filter-title"><strong><?php echo e($translate->name); ?></strong></div>
            <ul class="filter-items row">
                <?php $__currentLoopData = $item->terms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $translate = $term->translate(); ?>
                    <li class="filter-term-item col-xs-6 col-md-4">
                        <label>
                            <input
                                <?php if(in_array($term->slug,$selected[$item->id] ?? [])): ?> checked
                                <?php endif; ?> type="checkbox"
                                name="attrs[<?php echo e($item->id); ?>][]"
                                value="<?php echo e($term->slug); ?>"
                            > <?php echo e($translate->name); ?>

                        </label>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH /home/u983725807/domains/rezervo24.com/themes/BC/Layout/global/search/filters-map/attrs.blade.php ENDPATH**/ ?>