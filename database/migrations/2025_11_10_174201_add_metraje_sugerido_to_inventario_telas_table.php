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
        Schema::table('inventario_telas', function (Blueprint $table) {
            $table->decimal('metraje_sugerido', 10, 2)->nullable()->after('stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario_telas', function (Blueprint $table) {
            $table->dropColumn('metraje_sugerido');
        });
    }
};
