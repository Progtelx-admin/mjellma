

<?php $__env->startSection('content'); ?>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                Booking Core
                <span class="badge badge-warning">PRO</span>
            </div>
            <div class="card-body p-0">
                <?php echo $__env->make('Pro::admin.upgrade-form', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('Layout::admin.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\mjellma\app\Pro/Views/admin/upgrade.blade.php ENDPATH**/ ?>