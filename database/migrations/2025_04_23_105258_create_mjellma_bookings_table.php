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
        Schema::create('mjellma_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->string('partner_order_id')->nullable();
            $table->enum('booked_by', ['admin', 'agent', 'user', 'guest']);
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_phone')->nullable();
            $table->string('payment_type')->nullable();
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->string('currency_code', 10)->nullable();
            $table->string('pcb_status')->nullable();
            $table->string('api_status')->nullable();
            $table->text('api_error')->nullable();
            $table->unsignedBigInteger('create_user')->nullable();

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mjellma_bookings');
    }
};
