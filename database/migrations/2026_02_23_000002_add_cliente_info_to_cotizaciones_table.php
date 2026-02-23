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
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->string('cliente_nit')->nullable()->after('cliente_id')->comment('CC/NIT del cliente');
            $table->string('cliente_direccion')->nullable()->after('cliente_nit')->comment('Dirección del cliente');
            $table->string('cliente_telefono')->nullable()->after('cliente_direccion')->comment('Teléfono del cliente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropColumn(['cliente_nit', 'cliente_direccion', 'cliente_telefono']);
        });
    }
};
