<?php

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ReciboUsuarioVistoRepository
 *
 * Responsabilidades:
 * - Buscar recibos activos (para validar existencia)
 * - Registrar recibos vistos por usuario (idempotente)
 */
class ReciboUsuarioVistoRepository
{
    /**
     * Verificar que el recibo existe y es del tipo indicado.
     *
     * @return object|null  stdClass row de la tabla
     */
    public function findRecibo(int $reciboId, string $tipoRecibo): ?object
    {
        return DB::table('consecutivos_recibos_pedidos')
            ->where('id', $reciboId)
            ->where('tipo_recibo', $tipoRecibo)
            ->first();
    }

    /**
     * Insertar registro de "visto" de forma idempotente.
     */
    public function marcarVisto(int $reciboId, int $userId, string $tipoRecibo): void
    {
        DB::table('recibos_usuario_vistos')->insertOrIgnore([
            'consecutivo_recibo_id' => $reciboId,
            'user_id'               => $userId,
            'tipo_recibo'           => $tipoRecibo,
            'created_at'            => Carbon::now(),
        ]);
    }
}
