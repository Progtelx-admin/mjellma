

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('Layout::parts.bc', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <div class="page-profile-content page-template-content">
        <div class="container">
            <div class="">
                <div class="row">
                    <div class="col-md-3">
                        <?php echo $__env->make('User::frontend.profile.sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    </div>
                    <div class="col-md-9">
                        <?php
                        $reviews = \Modules\Review\Models\Review::query()->where([
                            'vendor_id'=>$user->id,
                            'status'=>'approved'
                        ])
                            ->orderBy('id','desc')
                            ->with('author')
                            ->paginate(10);
                        ?>
                        <?php if($reviews->total()): ?>
                            <div class="bravo-reviews">
                                <h3><?php echo e(__('Reviews from guests')); ?></h3>
                                <div class="review-pag-text">
                                    <?php echo e(__("Showing :from - :to of :total total",["from"=>$reviews->firstItem(),"to"=>$reviews->lastItem(),"total"=>$reviews->total()])); ?>

                                </div>
                                <div class="review-list">
                                    <?php if($reviews): ?>
                                        <?php $__currentLoopData = $reviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php $userInfo = $item->author;
                                                 if(!$userInfo){
                                                    continue;
                                                 }
                                            ?>
                                            <div class="review-item">
                                                <div class="review-item-head">
                                                    <div class="media">
                                                        <div class="media-left">
                                                            <?php if($avatar_url = $userInfo->getAvatarUrl()): ?>
                                                                <img class="avatar" src="<?php echo e($avatar_url); ?>" alt="<?php echo e($userInfo->getDisplayName()); ?>">
                                                            <?php else: ?>
                                                                <span class="avatar-text"><?php echo e(ucfirst($userInfo->getDisplayName()[0])); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="media-body">
                                                            <h4 class="media-heading"><?php echo e($userInfo->getDisplayName()); ?></h4>
                                                            <div class="date"><?php echo e(display_datetime($item->created_at)); ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="review-item-body">
                                                    <h4 class="title"> <?php echo e($item->title); ?> </h4>
                                                    <?php if($item->rate_number): ?>
                                                        <ul class="review-star">
                                                            <?php for( $i = 0 ; $i < 5 ; $i++ ): ?>
                                                                <?php if($i < $item->rate_number): ?>
                                                                    <li><i class="fa fa-star"></i></li>
                                                                <?php else: ?>
                                                                    <li><i class="fa fa-star-o"></i></li>
                                                                <?php endif; ?>
                                                            <?php endfor; ?>
                                                        </ul>
                                                    <?php endif; ?>
                                                    <div class="detail">
                                                        <?php echo e($item->content); ?>

                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="review-pag-wrapper">
                                    <div class="bravo-pagination">
                                        <?php echo e($reviews->appends(request()->query())->links()); ?>

                                    </div>
                                    <div class="review-pag-text">
                                        <?php echo e(__("Showing :from - :to of :total total",["from"=>$reviews->firstItem(),"to"=>$reviews->lastItem(),"total"=>$reviews->total()])); ?>

                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="review-pag-text"><?php echo e(__("No Review")); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/webdesig/amansite.com/themes/Base/User/Views/frontend/profile/all-reviews.blade.php ENDPATH**/ ?>