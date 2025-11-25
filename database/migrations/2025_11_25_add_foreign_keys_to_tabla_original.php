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
        Schema::table('tabla_original', function (Blueprint $table) {
            // Agregar foreign keys para tabla_original
            if (!Schema::hasColumn('tabla_original', 'asesora_id')) {
                $table->foreignId('asesora_id')->nullable()->constrained('users')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('tabla_original', 'cliente_id_nuevo')) {
                $table->foreignId('cliente_id_nuevo')->nullable()->constrained('clientes')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tabla_original', function (Blueprint $table) {
            // First drop the foreign key constraints if they exist
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableForeignKeys('tabla_original');
            
            foreach ($indexesFound as $index) {
                if ($index->getLocalColumns() == ['asesora_id']) {
                    $table->dropForeign([$index->getLocalColumnName()]);
                }
                if ($index->getLocalColumns() == ['cliente_id_nuevo']) {
                    $table->dropForeign([$index->getLocalColumnName()]);
                }
            }
            
            // Then drop columns if they exist
            if (Schema::hasColumn('tabla_original', 'asesora_id')) {
                $table->dropColumn('asesora_id');
            }
            
            if (Schema::hasColumn('tabla_original', 'cliente_id_nuevo')) {
                $table->dropColumn('cliente_id_nuevo');
            }
        });
    }
};
