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
        Schema::create('product_combination_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('options_id');
            $table->string('sku_code', 255);
            $table->dateTime('created_at');

            $table->foreign('options_id')
                  ->references('id')
                  ->on('variant_options')
                  ->onDelete('cascade');

            $table->foreign('sku_code')
                  ->references('sku_code')
                  ->on('product_skus')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_combination_options');
    }
};