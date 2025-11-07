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
        Schema::table('operaciones_balanceo', function (Blueprint $table) {
            $table->dropColumn('operario_a');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operaciones_balanceo', function (Blueprint $table) {
            $table->string('operario_a')->nullable()->after('seccion');
        });
    }
};
