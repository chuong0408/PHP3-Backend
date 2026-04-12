<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_addresses', function (Blueprint $table) {
            // Lưu ID quận/huyện của GHN để tính phí ship tự động
            $table->unsignedInteger('ghn_district_id')->nullable()->after('district');
            // Lưu mã phường/xã của GHN
            $table->string('ghn_ward_code', 20)->nullable()->after('ward');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_addresses', function (Blueprint $table) {
            $table->dropColumn(['ghn_district_id', 'ghn_ward_code']);
        });
    }
};