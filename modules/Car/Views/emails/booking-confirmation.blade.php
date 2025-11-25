<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental Booking Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #F27625 0%, #ff8a45 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #e5e7eb;
        }
        .info-section {
            margin-bottom: 25px;
        }
        .info-section h2 {
            color: #F27625;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 2px solid #F27625;
            padding-bottom: 5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: 600;
            color: #6b7280;
        }
        .value {
            color: #1f2937;
            font-weight: 500;
        }
        .total-row {
            background: #fff7ed;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .total-row .label {
            color: #F27625;
            font-size: 18px;
        }
        .total-row .value {
            color: #F27625;
            font-size: 20px;
            font-weight: 700;
        }
        .footer {
            background: #f9fafb;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            margin-top: 20px;
        }
        .footer p {
            margin: 5px 0;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚗 Car Rental Booking Confirmation</h1>
        <p style="margin: 10px 0 0 0;">Thank you for your booking!</p>
    </div>

    <div class="content">
        {{-- Booking Information --}}
        <div class="info-section">
            <h2>Booking Details</h2>
            @if(isset($bookingData['id']))
            <div class="info-row">
                <span class="label">Booking ID:</span>
                <span class="value">#{{ $bookingData['id'] }}</span>
            </div>
            @endif
            @if(isset($bookingData['reservation_nr']))
            <div class="info-row">
                <span class="label">Reservation Number:</span>
                <span class="value">{{ $bookingData['reservation_nr'] }}</span>
            </div>
            @endif
        </div>

        {{-- Customer Information --}}
        <div class="info-section">
            <h2>Customer Information</h2>
            <div class="info-row">
                <span class="label">Name:</span>
                <span class="value">{{ $customerData['first_name'] ?? ($bookingData['customer']['first_name'] ?? 'N/A') }} {{ $customerData['last_name'] ?? ($bookingData['customer']['last_name'] ?? '') }}</span>
            </div>
            <div class="info-row">
                <span class="label">Email:</span>
                <span class="value">{{ $customerData['email'] ?? ($bookingData['customer']['email'] ?? 'N/A') }}</span>
            </div>
            <div class="info-row">
                <span class="label">Phone:</span>
                <span class="value">{{ $customerData['phone'] ?? ($bookingData['customer']['phone'] ?? 'N/A') }}</span>
            </div>
            @if(isset($customerData['driving_license']) || isset($bookingData['customer']['driving_license']))
            <div class="info-row">
                <span class="label">Driving License:</span>
                <span class="value">{{ $customerData['driving_license'] ?? $bookingData['customer']['driving_license'] }}</span>
            </div>
            @endif
        </div>

        {{-- Car Information --}}
        @if(isset($bookingData['car']))
        <div class="info-section">
            <h2>Car Details</h2>
            <div class="info-row">
                <span class="label">Car:</span>
                <span class="value">
                    {{ ($bookingData['car']['car_manufacturer']['name'] ?? '') . ' ' . ($bookingData['car']['car_model']['name'] ?? '') }}
                </span>
            </div>
        </div>
        @endif

        {{-- Rental Period --}}
        <div class="info-section">
            <h2>Rental Period</h2>
            <div class="info-row">
                <span class="label">Pickup Date & Time:</span>
                <span class="value">{{ $bookingData['pickup_datetime'] ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Drop-off Date & Time:</span>
                <span class="value">{{ $bookingData['dropoff_datetime'] ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Duration:</span>
                <span class="value">{{ $bookingData['days'] ?? 'N/A' }} Days</span>
            </div>
        </div>

        {{-- Locations --}}
        <div class="info-section">
            <h2>Pickup & Drop-off Locations</h2>
            @if(isset($bookingData['pickup_place']['address']))
            <div class="info-row">
                <span class="label">Pickup Location:</span>
                <span class="value">{{ $bookingData['pickup_place']['address'] }}</span>
            </div>
            @endif
            @if(isset($bookingData['dropoff_place']['address']))
            <div class="info-row">
                <span class="label">Drop-off Location:</span>
                <span class="value">{{ $bookingData['dropoff_place']['address'] }}</span>
            </div>
            @endif
        </div>

        {{-- Selected Extras --}}
        @if(isset($bookingData['extras']) && is_array($bookingData['extras']) && count($bookingData['extras']) > 0)
        <div class="info-section">
            <h2>Selected Extras</h2>
            @foreach($bookingData['extras'] as $extra)
            <div class="info-row">
                <span class="label">{{ $extra['name'] ?? 'Extra' }}:</span>
                <span class="value">{{ number_format($extra['price'] ?? 0, 2) }} €</span>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Selected Franchise --}}
        @if(isset($bookingData['franchise']) && is_array($bookingData['franchise']))
        <div class="info-section">
            <h2>Insurance / Franchise</h2>
            <div class="info-row">
                <span class="label">{{ $bookingData['franchise']['name'] ?? 'Franchise' }}:</span>
                <span class="value">
                    @if(($bookingData['franchise']['price'] ?? 0) > 0)
                        {{ number_format($bookingData['franchise']['price'], 2) }} € / day
                    @else
                        Included
                    @endif
                </span>
            </div>
        </div>
        @endif

        {{-- Price --}}
        @if(isset($bookingData['calculation']['grand_total']))
        <div class="total-row">
            <div class="info-row" style="border: none;">
                <span class="label">Total Amount:</span>
                <span class="value">{{ number_format($bookingData['calculation']['grand_total'], 2) }} €</span>
            </div>
        </div>
        @endif
        
        {{-- Payment Method --}}
        @if(isset($bookingData['payment_method']))
        <div class="info-section">
            <h2>Payment Method</h2>
            <div class="info-row">
                <span class="label">Payment Type:</span>
                <span class="value">{{ $bookingData['payment_method'] }}</span>
            </div>
            @if(isset($bookingData['pcb_order_id']))
            <div class="info-row">
                <span class="label">Transaction ID:</span>
                <span class="value">{{ $bookingData['pcb_order_id'] }}</span>
            </div>
            @endif
        </div>
        @endif

        {{-- Important Information --}}
        <div class="info-section">
            <h2>Important Information</h2>
            <ul style="padding-left: 20px; margin: 0;">
                <li>Please bring a valid driver's license</li>
                <li>Arrive 15 minutes before your pickup time</li>
                <li>Have your booking confirmation ready</li>
                <li>Check the vehicle carefully before departure</li>
            </ul>
        </div>
    </div>

    <div class="footer">
        <p><strong>Thank you for choosing our service!</strong></p>
        <p>If you have any questions, please contact us.</p>
        <p style="font-size: 12px; color: #9ca3af; margin-top: 15px;">
            This is an automated email. Please do not reply directly to this message.
        </p>
    </div>
</body>
</html>

