<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero hacer el campo nullable
        Schema::table('balanceos', function (Blueprint $table) {
            $table->boolean('estado_completo')->nullable()->change();
        });
        
        // Luego actualizar los registros existentes con false a null
        \DB::table('balanceos')->where('estado_completo', false)->update(['estado_completo' => null]);
        
        // Finalmente cambiar el default a null
        Schema::table('balanceos', function (Blueprint $table) {
            $table->boolean('estado_completo')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('balanceos', function (Blueprint $table) {
            $table->boolean('estado_completo')->default(false)->change();
        });
    }
};
