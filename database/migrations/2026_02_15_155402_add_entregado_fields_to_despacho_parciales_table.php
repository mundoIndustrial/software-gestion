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
        Schema::table('despacho_parciales', function (Blueprint $table) {
            $table->boolean('entregado')->default(false)->after('observaciones');
            $table->timestamp('fecha_entrega')->nullable()->after('entregado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('despacho_parciales', function (Blueprint $table) {
            $table->dropColumn(['entregado', 'fecha_entrega']);
        });
    }
};
