<?php $__env->startSection('content'); ?>
    <div class="container mt-5 mb-5">
        <h1 class="fw-bold text-center">Complete Your Booking</h1>
        <hr class="w-50 mx-auto mb-4">

        <?php if($errors->any()): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if(session('success')): ?>
            <div class="alert alert-success">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>

        <form action="<?php echo e(route('hotel.booking.finish')); ?>" method="POST" id="bookingForm" class="card shadow-sm p-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" id="partner_order_id" name="partner_order_id"
                value="<?php echo e($bookingData['partner_order_id'] ?? ''); ?>">
            <input type="hidden" id="user_email" name="user[email]" value="default@example.com">
            <input type="hidden" id="user_phone" name="user[phone]" value="+123456789">
            <input type="hidden" id="supplier_first_name" name="supplier_data[first_name_original]" value="SupplierFirst">
            <input type="hidden" id="supplier_last_name" name="supplier_data[last_name_original]" value="SupplierLast">
            <input type="hidden" id="supplier_phone" name="supplier_data[phone]" value="+987654321">
            <input type="hidden" id="supplier_email" name="supplier_data[email]" value="supplier@example.com">
            <input type="hidden" id="return_path" name="return_path" value="">

            <h3 class="fw-bold">Guests</h3>
            <div id="guests-container">
                <div class="guest mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="guest_first_name_1" class="form-label">Guest First Name</label>
                            <input type="text" class="form-control" id="guest_first_name_1"
                                name="rooms[0][guests][0][first_name]" required>
                        </div>
                        <div class="col-md-6">
                            <label for="guest_last_name_1" class="form-label">Guest Last Name</label>
                            <input type="text" class="form-control" id="guest_last_name_1"
                                name="rooms[0][guests][0][last_name]" required>
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-secondary mb-3" id="addGuest">Add Another Guest</button>

            <h3 class="fw-bold">Payment Type</h3>
            <div class="mb-3">
                <label for="payment_type" class="form-label">Payment Type</label>
                <select class="form-select" id="payment_type" name="payment_type[type]" required>
                    <?php $__currentLoopData = $bookingData['payment_types']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($payment['type']); ?>" data-amount="<?php echo e($payment['amount']); ?>"
                            data-currency="<?php echo e($payment['currency_code']); ?>">
                            <?php echo e(ucfirst($payment['type'])); ?> - <?php echo e($payment['amount']); ?> <?php echo e($payment['currency_code']); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="payment_amount" class="form-label">Amount</label>
                <input type="text" class="form-control" id="payment_amount" name="payment_type[amount]" readonly>
            </div>
            <div class="mb-3">
                <label for="payment_currency" class="form-label">Currency</label>
                <input type="text" class="form-control" id="payment_currency" name="payment_type[currency_code]"
                    readonly>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mt-3">Finish Booking</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentTypeSelect = document.getElementById('payment_type');
            const paymentAmount = document.getElementById('payment_amount');
            const paymentCurrency = document.getElementById('payment_currency');

            paymentTypeSelect.addEventListener('change', function() {
                const selectedOption = paymentTypeSelect.options[paymentTypeSelect.selectedIndex];
                paymentAmount.value = selectedOption.dataset.amount;
                paymentCurrency.value = selectedOption.dataset.currency;
            });

            if (paymentTypeSelect.options.length > 0) {
                const selectedOption = paymentTypeSelect.options[paymentTypeSelect.selectedIndex];
                paymentAmount.value = selectedOption.dataset.amount;
                paymentCurrency.value = selectedOption.dataset.currency;
            }

            const addGuestButton = document.getElementById('addGuest');
            const guestsContainer = document.getElementById('guests-container');
            let guestIndex = 1;

            addGuestButton.addEventListener('click', function() {
                guestIndex++;
                const guestDiv = document.createElement('div');
                guestDiv.classList.add('guest', 'mb-4');
                guestDiv.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Guest First Name</label>
                            <input type="text" class="form-control" name="rooms[0][guests][${guestIndex - 1}][first_name]" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Guest Last Name</label>
                            <input type="text" class="form-control" name="rooms[0][guests][${guestIndex - 1}][last_name]" required>
                        </div>
                    </div>`;
                guestsContainer.appendChild(guestDiv);
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentTypeSelect = document.getElementById('payment_type');
            const returnPathField = document.getElementById('return_path');

            function updateReturnPath() {
                const selectedOption = paymentTypeSelect.options[paymentTypeSelect.selectedIndex];
                if (selectedOption.value === "now") {
                    returnPathField.value = "<?php echo e(route('hotel.booking.confirmation')); ?>";
                } else {
                    returnPathField.value = "";
                }
            }

            // Update return_path on load and when changing selection
            updateReturnPath();
            paymentTypeSelect.addEventListener('change', updateReturnPath);
        });
    </script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\mjellma\themes/BC/Hotel/Views/frontend/booking-confirmation-ha.blade.php ENDPATH**/ ?>