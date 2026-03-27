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
        Schema::create('products_tag', function (Blueprint $table) {
            $table->id();
            $table->string('product_sku_code', 255);
            $table->unsignedBigInteger('tag_id');

            $table->foreign('product_sku_code')
                  ->references('sku_code')
                  ->on('product_skus')
                  ->onDelete('cascade');

            $table->foreign('tag_id')
                  ->references('id')
                  ->on('tag')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products_tag');
    }
};