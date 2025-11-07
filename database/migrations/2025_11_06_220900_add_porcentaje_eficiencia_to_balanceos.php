<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('balanceos', function (Blueprint $table) {
            // Agregar campo porcentaje_eficiencia con valor por defecto de 90
            $table->decimal('porcentaje_eficiencia', 5, 2)->default(90.00)->after('meta_real');
        });
        
        // Actualizar registros existentes para que tengan 90% por defecto
        DB::table('balanceos')->update(['porcentaje_eficiencia' => 90.00]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('balanceos', function (Blueprint $table) {
            $table->dropColumn('porcentaje_eficiencia');
        });
    }
};
