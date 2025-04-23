@extends('layouts.app')

@section('content')
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
                    value="{{ old('first_name', $defaultFirstName) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Your Last Name</label>
                <input type="text" id="last_name" name="last_name" class="form-control"
                    value="{{ old('last_name', $defaultLastName) }}" required>
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
                            <input type="text" class="form-control" name="rooms[0][guests][0][first_name]" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Guest Last Name</label>
                            <input type="text" class="form-control" name="rooms[0][guests][0][last_name]" required>
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-outline-secondary mb-3" id="addGuest">Add Another Guest</button>

            <!-- Payment Section -->
            <h3 class="fw-bold">Payment</h3>
            <div class="mb-3">
                <label class="form-label">Payment Type</label>
                <select class="form-select" id="payment_type" required>
                    @foreach ($bookingData['payment_types'] as $payment)
                        <option value="{{ $payment['type'] }}|{{ $payment['currency_code'] }}"
                            data-type="{{ $payment['type'] }}" data-currency="{{ $payment['currency_code'] }}"
                            data-amount="{{ $payment['amount'] }}"
                            data-need-card="{{ $payment['is_need_credit_card_data'] ? '1' : '0' }}">
                            {{ ucfirst($payment['type']) }} - {{ $payment['amount'] }} {{ $payment['currency_code'] }}
                            @if ($payment['is_need_credit_card_data'])
                                (Pay with Card)
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <input type="hidden" id="payment_type_amount" name="payment_type[amount]">
            <input type="hidden" id="payment_type_currency_code" name="payment_type[currency_code]">
            <input type="hidden" id="payment_type_type" name="payment_type[type]">
            <input type="hidden" id="payment_type_is_need_credit_card_data"
                name="payment_type[is_need_credit_card_data]">

            <button type="submit" class="btn btn-primary w-100 mt-3">Finish Booking</button>
        </form>
    </div>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentTypeSelect = document.getElementById('payment_type');
            const paymentAmountInput = document.getElementById('payment_type_amount');
            const paymentCurrencyInput = document.getElementById('payment_type_currency_code');
            const paymentTypeHiddenInput = document.getElementById('payment_type_type');
            const creditCardRequiredInput = document.getElementById('payment_type_is_need_credit_card_data');
            const guestsContainer = document.getElementById('guests-container');

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
                const selected = paymentTypeSelect.options[paymentTypeSelect.selectedIndex];
                paymentAmountInput.value = selected.dataset.amount;
                paymentCurrencyInput.value = selected.dataset.currency;
                paymentTypeHiddenInput.value = selected.dataset.type;
                creditCardRequiredInput.value = selected.dataset.needCard === '1' ? '1' : '0';
            }

            paymentTypeSelect.addEventListener('change', updatePaymentFields);
            updatePaymentFields();

            // Add guest dynamically
            let guestIndex = 1;
            document.getElementById('addGuest').addEventListener('click', function() {
                const guestDiv = document.createElement('div');
                guestDiv.classList.add('guest', 'mb-4');
                guestDiv.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Guest First Name</label>
                        <input type="text" class="form-control" name="rooms[0][guests][${guestIndex}][first_name]" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Guest Last Name</label>
                        <input type="text" class="form-control" name="rooms[0][guests][${guestIndex}][last_name]" required>
                    </div>
                </div>
            `;
                guestsContainer.appendChild(guestDiv);
                guestIndex++;
            });
        });
    </script>
@endsection
