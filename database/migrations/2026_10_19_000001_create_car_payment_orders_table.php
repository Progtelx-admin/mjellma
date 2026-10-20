<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('car_payment_orders', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->string('bank_order_id')->nullable()->unique();
            $table->text('bank_password')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('payment_status')->default('pending');
            $table->string('reservation_status')->default('pending');
            $table->longText('booking_data');
            $table->longText('reservation_response')->nullable();
            $table->unsignedSmallInteger('reservation_http_status')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_payment_orders');
    }
};
