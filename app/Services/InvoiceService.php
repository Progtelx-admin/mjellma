<?php

namespace App\Services;

use App\Models\Invoice;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Payment;
use Modules\Booking\Gateways\PcbBankGateway;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    /**
     * Create invoice from payment
     */
    public function createInvoice(Payment $payment): Invoice
    {
        $booking = $payment->booking;
        $gateway = new PcbBankGateway($payment->payment_gateway);
        $invoiceData = $gateway->getInvoiceData($payment);

        if (!$invoiceData) {
            throw new \Exception('Cannot create invoice: Payment data not available');
        }

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'payment_id' => $payment->id,
            'invoice_number' => Invoice::generateInvoiceNumber($invoiceData['gateway_order_id']),
            'status' => 'draft',
            'amount' => $invoiceData['amount_eur'],
            'currency' => $invoiceData['currency'],
            'payment_gateway' => $invoiceData['payment_gateway'],
            'gateway_order_id' => $invoiceData['gateway_order_id'],
            'transaction_id' => $invoiceData['transaction_id'],
            'approval_code' => $invoiceData['approval_code'],
            'card_brand' => $invoiceData['card_brand'],
            'card_pan' => $invoiceData['card_pan'],
            'transaction_datetime' => $invoiceData['transaction_datetime'],
            'invoice_data' => $invoiceData,
        ]);

        Log::info('📄 Invoice created', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'booking_id' => $booking->id,
            'amount' => $invoice->amount
        ]);

        return $invoice;
    }

    /**
     * Generate PDF for invoice
     */
    public function generatePdf(Invoice $invoice): string
    {
        $pdfPath = "invoices/{$invoice->invoice_number}.pdf";
        
        // Generate PDF content
        $html = $this->generateInvoiceHtml($invoice);
        
        // For now, we'll create a simple HTML file
        // In production, you'd use a PDF library like DomPDF or TCPDF
        $htmlPath = "invoices/{$invoice->invoice_number}.html";
        Storage::disk('local')->put($htmlPath, $html);
        
        // Store PDF path (in production, this would be the actual PDF)
        if ($invoice->exists) {
            $invoice->update(['pdf_path' => $htmlPath]);
        }
        
        Log::info('📄 Invoice PDF generated', [
            'invoice_id' => $invoice->id ?? 'mock',
            'pdf_path' => $htmlPath
        ]);

        return $htmlPath; // Return the actual path that was created
    }

    /**
     * Generate HTML content for invoice
     */
    private function generateInvoiceHtml(Invoice $invoice): string
    {
        // Get actual booking data
        $booking = $invoice->booking;
        $bookingData = [
            'first_name' => $booking->first_name ?? 'Customer',
            'last_name' => $booking->last_name ?? 'Name',
            'email' => $booking->email ?? 'customer@example.com',
            'phone' => $booking->phone ?? 'N/A',
            'address' => $booking->address ?? 'N/A',
            'service_title' => 'Hotel Booking - Order #' . ($booking->code ?? 'Unknown')
        ];
        
        $invoiceData = $invoice->invoice_data;

        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Invoice {$invoice->invoice_number}</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background-color: #f9f9f9;
            color: #333;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header { 
            text-align: center; 
            margin-bottom: 40px; 
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }
        .merchant-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .merchant-address {
            color: #7f8c8d;
            line-height: 1.6;
        }
        .order-details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .order-details-table td {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .order-details-table .label {
            font-weight: bold;
            width: 30%;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .products-table th,
        .products-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .products-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .totals-table {
            width: 50%;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .totals-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
        }
        .totals-table .label {
            font-weight: bold;
        }
        .totals-table .total-row {
            font-weight: bold;
            font-size: 16px;
            background-color: #f8f9fa;
        }
        .payment-section {
            margin-bottom: 30px;
        }
        .payment-section h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .payment-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .payment-details ul {
            margin: 0;
            padding-left: 20px;
        }
        .addresses-section {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
        }
        .address-column {
            flex: 1;
        }
        .address-column h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .address-content {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .address-content p {
            margin: 5px 0;
            font-style: italic;
            color: #555;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #7f8c8d;
            text-align: center;
        }
        .footer a {
            color: #3498db;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class='invoice-container'>
        <div class='header'>
            <div class='merchant-name'>" . config('invoice.merchant.name') . "</div>
            <div class='merchant-address'>
                Address: " . config('invoice.merchant.address') . "<br>
                " . config('invoice.merchant.city') . "<br>
                Website: " . config('invoice.merchant.website') . " | Email: " . config('invoice.merchant.email') . " | Phone: " . config('invoice.merchant.phone') . "
            </div>
        </div>

        <table class='order-details-table'>
            <tr>
                <td class='label'>Order ID:</td>
                <td>#{$invoice->gateway_order_id}</td>
            </tr>
            <tr>
                <td class='label'>Order Date:</td>
                <td>" . date('d-m-Y H:i:s', strtotime($invoice->transaction_datetime)) . "</td>
            </tr>
            <tr>
                <td class='label'>Invoice Number:</td>
                <td>{$invoice->invoice_number}</td>
            </tr>
        </table>

        <table class='products-table'>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{$bookingData['service_title']}</td>
                    <td>1</td>
                    <td>{$invoice->amount} €</td>
                </tr>
            </tbody>
        </table>

        <table class='totals-table'>
            <tr>
                <td class='label'>Nëntotali:</td>
                <td>{$invoice->amount}€</td>
            </tr>
            <tr>
                <td class='label'>Transporti:</td>
                <td>0.00€ - Standard</td>
            </tr>
            <tr class='total-row'>
                <td class='label'>Totali:</td>
                <td>{$invoice->amount}€</td>
            </tr>
        </table>

        <div class='payment-section'>
            <h3>Payment Method: By Card</h3>
            <div class='payment-details'>
                <ul>
                    <li>Approval Code: {$invoice->approval_code}</li>
                    <li>Transaction date: " . date('d-m-Y H:i:s', strtotime($invoice->transaction_datetime)) . "</li>
                    <li>Card: {$invoice->card_brand}, {$invoice->card_pan}</li>
                </ul>
            </div>
        </div>

        <div class='addresses-section'>
            <div class='address-column'>
                <h3>Billing Address</h3>
                <div class='address-content'>
                    <p><strong>Card holder name:</strong> {$bookingData['first_name']} {$bookingData['last_name']}</p>
                    <p><strong>Address:</strong> {$bookingData['address']}</p>
                    <p><strong>Email:</strong> {$bookingData['email']}</p>
                    <p><strong>Phone:</strong> {$bookingData['phone']}</p>
                </div>
            </div>
            <div class='address-column'>
                <h3>Shipping Address</h3>
                <div class='address-content'>
                    <p><strong>Client Name:</strong> {$bookingData['first_name']} {$bookingData['last_name']}</p>
                    <p><strong>Address:</strong> {$bookingData['address']}</p>
                    <p><strong>Email:</strong> {$bookingData['email']}</p>
                    <p><strong>Phone:</strong> {$bookingData['phone']}</p>
                </div>
            </div>
        </div>

        <div class='footer'>
            <p>No Refunds after 30 days, see our <a href='#'>Return Policy</a>.</p>
            <p>Email: " . config('invoice.merchant.email') . " | Customer Service: " . config('invoice.merchant.phone') . "</p>
        </div>
    </div>
</body>
</html>";
    }

    /**
     * Send invoice via email
     */
    public function sendInvoice(Invoice $invoice, string $email = null): bool
    {
        try {
            $email = $email ?? $invoice->booking->email;
            
            if (!$email) {
                throw new \Exception('No email address provided');
            }

            // Generate PDF if not exists
            if (!$invoice->hasPdf()) {
                $this->generatePdf($invoice);
            }

            // Send email with invoice attachment
            $booking = $invoice->booking;
            $invoiceHtml = $this->generateInvoiceHtml($invoice);
            
            // Create email content
            $subject = config('invoice.email.subject_prefix') . " #{$invoice->invoice_number} - " . config('invoice.merchant.name');
            $emailContent = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #2c3e50;'>Thank you for your booking!</h2>
                    <p>Dear {$booking->first_name} {$booking->last_name},</p>
                    <p>Your booking has been confirmed and payment processed successfully.</p>
                    <p><strong>Invoice Number:</strong> {$invoice->invoice_number}</p>
                    <p><strong>Amount:</strong> {$invoice->amount} {$invoice->currency}</p>
                    <p><strong>Payment Method:</strong> {$invoice->card_brand} ending in " . substr($invoice->card_pan, -4) . "</p>
                    <p>Please find your invoice attached below.</p>
                    <hr style='margin: 20px 0;'>
                    <p>If you have any questions, please contact us at:</p>
                    <p>Email: " . config('invoice.merchant.email') . "<br>
                    Phone: " . config('invoice.merchant.phone') . "</p>
                    <p>Best regards,<br>" . config('invoice.merchant.name') . " Team</p>
                </div>
            ";
            
            // Send email using Laravel's Mail facade
            \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($email, $subject, $emailContent, $invoiceHtml, $invoice) {
                $message->to($email)
                        ->subject($subject)
                        ->html($emailContent)
                        ->attachData($invoiceHtml, "invoice_{$invoice->invoice_number}.html", [
                            'mime' => 'text/html'
                        ]);
            });
            
            $invoice->markAsSent();
            
            Log::info('📧 Invoice sent via email', [
                'invoice_id' => $invoice->id,
                'email' => $email,
                'invoice_number' => $invoice->invoice_number
            ]);

            return true;
            
        } catch (\Exception $e) {
            Log::error('❌ Failed to send invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Get invoice by booking
     */
    public function getInvoiceByBooking(Booking $booking): ?Invoice
    {
        return Invoice::where('booking_id', $booking->id)->first();
    }

    /**
     * Get invoice by payment
     */
    public function getInvoiceByPayment(Payment $payment): ?Invoice
    {
        return Invoice::where('payment_id', $payment->id)->first();
    }
}
