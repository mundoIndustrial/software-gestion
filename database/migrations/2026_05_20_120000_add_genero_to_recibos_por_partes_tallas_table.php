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
        Schema::table('recibos_por_partes_tallas', function (Blueprint $table) {
            if (!Schema::hasColumn('recibos_por_partes_tallas', 'genero')) {
                $table->string('genero', 20)->nullable()->after('talla');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recibos_por_partes_tallas', function (Blueprint $table) {
            if (Schema::hasColumn('recibos_por_partes_tallas', 'genero')) {
                $table->dropColumn('genero');
            }
        });
    }
};

