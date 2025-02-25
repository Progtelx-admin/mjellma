<?php $__env->startSection('content'); ?>
    <div id="carouselBackground"
        class="carousel slide carousel-fade container-fluid p-0 position-relative min-vh-100 d-flex justify-content-center align-items-center"
        data-bs-ride="carousel" data-bs-interval="4000">
        <div class="carousel-inner w-100 h-100">
            <div class="carousel-item active"
                style="background-image: url('https://images.bubbleup.com/width1920/quality35/mville2017/1-brand/1-margaritaville.com/gallery-media/220803-compasshotel-medford-pool-73868-1677873697-78625-1694019828.jpg');">
            </div>
            <div class="carousel-item"
                style="background-image: url('https://digital.ihg.com/is/image/ihg/ihgor-member-rate-web-offers-1440x720');">
            </div>
            <div class="carousel-item"
                style="background-image: url('https://hoteldel.com/wp-content/uploads/2021/01/hotel-del-coronado-views-suite-K1TOS1-K1TOJ1-1600x900-1.jpg');">
            </div>
        </div>

        <div class="carousel-indicators">
            <button type="button" data-bs-target="#carouselBackground" data-bs-slide-to="0" class="active"
                aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#carouselBackground" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#carouselBackground" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>

        <div class="w-75 position-absolute" style="z-index: 1">
            <!-- Centered Heading -->
            <h1 class="text-white fw-bold mb-4 text-center" style="text-shadow: 2px 2px 5px rgba(0,0,0,0.7);">
                Escape To Paradise, Unwind In Luxury
            </h1>

            <!-- Form Section -->
            <div class="p-4 rounded shadow-sm bg-white">
                <!-- Tabs for Flights, Hotels, Cars -->
                <ul class="nav nav-tabs mb-3" id="searchTabs">
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-plane"></i> Flights</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="fa fa-hotel"></i> Hotels</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fa fa-car"></i> Cars</a>
                    </li>
                </ul>

                <!-- Search Form -->
                <form method="GET" action="<?php echo e(route('hotel.search')); ?>">
                    <?php echo csrf_field(); ?>

                    <!-- Global Validation Error Messages -->
                    <?php if($errors->any()): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li><?php echo e($error); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <!-- Hotel Name -->
                        <div class="col-md-6 position-relative">
                            <label for="hotel_name" class="form-label">Hotel Name:</label>
                            <input type="text" id="hotel_name" name="hotel_name"
                                class="form-control <?php $__errorArgs = ['hotel_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                placeholder="Enter hotel name" value="<?php echo e(old('hotel_name')); ?>">
                            <?php $__errorArgs = ['hotel_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Location -->
                        <div class="col-md-6 position-relative">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" id="location" name="location"
                                class="form-control <?php $__errorArgs = ['location'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                placeholder="Where are you going?" autocomplete="off">
                            <?php $__errorArgs = ['location'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                            <ul id="suggestions" class="list-group position-absolute w-100 mt-1 d-none"
                                style="max-height: 200px; overflow-y: auto; z-index: 1000;"></ul>
                        </div>
                    </div>

                    <!-- Check-in, Check-out, Guests, Rooms -->
                    <div class="row g-3 mt-3">
                        <!-- Check-in Date -->
                        <div class="col-6 col-md-3">
                            <label for="checkin" class="form-label">Check-in Date</label>
                            <input type="date" id="checkin" name="checkin"
                                class="form-control <?php $__errorArgs = ['checkin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('checkin')); ?>"
                                required>
                            <?php $__errorArgs = ['checkin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Check-out Date -->
                        <div class="col-6 col-md-3">
                            <label for="checkout" class="form-label">Check-out Date</label>
                            <input type="date" id="checkout" name="checkout"
                                class="form-control <?php $__errorArgs = ['checkout'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('checkout')); ?>"
                                required>
                            <?php $__errorArgs = ['checkout'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Adults -->
                        <div class="col-6 col-md-2">
                            <label for="adults" class="form-label">Adults</label>
                            <input type="number" id="adults" name="adults"
                                class="form-control <?php $__errorArgs = ['adults'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('adults', 1)); ?>"
                                min="1" required>
                            <?php $__errorArgs = ['adults'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Children -->
                        <div class="col-6 col-md-2">
                            <label for="children" class="form-label">Children</label>
                            <input type="number" id="children" name="children[]"
                                class="form-control <?php $__errorArgs = ['children.0'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                value="<?php echo e(old('children.0', 0)); ?>" min="0">
                            <?php $__errorArgs = ['children.0'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Rooms -->
                        <div class="col-6 col-md-2">
                            <label for="rooms" class="form-label">Rooms</label>
                            <input type="number" id="rooms" name="rooms"
                                class="form-control <?php $__errorArgs = ['rooms'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('rooms', 1)); ?>"
                                min="1" required>
                            <?php $__errorArgs = ['rooms'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>

                    <!-- Hidden Latitude and Longitude -->
                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">
                </form>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Initialize Carousel -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const carouselElement = document.querySelector('#carouselBackground');
            const carousel = new bootstrap.Carousel(carouselElement, {
                interval: 2000,
                ride: 'carousel',
                pause: false,
                wrap: true
            });
        });
    </script>

    <!-- JavaScript for Location Suggestions -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const locationInput = document.getElementById("location");
            const suggestionsList = document.getElementById("suggestions");
            const latitudeInput = document.getElementById("latitude");
            const longitudeInput = document.getElementById("longitude");

            locationInput.addEventListener("input", function() {
                const query = this.value;
                if (query.length < 3) {
                    suggestionsList.innerHTML = "";
                    suggestionsList.classList.add("d-none");
                    return;
                }

                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}`)
                    .then(response => response.json())
                    .then(data => {
                        suggestionsList.innerHTML = "";
                        suggestionsList.classList.toggle("d-none", data.length === 0);

                        data.forEach(location => {
                            const suggestion = document.createElement("li");
                            suggestion.classList.add("list-group-item");
                            suggestion.textContent = location.display_name;
                            suggestion.style.cursor = "pointer";
                            suggestion.addEventListener("click", function() {
                                locationInput.value = location.display_name;
                                latitudeInput.value = location.lat;
                                longitudeInput.value = location.lon;
                                suggestionsList.innerHTML = "";
                                suggestionsList.classList.add("d-none");
                            });
                            suggestionsList.appendChild(suggestion);
                        });
                    })
                    .catch(error => console.error("Error fetching location suggestions:", error));
            });

            document.addEventListener("click", function(e) {
                if (!suggestionsList.contains(e.target) && e.target !== locationInput) {
                    suggestionsList.innerHTML = "";
                    suggestionsList.classList.add("d-none");
                }
            });
        });
    </script>

    <!-- Styling for Carousel and Form -->
    <style>
        /* Carousel items as background */
        .carousel-item {
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            min-height: 100vh;
            width: 100%;
        }

        /* Hide img tags if any */
        .carousel-item img {
            display: none;
        }

        /* Style carousel indicators */
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

        /* Position carousel indicators at the bottom */
        .carousel-indicators {
            bottom: 20px;
        }

        /* Form background transparency */
        .bg-white {
            background-color: rgba(255, 255, 255, 0.9);
        }

        /* Header Text Shadow */
        h1 {
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
        }

        /* Form button styles */
        .btn-primary {
            background-color: #007bff;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .nav-tabs .nav-link.active {
            border-bottom: 3px solid #007bff;
            font-weight: bold;
        }

        .form-label {
            font-weight: bold;
        }
    </style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\laragon\www\mjellma\themes/BC/Hotel/Views/frontend/form-search-ha.blade.php ENDPATH**/ ?>