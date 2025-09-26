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
        Schema::table('mjellma_bookings', function (Blueprint $table) {
            $table->json('pcb_bank_response')->nullable()->after('api_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mjellma_bookings', function (Blueprint $table) {
            $table->dropColumn('pcb_bank_response');
        });
    }
};
