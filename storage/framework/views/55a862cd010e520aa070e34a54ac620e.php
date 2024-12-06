<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <url>
            <loc><?php echo e($url['loc']); ?></loc>
            <lastmod><?php echo e($url['lastmod'] ?? ''); ?></lastmod>
        </url>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</urlset>
<?php /**PATH /home/webdesig/amansite.com/themes/BC/Core/Views/frontend/sitemap-path.blade.php ENDPATH**/ ?>