<div class="bravo-more-book-mobile">
    <div class="container">
        <div class="left">
            <div class="g-price">
                <div class="prefix">
                    <span class="fr_text"><?php echo e(__("from")); ?></span>
                </div>
                <div class="price ml-1">
                    <div><strong><?php echo e(format_money($row->price_per_hour)); ?></strong><small><?php echo e(__("/per hour")); ?></small></div>
                    <div><strong><?php echo e(format_money($row->price_per_day)); ?></strong><small><?php echo e(__("/per day")); ?></small></div>
                </div>
            </div>
        </div>
        <div class="right">
            <?php if($row->getBookingEnquiryType() === "book"): ?>
                <a class="btn btn-primary bravo-button-book-mobile"><?php echo e(__("Book Now")); ?></a>
            <?php else: ?>
                <a class="btn btn-primary" data-toggle="modal" data-target="#enquiry_form_modal"><?php echo e(__("Contact Now")); ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH /home/webdesig/amansite.com/themes/BC/Boat/Views/frontend/layouts/details/form-book-mobile.blade.php ENDPATH**/ ?>