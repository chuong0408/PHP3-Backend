<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->string('image', 255)->notNull()->default('')->change();
        });
    }

    public function down(): void
    {
        Schema::table('user', function (Blueprint $table) {
            $table->string('image', 50)->notNull()->default('')->change();
        });
    }
};