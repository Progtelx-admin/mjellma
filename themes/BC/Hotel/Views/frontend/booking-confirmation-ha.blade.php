@extends('layouts.app')

@section('content')
    <style>
        /* Accordion collapse styling */
        .accordion .collapse {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }

        .accordion .collapse.show {
            max-height: 2000px;
            transition: max-height 0.5s ease-in;
        }

        .payment-method-label {
            width: 100%;
            display: block;
        }

        .accordion .card {
            margin-bottom: 0.5rem;
        }
    </style>
    @php
        use Illuminate\Support\Str;

        $user = Auth::user();
        $isAdmin = $user && $user->role_id === 1;

        $bookingData = $bookingData ?? [];
        $partnerOrderId = $bookingData['partner_order_id'] ?? session('partner_order_id', Str::uuid());
        $orderId = $bookingData['order_id'] ?? session('order_id', rand(100000000, 999999999));
        $language = $bookingData['language'] ?? 'en';
        $bookHash = $bookingData['book_hash'] ?? ($selectedRate['book_hash'] ?? '');
        $itemId = $bookingData['item_id'] ?? '';

        $defaultNameParts = explode(' ', $user->name ?? '');
        $defaultFirstName = $defaultNameParts[0] ?? '';
        $defaultLastName = $defaultNameParts[1] ?? '';

    @endphp

    <div class="container mt-5 mb-5">
        <h1 class="fw-bold text-center">Complete Your Booking</h1>
        <hr class="w-50 mx-auto mb-4">

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('hotel.booking.handle') }}" method="POST" id="bookingForm" class="card shadow-sm p-4">
            @csrf

            <!-- Hidden Fields -->
            <input type="hidden" name="partner_order_id" value="{{ $partnerOrderId }}">
            <input type="hidden" name="order_id" value="{{ $orderId }}">
            <input type="hidden" name="return_path" value="{{ route('hotel.payment.success') }}">
            <input type="hidden" name="language" value="{{ $language }}">
            <input type="hidden" name="book_hash" value="{{ $bookHash }}">
            <input type="hidden" name="item_id" value="{{ $itemId }}">

            <!-- Admin Dropdown for Agent Booking -->
            @if ($isAdmin)
                <div class="mb-3">
                    <label class="form-label">Book on behalf of Agent</label>
                    <select class="form-select" name="agent_id" id="agent_id">
                        <option value="">Select an Agent</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" data-name="{{ $vendor->name }}"
                                data-email="{{ $vendor->email }}" data-phone="{{ $vendor->phone }}">
                                {{ $vendor->name }} ({{ $vendor->email }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- User Info -->
            <div class="mb-3">
                <label class="form-label">Your First Name</label>
                <input type="text" id="first_name" name="first_name" class="form-control"
                    value="{{ old('first_name', $defaultFirstName) }}" required pattern="[A-Za-zÀ-ÖØ-öø-ÿ\s\-\.,]+"
                    title="Only letters, spaces, hyphens (-), commas (,), and periods (.) are allowed in names.">
            </div>
            <div class="mb-3">
                <label class="form-label">Your Last Name</label>
                <input type="text" id="last_name" name="last_name" class="form-control"
                    value="{{ old('last_name', $defaultLastName) }}" required pattern="[A-Za-zÀ-ÖØ-öø-ÿ\s\-\.,]+"
                    title="Only letters, spaces, hyphens (-), commas (,), and periods (.) are allowed in names.">
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control"
                    value="{{ old('email', $user->email ?? '') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" id="phone" name="phone" class="form-control"
                    value="{{ old('phone', $user->phone ?? '+0000000000') }}" required>
            </div>

            <!-- Supplier Info (hidden) -->
            <input type="hidden" name="supplier_data[first_name_original]" value="Mjellma Travel">
            <input type="hidden" name="supplier_data[last_name_original]" value="Mjellma Travel">
            <input type="hidden" name="supplier_data[email]" value="mjellmatravel@hotmail.com">
            <input type="hidden" name="supplier_data[phone]" value="{{ rand(1000000000, 9999999999) }}">

            <!-- Guests Section -->
            <h3 class="fw-bold">Guests</h3>
            <div id="guests-container">
                <div class="guest mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Guest First Name</label>
                            <input type="text" class="form-control" name="rooms[0][guests][0][first_name]" required
                                pattern="[A-Za-zÀ-ÖØ-öø-ÿ\s\-\.,]+"
                                title="Only letters, spaces, hyphens (-), commas (,), and periods (.) are allowed in names.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Guest Last Name</label>
                            <input type="text" class="form-control" name="rooms[0][guests][0][last_name]" required
                                pattern="[A-Za-zÀ-ÖØ-öø-ÿ\s\-\.,]+"
                                title="Only letters, spaces, hyphens (-), commas (,), and periods (.) are allowed in names.">
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-outline-secondary mb-3" id="addGuest">Add Another Guest</button>

            <!-- Payment Section -->
            <h3 class="fw-bold">Payment</h3>
            <div class="mb-3">
                <label class="form-label">Select Payment Method</label>
                <div class="gateways-table accordion" id="accordionExample">
                    {{-- COMMENTED OUT: RateHawk payment types (deposit, etc.) - Only PCB Bank is available now --}}
                    {{-- @foreach ($bookingData['payment_types'] as $index => $payment)
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <label class="payment-method-label" data-collapse-target="gateway_{{ $index }}" style="cursor: pointer;">
                                        <input type="radio" name="payment_method"
                                               id="payment_method_{{ $index }}"
                                               value="{{ $payment['type'] }}|{{ $payment['currency_code'] }}"
                                               data-type="{{ $payment['type'] }}"
                                               data-currency="{{ $payment['currency_code'] }}"
                                               data-amount="{{ $payment['amount'] }}"
                                               data-need-card="{{ $payment['is_need_credit_card_data'] ? '1' : '0' }}"
                                               {{ $index === 0 ? 'checked' : '' }}>
                                        {{ ucfirst($payment['type']) }} - {{ $payment['amount'] }} {{ $payment['currency_code'] }}
                                        @if ($payment['is_need_credit_card_data'])
                                            (Pay with Card)
                                        @endif
                                    </label>
                                </h4>
                            </div>
                            <div id="gateway_{{ $index }}" class="collapse {{ $index === 0 ? 'show' : '' }}" data-parent="#accordionExample">
                                <div class="card-body">
                                    <div class="gateway_name">
                                        {{ ucfirst($payment['type']) }} Payment
                                    </div>
                                    <p>Amount: {{ $payment['amount'] }} {{ $payment['currency_code'] }}</p>
                                    @if ($payment['is_need_credit_card_data'])
                                        <p class="text-info">This payment method requires credit card information.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach --}}

                    <!-- PCB Bank Payment Method (ONLY OPTION) -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">
                                <label class="payment-method-label" data-collapse-target="gateway_pcb"
                                    style="cursor: pointer;">
                                    <input type="radio" name="payment_method" id="payment_method_pcb"
                                        value="pcb_bank|{{ $displayCurrency ?? 'EUR' }}" data-type="pcb_bank"
                                        data-currency="{{ $displayCurrency ?? 'EUR' }}"
                                        data-amount="{{ $displayFinalPrice ?? '0' }}" data-need-card="1" checked>
                                    PCB Bank Payment - {{ number_format($displayFinalPrice ?? 0, 2) }}
                                    {{ $displayCurrency ?? 'EUR' }}
                                </label>
                            </h4>
                        </div>
                        <div id="gateway_pcb" class="collapse show" data-parent="#accordionExample">
                            <div class="card-body">
                                <div class="gateway_name">
                                    PCB Bank Payment
                                </div>
                                <p>Amount: {{ number_format($displayFinalPrice ?? 0, 2) }} {{ $displayCurrency ?? 'EUR' }}
                                </p>
                                <p class="text-info">Secure payment through PCB Bank gateway. You will be redirected to
                                    enter your card information.</p>

                                <!-- Terms and Conditions Checkbox -->
                                <div class="mt-3">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="pcb_terms_checkbox" style="margin-right: 8px;">
                                        I have read and accept the <a href="#"
                                            onclick="toggleTermsDiv(); return false;"
                                            style="color: #F27625; text-decoration: underline; margin-left: 4px;">terms and
                                            conditions</a>
                                    </label>
                                    <div class="invalid-feedback" style="display: none; margin-top: 0.25rem;">
                                        You must accept the terms and conditions to proceed with PCB Bank payment.
                                    </div>
                                </div>

                                <!-- Hidden Terms and Conditions Div -->
                                <div id="terms-div"
                                    style="display: none; margin-top: 15px; padding: 20px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">
                                    <h5 style="color: #F27625; margin-bottom: 15px;">Terms and Conditions</h5>
                                    <p><strong>Welcome to Rezervo24!</strong></p>
                                    <p>By using this website and/or making a reservation through our platform, you agree to
                                        the following terms and conditions. Please read them carefully before proceeding.
                                    </p>

                                    <h6>1. Use of the Platform</h6>
                                    <ul>
                                        <li>Rezervo24 is an online platform that facilitates hotel and accommodation
                                            bookings.</li>
                                        <li>We do not own or operate the listed properties and are not directly responsible
                                            for their management. We simply display the information and booking conditions
                                            provided by the hotels.</li>
                                    </ul>

                                    <h6>2. Reservations</h6>
                                    <ul>
                                        <li>All reservations made through Rezervo24 are in real-time and are considered
                                            valid only after confirmation is received via email or your user account.</li>
                                        <li>Users are responsible for providing accurate and complete information during the
                                            booking process.</li>
                                    </ul>

                                    <h6>3. Payments</h6>
                                    <ul>
                                        <li>Payments can be made securely through the online payment methods we offer.</li>
                                        <li>All prices are displayed transparently and include applicable taxes unless
                                            otherwise stated.</li>
                                    </ul>

                                    <h6>4. Cancellation Policy</h6>
                                    <p>Before completing a booking, users must read and agree to the cancellation policy,
                                        which may vary depending on the selected hotel. The full cancellation policy is
                                        outlined below in this document and also displayed during the booking process on
                                        each hotel's page.</p>

                                    <h6>5. Hotel-Initiated Changes or Cancellations</h6>
                                    <p>In exceptional cases, the hotel may modify or cancel a reservation. We will do our
                                        best to assist you in finding an appropriate alternative or to provide a full
                                        refund, depending on the situation.</p>

                                    <h6>6. Privacy</h6>
                                    <p>Your personal data will be handled in accordance with our Privacy Policy, in
                                        compliance with data protection regulations.</p>

                                    <h6>7. Dispute Resolution</h6>
                                    <p>Any disputes will be resolved in accordance with the applicable laws of the Republic
                                        of Albania / Republic of Kosovo (depending on the company's legal base).</p>

                                    <hr style="margin: 20px 0;">

                                    <h5 style="color: #F27625; margin-bottom: 15px;">Cancellation Policy</h5>
                                    <p>The cancellation policy depends on each specific hotel and is clearly outlined during
                                        the booking process. However, the following general rules may apply:</p>

                                    <h6>1. Free Cancellation</h6>
                                    <ul>
                                        <li>Some hotels offer free cancellation up to a certain deadline (e.g., 24 to 72
                                            hours before the check-in date).</li>
                                        <li>If you cancel within this period, you will not be charged and any payments will
                                            be fully refunded.</li>
                                    </ul>

                                    <h6>2. Cancellation Fee</h6>
                                    <ul>
                                        <li>If you cancel after the free cancellation period, a fee may apply—usually
                                            equivalent to the price of one night or more, depending on the hotel's
                                            individual policy.</li>
                                    </ul>

                                    <h6>3. No-Show</h6>
                                    <ul>
                                        <li>If you fail to check in without prior cancellation (no-show), you may be charged
                                            the full reservation amount.</li>
                                    </ul>

                                    <h6>4. Booking Modifications</h6>
                                    <ul>
                                        <li>Changes to booking dates, guest numbers, or room types are subject to
                                            availability and may involve a price adjustment or additional fees.</li>
                                    </ul>

                                    <h6>5. Cancellation Process</h6>
                                    <ul>
                                        <li>Cancellations can be made directly from your account on Rezervo24 or by
                                            contacting our customer support team.</li>
                                        <li>If eligible, refunds will be processed within 5–10 business days.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" id="payment_type_amount" name="payment_type[amount]"
                value="{{ $bookingData['payment_types'][0]['amount'] ?? '0' }}">
            <input type="hidden" id="payment_type_currency_code" name="payment_type[currency_code]">
            <input type="hidden" id="payment_type_type" name="payment_type[type]">
            <input type="hidden" id="payment_type_is_need_credit_card_data"
                name="payment_type[is_need_credit_card_data]">

            <!-- Display final price (with 15% markup for guests) - used for customer payment -->
            <input type="hidden" name="display_final_price" value="{{ $displayFinalPrice ?? '0' }}">
            <input type="hidden" name="display_currency" value="{{ $displayCurrency ?? 'EUR' }}">

            <button type="submit" class="btn w-50 mt-3 d-block mx-auto" style="background:#F27625; color:white;">
                Finish Booking
            </button>

        </form>
    </div>


    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
            const paymentAmountInput = document.getElementById('payment_type_amount');
            const paymentCurrencyInput = document.getElementById('payment_type_currency_code');
            const paymentTypeHiddenInput = document.getElementById('payment_type_type');
            const creditCardRequiredInput = document.getElementById('payment_type_is_need_credit_card_data');
            const guestsContainer = document.getElementById('guests-container');

            // Custom accordion collapse handler (works with any Bootstrap version)
            document.querySelectorAll('.payment-method-label').forEach(function(label) {
                label.addEventListener('click', function(e) {
                    // Prevent double-triggering if clicking directly on radio
                    if (e.target.tagName !== 'INPUT') {
                        const radio = this.querySelector('input[type="radio"]');
                        if (radio) {
                            radio.checked = true;
                            radio.dispatchEvent(new Event('change'));
                        }
                    }

                    const targetId = this.getAttribute('data-collapse-target');
                    const targetElement = document.getElementById(targetId);

                    if (targetElement) {
                        // Close all other collapse items
                        document.querySelectorAll('.accordion .collapse').forEach(function(
                            collapse) {
                            if (collapse.id !== targetId) {
                                collapse.classList.remove('show');
                            }
                        });

                        // Toggle the clicked one
                        targetElement.classList.add('show');
                    }
                });
            });

            // Also handle radio button changes
            paymentMethodRadios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    const label = this.closest('.payment-method-label');
                    if (label) {
                        const targetId = label.getAttribute('data-collapse-target');
                        const targetElement = document.getElementById(targetId);

                        if (targetElement) {
                            // Close all other collapse items
                            document.querySelectorAll('.accordion .collapse').forEach(function(
                                collapse) {
                                if (collapse.id !== targetId) {
                                    collapse.classList.remove('show');
                                }
                            });

                            // Show the selected one
                            targetElement.classList.add('show');
                        }
                    }
                });
            });

            // Admin autofill logic
            const agentSelect = document.getElementById('agent_id');
            const firstNameInput = document.getElementById('first_name');
            const lastNameInput = document.getElementById('last_name');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');

            const defaultFirstName = firstNameInput.value;
            const defaultLastName = lastNameInput.value;
            const defaultEmail = emailInput.value;
            const defaultPhone = phoneInput.value;

            if (agentSelect) {
                agentSelect.addEventListener('change', function() {
                    const selected = agentSelect.options[agentSelect.selectedIndex];

                    if (!selected.value) {
                        // Reset to default user data
                        firstNameInput.value = defaultFirstName;
                        lastNameInput.value = defaultLastName;
                        emailInput.value = defaultEmail;
                        phoneInput.value = defaultPhone;
                        return;
                    }

                    const fullName = selected.dataset.name || '';
                    const email = selected.dataset.email || '';
                    const phone = selected.dataset.phone || '';
                    const nameParts = fullName.trim().split(' ');
                    const firstName = nameParts[0] || '';
                    const lastName = nameParts.slice(1).join(' ') || '';

                    firstNameInput.value = firstName;
                    lastNameInput.value = lastName;
                    emailInput.value = email;
                    phoneInput.value = phone;
                });
            }

            // Payment field sync
            function updatePaymentFields() {
                const selectedRadio = document.querySelector('input[name="payment_method"]:checked');
                const finishButton = document.querySelector('button[type="submit"]');
                const termsCheckbox = document.getElementById('pcb_terms_checkbox');

                if (selectedRadio) {
                    paymentAmountInput.value = selectedRadio.dataset.amount;
                    paymentCurrencyInput.value = selectedRadio.dataset.currency;
                    paymentTypeHiddenInput.value = selectedRadio.dataset.type;
                    creditCardRequiredInput.value = selectedRadio.dataset.needCard === '1' ? '1' : '0';

                    // Handle PCB Bank payment type
                    if (selectedRadio.dataset.type === 'pcb_bank') {
                        paymentTypeHiddenInput.value = 'now'; // Set the underlying type to 'now' for PCB

                        // Disable finish button if PCB Bank is selected but terms not accepted
                        if (!termsCheckbox.checked) {
                            finishButton.disabled = true;
                            finishButton.style.opacity = '0.5';
                            finishButton.style.cursor = 'not-allowed';
                        } else {
                            finishButton.disabled = false;
                            finishButton.style.opacity = '1';
                            finishButton.style.cursor = 'pointer';
                        }
                    } else {
                        // Enable finish button for other payment methods
                        finishButton.disabled = false;
                        finishButton.style.opacity = '1';
                        finishButton.style.cursor = 'pointer';
                    }
                }
            }

            // Add event listeners to all radio buttons
            paymentMethodRadios.forEach(radio => {
                radio.addEventListener('change', updatePaymentFields);
            });

            // Add event listener for terms checkbox
            const termsCheckbox = document.getElementById('pcb_terms_checkbox');
            if (termsCheckbox) {
                termsCheckbox.addEventListener('change', updatePaymentFields);
            }

            // Initialize with the first selected option
            updatePaymentFields();

            // Form submission validation
            const bookingForm = document.getElementById('bookingForm');
            bookingForm.addEventListener('submit', function(e) {
                const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
                const termsCheckbox = document.getElementById('pcb_terms_checkbox');

                // Check if PCB Bank is selected
                if (selectedPayment && selectedPayment.dataset.type === 'pcb_bank') {
                    // Check if terms are accepted
                    if (!termsCheckbox.checked) {
                        e.preventDefault();
                        termsCheckbox.classList.add('is-invalid');
                        termsCheckbox.focus();

                        // Show error message
                        const invalidFeedback = termsCheckbox.parentNode.nextElementSibling;
                        invalidFeedback.style.display = 'block';

                        return false;
                    } else {
                        termsCheckbox.classList.remove('is-invalid');
                        const invalidFeedback = termsCheckbox.parentNode.nextElementSibling;
                        invalidFeedback.style.display = 'none';
                    }
                }
            });

            // Add guest dynamically
            let guestIndex = 1;
            document.getElementById('addGuest').addEventListener('click', function() {
                const guestDiv = document.createElement('div');
                guestDiv.classList.add('guest', 'mb-4');
                guestDiv.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Guest First Name</label>
                        <input type="text" class="form-control" name="rooms[0][guests][${guestIndex}][first_name]" required
                            pattern="[A-Za-zÀ-ÖØ-öø-ÿ\\s\\-\\.,]+"
                            title="Only letters, spaces, hyphens (-), commas (,), and periods (.) are allowed in names.">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Guest Last Name</label>
                        <input type="text" class="form-control" name="rooms[0][guests][${guestIndex}][last_name]" required
                            pattern="[A-Za-zÀ-ÖØ-öø-ÿ\\s\\-\\.,]+"
                            title="Only letters, spaces, hyphens (-), commas (,), and periods (.) are allowed in names.">
                    </div>
                </div>
            `;
                guestsContainer.appendChild(guestDiv);
                // Attach validation listener to the new guest name inputs
                guestDiv.querySelectorAll('input').forEach(function(input) {
                    input.addEventListener('input', function() {
                        // Use the outer function from DOMContentLoaded scope
                        validateNameInput(this);
                    });
                });
                guestIndex++;
            });

            // Real‑time name validation on all name inputs (user and guests).
            // Allowed characters: Latin letters (including accented characters), spaces,
            // hyphens, commas and periods. When invalid characters are typed the
            // input will be marked with the Bootstrap `.is-invalid` class and
            // the finish button will be disabled until corrected.
            const nameRegex = /^[A-Za-zÀ-ÖØ-öø-ÿ\s\-\.,]+$/;
            const finishBtn = document.querySelector('button[type="submit"]');

            function validateNameInput(input) {
                if (nameRegex.test(input.value.trim()) || input.value.trim() === '') {
                    input.classList.remove('is-invalid');
                } else {
                    input.classList.add('is-invalid');
                }
                // Disable finish button if any name field is invalid
                const anyInvalid = document.querySelectorAll('input.is-invalid').length > 0;
                if (anyInvalid) {
                    finishBtn.disabled = true;
                    finishBtn.style.opacity = '0.5';
                    finishBtn.style.cursor = 'not-allowed';
                } else {
                    // Only enable if PCB terms accepted when using PCB payment
                    const selectedRadio = document.querySelector('input[name="payment_method"]:checked');
                    const termsCheckbox = document.getElementById('pcb_terms_checkbox');
                    if (selectedRadio && selectedRadio.dataset.type === 'pcb_bank' && !termsCheckbox.checked) {
                        finishBtn.disabled = true;
                        finishBtn.style.opacity = '0.5';
                        finishBtn.style.cursor = 'not-allowed';
                    } else {
                        finishBtn.disabled = false;
                        finishBtn.style.opacity = '1';
                        finishBtn.style.cursor = 'pointer';
                    }
                }
            }
            // Attach validation to existing name fields
            document.querySelectorAll('#first_name, #last_name, input[name^="rooms"]').forEach(function(el) {
                el.addEventListener('input', function() {
                    validateNameInput(this);
                });
            });
        });

        // Function to toggle terms and conditions div
        function toggleTermsDiv() {
            const termsDiv = document.getElementById('terms-div');

            if (termsDiv.style.display === 'none' || termsDiv.style.display === '') {
                termsDiv.style.display = 'block';
            } else {
                termsDiv.style.display = 'none';
            }
        }
    </script>
@endsection
