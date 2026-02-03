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
        // Modificar enum en consecutivos_recibos para agregar DTF y SUBLIMADO
        DB::statement("ALTER TABLE consecutivos_recibos MODIFY COLUMN tipo_recibo ENUM('COSTURA','ESTAMPADO','BORDADO','REFLECTIVO','GENERAL','DTF','SUBLIMADO') NOT NULL DEFAULT 'GENERAL'");
        
        // Modificar enum en consecutivos_recibos_pedidos para agregar DTF y SUBLIMADO
        DB::statement("ALTER TABLE consecutivos_recibos_pedidos MODIFY COLUMN tipo_recibo ENUM('COSTURA','ESTAMPADO','BORDADO','REFLECTIVO','DTF','SUBLIMADO') NOT NULL DEFAULT 'COSTURA'");
        
        // Actualizar nota de ESTAMPADO para remover mención a DTF y SUBLIMADO
        DB::table('consecutivos_recibos')
            ->where('tipo_recibo', 'ESTAMPADO')
            ->update([
                'notas' => 'Consecutivo para estampado - Configurar valor inicial',
                'updated_at' => now()
            ]);
        
        // Insertar registros iniciales para DTF y SUBLIMADO si no existen
        $dtfExists = DB::table('consecutivos_recibos')->where('tipo_recibo', 'DTF')->exists();
        if (!$dtfExists) {
            DB::table('consecutivos_recibos')->insert([
                'tipo_recibo' => 'DTF',
                'consecutivo_actual' => 0,
                'consecutivo_inicial' => 0,
                'año' => date('Y'),
                'activo' => 1,
                'notas' => 'Consecutivo para DTF (Direct-to-Film) - Configurar valor inicial',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        $sublimadoExists = DB::table('consecutivos_recibos')->where('tipo_recibo', 'SUBLIMADO')->exists();
        if (!$sublimadoExists) {
            DB::table('consecutivos_recibos')->insert([
                'tipo_recibo' => 'SUBLIMADO',
                'consecutivo_actual' => 0,
                'consecutivo_inicial' => 0,
                'año' => date('Y'),
                'activo' => 1,
                'notas' => 'Consecutivo para Sublimado - Configurar valor inicial',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar registros DTF y SUBLIMADO
        DB::table('consecutivos_recibos')->whereIn('tipo_recibo', ['DTF', 'SUBLIMADO'])->delete();
        
        // Revertir enums a versión anterior
        DB::statement("ALTER TABLE consecutivos_recibos MODIFY COLUMN tipo_recibo ENUM('COSTURA','ESTAMPADO','BORDADO','REFLECTIVO','GENERAL') NOT NULL DEFAULT 'GENERAL'");
        DB::statement("ALTER TABLE consecutivos_recibos_pedidos MODIFY COLUMN tipo_recibo ENUM('COSTURA','ESTAMPADO','BORDADO','REFLECTIVO') NOT NULL DEFAULT 'COSTURA'");
    }
};
