{{-- themes/BC/Car/Views/frontend/layouts/car-rental-strip.blade.php --}}
@php
    $action = $action ?? route('car.search');
    $submitText = $submitText ?? __('Search Cars');
@endphp

<div id="carErrorAlert" class="alert alert-danger d-none" role="alert"></div>

<form id="car-rental-form" method="GET" action="{{ route('car.do_search') }}">
    {{-- Top Checkbox --}}
    <div class="mb-2 text-left">
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" id="different_location">
            <label class="form-check-label fw-semibold ms-2" for="different_location">
                {{ __('Return to a different location') }}
            </label>
        </div>
    </div>

    {{-- Row 1: Pickup + Dropoff (Dynamic Layout) --}}
    <div class="row g-2 mb-3" id="locationRow">
        <div class="col-md-6 position-relative full-width" id="pickupCol">
            <label for="pickup_place" class="form-label">{{ __('Pickup Location') }}</label>
            <select id="pickup_place" name="pickup_place"
                class="form-select @error('pickup_place') is-invalid @enderror" required>
                <option value="">{{ __('Select pickup location...') }}</option>
            </select>
            @error('pickup_place')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6 position-relative hidden" id="dropoffCol">
            <label for="return_place" class="form-label">{{ __('Dropoff Location') }}</label>
            <select id="return_place" name="return_place"
                class="form-select @error('return_place') is-invalid @enderror" disabled>
                <option value="">{{ __('Select dropoff location...') }}</option>
            </select>
            @error('return_place')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Row 2: Pickup/Dropoff Date and Time --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <label for="pickup_date" class="form-label">{{ __('Pickup Date') }}</label>
            <input type="date" id="pickup_date" name="pickup_date"
                class="form-control @error('pickup_date') is-invalid @enderror" value="{{ old('pickup_date') }}"
                min="{{ date('Y-m-d') }}" required>
        </div>

        <div class="col-6 col-md-3">
            <label for="pickup_time" class="form-label">{{ __('Pickup Time') }}</label>
            <input type="time" id="pickup_time" name="pickup_time"
                class="form-control @error('pickup_time') is-invalid @enderror"
                value="{{ old('pickup_time', '10:00') }}" step="60" required>
        </div>

        <div class="col-6 col-md-3">
            <label for="dropoff_date" class="form-label">{{ __('Dropoff Date') }}</label>
            <input type="date" id="dropoff_date" name="dropoff_date"
                class="form-control @error('dropoff_date') is-invalid @enderror" value="{{ old('dropoff_date') }}"
                min="{{ date('Y-m-d') }}" required>
        </div>

        <div class="col-6 col-md-3">
            <label for="return_time" class="form-label">{{ __('Dropoff Time') }}</label>
            <input type="time" id="return_time" name="return_time"
                class="form-control @error('return_time') is-invalid @enderror"
                value="{{ old('return_time', '10:00') }}" step="60" required>
        </div>
    </div>

    {{-- Row 3: Submit Button --}}
    <div class="row">
        <div class="col text-center">
            <button type="submit" id="car-search-btn" class="btn btn-primary rounded-pill">
                <span id="car-search-text">{{ $submitText }}</span>
                <span id="car-search-spinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status"
                    aria-hidden="true"></span>
            </button>
        </div>
    </div>
</form>

<style>
    /* === FORM CONTAINER === */
    #car-rental-form {
        background: #ffffff;
        min-width: 800px;
        padding: 1.25rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
    }

    /* === LABELS === */
    #car-rental-form .form-label {
        font-weight: 600;
        color: #0B0B45;
        margin-bottom: 0.4rem;
        font-size: 0.9rem;
        display: block;
    }

    /* === INPUTS & SELECTS === */
    #car-rental-form input.form-control,
    #car-rental-form select.form-select {
        width: 100%;
        height: 44px;
        border-radius: 0.375rem;
        padding: 0.625rem 0.875rem;
        font-size: 0.95rem;
        border: 1px solid #dee2e6;
        background-color: #fff;
        color: #212529;
        line-height: 1.5;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    /* === DROPDOWN SPECIFIC === */
    #car-rental-form select.form-select {
        cursor: pointer;
        padding-right: 2.75rem;
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23495057' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.875rem center;
        background-size: 14px 10px;
    }

    #car-rental-form .form-select option {
        padding: 0.5rem;
        font-size: 0.95rem;
    }

    /* === FOCUS STATES === */
    #car-rental-form .form-control:focus,
    #car-rental-form .form-select:focus {
        border-color: #0B0B45;
        box-shadow: 0 0 0 0.25rem rgba(11, 11, 69, 0.15);
        outline: 0;
    }

    /* === DISABLED STATES === */
    #car-rental-form .form-control:disabled,
    #car-rental-form .form-select:disabled {
        background-color: #f8f9fa;
        color: #6c757d;
        opacity: 0.75;
        cursor: not-allowed;
        border-color: #e9ecef;
    }

    /* === VALIDATION STATES === */
    #car-rental-form .form-control.is-invalid,
    #car-rental-form .form-select.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    /* === CHECKBOX === */
    #car-rental-form .form-check-input {
        width: 1.25rem;
        height: 1.25rem;
        cursor: pointer;
        accent-color: #0B0B45;
    }

    #car-rental-form .form-check-label {
        font-size: 0.95rem;
        cursor: pointer;
        user-select: none;
    }

    /* === BUTTON === */
    #car-rental-form .btn {
        padding: 0.625rem 2.75rem;
        font-size: 1rem;
        font-weight: 500;
    }

    #car-rental-form .btn-primary {
        background: linear-gradient(135deg, #0B0B45 0%, #1a1a6e 100%);
        border: none;
        color: white;
        transition: all 0.3s ease;
    }

    #car-rental-form .btn-primary:hover {
        background: linear-gradient(135deg, #1a1a6e 0%, #0B0B45 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(11, 11, 69, 0.4);
        color: white;
    }

    #car-rental-form .btn-primary:active,
    #car-rental-form .btn-primary:focus {
        background: linear-gradient(135deg, #0B0B45 0%, #1a1a6e 100%);
        box-shadow: 0 0 0 0.25rem rgba(11, 11, 69, 0.25);
        color: white;
    }

    /* === ERROR ALERT === */
    #carErrorAlert {
        margin-bottom: 0.75rem;
    }

    /* === LOCATION ROW TRANSITIONS === */
    #locationRow {
        position: relative;
    }

    #pickupCol,
    #dropoffCol {
        position: relative;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    #pickupCol {
        flex: 0 0 50%;
        max-width: 50%;
    }

    #dropoffCol {
        flex: 0 0 50%;
        max-width: 50%;
        opacity: 1;
    }

    #pickupCol.full-width {
        flex: 0 0 100%;
        max-width: 100%;
    }

    #dropoffCol.hidden {
        flex: 0 0 0%;
        max-width: 0%;
        opacity: 0;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    /* === DESKTOP === */
    @media (min-width: 768px) {
        #pickupCol {
            flex: 0 0 50%;
            max-width: 50%;
        }

        #pickupCol.full-width {
            flex: 0 0 100%;
            max-width: 100%;
        }

        #dropoffCol {
            flex: 0 0 50%;
            max-width: 50%;
        }

        #dropoffCol.hidden {
            flex: 0 0 0%;
            max-width: 0%;
        }
    }

    /* === MOBILE === */
    @media (max-width: 767px) {
        #car-rental-form {
            min-width: auto;
            padding: 1rem;
        }

        #car-rental-form input.form-control,
        #car-rental-form select.form-select {
            height: 42px;
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
        }

        #car-rental-form select.form-select {
            padding-right: 2.5rem;
        }

        #pickupCol,
        #dropoffCol,
        #pickupCol.full-width {
            flex: 0 0 100%;
            max-width: 100%;
        }

        #dropoffCol.hidden {
            flex: 0 0 0%;
            max-width: 0%;
            padding: 0 !important;
            display: none;
        }

        #car-rental-form .btn {
            padding: 0.5rem 2rem;
            font-size: 0.95rem;
        }
    }
</style>
<script>
    (function() {
        const diffLoc = document.getElementById('different_location');
        const pickup = document.getElementById('pickup_place');
        const dropoff = document.getElementById('return_place');
        const pickupCol = document.getElementById('pickupCol');
        const dropoffCol = document.getElementById('dropoffCol');
        const form = document.getElementById('car-rental-form');
        const alertBox = document.getElementById('carErrorAlert');
        const pickupDate = document.getElementById('pickup_date');
        const dropoffDate = document.getElementById('dropoff_date');
        const pickupTime = document.getElementById('pickup_time');
        const dropoffTime = document.getElementById('return_time');
        const searchBtn = document.getElementById('car-search-btn');
        const searchText = document.getElementById('car-search-text');
        const searchSpinner = document.getElementById('car-search-spinner');

        const showError = (msg) => {
            alertBox.textContent = msg;
            alertBox.classList.remove('d-none');
            alertBox.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        };

        const clearError = () => {
            alertBox.textContent = '';
            alertBox.classList.add('d-none');
        };

        // Fetch and populate locations
        const loadLocations = () => {
            console.log('🚗 Loading locations from API...');
            console.log('API URL:', '{{ route('car.api.locations') }}');

            fetch('{{ route('car.api.locations') }}')
                .then(response => {
                    console.log('📡 API Response Status:', response.status, response.statusText);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('📦 API Response Data:', data);

                    if (data.success && data.locations && data.locations.length > 0) {
                        console.log('✅ Found', data.locations.length, 'locations');

                        // Clear existing options except the first one
                        pickup.innerHTML =
                            '<option value="">{{ __('Select pickup location...') }}</option>';
                        dropoff.innerHTML =
                            '<option value="">{{ __('Select dropoff location...') }}</option>';

                        // Populate both dropdowns
                        data.locations.forEach(location => {
                            console.log('Adding location:', location);

                            const optionPickup = document.createElement('option');
                            optionPickup.value = location.id;
                            optionPickup.textContent = location.address;
                            if (location.location_extra_price && parseFloat(location
                                    .location_extra_price) > 0) {
                                optionPickup.textContent += ` (+€${location.location_extra_price})`;
                            }
                            pickup.appendChild(optionPickup);

                            const optionDropoff = document.createElement('option');
                            optionDropoff.value = location.id;
                            optionDropoff.textContent = location.address;
                            if (location.location_extra_price && parseFloat(location
                                    .location_extra_price) > 0) {
                                optionDropoff.textContent +=
                                    ` (+€${location.location_extra_price})`;
                            }
                            dropoff.appendChild(optionDropoff);
                        });

                        console.log('✅ Locations populated successfully!');
                    } else {
                        console.warn('⚠️ No locations found in response');
                        showError('{{ __('No locations available at the moment.') }}');
                    }
                })
                .catch(error => {
                    console.error('❌ Error loading locations:', error);
                    showError('{{ __('Failed to load locations. Please refresh the page.') }}');
                });
        };

        // Load locations on page load
        loadLocations();

        const updateDropoffUI = () => {
            if (diffLoc.checked) {
                // Show both columns side by side - smooth transition from full width to 50/50
                pickupCol.classList.remove('full-width');
                dropoffCol.classList.remove('hidden');
                dropoff.removeAttribute('disabled');
                setTimeout(() => {
                    if (!dropoff.value) {
                        dropoff.focus();
                    }
                }, 400);
            } else {
                // Full width pickup, hide dropoff - smooth transition back
                pickupCol.classList.add('full-width');
                dropoffCol.classList.add('hidden');
                dropoff.value = pickup.value || '';
                dropoff.setAttribute('disabled', 'disabled');
            }
        };

        diffLoc.addEventListener('change', updateDropoffUI);

        // Sync dropoff location with pickup when same location
        pickup.addEventListener('change', () => {
            if (!diffLoc.checked) {
                dropoff.value = pickup.value;
            }
        });

        updateDropoffUI();

        // Date handling
        const today = new Date().toISOString().split('T')[0];
        pickupDate.min = today;
        dropoffDate.min = today;
        pickupDate.addEventListener('change', () => {
            dropoffDate.min = pickupDate.value;
            if (dropoffDate.value < pickupDate.value) {
                dropoffDate.value = pickupDate.value;
            }
        });

        // Validation
        form.addEventListener('submit', (e) => {
            clearError();
            let errors = [];
            let hasErrors = false;
            form.querySelectorAll('.form-control, .form-select').forEach(el => el.classList.remove(
                'is-invalid'));

            // Validate pickup location
            if (!pickup.value || pickup.value === '') {
                pickup.classList.add('is-invalid');
                errors.push('{{ __('Please select a pickup location.') }}');
                hasErrors = true;
            }

            // Validate dropoff location if different location is selected
            if (diffLoc.checked && (!dropoff.value || dropoff.value === '')) {
                dropoff.classList.add('is-invalid');
                errors.push('{{ __('Please select a dropoff location.') }}');
                hasErrors = true;
            }

            // Ensure dropoff has value if same location
            if (!diffLoc.checked) {
                dropoff.disabled = false;
                dropoff.value = pickup.value;
            }

            if (!pickupDate.value || !pickupTime.value || !dropoffDate.value || !dropoffTime.value) {
                errors.push('{{ __('Please fill all date and time fields.') }}');
                hasErrors = true;
            }

            if (pickupDate.value && dropoffDate.value && pickupTime.value && dropoffTime.value) {
                const pickDT = new Date(`${pickupDate.value} ${pickupTime.value}`);
                const dropDT = new Date(`${dropoffDate.value} ${dropoffTime.value}`);
                if (dropDT <= pickDT) {
                    dropoffDate.classList.add('is-invalid');
                    errors.push('{{ __('Dropoff date/time must be after pickup date/time.') }}');
                    hasErrors = true;
                }
            }

            if (hasErrors) {
                e.preventDefault();
                showError(errors.join(' '));
                return;
            }

            // Loading spinner
            searchBtn.disabled = true;
            searchText.textContent = "{{ __('Searching...') }}";
            searchSpinner.classList.remove('d-none');
        });
    })
    ();
</script>
