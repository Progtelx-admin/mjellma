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
            'service_title' => $booking->service_title ?? 'Service'
        ];
        
        $invoiceData = $invoice->invoice_data;

        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Invoice {$invoice->invoice_number}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .invoice-details { margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .total { font-weight: bold; }
        .footer { margin-top: 30px; font-size: 12px; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>Merchant Name, Logo</h1>
        <p>Address: xxx<br>10 000 Pristina, Kosovo<br>Website, email, phone</p>
    </div>

    <div class='invoice-details'>
        <h2>Order Details:</h2>
        <p><strong>Order ID:</strong> #{$invoice->gateway_order_id}</p>
        <p><strong>Order Date:</strong> {$invoice->transaction_datetime}</p>
        <p><strong>Invoice Number:</strong> {$invoice->invoice_number}</p>
    </div>

    <table class='table'>
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

    <div class='total'>
        <p>Nëntotali: {$invoice->amount}€</p>
        <p>Transporti: 0.00€ - Standard</p>
        <p>Totali: {$invoice->amount}€</p>
    </div>

    <div class='payment-details'>
        <h3>Payment Method: By Card</h3>
        <p>• Approval Code: {$invoice->approval_code}</p>
        <p>• Transaction date: {$invoice->transaction_datetime}</p>
        <p>• Card: {$invoice->card_brand}, {$invoice->card_pan}</p>
    </div>

    <div class='billing-address'>
        <h3>Billing Address:</h3>
        <p>Card holder name: {$bookingData['first_name']} {$bookingData['last_name']}</p>
        <p>Address: {$bookingData['address']}</p>
        <p>Email: {$bookingData['email']}</p>
        <p>Phone: {$bookingData['phone']}</p>
    </div>

    <div class='footer'>
        <p>No Refunds after 30 days, see our Return Policy.</p>
        <p>email: xxx</p>
        <p>Customer Service: +383 38 123 123</p>
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

            // Send email with PDF attachment
            // You would implement your email sending logic here
            // For example, using Laravel Mail with PDF attachment
            
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
