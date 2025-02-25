<?php $__env->startSection('content'); ?>
    <div class="container mt-5 mb-5">
        <h1 class="fw-bold text-center">Prebooking Confirmation</h1>
        <hr class="w-50 mx-auto mb-4">

        <?php if(isset($prebookData['data']['hotels'][0])): ?>
            <?php
                $hotel = $prebookData['data']['hotels'][0];
                $rate = $hotel['rates'][0] ?? null;
            ?>

            <div class="row align-items-stretch">
                <!-- Hotel Information -->
                <div class="col-md-6 d-flex">
                    <div class="card shadow-sm border-0 w-100 d-flex flex-column">
                        <img src="<?php echo e($hotelImage ?? asset('images/default-hotel.jpg')); ?>" class="card-img-top"
                            alt="<?php echo e($hotelDetails['name']); ?>">

                        <div class="card-body d-flex flex-column">
                            <h4 class="fw-bold"><?php echo e($hotelDetails['name']); ?></h4>
                            <p><i class="fa fa-map-marker-alt text-primary"></i>
                                <?php echo e($hotelDetails['address']); ?>

                            </p>

                            <?php if(!empty($hotelDetails['star_rating']) && $hotelDetails['star_rating'] > 0): ?>
                                <div class="text-warning">
                                    <?php for($star = 1; $star <= $hotelDetails['star_rating']; $star++): ?>
                                        <i class="fa fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Booking Details -->
                <div class="col-md-6 d-flex">
                    <div class="card shadow-sm border-0 w-100 p-4 d-flex flex-column">
                        <h5 class="fw-bold">Booking Summary</h5>
                        <hr>
                        <p><strong>Check-in:</strong> <?php echo e($checkin ?? 'N/A'); ?></p>
                        <p><strong>Check-out:</strong> <?php echo e($checkout ?? 'N/A'); ?></p>

                        <?php if($rate): ?>
                            <p><strong>Room Type:</strong> <?php echo e($rate['room_name'] ?? 'N/A'); ?></p>
                            <p><strong>Meal Plan:</strong> <?php echo e(ucfirst($rate['meal'] ?? 'N/A')); ?></p>
                            <p><strong>Breakfast Included:</strong> <?php echo e($rate['meal_data']['has_breakfast'] ? 'Yes' : 'No'); ?>

                            </p>
                            <h4 class="fw-bold text-success">Total Price:
                                <?php echo e($rate['payment_options']['payment_types'][0]['amount'] ?? 'N/A'); ?>

                                <?php echo e($rate['payment_options']['payment_types'][0]['currency_code'] ?? 'EUR'); ?></h4>

                            <!-- Book Now Button -->
                            <form method="POST" action="<?php echo e(route('hotel.book')); ?>" class="mt-auto">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="book_hash" value="<?php echo e($rate['book_hash']); ?>">
                                <input type="hidden" name="partner_order_id" value="order_<?php echo e(uniqid()); ?>">
                                <input type="hidden" name="user_ip" value="<?php echo e(request()->ip()); ?>">
                                <button type="submit" class="btn btn-primary w-100 py-2 mt-3">Confirm Booking</button>
                            </form>
                        <?php else: ?>
                            <p class="text-danger">No rate information available. Please select another room.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center">No prebooking data available. Please try again.</div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\mjellma\themes/BC/Hotel/Views/frontend/prebook-result-ha.blade.php ENDPATH**/ ?>