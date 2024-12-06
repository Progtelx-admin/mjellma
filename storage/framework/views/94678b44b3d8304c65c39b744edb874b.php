<?php if(empty($hide_form_search)): ?>
    <div class="g-form-control">
        <ul class="nav nav-tabs" role="tablist">
            <?php if(!empty($service_types)): ?>
                <?php $number = 0; ?>
                <?php $__currentLoopData = $service_types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service_type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $allServices = get_bookable_services();
                        if(empty($allServices[$service_type])) continue;
                        $module = new $allServices[$service_type];
                    ?>
                    <li role="bravo_<?php echo e($service_type); ?>">
                        <a href="#bravo_<?php echo e($service_type); ?>" class="<?php if($number == 0): ?> active <?php endif; ?>" aria-controls="bravo_<?php echo e($service_type); ?>" role="tab" data-toggle="tab">
                            <i class="<?php echo e($module->getServiceIconFeatured()); ?>"></i>
                            <?php echo e(!empty($modelBlock["title_for_".$service_type]) ? $modelBlock["title_for_".$service_type] : $module->getModelName()); ?>

                        </a>
                    </li>
                    <?php $number++; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </ul>
        <div class="tab-content">
            <?php if(!empty($service_types)): ?>
                <?php $number = 0; ?>
                <?php $__currentLoopData = $service_types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service_type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $allServices = get_bookable_services();
                        if(empty($allServices[$service_type])) continue;
                        $module = new $allServices[$service_type];
                    ?>
                    <div role="tabpanel" class="tab-pane <?php if($number == 0): ?> active <?php endif; ?>" id="bravo_<?php echo e($service_type); ?>">
                        <?php echo $__env->make(ucfirst($service_type).'::frontend.layouts.search.form-search', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    </div>
                    <?php $number++; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
            <div role="tabpanel" class="tab-pane active" id="thomalex">
                <div id="thomalex-widget" data-widget="https://MjellmaTravel.resvoyage.com/widget/index?widgetId=b6f09e37-6e72-43cc-9da6-583d693a12fb&lang=en-US" style="height:550px;"></div>
                <script src="https://MjellmaTravel.resvoyage.com/scripts/thomalex-integration.js"></script>
            </div>
        </div> <!-- Closing for tab-content -->
    </div> <!-- Closing for g-form-control -->
<?php endif; ?>
<?php /**PATH /home/u983725807/domains/rezervo24.com/themes/Base/Template/Views/frontend/blocks/form-search-all-service/form-search.blade.php ENDPATH**/ ?>