<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('position')->default(0)->after('active');
        });

        foreach (DB::table('products')->orderByDesc('id')->pluck('id') as $index => $id) {
            DB::table('products')->where('id', $id)->update(['position' => $index + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
