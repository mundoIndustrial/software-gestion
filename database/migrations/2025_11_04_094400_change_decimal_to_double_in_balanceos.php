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
            $table->double('horas_por_turno')->default(8.00)->change();
            $table->double('tiempo_disponible_horas')->nullable()->change();
            $table->double('tiempo_disponible_segundos')->nullable()->change();
            $table->double('sam_total')->default(0)->change();
            $table->double('tiempo_cuello_botella')->nullable()->change();
            $table->double('sam_real')->nullable()->change();
        });

        Schema::table('operaciones_balanceo', function (Blueprint $table) {
            $table->double('sam')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('balanceos', function (Blueprint $table) {
            $table->decimal('horas_por_turno', 5, 2)->default(8.00)->change();
            $table->decimal('tiempo_disponible_horas', 8, 2)->nullable()->change();
            $table->decimal('tiempo_disponible_segundos', 10, 2)->nullable()->change();
            $table->decimal('sam_total', 10, 2)->default(0)->change();
            $table->decimal('tiempo_cuello_botella', 10, 2)->nullable()->change();
            $table->decimal('sam_real', 10, 2)->nullable()->change();
        });

        Schema::table('operaciones_balanceo', function (Blueprint $table) {
            $table->decimal('sam', 10, 2)->change();
        });
    }
};
