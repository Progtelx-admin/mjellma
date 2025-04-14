@extends('layouts.app')

@section('content')
    @php
        $user = Auth::user();
        // You can pass additional dynamic booking data from your controller
        // For example: partner_order_id, order_id, language, book_hash, item_id, etc.
        $partnerOrderId = $bookingData['partner_order_id'] ?? session('partner_order_id', Str::uuid());
        $orderId = $bookingData['order_id'] ?? session('order_id', rand(100000000, 999999999));
        $language = $bookingData['language'] ?? 'en';
        $bookHash = $bookingData['book_hash'] ?? ($selectedRate['book_hash'] ?? '');
        $itemId = $bookingData['item_id'] ?? '';
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

            <!-- Dynamic Hidden Inputs -->
            <input type="hidden" name="partner_order_id" value="{{ $partnerOrderId }}">
            <input type="hidden" name="order_id" value="{{ $orderId }}">
            <input type="hidden" name="return_path" value="{{ route('hotel.payment.success') }}">
            <input type="hidden" name="language" value="{{ $language }}">
            <input type="hidden" name="book_hash" value="{{ $bookHash }}">
            <input type="hidden" name="item_id" value="{{ $itemId }}">

            {{-- User Info --}}
            @if ($user)
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="user[name]" value="{{ $user->name }}" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="user[email]" value="{{ $user->email }}" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-control" name="user[phone]" value="{{ $user->phone }}" readonly>
                </div>
            @else
                <input type="hidden" name="user[email]" value="guest@example.com">
                <input type="hidden" name="user[phone]" value="+1000000000">
            @endif

            {{-- Supplier Info (use user details if available, otherwise fallback values) --}}
            <input type="hidden" name="supplier_data[first_name_original]"
                value="{{ $user->first_name ?? 'SupplierFirst' }}">
            <input type="hidden" name="supplier_data[last_name_original]"
                value="{{ $user->last_name ?? 'SupplierLast' }}">
            <input type="hidden" name="supplier_data[phone]" value="{{ $user->phone ?? '+999999999' }}">
            <input type="hidden" name="supplier_data[email]" value="{{ $user->email ?? 'supplier@example.com' }}">

            {{-- Guests Section --}}
            <h3 class="fw-bold">Guests</h3>
            <div id="guests-container">
                <div class="guest mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="rooms[0][guests][0][first_name]" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="rooms[0][guests][0][last_name]" required>
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-outline-secondary mb-3" id="addGuest">Add Another Guest</button>

            {{-- Payment Type Section --}}
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

            <!-- Hidden fields to capture payment type details dynamically -->
            <input type="hidden" id="payment_type_amount" name="payment_type[amount]">
            <input type="hidden" id="payment_type_currency_code" name="payment_type[currency_code]">
            <input type="hidden" id="payment_type_type" name="payment_type[type]">
            <input type="hidden" id="payment_type_is_need_credit_card_data"
                name="payment_type[is_need_credit_card_data]">

            <button type="submit" class="btn btn-primary w-100 mt-3">Finish Booking</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentTypeSelect = document.getElementById('payment_type');
            const paymentAmountInput = document.getElementById('payment_type_amount');
            const paymentCurrencyInput = document.getElementById('payment_type_currency_code');
            const paymentTypeHiddenInput = document.getElementById('payment_type_type');
            const creditCardRequiredInput = document.getElementById('payment_type_is_need_credit_card_data');
            const guestsContainer = document.getElementById('guests-container');

            // Function to update hidden payment fields based on selected option
            function updatePaymentFields() {
                const selected = paymentTypeSelect.options[paymentTypeSelect.selectedIndex];
                const type = selected.dataset.type;
                const currency = selected.dataset.currency;
                const amount = selected.dataset.amount;
                const needCard = selected.dataset.needCard === '1';

                paymentAmountInput.value = amount;
                paymentCurrencyInput.value = currency;
                paymentTypeHiddenInput.value = type;
                creditCardRequiredInput.value = needCard ? '1' : '0';
            }

            paymentTypeSelect.addEventListener('change', updatePaymentFields);
            updatePaymentFields(); // Initialize on page load

            // Add additional guest fields dynamically
            let guestIndex = 1;
            document.getElementById('addGuest').addEventListener('click', function() {
                const guestDiv = document.createElement('div');
                guestDiv.classList.add('guest', 'mb-4');
                guestDiv.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="rooms[0][guests][${guestIndex}][first_name]" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
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
