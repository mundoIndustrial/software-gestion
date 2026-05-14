<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $exists = DB::table('roles')->where('name', 'gestion-bodega')->exists();

        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'gestion-bodega',
                'description' => 'Gestion de recibos de bodega (acceso a /recibos-bodega)',
                'requires_credentials' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->where('name', 'gestion-bodega')->delete();
    }
};

