<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('receiver_name');          // Tên người nhận
            $table->string('phone', 20);              // SĐT người nhận
            $table->string('province');               // Tỉnh/Thành phố
            $table->string('district');               // Quận/Huyện
            $table->string('ward');                   // Phường/Xã
            $table->string('detail_address');         // Số nhà, tên đường
            $table->boolean('is_default')->default(false); // Địa chỉ mặc định
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('user')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_addresses');
    }
};