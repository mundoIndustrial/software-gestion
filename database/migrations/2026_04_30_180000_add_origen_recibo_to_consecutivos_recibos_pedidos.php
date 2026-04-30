<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            $table->enum('origen_recibo', ['BASE', 'ANEXO'])
                ->default('BASE')
                ->after('tipo_recibo');
        });

        // Backfill: registros históricos creados desde anexos se detectan por la nota parcial_id:XX
        DB::table('consecutivos_recibos_pedidos')
            ->where('notas', 'LIKE', '%parcial_id:%')
            ->update([
                'origen_recibo' => 'ANEXO',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            $table->dropColumn('origen_recibo');
        });
    }
};

