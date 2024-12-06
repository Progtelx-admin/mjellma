<hr>
<div class="row">
    <div class="col-sm-4">
        <h3 class="form-group-title"><?php echo e(__("Header Settings")); ?></h3>
    </div>
    <div class="col-sm-8">
        <div class="panel">
            <div class="panel-title"><strong><?php echo e(__('Header Style')); ?></strong></div>
            <div class="panel-body"><?php if(is_default_lang()): ?>
                    <div class="form-group">
                        <label><?php echo e(__("Enable Header Sticky")); ?></label>
                        <div class="form-controls">
                            <label>
                                <input
                                        type="checkbox"
                                        <?php if(setting_item('enable_header_sticky') ?? '' == 1): ?> checked
                                        <?php endif; ?> name="enable_header_sticky"
                                        value="1"
                                ><?php echo e(__('Enable')); ?></label>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/webdesig/amansite.com/themes/BC/Core/Views/admin/settings/header.blade.php ENDPATH**/ ?>