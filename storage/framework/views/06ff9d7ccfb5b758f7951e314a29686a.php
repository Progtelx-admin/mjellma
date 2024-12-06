<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id=>$data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <sitemap>
            <loc><?php echo e(route('sitemap.path',['id'=>$id])); ?></loc>
        </sitemap>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</sitemapindex>
<?php /**PATH /home/u983725807/domains/rezervo24.com/themes/BC/Core/Views/frontend/sitemap.blade.php ENDPATH**/ ?>