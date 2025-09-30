<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('offer_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_section_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('link')->nullable();
            $table->string('image_path');
            $table->boolean('show_caption')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('offer_cards');
    }
};
