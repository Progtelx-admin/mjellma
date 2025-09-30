<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('payment_id');
            $table->string('invoice_number')->unique();
            $table->enum('status', ['draft', 'sent', 'paid', 'cancelled'])->default('draft');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('payment_gateway');
            $table->string('gateway_order_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('approval_code')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('card_pan')->nullable();
            $table->datetime('transaction_datetime')->nullable();
            $table->json('invoice_data')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bravo_bookings')->onDelete('cascade');
            $table->foreign('payment_id')->references('id')->on('bravo_booking_payments')->onDelete('cascade');
            
            $table->index(['booking_id', 'payment_id']);
            $table->index('invoice_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
