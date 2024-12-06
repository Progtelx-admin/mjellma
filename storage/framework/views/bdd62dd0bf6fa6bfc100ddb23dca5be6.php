<?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        $translation = $row->translate(); ?>
    <div class="post_item ">
        <div class="header">
            <?php if($image_tag = get_image_tag($row->image_id,'full',['alt'=>$translation->title])): ?>
                <header class="post-header">
                    <a href="<?php echo e($row->getDetailUrl()); ?>">
                        <?php echo $image_tag; ?>

                    </a>
                </header>
                <div class="cate">
                    <?php $category = $row->category; ?>
                    <?php if(!empty($category)): ?>
                        <?php $t = $category->translate(); ?>
                        <ul>
                            <li>
                                <a href="<?php echo e($category->getDetailUrl(app()->getLocale())); ?>">
                                    <?php echo e($t->name ?? ''); ?>

                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="post-inner">
                <h3 class="post-title">
                    <a class="text-darken" href="<?php echo e($row->getDetailUrl()); ?>"> <?php echo e($translation->title); ?></a>
                </h3>
                <div class="post-info">
                    <ul>
                        <?php if(!empty($row->author)): ?>
                            <li>
                                <?php if($avatar_url = $row->author->getAvatarUrl()): ?>
                                    <img class="avatar" src="<?php echo e($avatar_url); ?>" alt="<?php echo e($row->author->getDisplayName()); ?>">
                                <?php else: ?>
                                    <span class="avatar-text"><?php echo e(ucfirst($row->author->getDisplayName()[0])); ?></span>
                                <?php endif; ?>
                                <span> <?php echo e(__('BY ')); ?> </span>
                                <?php echo e($row->author->getDisplayName() ?? ''); ?>

                            </li>
                        <?php endif; ?>
                        <li> <?php echo e(__('DATE ')); ?>  <?php echo e(display_date($row->updated_at)); ?>  </li>
                    </ul>
                </div>
                <div class="post-desciption">
                    <?php echo get_exceprt($translation->content); ?>

                </div>
                <a class="btn-readmore" href="<?php echo e($row->getDetailUrl()); ?>"><?php echo e(__('Read More')); ?></a>
            </div>
        </div>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH /home/webdesig/amansite.com/themes/BC/News/Views/frontend/layouts/details/news-loop.blade.php ENDPATH**/ ?>