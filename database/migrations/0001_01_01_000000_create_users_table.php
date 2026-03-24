<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fullname');
            $table->string('email', 50);
            $table->string('phone', 50);
            $table->string('address');
            $table->date('birthday')->nullable();
            $table->string('image', 50)->nullable();
            $table->integer('role')->default(0);
            $table->integer('status')->default(1);
            $table->string('otp', 10)->nullable();
            $table->dateTime('otp_time')->nullable();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};