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
        Schema::table('prendas', function (Blueprint $table) {
            // Cambiar el campo imagen de string a text para almacenar URLs largas de Firebase
            $table->text('imagen')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas', function (Blueprint $table) {
            // Revertir a string
            $table->string('imagen')->nullable()->change();
        });
    }
};
