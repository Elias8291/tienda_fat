<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->unsignedTinyInteger('focus_x')->default(50)->after('position');
            $table->unsignedTinyInteger('focus_y')->default(50)->after('focus_x');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn(['focus_x', 'focus_y']);
        });
    }
};
