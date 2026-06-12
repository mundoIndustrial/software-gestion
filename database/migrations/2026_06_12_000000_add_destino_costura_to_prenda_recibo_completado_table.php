<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenda_recibo_completado', function (Blueprint $table) {
            if (!Schema::hasColumn('prenda_recibo_completado', 'destino_costura')) {
                $table->string('destino_costura', 20)->nullable()->after('area');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prenda_recibo_completado', function (Blueprint $table) {
            if (Schema::hasColumn('prenda_recibo_completado', 'destino_costura')) {
                $table->dropColumn('destino_costura');
            }
        });
    }
};
