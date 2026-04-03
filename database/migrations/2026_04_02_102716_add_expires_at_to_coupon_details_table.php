<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupon_details', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('created_at');
            $table->boolean('is_birthday_coupon')->default(false)->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('coupon_details', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'is_birthday_coupon']);
        });
    }
};