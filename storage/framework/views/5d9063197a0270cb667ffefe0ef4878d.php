<div class="list-item">
    <div class="row">
        <?php if($rows->total() > 0): ?>
            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-lg-4 col-md-6">
                    <?php echo $__env->make('Car::frontend.layouts.search.loop-grid', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
            <div class="col-lg-12">
                <?php echo e(__("Car not found")); ?>

            </div>
        <?php endif; ?>
    </div>
</div>
<div class="bravo-pagination">
    <?php echo e($rows->appends(request()->except(['_ajax']))->links()); ?>

    <?php if($rows->total() > 0): ?>
        <span class="count-string"><?php echo e(__("Showing :from - :to of :total Cars",["from"=>$rows->firstItem(),"to"=>$rows->lastItem(),"total"=>$rows->total()])); ?></span>
    <?php endif; ?>
</div>
<?php /**PATH /home/webdesig/amansite.com/themes/BC/Car/Views/frontend/ajax/search-result.blade.php ENDPATH**/ ?>