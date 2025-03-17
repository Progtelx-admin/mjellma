
<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar"><?php echo e(__("All Modules")); ?></h1>
        </div>
        <?php echo $__env->make('admin.message', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <div class="filter-div d-flex justify-content-between ">
            <div class="col-left">
                <?php if(!empty($rows)): ?>
                    <form method="post" action="<?php echo e(route('core.admin.module.bulkEdit')); ?>" class="filter-form filter-form-left d-flex justify-content-start">
                        <?php echo e(csrf_field()); ?>

                        <select name="action" class="form-control">
                            <option value=""><?php echo e(__(" Bulk Actions ")); ?></option>
                            
                        </select>
                        <button class="btn-info btn btn-icon dungdt-apply-form-btn" type="button"><?php echo e(__('Apply')); ?></button>
                    </form>
                <?php endif; ?>
            </div>
            
        </div>
        <div class="panel">
            <div class="panel-body">
                <form action="" class="bravo-form-item">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th width="60px"><input type="checkbox" class="check-all"></th>
                                <th width="200px"> <?php echo e(__('Module name')); ?></th>
                                <th > <?php echo e(__('Description')); ?></th>
                                <th width="130px"> <?php echo e(__('Author')); ?></th>
                                <th width="100px"> <?php echo e(__('Version')); ?></th>
                                <th width="100px"> <?php echo e(__('Status')); ?></th>
                                <th width="100px"> <?php echo e(__('Actions')); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if(!empty($rows)): ?>
                                <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id=>$row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr class="">
                                        <td><input type="checkbox" name="ids[]" class="check-item" value="<?php echo e($id); ?>">
                                        </td>
                                        <td class="title">
                                            <div class="d-flex align-items-center">
                                                <?php if($thumb = $row::getThumb()): ?>
                                                    <div class="mr-3">
                                                        <img src="<?php echo e($thumb); ?>" width="50" height="50" alt="">
                                                    </div>
                                                <?php endif; ?>
                                                <a href="#"><?php echo e($row::getName()); ?></a>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo e($row::getDesc()); ?>

                                        </td>
                                        <td>
                                            <?php echo e($row::getAuthor()); ?>

                                        </td>
                                        <td>
                                            <?php echo e($row::getVersion()); ?>

                                        </td>
                                        <td><span class="badge badge-1">1</span></td>
                                        <td>

                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7"><?php echo e(__("No Module found")); ?></td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\mjellma\modules/Core/Views/admin/module/index.blade.php ENDPATH**/ ?>