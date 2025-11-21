<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * DEPRECATED - Esta migración fue reemplazada por 2025_11_21_drop_unused_tables.php
     */
    public function up(): void
    {
        // No hacer nada - la tabla será eliminada por la migración de limpieza
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacer nada
    }
};
