
<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar"><?php echo e(__('Manage Fields')); ?></h1>
        </div>
        <?php echo $__env->make('admin.message', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <div class="filter-div d-flex justify-content-end ">
            <div class="col-left">
                <?php if(!empty($fields)): ?>
                    <form method="post" action="<?php echo e(route('user.admin.role.bulkEdit')); ?>" class="filter-form filter-form-left d-flex justify-content-end">
                        <?php echo e(csrf_field()); ?>

                        <select name="action" class="form-control">
                            <option value=""><?php echo e(__(" Bulk Actions ")); ?></option>
                            
                            
                            <option value="delete"><?php echo e(__(" Delete ")); ?></option>
                        </select>
                        <button data-confirm="<?php echo e(__("Do you want to delete?")); ?>" class="btn-info btn btn-icon dungdt-apply-form-btn" type="button"><?php echo e(__('Apply')); ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <form method="post" action="<?php echo e(route('user.admin.role.verifyFieldsStore')); ?>" class="needs-validation" novalidate>
                    <?php echo csrf_field(); ?>
                <div class="panel">
                    <div class="panel-title"><strong><?php echo e(__("Add new field")); ?></strong></div>
                    <div class="panel-body">
                        <?php echo $__env->make('User::admin.role.verifyFieldsForm', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" class="btn btn-success"><?php echo e(__('Add new')); ?></button>
                    </div>
                </div>
                </form>
            </div>
            <div class="col-md-8">
                <div class="panel">
                    <div class="panel-title"><?php echo e(__('All Fields')); ?></div>
                    <div class="panel-body">
                        <form action="" class="bravo-form-item">
                            <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th width="60px"><input type="checkbox" class="check-all"></th>
                                <th><?php echo e(__('ID')); ?></th>
                                <th><?php echo e(__('Icon')); ?></th>
                                <th><?php echo e(__('Name')); ?></th>
                                <th><?php echo e(__('Type')); ?></th>
                                <th><?php echo e(__('For roles')); ?></th>
                                <th><?php echo e(__('Order')); ?></th>
                                <th><?php echo e(__('Required')); ?></th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $__currentLoopData = $fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id=>$row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><input type="checkbox" name="ids[]" value="<?php echo e($id); ?>" class="check-item"></td>
                                    <td><?php echo e($id); ?></td>
                                    <td><i class="<?php echo e($row['icon'] ??''); ?>"></i></td>
                                    <td><?php echo e($row['name']); ?></td>
                                    <td><?php echo e(verify_type_to($row['type'],'name')); ?></td>
                                    <td><?php
                                        if(!empty($row['roles'])){
                                            $roles = \Modules\User\Models\Role::query()->whereIn('id',$row['roles'])->get();
                                            if(!empty($roles))
                                            {
                                                echo implode(", ",$roles->pluck('name')->toArray());
                                            }
                                        }
                                        ?>
                                    </td>
                                    <th><?php echo e($row['order'] ?? 0); ?></th>
                                    <td><?php echo e($row['required'] ? __("Yes") : 'No'); ?></td>
                                    <th><a href="<?php echo e(route('user.admin.role.verifyFieldsEdit',['id'=>$id])); ?>" class="btn btn-primary btn-sm"> <i class="fa fa-edit"></i>  <?php echo e(__('Edit')); ?></a></th>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\mjellma\modules/User/Views/admin/role/verifyFields.blade.php ENDPATH**/ ?>