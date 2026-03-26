<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->string('fullname', 255);
            $table->string('email', 50)->unique();
            $table->string('phone', 50);
            $table->string('address', 255)->default('');
            $table->datetime('brithday')->default(now());
            $table->string('image', 50)->default('');
            $table->integer('role')->default(0);   // 0 = user, 1 = admin
            $table->integer('status')->default(1); // 1 = active, 0 = banned
            $table->string('otp', 10)->default('');
            $table->datetime('otp_time')->default(now());
            $table->string('password', 255);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};