<div class="panel">
    <div class="panel-title"><strong><?php echo e(__("General")); ?></strong></div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php echo e(__("Coupon Code")); ?> <span class="text-danger">*</span></label>
                    <input type="text" maxlength="50" required value="<?php echo e($row->code); ?>" placeholder="<?php echo e(__("Unique Code")); ?>" name="code" class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php echo e(__("Coupon Name")); ?> </label>
                    <input type="text" value="<?php echo e($row->name); ?>" placeholder="<?php echo e(__("Name")); ?>" name="name" class="form-control">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php echo e(__("Coupon Amount")); ?> <span class="text-danger">*</span></label>
                    <input type="number" required step="0.1" min="0" value="<?php echo e($row->amount); ?>" placeholder="0" name="amount" class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php echo e(__("Discount Type")); ?> </label>
                    <select class="form-control" name="discount_type">
                        <option <?php if($row->discount_type && $row->discount_type == 'fixed'): ?> selected <?php endif; ?> value="fixed"><?php echo e(__("Amount")); ?></option>
                        <option <?php if($row->discount_type && $row->discount_type == 'percent'): ?> selected <?php endif; ?> value="percent"><?php echo e(__("Percent")); ?></option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php echo e(__("End Date")); ?></label>
                    <input type="text" value="<?php echo e($row->end_date); ?>" placeholder="<?php echo e(__("2021-12-12 00:00:00")); ?>" name="end_date" class="form-control has-datetimepicker">
                </div>
            </div>
        </div>
    </div>
</div>
<div class="panel">
    <div class="panel-title"><strong><?php echo e(__("Usage Restriction")); ?></strong></div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php echo e(__("Minimum Spend")); ?></label>
                    <input type="text" value="<?php echo e($row->min_total); ?>" placeholder="<?php echo e(__("No Minimum")); ?>" name="min_total" class="form-control">
                    <small><?php echo e(__("The Minimum Spend does not include any Booking fee")); ?></small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php echo e(__("Maximum Spend")); ?></label>
                    <input type="text" value="<?php echo e($row->max_total); ?>" placeholder="<?php echo e(__("No Maximum")); ?>" name="max_total" class="form-control">
                    <small><?php echo e(__("The Maximum Spend does not include any Booking fee")); ?></small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php echo e(__("Only For Services")); ?></label>
                    <?php
                    \App\Helpers\AdminForm::select2('services[]', [
                        'configs' => [
                            'ajax'        => [
                                'url'      => route("coupon.admin.getServices"),
                                'dataType' => 'json'
                            ],
                            'allowClear'  => true,
                            'multiple'    => true,
                            'placeholder' => __('-- Select Services --')
                        ]
                    ], $row->getServicesToArray() , true)
                    ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php echo e(__("Only For User")); ?></label>
                    <?php
                    \App\Helpers\AdminForm::select2('only_for_user[]', [
                        'configs' => [
                            'ajax'        => [
                                'url'      => route("user.admin.getForSelect2"),
                                'dataType' => 'json'
                            ],
                            'allowClear'  => true,
                            'multiple'    => true,
                            'placeholder' => __('-- Select User --'),
                            'pre_selected' => route('user.admin.getForSelect2', [
                                'type'         => 'car',
                                'pre_selected' => 1
                            ])
                        ]
                    ], $row->getUsersToArray() , true)
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="panel">
    <div class="panel-title"><strong><?php echo e(__("Usage Limits")); ?></strong></div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php echo e(__("Usage Limit per Coupon")); ?></label>
                    <input type="number" value="<?php echo e($row->quantity_limit); ?>" placeholder="<?php echo e(__("Unlimited Usage")); ?>" name="quantity_limit" class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label><?php echo e(__("Usage Limit Per User")); ?></label>
                    <input type="number" value="<?php echo e($row->limit_per_user); ?>" placeholder="<?php echo e(__("Unlimited Usage")); ?>" name="limit_per_user" class="form-control">
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\mjellma\modules/Coupon/Views/admin/form.blade.php ENDPATH**/ ?>