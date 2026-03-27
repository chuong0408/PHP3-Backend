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
        Schema::create('coupon_details', function (Blueprint $table) {
            $table->string('coupon_code', 255)->primary();
            $table->string('discount', 10);
            $table->string('description', 255);
            $table->decimal('minordervalue', 10, 2);
            $table->dateTime('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_details');
    }
};