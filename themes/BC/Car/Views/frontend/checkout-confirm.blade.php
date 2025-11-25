@extends('layouts.app')

@push('css')
    <link href="{{ asset('dist/frontend/module/car/css/car.css?_ver=' . config('app.asset_version')) }}" rel="stylesheet">
    <style>
        .checkout-confirm {
            background: #f5f7fa;
            min-height: 100vh;
            padding: 3rem 0;
        }

        .confirm-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: scaleIn 0.5s ease-out;
        }

        .success-icon i {
            font-size: 2.5rem;
            color: white;
        }

        @keyframes scaleIn {
            0% {
                transform: scale(0);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        .confirm-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .confirm-subtitle {
            font-size: 1.1rem;
            color: #6b7280;
            text-align: center;
            margin-bottom: 2rem;
        }

        .info-section {
            border-top: 1px solid #e5e7eb;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }

        .info-section h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #6b7280;
            font-weight: 500;
        }

        .info-value {
            color: #1f2937;
            font-weight: 600;
        }

        .btn-action {
            padding: 0.75rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 0.375rem;
            transition: all 0.3s ease;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #0B0B45 0%, #1a1a6e 100%);
            border: none;
            color: white;
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #1a1a6e 0%, #0B0B45 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(11, 11, 69, 0.4);
            color: white;
        }

        .btn-secondary-custom {
            background: white;
            border: 2px solid #e5e7eb;
            color: #374151;
        }

        .btn-secondary-custom:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .btn-action {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="checkout-confirm">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="confirm-card">
                        {{-- Success Icon --}}
                        <div class="success-icon">
                            <i class="fa fa-check"></i>
                        </div>

                        {{-- Success Message --}}
                        <h1 class="confirm-title">{{ __('Checkout Successful!') }}</h1>
                        <p class="confirm-subtitle">
                            {{ __('Your car reservation request has been submitted successfully.') }}</p>

                        {{-- Checkout Information --}}
                        @if (isset($api_response) && !empty($api_response))
                            <div class="info-section">
                                <h3>{{ __('Reservation Details') }}</h3>

                                {{-- Display API response data --}}
                                @if (isset($api_response['id']))
                                    <div class="info-row">
                                        <span class="info-label">{{ __('Booking ID') }}</span>
                                        <span class="info-value">#{{ $api_response['id'] }}</span>
                                    </div>
                                @endif

                                @if (isset($api_response['reservation_nr']))
                                    <div class="info-row">
                                        <span class="info-label">{{ __('Reservation Number') }}</span>
                                        <span class="info-value">{{ $api_response['reservation_nr'] }}</span>
                                    </div>
                                @endif

                                @if (isset($customer_data['first_name']) && isset($customer_data['last_name']))
                                    <div class="info-row">
                                        <span class="info-label">{{ __('Customer Name') }}</span>
                                        <span class="info-value">{{ $customer_data['first_name'] }}
                                            {{ $customer_data['last_name'] }}</span>
                                    </div>
                                @endif

                                @if (isset($customer_data['email']))
                                    <div class="info-row">
                                        <span class="info-label">{{ __('Email') }}</span>
                                        <span class="info-value">{{ $customer_data['email'] }}</span>
                                    </div>
                                @endif

                                @if (isset($booking_data['reservation_data']['car_id']))
                                    <div class="info-row">
                                        <span class="info-label">{{ __('Car ID') }}</span>
                                        <span class="info-value">{{ $booking_data['reservation_data']['car_id'] }}</span>
                                    </div>
                                @endif

                                {{-- Display all API response data for debugging --}}
                                @if (config('app.debug'))
                                    <div class="info-row">
                                        <span class="info-label">{{ __('API Response') }}</span>
                                        <span class="info-value">
                                            <pre style="font-size: 0.8rem; max-height: 200px; overflow-y: auto;">{{ json_encode($api_response, JSON_PRETTY_PRINT) }}</pre>
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Next Steps --}}
                        <div class="info-section">
                            <h3>{{ __('What\'s Next?') }}</h3>
                            <p style="color: #6b7280; line-height: 1.6;">
                                {{ __('You will receive a confirmation email shortly with your reservation details. Please check your email for further instructions.') }}
                            </p>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="action-buttons">
                            <a href="{{ url('/') }}" class="btn btn-action btn-primary-custom">
                                <i class="fa fa-home mr-2"></i>{{ __('Go to Homepage') }}
                            </a>
                        </div>
                    </div>

                    {{-- Additional Info Card --}}
                    <div class="confirm-card">
                        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">
                            <i class="fa fa-info-circle" style="color: #F27625;"></i> {{ __('Important Information') }}
                        </h3>
                        <ul style="color: #6b7280; line-height: 2; list-style: none; padding-left: 0;">
                            <li><i class="fa fa-check-circle"
                                    style="color: #22c55e; margin-right: 0.5rem;"></i>{{ __('Please bring a valid driver\'s license') }}
                            </li>
                            <li><i class="fa fa-check-circle"
                                    style="color: #22c55e; margin-right: 0.5rem;"></i>{{ __('Arrive 15 minutes before your pickup time') }}
                            </li>
                            <li><i class="fa fa-check-circle"
                                    style="color: #22c55e; margin-right: 0.5rem;"></i>{{ __('Have your booking confirmation ready') }}
                            </li>
                            <li><i class="fa fa-check-circle"
                                    style="color: #22c55e; margin-right: 0.5rem;"></i>{{ __('Check the vehicle carefully before departure') }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        // Clear checkout data from session after displaying
        // This prevents users from refreshing and seeing old data
        window.addEventListener('load', function() {
            // You could make an AJAX call here to clear session data if needed
            console.log('Checkout confirmation page loaded');
        });
    </script>
@endpush
