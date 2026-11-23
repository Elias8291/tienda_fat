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
            $table->string('code', 20)->nullable()->unique()->after('name');
        });

        foreach (DB::table('products')->orderBy('id')->get(['id']) as $product) {
            DB::table('products')->where('id', $product->id)->update([
                'code' => sprintf('FN-%04d', $product->id),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
