<?php $__env->startSection('content'); ?>
    <div class="container mt-5">
        <div class="hotel-header d-flex justify-content-between align-items-center">
            <div>
                <h1 class="fw-bold"><?php echo e($hotel['name'] ?? 'Hotel Name Not Available'); ?></h1>
                <p class="text-muted"><i class="fa fa-map-marker"></i> <?php echo e($hotel['address'] ?? 'Location Not Available'); ?></p>
                <p class="star-rating">
                    <?php for($star = 1; $star <= ($hotel['star_rating'] ?? 0); $star++): ?>
                        <i class="fa fa-star" style="color: #FFD700;"></i>
                    <?php endfor; ?>
                </p>
            </div>
        </div>

        <!-- ✅ Carousel with Circle Indicators -->
        <?php if(!empty($hotel['images_ext'])): ?>
            <div id="hotelImageCarousel" class="carousel slide carousel-fade mt-4" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php $__currentLoopData = $hotel['images_ext']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="carousel-item <?php echo e($index === 0 ? 'active' : ''); ?>">
                            <img src="<?php echo e($image); ?>" class="d-block w-100 img-fluid rounded shadow-sm"
                                alt="Hotel Image <?php echo e($index + 1); ?>" style="height: 600px; object-fit: cover;">
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <!-- ✅ Circle Indicators -->
                <div class="carousel-indicators">
                    <?php $__currentLoopData = $hotel['images_ext']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <button type="button" data-bs-target="#hotelImageCarousel" data-bs-slide-to="<?php echo e($index); ?>"
                            class="<?php echo e($index === 0 ? 'active' : ''); ?>" aria-current="<?php echo e($index === 0 ? 'true' : 'false'); ?>"
                            aria-label="Slide <?php echo e($index + 1); ?>"></button>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php else: ?>
            <p>No images available for this hotel.</p>
        <?php endif; ?>

        <!-- ✅ Room Selection -->
        <?php if(!empty($roomRates)): ?>
            <h3 class="mt-5">Available Rooms</h3>
            <div class="room-list mt-3">
                <?php $__currentLoopData = $roomRates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="room-card card p-3 mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="fw-bold"><?php echo e($rate['room_name'] ?? 'N/A'); ?></h5>
                                <p class="text-muted"><?php echo e(ucfirst($rate['meal'] ?? 'No meal included')); ?></p>
                                <p class="text-success">Breakfast Included:
                                    <?php echo e($rate['meal_data']['has_breakfast'] ? 'Yes' : 'No'); ?></p>
                            </div>
                            <div class="col-md-6 text-end">
                                <h4 class="text-primary">
                                    <?php echo e($rate['payment_options']['payment_types'][0]['amount'] ?? 'N/A'); ?>

                                    <?php echo e($rate['payment_options']['payment_types'][0]['currency_code'] ?? 'EUR'); ?>

                                </h4>
                                <form method="POST" action="<?php echo e(route('hotel.prebook')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="book_hash" value="<?php echo e($rate['book_hash']); ?>">
                                    <input type="hidden" name="room_name" value="<?php echo e($rate['room_name']); ?>">
                                    <input type="hidden" name="checkin" value="<?php echo e($checkin); ?>">
                                    <input type="hidden" name="checkout" value="<?php echo e($checkout); ?>">
                                    <input type="hidden" name="adults" value="<?php echo e($adults); ?>">
                                    <input type="hidden" name="children" value="<?php echo e(json_encode($children)); ?>">
                                    <input type="hidden" name="currency" value="<?php echo e($currency); ?>">
                                    <button type="submit" class="btn btn-primary">Choose</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <p>No specific room rates available.</p>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<!-- ✅ Styling -->
<style>
    .room-card {
        border-radius: 10px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    }

    .star-rating i {
        font-size: 18px;
    }

    /* ✅ Carousel Indicators */
    .carousel-indicators {
        bottom: -30px;
    }

    .carousel-indicators button {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.7);
        border: none;
        margin: 0 5px;
        transition: background-color 0.3s ease;
    }

    .carousel-indicators .active {
        background-color: #007bff;
    }

    /* ✅ Carousel Image */
    .carousel-item img {
        border-radius: 10px;
        transition: transform 0.5s ease;
    }

    .carousel-item img:hover {
        transform: scale(1.03);
    }
</style>

<!-- ✅ Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\mjellma\themes/BC/Hotel/Views/frontend/info-ha.blade.php ENDPATH**/ ?>