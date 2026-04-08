<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoAnexoHistorial;
use Illuminate\Support\Facades\DB;

class VariantesPrendaAuditoriaService
{
    public function obtenerContextoActual(int $prendaId): array
    {
        $varianteActual = DB::table('prenda_pedido_variantes')
            ->where('prenda_pedido_id', $prendaId)
            ->first();

        $nombrePrenda = DB::table('prenda_pedido')
            ->where('id', $prendaId)
            ->value('nombre_prenda') ?? 'PRENDA';

        return [
            'variante_actual' => $varianteActual,
            'nombre_prenda' => (string) $nombrePrenda,
        ];
    }

    public function registrarCambios(
        int $pedidoId,
        int $prendaId,
        string $nombrePrenda,
        array $validated,
        ?object $varianteActual
    ): void {
        $cambiosDetalle = [];
        $mapaLabels = [
            'tipo_manga_id' => 'manga',
            'tipo_broche_boton_id' => 'broche',
            'tiene_bolsillos' => 'bolsillos',
            'manga_obs' => 'obs manga',
            'broche_boton_obs' => 'obs broche',
            'bolsillos_obs' => 'obs bolsillos',
        ];

        foreach ($mapaLabels as $campo => $label) {
            if (!array_key_exists($campo, $validated)) {
                continue;
            }
            $vAntes = (string) ($varianteActual?->$campo ?? '');
            $vDespues = (string) ($validated[$campo] ?? '');
            if ($vAntes !== $vDespues) {
                $cambiosDetalle[] = $label . ': "' . $vAntes . '" -> "' . $vDespues . '"';
            }
        }

        PedidoAnexoHistorial::registrarPrendaEditada(
            $pedidoId,
            $prendaId,
            $nombrePrenda,
            'manga/broche/bolsillos',
            $cambiosDetalle ? implode(' | ', $cambiosDetalle) : null
        );
    }
}
