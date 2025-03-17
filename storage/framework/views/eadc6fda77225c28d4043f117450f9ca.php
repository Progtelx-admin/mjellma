<?php if(empty($row['id'])): ?>
<div class="form-group">
    <label><?php echo e(__("Field ID")); ?> <span class="text-danger">*</span></label>
    <input type="text" value="<?php echo e($row['id'] ?? ''); ?>" placeholder="<?php echo e(__("Field ID ")); ?>" name="id" class="form-control" required>
    <i><?php echo e(__('Must be unique. Only accept letter and number, dash, underscore, without space')); ?></i>
    <div class="invalid-feedback">
        <?php echo e(__('Please enter field id and make sure it unique')); ?>

    </div>
</div>
<?php else: ?>
    <input type="hidden" name="id" value="<?php echo e($row['id']); ?>">
<?php endif; ?>
<?php  $languages = \Modules\Language\Models\Language::getActive(); ?>
<div class="form-group form-group-item">
    <label><?php echo e(__("Field Name")); ?> <span class="text-danger">*</span></label>
    <div class="border p-2 rounded">
        <?php if(!empty($languages) && setting_item('site_enable_multi_lang') && setting_item('site_locale')): ?>
            <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $key_lang = setting_item('site_locale') != $language->locale ? "_".$language->locale : ""  ?>
                <div class="g-lang">
                    <div class="title-lang"><?php echo e($language->name); ?></div>
                    <input type="text" value="<?php echo e($row['name'.$key_lang] ?? ''); ?>" placeholder="" name="name<?php echo e($key_lang); ?>" class="form-control" required>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php else: ?>
            <input type="text" value="<?php echo e($row['name'] ?? ''); ?>" placeholder="" name="name" class="form-control" required>
        <?php endif; ?>
    </div>
    <div class="invalid-feedback">
        <?php echo e(__('Please enter field name')); ?>

    </div>
</div>
<div class="form-group">
    <label><?php echo e(__("Type")); ?> <span class="text-danger">*</span></label>
    <select class="custom-select" name="type" required>
        <option value="text"><?php echo e(__("Text")); ?></option>
        <option <?php echo e(($row['type'] ?? '') == 'phone' ? 'selected':''); ?> value="phone"><?php echo e(__("Phone")); ?></option>
        <option <?php echo e(($row['type'] ?? '') == 'number' ? 'selected':''); ?> value="number"><?php echo e(__("Number")); ?></option>
        <option <?php echo e(($row['type'] ?? '') == 'file' ? 'selected':''); ?> value="file"><?php echo e(__("File attachment")); ?></option>
        <option <?php echo e(($row['type'] ?? '') == 'multi_files' ? 'selected':''); ?> value="multi_files"><?php echo e(__("Multi files attachment")); ?></option>
    </select>
    <div class="invalid-feedback">
        <?php echo e(__('Please enter field type')); ?>

    </div>
</div>
<div class="form-group">
    <label><?php echo e(__("For Roles?")); ?> <span class="text-danger">*</span></label>
    <div class=" terms-scrollable">
        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div>
                <label >
                     <input type="checkbox" name="roles[]" value="<?php echo e($role->id); ?>" <?php if(!empty($row['roles'] ?? []) and in_array($role->id,$row['roles'] ?? [])): ?> checked <?php endif; ?> /><?php echo e(ucfirst($role->name)); ?>

                </label>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <div class="invalid-feedback">
        <?php echo e(__('Please enter roles')); ?>

    </div>
</div>
<div class="form-group">
    <label><?php echo e(__("Is Required?")); ?></label>
    <select class="custom-select" name="required">
        <option value=""><?php echo e(__("No")); ?></option>
        <option <?php echo e(($row['required'] ?? '') == 1 ? 'selected':''); ?> value="1"><?php echo e(__("Yes")); ?></option>
    </select>
</div>
<div class="form-group">
    <label><?php echo e(__("Order")); ?></label>
    <input type="text" value="<?php echo e($row['order'] ?? 0); ?>" placeholder="" name="order" class="form-control">
</div>
<div class="form-group">
    <label><?php echo e(__("Icon code")); ?></label>
    <input type="text" value="<?php echo e($row['icon'] ?? ''); ?>" placeholder="<?php echo e(__("Eg: fa fa-phone")); ?>" name="icon" class="form-control">
</div>
<?php /**PATH C:\laragon\www\mjellma\modules/User/Views/admin/role/verifyFieldsForm.blade.php ENDPATH**/ ?>