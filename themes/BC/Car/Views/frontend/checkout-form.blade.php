@extends('layouts.app')

@push('css')
    <link href="{{ asset('dist/frontend/module/car/css/car.css?_ver=' . config('app.asset_version')) }}" rel="stylesheet">
    <style>
        .checkout-page {
            background: #f9fafb;
            min-height: 100vh;
            padding: 3rem 0;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #F27625;
            margin-bottom: 2rem;
            text-align: center;
        }

        .checkout-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #F27625;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            display: block;
            font-size: 0.95rem;
        }

        .form-group label .required {
            color: #ef4444;
        }

        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 0.25rem;
            padding: 0.625rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: #f9fafb;
        }

        .form-control:focus {
            border-color: #F27625;
            box-shadow: 0 0 0 3px rgba(242, 118, 37, 0.1);
            background: white;
        }

        .form-control.is-invalid {
            border-color: #ef4444;
        }

        .invalid-feedback {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* Reservation Summary Styles */
        .reservation-summary {
            background: white;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .summary-header {
            background: #F27625;
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
        }

        .summary-header .car-name {
            font-size: 1.1rem;
        }

        .summary-header .car-price {
            text-align: right;
        }

        .summary-body {
            padding: 0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            background: white;
        }

        .summary-row:hover {
            background: #f9fafb;
        }

        .summary-row .label {
            color: #6b7280;
            font-weight: 500;
        }

        .summary-row .value {
            color: #1f2937;
            font-weight: 600;
        }

        .summary-footer {
            background: #F27625;
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 700;
            font-size: 1.25rem;
        }

        /* Payment Method Styles */
        .accordion .card {
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
        }

        .accordion .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.25rem;
        }

        .accordion .card-header h4 {
            font-size: 1rem;
            font-weight: 600;
        }

        .payment-method-label {
            display: flex;
            align-items: center;
            margin: 0;
            width: 100%;
        }

        .payment-method-label input[type="radio"] {
            margin-right: 0.75rem;
            cursor: pointer;
        }

        .accordion .card-body {
            padding: 1.25rem;
        }

        .gateway_name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #F27625;
            margin-bottom: 0.75rem;
        }

        .text-info {
            color: black !important;
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #0B0B45 0%, #1a1a6e 100%);
            border: none;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            padding: 1rem 2rem;
            border-radius: 0.375rem;
            transition: all 0.3s ease;
            margin-top: 2rem;
            text-transform: uppercase;
        }

        .btn-submit:hover:not(:disabled) {
            background: linear-gradient(135deg, #1a1a6e 0%, #0B0B45 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(11, 11, 69, 0.4);
            color: white;
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .alert {
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .info-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #F27625;
            color: white;
            font-size: 0.75rem;
            margin-left: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .info-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(242, 118, 37, 0.4);
        }

        @media (max-width: 768px) {
            .checkout-page {
                padding: 1.5rem 0;
            }

            .checkout-card {
                padding: 1.5rem;
            }

            .page-title {
                font-size: 1.5rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="checkout-page">
        <div class="container">
            <h1 class="page-title">{{ __('Të Dhënat Personale') }}</h1>

            <div id="alert-container"></div>

            <form id="checkout-form">
                @csrf

                {{-- Row 1: Personal Information Split --}}
                <div class="row">
                    {{-- Driver Information --}}
                    <div class="col-lg-6">
                        <div class="checkout-card">
                            <h2 class="section-title">{{ __('Të dhënat e shoferit') }}</h2>

                            <div class="row">
                                {{-- Title --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="title">{{ __('Titulli') }}</label>
                                        <select name="title" id="title" class="form-control" required>
                                            <option value="">{{ __('Titulli') }}</option>
                                            <option value="Mr">{{ __('Mr') }}</option>
                                            <option value="Mrs">{{ __('Mrs') }}</option>
                                            <option value="Ms">{{ __('Ms') }}</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>

                                {{-- First Name --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="first_name">{{ __('Emri') }}</label>
                                        <input type="text" name="first_name" id="first_name" class="form-control"
                                            placeholder="{{ __('Emri') }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                {{-- Last Name --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="last_name">{{ __('Mbiemri') }}</label>
                                        <input type="text" name="last_name" id="last_name" class="form-control"
                                            placeholder="{{ __('Mbiemri') }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>

                                {{-- Email --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">{{ __('E-mail') }}</label>
                                        <input type="email" name="email" id="email" class="form-control"
                                            placeholder="Email" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                {{-- Birthday --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="birthday">{{ __('Datëlindja') }}</label>
                                        <input type="date" name="birthday" id="birthday" class="form-control" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>

                                {{-- Driving License --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="driving_license">
                                            {{ __('Patentë shoferi') }}
                                            <span class="info-icon" onclick="toggleDriverLicenseInfo()"
                                                style="cursor: pointer;" title="{{ __('Click to see example') }}">i</span>
                                        </label>
                                        <input type="text" name="driving_license" id="driving_license"
                                            class="form-control" placeholder="{{ __('Patentë shoferi') }}" required>
                                        <div class="invalid-feedback"></div>

                                        {{-- Inline Driver License Info --}}
                                        <div id="driverLicenseInfo" style="display: none; margin-top: 1rem;">
                                            <div
                                                style="background: #fff7ed; border: 2px solid #F27625; border-radius: 0.5rem; padding: 1rem;">
                                                <p
                                                    style="color: #F27625; font-weight: 600; margin-bottom: 0.75rem; font-size: 1rem;">
                                                    {{ __('Shënoje këtë numër') }}
                                                </p>
                                                <img src="{{ asset('uploads/driver_licence_for_rent.png') }}"
                                                     alt="Driver License Example"
                                                     style="width: 100%; border-radius: 0.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Contact Information --}}
                    <div class="col-lg-6">
                        <div class="checkout-card">
                            <h2 class="section-title">{{ __('Të dhënat kontaktuese') }}</h2>

                            <div class="row">
                                {{-- Phone --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">{{ __('Numri telefonit') }}</label>
                                        <input type="tel" name="phone" id="phone" class="form-control"
                                            placeholder="{{ __('Numri telefonit') }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>

                                {{-- Address --}}
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="address">{{ __('Rruga') }}</label>
                                        <input type="text" name="address" id="address" class="form-control"
                                            placeholder="{{ __('Rruga') }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                {{-- PLZ --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="plz">{{ __('Kodi postar') }}</label>
                                        <input type="text" name="plz" id="plz" class="form-control"
                                            placeholder="{{ __('Kodi postar') }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>

                                {{-- City --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="city">{{ __('Qyteti') }}</label>
                                        <input type="text" name="city" id="city" class="form-control"
                                            placeholder="{{ __('Qyteti') }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>

                                {{-- Country --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="country">{{ __('Shteti') }}</label>
                                        <select name="country" id="country" class="form-control" required>
                                            <option value="">{{ __('Shteti') }}</option>
                                            @if (isset($countries) && is_array($countries))
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country['name'] ?? ($country['code'] ?? '') }}">
                                                        {{ $country['name'] ?? ($country['code'] ?? '') }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 2: Summary and Payment Method --}}
                <div class="row">
                    {{-- Reservation Summary --}}
                    <div class="col-lg-4">
                        <div class="reservation-summary mb-4">
                            @php
                                $car = $reservation['car'] ?? [];
                                $carName = trim(
                                    ($car['car_manufacturer']['name'] ?? '') . ' ' . ($car['car_model']['name'] ?? ''),
                                );
                                $calculation = $reservation['calculation'] ?? [];
                                $dayPrice = $calculation['day_price'] ?? 0;
                                $days = $reservation['days'] ?? 1;
                                $subtotal = $dayPrice * $days;
                                $grandTotal = $calculation['grand_total'] ?? 0;

                                // Add selected extras to total
                                $extrasTotal = 0;
                                if (
                                    isset($reservation['selected_extra_equipment']) &&
                                    is_array($reservation['selected_extra_equipment'])
                                ) {
                                    foreach ($reservation['selected_extra_equipment'] as $extra) {
                                        $extrasTotal += floatval($extra['price'] ?? 0);
                                    }
                                }

                                // Add selected franchise to total
                                $franchiseTotal = 0;
                                if (
                                    isset($reservation['selected_franchise']) &&
                                    is_array($reservation['selected_franchise'])
                                ) {
                                    $franchisePrice = floatval($reservation['selected_franchise']['price'] ?? 0);
                                    $franchiseTotal = $franchisePrice * $days;
                                }

                                // Calculate final total
                                $finalTotal = $grandTotal + $extrasTotal + $franchiseTotal;
                            @endphp

                            <div class="summary-header">
                                <span class="car-name">{{ $carName }}</span>
                                <div class="car-price">
                                    <div>{{ number_format($dayPrice, 2) }} € x {{ $days }} {{ __('Ditë') }}
                                    </div>
                                    <div>{{ number_format($subtotal, 2) }} €</div>
                                </div>
                            </div>

                            <div class="summary-body">
                                {{-- Selected Extras --}}
                                @if (isset($reservation['selected_extra_equipment']) &&
                                        is_array($reservation['selected_extra_equipment']) &&
                                        count($reservation['selected_extra_equipment']) > 0)
                                    @foreach ($reservation['selected_extra_equipment'] as $extra)
                                        <div class="summary-row">
                                            <span class="label">{{ $extra['name'] ?? 'Extra' }}</span>
                                            <span class="value">{{ number_format($extra['price'] ?? 0, 2) }} €</span>
                                        </div>
                                    @endforeach
                                @endif

                                {{-- Selected Franchise --}}
                                @if (isset($reservation['selected_franchise']) && is_array($reservation['selected_franchise']) && $franchiseTotal > 0)
                                    <div class="summary-row">
                                        <span
                                            class="label">{{ $reservation['selected_franchise']['name'] ?? __('Franchise') }}</span>
                                        <span class="value">{{ number_format($franchiseTotal, 2) }} €</span>
                                    </div>
                                @endif

                                {{-- VAT Breakdown --}}
                                @if (isset($calculation['no_tax']) && isset($calculation['tax']))
                                    <div class="summary-row">
                                        <span class="label">{{ __('Pa TVSH') }}</span>
                                        <span class="value">{{ number_format($calculation['no_tax'], 2) }} €</span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="label">{{ __('TVSH') }}
                                            {{ $calculation['tax_percent'] ?? 18 }}%</span>
                                        <span class="value">{{ number_format($calculation['tax'], 2) }} €</span>
                                    </div>
                                @endif
                            </div>

                            <div class="summary-footer">
                                <span>{{ __('Total') }}</span>
                                <span>{{ number_format($finalTotal, 2) }} €</span>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Method --}}
                    <div class="col-lg-8">
                        <div class="checkout-card">
                            <h2 class="section-title">{{ __('Metoda e pagesës') }}</h2>

                            <div class="accordion" id="accordionPayment">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="mb-0">
                                            <label class="payment-method-label" data-collapse-target="gateway_pcb"
                                                style="cursor: pointer;">
                                                <input type="radio" name="payment_method" id="payment_method_pcb"
                                                    value="pcb_bank" data-type="pcb_bank" data-currency="EUR"
                                                    data-amount="{{ $finalTotal ?? 0 }}" data-need-card="1" checked>
                                                {{ __('PCB Bank Payment') }} - {{ number_format($finalTotal ?? 0, 2) }} €
                                            </label>
                                        </h4>
                                    </div>
                                    <div id="gateway_pcb" class="collapse show" data-parent="#accordionPayment">
                                        <div class="card-body">
                                            <div class="gateway_name">
                                                {{ __('PCB Bank Payment') }}
                                            </div>
                                            <p>{{ __('Amount') }}: {{ number_format($finalTotal ?? 0, 2) }} €</p>
                                            <p class="text-info">
                                                {{ __('Secure payment through PCB Bank gateway. You will be redirected to enter your card information.') }}
                                            </p>

                                            {{-- Terms and Conditions Checkbox --}}
                                            <div class="mt-3">
                                                <label style="display: flex; align-items: center; cursor: pointer;">
                                                    <input type="checkbox" id="pcb_terms_checkbox"
                                                        style="margin-right: 8px;">
                                                    {{ __('I have read and accept the') }} <a href="#"
                                                        onclick="toggleTermsDiv(); return false;"
                                                        style="color: #F27625; text-decoration: underline; margin-left: 4px;">{{ __('terms and conditions') }}</a>
                                                </label>
                                                <div class="invalid-feedback" style="display: none; margin-top: 0.25rem;">
                                                    {{ __('You must accept the terms and conditions to proceed with PCB Bank payment.') }}
                                                </div>
                                            </div>

                                            {{-- Hidden Terms and Conditions Div --}}
                                            <div id="terms-div"
                                                style="display: none; margin-top: 15px; padding: 20px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">
                                                <h5 style="color: #F27625; margin-bottom: 15px;">
                                                    {{ __('Terms and Conditions') }}</h5>
                                                <p><strong>{{ __('Welcome to Rezervo24!') }}</strong></p>
                                                <p>{{ __('By using this website and/or making a reservation through our platform, you agree to the following terms and conditions. Please read them carefully before proceeding.') }}
                                                </p>

                                                <h6>{{ __('1. Use of the Platform') }}</h6>
                                                <ul>
                                                    <li>{{ __('Rezervo24 is an online platform that facilitates car rental bookings.') }}
                                                    </li>
                                                    <li>{{ __('We do not own or operate the listed vehicles and are not directly responsible for their management. We simply display the information and booking conditions provided by the car rental companies.') }}
                                                    </li>
                                                </ul>

                                                <h6>{{ __('2. Reservations') }}</h6>
                                                <ul>
                                                    <li>{{ __('All reservations made through Rezervo24 are in real-time and are considered valid only after confirmation is received via email or your user account.') }}
                                                    </li>
                                                    <li>{{ __('Users are responsible for providing accurate and complete information during the booking process.') }}
                                                    </li>
                                                </ul>

                                                <h6>{{ __('3. Payments') }}</h6>
                                                <ul>
                                                    <li>{{ __('Payments can be made securely through the online payment methods we offer.') }}
                                                    </li>
                                                    <li>{{ __('All prices are displayed transparently and include applicable taxes unless otherwise stated.') }}
                                                    </li>
                                                </ul>

                                                <h6>{{ __('4. Cancellation Policy') }}</h6>
                                                <p>{{ __('Before completing a booking, users must read and agree to the cancellation policy, which may vary depending on the selected car rental company. The full cancellation policy is outlined below in this document and also displayed during the booking process on each car\'s page.') }}
                                                </p>

                                                <h6>{{ __('5. Company-Initiated Changes or Cancellations') }}</h6>
                                                <p>{{ __('In exceptional cases, the car rental company may modify or cancel a reservation. We will do our best to assist you in finding an appropriate alternative or to provide a full refund, depending on the situation.') }}
                                                </p>

                                                <h6>{{ __('6. Privacy') }}</h6>
                                                <p>{{ __('Your personal data will be handled in accordance with our Privacy Policy, in compliance with data protection regulations.') }}
                                                </p>

                                                <h6>{{ __('7. Dispute Resolution') }}</h6>
                                                <p>{{ __('Any disputes will be resolved in accordance with the applicable laws of the Republic of Albania / Republic of Kosovo (depending on the company\'s legal base).') }}
                                                </p>

                                                <hr style="margin: 20px 0;">

                                                <h5 style="color: #F27625; margin-bottom: 15px;">
                                                    {{ __('Cancellation Policy') }}</h5>
                                                <p>{{ __('The cancellation policy depends on each specific car rental company and is clearly outlined during the booking process. However, the following general rules may apply:') }}
                                                </p>

                                                <h6>{{ __('1. Free Cancellation') }}</h6>
                                                <ul>
                                                    <li>{{ __('Some car rental companies offer free cancellation up to a certain deadline (e.g., 24 to 72 hours before the pickup date).') }}
                                                    </li>
                                                    <li>{{ __('If you cancel within this period, you will not be charged and any payments will be fully refunded.') }}
                                                    </li>
                                                </ul>

                                                <h6>{{ __('2. Cancellation Fee') }}</h6>
                                                <ul>
                                                    <li>{{ __('If you cancel after the free cancellation period, a fee may apply—usually equivalent to the price of one day or more, depending on the company\'s individual policy.') }}
                                                    </li>
                                                </ul>

                                                <h6>{{ __('3. No-Show') }}</h6>
                                                <ul>
                                                    <li>{{ __('If you fail to pick up the vehicle without prior cancellation (no-show), you may be charged the full reservation amount.') }}
                                                    </li>
                                                </ul>

                                                <h6>{{ __('4. Booking Modifications') }}</h6>
                                                <ul>
                                                    <li>{{ __('Changes to booking dates, rental duration, or vehicle types are subject to availability and may involve a price adjustment or additional fees.') }}
                                                    </li>
                                                </ul>

                                                <h6>{{ __('5. Cancellation Process') }}</h6>
                                                <ul>
                                                    <li>{{ __('Cancellations can be made directly from your account on Rezervo24 or by contacting our customer support team.') }}
                                                    </li>
                                                    <li>{{ __('If eligible, refunds will be processed within 5–10 business days.') }}
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-submit" id="submit-btn">
                            {{ __('Përfundo rezervimin') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script>
        // Function to toggle terms and conditions div
        function toggleTermsDiv() {
            const termsDiv = document.getElementById('terms-div');

            if (termsDiv.style.display === 'none' || termsDiv.style.display === '') {
                termsDiv.style.display = 'block';
            } else {
                termsDiv.style.display = 'none';
            }
        }

        // Function to update submit button state based on terms acceptance
        function updateSubmitButton() {
            const termsCheckbox = document.getElementById('pcb_terms_checkbox');
            const submitBtn = document.getElementById('submit-btn');

            if (!termsCheckbox.checked) {
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.5';
                submitBtn.style.cursor = 'not-allowed';
            } else {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const termsCheckbox = document.getElementById('pcb_terms_checkbox');

            // Set initial button state
            updateSubmitButton();

            // Add event listener for checkbox changes
            if (termsCheckbox) {
                termsCheckbox.addEventListener('change', updateSubmitButton);
            }

            // Custom accordion collapse handler
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
        });

        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submit-btn');
            const alertContainer = document.getElementById('alert-container');
            const termsCheckbox = document.getElementById('pcb_terms_checkbox');

            // Validate terms acceptance
            if (!termsCheckbox.checked) {
                alertContainer.innerHTML =
                    '<div class="alert alert-danger"><i class="fa fa-exclamation-circle mr-2"></i>{{ __('Duhet të pranoni termat dhe kushtet për të vazhduar.') }}</div>';
                alertContainer.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                // Show error on checkbox
                const invalidFeedback = termsCheckbox.parentNode.nextElementSibling;
                invalidFeedback.style.display = 'block';
                return false;
            }

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i>{{ __('Processing...') }}';

            // Clear previous errors
            document.querySelectorAll('.form-control').forEach(field => {
                field.classList.remove('is-invalid');
            });
            document.querySelectorAll('.invalid-feedback').forEach(feedback => {
                feedback.textContent = '';
            });
            alertContainer.innerHTML = '';

            // Get form data
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            // Submit to store-checkout
            fetch('{{ route('car.checkout.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Booking Response:', data);

                    if (data.success) {
                        // Show success message
                        alertContainer.innerHTML =
                            '<div class="alert alert-success"><i class="fa fa-check-circle mr-2"></i>' + data
                            .message + '</div>';

                        // Redirect to confirmation page
                        if (data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1000);
                        }
                    } else {
                        // Show error message
                        alertContainer.innerHTML =
                            '<div class="alert alert-danger"><i class="fa fa-exclamation-circle mr-2"></i>' +
                            (data.message || '{{ __('Booking failed. Please try again.') }}') + '</div>';

                        // Scroll to alert
                        alertContainer.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });

                        // Re-enable submit button
                        submitBtn.disabled = false;
                        submitBtn.innerHTML =
                            '<i class="fa fa-check-circle mr-2"></i>{{ __('Complete Booking') }}';
                    }
                })
                .catch(error => {
                    console.error('Booking Error:', error);

                    alertContainer.innerHTML =
                        '<div class="alert alert-danger"><i class="fa fa-exclamation-circle mr-2"></i>{{ __('An error occurred. Please try again.') }}</div>';

                    alertContainer.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML =
                        '<i class="fa fa-check-circle mr-2"></i>{{ __('Complete Booking') }}';
                });
        });

        // Toggle Driver License Info inline
        function toggleDriverLicenseInfo() {
            const infoDiv = document.getElementById('driverLicenseInfo');
            const icon = event.target;

            if (infoDiv.style.display === 'none') {
                // Show with smooth animation
                infoDiv.style.display = 'block';
                infoDiv.style.opacity = '0';
                setTimeout(() => {
                    infoDiv.style.transition = 'opacity 0.3s';
                    infoDiv.style.opacity = '1';
                }, 10);

                // Scroll to the image smoothly
                setTimeout(() => {
                    infoDiv.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                }, 100);
            } else {
                // Hide with smooth animation
                infoDiv.style.opacity = '0';
                setTimeout(() => {
                    infoDiv.style.display = 'none';
                }, 300);
            }
        }
    </script>
@endpush
