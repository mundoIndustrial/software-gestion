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
        Schema::table('balanceos', function (Blueprint $table) {
            $table->boolean('estado_completo')->default(false)->after('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('balanceos', function (Blueprint $table) {
            $table->dropColumn('estado_completo');
        });
    }
};
