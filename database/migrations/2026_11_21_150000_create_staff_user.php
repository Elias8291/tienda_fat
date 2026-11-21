<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('users')->where('username', 'jacquempd189')->exists()) {
            return;
        }

        DB::table('users')->insert([
            'name' => 'Jacque',
            'username' => 'jacquempd189',
            'password' => '$2y$12$IAjatXFnIpfBC1RRPVu0rOrARsSmMZ2.JTA8NzNc2h5vHhpeWSIee',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('username', 'jacquempd189')->delete();
    }
};
