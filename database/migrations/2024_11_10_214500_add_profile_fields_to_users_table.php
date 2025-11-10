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
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
            $table->string('telefono')->nullable()->after('avatar');
            $table->text('bio')->nullable()->after('telefono');
            $table->string('ciudad')->nullable()->after('bio');
            $table->string('departamento')->nullable()->after('ciudad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'telefono', 'bio', 'ciudad', 'departamento']);
        });
    }
};
