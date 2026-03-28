<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thêm các cột cần thiết cho Google OAuth vào bảng users.
     */
    public function up(): void
    {
        Schema::table('user', function (Blueprint $table) {
            // Kiểm tra và thêm cột nếu chưa tồn tại
            if (!Schema::hasColumn('user', 'google_id')) {
                $table->string('google_id')->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('user', 'avatar')) {
                $table->string('avatar')->nullable()->after('google_id');
            }
            if (!Schema::hasColumn('user', 'provider')) {
                // 'local' = đăng ký thường, 'google' = qua Google
                $table->string('provider')->default('local')->after('avatar');
            }
            if (!Schema::hasColumn('user', 'role')) {
                $table->string('role')->default('user')->after('provider');
            }
            // Cho phép password null (tài khoản Google không cần password)
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->dropColumnIfExists('google_id');
            $table->dropColumnIfExists('avatar');
            $table->dropColumnIfExists('provider');
            $table->dropColumnIfExists('role');
            $table->string('password')->nullable(false)->change();
        });
    }
};