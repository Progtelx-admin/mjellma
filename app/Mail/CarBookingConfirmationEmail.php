<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CarBookingConfirmationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $bookingData;
    public $customerData;

    public function __construct($bookingData, $customerData = [])
    {
        $this->bookingData = $bookingData;
        $this->customerData = $customerData;
    }

    public function build()
    {
        $bookingId = $this->bookingData['id'] ?? $this->bookingData['reservation_nr'] ?? 'N/A';
        
        return $this->view('Car::emails.booking-confirmation')
                    ->subject('Car Rental Booking Confirmation – #' . $bookingId);
    }
}

