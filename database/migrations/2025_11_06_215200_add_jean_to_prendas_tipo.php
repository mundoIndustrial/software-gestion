<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modificar el enum para agregar 'jean'
        DB::statement("ALTER TABLE prendas MODIFY COLUMN tipo ENUM('camisa', 'pantalon', 'polo', 'chaqueta', 'vestido', 'jean', 'otro') DEFAULT 'otro'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volver al enum original
        DB::statement("ALTER TABLE prendas MODIFY COLUMN tipo ENUM('camisa', 'pantalon', 'polo', 'chaqueta', 'vestido', 'otro') DEFAULT 'otro'");
    }
};
