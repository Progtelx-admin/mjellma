<?php

use App\Models\CarPaymentOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('car_payment_orders', function (Blueprint $table) {
            $table->dateTime('pickup_at')->nullable()->index();
            $table->dateTime('return_at')->nullable()->index();
            $table->string('external_reservation_id')->nullable()->index();
        });
        CarPaymentOrder::chunkById(100, function ($orders) {
            foreach ($orders as $order) {
                $order->save();
            }
        });
    }

    public function down(): void
    {
        Schema::table('car_payment_orders', function (Blueprint $table) {
            $table->dropIndex(['pickup_at']);
            $table->dropIndex(['return_at']);
            $table->dropIndex(['external_reservation_id']);
            $table->dropColumn(['pickup_at', 'return_at', 'external_reservation_id']);
        });
    }
};
