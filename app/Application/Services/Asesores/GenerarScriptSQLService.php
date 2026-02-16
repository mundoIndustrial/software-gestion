<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaPedidoColorTela;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PedidosProcessImagenes;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use Illuminate\Support\Facades\DB;

class GenerarScriptSQLService
{
    /**
     * Generar script SQL completo para un pedido
     */
    public function generarScript(int $pedidoId): string
    {
        $pedido = PedidoProduccion::with([
            'prendas' => function ($q) {
                $q->with([
                    'variantes',
                    'fotos',
                    'procesos'
                ]);
            },
            'epps'
        ])->findOrFail($pedidoId);

        $sql = "-- ============================================================\n";
        $sql .= "-- SCRIPT SQL COMPLETO DEL PEDIDO #" . $pedido->numero_pedido . "\n";
        $sql .= "-- Cliente: " . $pedido->cliente . "\n";
        $sql .= "-- Generado: " . now()->format('Y-m-d H:i:s') . "\n";
        $sql .= "-- ============================================================\n\n";

        // 1. Insertar pedido principal
        $sql .= $this->generarInsertPedido($pedido);

        // 2. Insertar prendas y sus relaciones
        if ($pedido->prendas && $pedido->prendas->count() > 0) {
            foreach ($pedido->prendas as $prenda) {
                $sql .= $this->generarInsertPrenda($prenda);
                
                // Variantes de prenda
                if ($prenda->variantes && $prenda->variantes->count() > 0) {
                    foreach ($prenda->variantes as $variante) {
                        $sql .= $this->generarInsertVariante($variante);
                    }
                }
                
                // Fotos de prenda
                if ($prenda->fotos && $prenda->fotos->count() > 0) {
                    foreach ($prenda->fotos as $foto) {
                        $sql .= $this->generarInsertFotoPrenda($foto);
                    }
                }
                
                // Procesos
                if ($prenda->procesos && $prenda->procesos->count() > 0) {
                    foreach ($prenda->procesos as $proceso) {
                        $sql .= $this->generarInsertProceso($proceso);
                    }
                }
            }
        }

        // 3. Insertar EPP
        if ($pedido->epps && $pedido->epps->count() > 0) {
            foreach ($pedido->epps as $epp) {
                $sql .= $this->generarInsertEpp($epp);
            }
        }

        $sql .= "\n-- ============================================================\n";
        $sql .= "-- FIN DEL SCRIPT\n";
        $sql .= "-- ============================================================\n";

        return $sql;
    }

    /**
     * Generar INSERT para pedido
     */
    private function generarInsertPedido(PedidoProduccion $pedido): string
    {
        $values = [
            $pedido->cotizacion_id ?? 'NULL',
            $this->escaparString($pedido->numero_cotizacion ?? ''),
            $pedido->numero_pedido ?? 'NULL',
            $this->escaparString($pedido->cliente ?? ''),
            $this->escaparString($pedido->novedades ?? ''),
            $this->escaparString($pedido->forma_de_pago ?? ''),
            $this->escaparString($pedido->estado ?? 'Pendiente'),
            $pedido->aprobado_por_usuario_cartera ?? 'NULL',
            $this->escaparDateTime($pedido->aprobado_por_cartera_en ?? null),
            $pedido->rechazado_por_usuario_cartera ?? 'NULL',
            $this->escaparDateTime($pedido->rechazado_por_cartera_en ?? null),
            $this->escaparString($pedido->motivo_rechazo_cartera ?? ''),
            $this->escaparDateTime($pedido->aprobado_por_supervisor_en ?? null),
            $this->escaparString($pedido->area ?? ''),
            $this->escaparDateTime($pedido->fecha_ultimo_proceso ?? null),
            $this->escaparDateTime($pedido->fecha_de_creacion_de_orden ?? null),
            $pedido->dia_de_entrega ?? 'NULL',
            $this->escaparDateTime($pedido->fecha_estimada_de_entrega ?? null),
            $this->escaparDateTime($pedido->created_at ?? now()),
            $this->escaparDateTime($pedido->updated_at ?? now()),
            $this->escaparDateTime($pedido->deleted_at ?? null),
            $pedido->asesor_id ?? 'NULL',
            $pedido->cliente_id ?? 'NULL',
            $pedido->cantidad_total ?? 'NULL',
        ];

        $sql = "INSERT INTO `pedidos_produccion` (\n";
        $sql .= "  `cotizacion_id`, `numero_cotizacion`, `numero_pedido`, `cliente`, `novedades`,\n";
        $sql .= "  `forma_de_pago`, `estado`, `aprobado_por_usuario_cartera`, `aprobado_por_cartera_en`,\n";
        $sql .= "  `rechazado_por_usuario_cartera`, `rechazado_por_cartera_en`, `motivo_rechazo_cartera`,\n";
        $sql .= "  `aprobado_por_supervisor_en`, `area`, `fecha_ultimo_proceso`, `fecha_de_creacion_de_orden`,\n";
        $sql .= "  `dia_de_entrega`, `fecha_estimada_de_entrega`, `created_at`, `updated_at`, `deleted_at`,\n";
        $sql .= "  `asesor_id`, `cliente_id`, `cantidad_total`\n";
        $sql .= ") VALUES (" . implode(", ", $values) . ");\n\n";

        return $sql;
    }

    /**
     * Generar INSERT para prenda
     */
    private function generarInsertPrenda(PrendaPedido $prenda): string
    {
        $values = [
            $prenda->pedido_produccion_id ?? 'NULL',
            $this->escaparString($prenda->nombre_prenda ?? ''),
            $this->escaparString($prenda->descripcion ?? ''),
            $this->escaparDateTime($prenda->created_at ?? now()),
            $this->escaparDateTime($prenda->updated_at ?? now()),
            $this->escaparDateTime($prenda->deleted_at ?? null),
            isset($prenda->de_bodega) ? ($prenda->de_bodega ? 1 : 0) : 0,
        ];

        $sql = "INSERT INTO `prendas_pedido` (\n";
        $sql .= "  `pedido_produccion_id`, `nombre_prenda`, `descripcion`, `created_at`, `updated_at`, `deleted_at`, `de_bodega`\n";
        $sql .= ") VALUES (" . implode(", ", $values) . ");\n\n";

        return $sql;
    }

    /**
     * Generar INSERT para variante
     */
    private function generarInsertVariante($variante): string
    {
        $values = [
            $variante->prenda_pedido_id ?? 'NULL',
            $variante->tipo_manga_id ?? 'NULL',
            $variante->tipo_broche_boton_id ?? 'NULL',
            $this->escaparString($variante->manga_obs ?? ''),
            $this->escaparString($variante->broche_boton_obs ?? ''),
            isset($variante->tiene_bolsillos) ? ($variante->tiene_bolsillos ? 1 : 0) : 0,
            $this->escaparString($variante->bolsillos_obs ?? ''),
            $this->escaparDateTime($variante->created_at),
            $this->escaparDateTime($variante->updated_at),
            $this->escaparDateTime($variante->deleted_at ?? null),
        ];

        $sql = "INSERT INTO `prenda_pedido_variantes` (\n";
        $sql .= "  `prenda_pedido_id`, `tipo_manga_id`, `tipo_broche_boton_id`,\n";
        $sql .= "  `manga_obs`, `broche_boton_obs`, `tiene_bolsillos`, `bolsillos_obs`,\n";
        $sql .= "  `created_at`, `updated_at`, `deleted_at`\n";
        $sql .= ") VALUES (" . implode(", ", $values) . ");\n\n";

        return $sql;
    }

    /**
     * Generar INSERT para foto de prenda
     */
    private function generarInsertFotoPrenda($foto): string
    {
        $values = [
            $foto->prenda_pedido_id ?? 'NULL',
            $this->escaparString($foto->ruta_original ?? ''),
            $this->escaparString($foto->ruta_webp ?? ''),
            $foto->orden ?? 0,
            $this->escaparDateTime($foto->created_at ?? now()),
            $this->escaparDateTime($foto->updated_at ?? now()),
            $this->escaparDateTime($foto->deleted_at ?? null),
        ];

        $sql = "INSERT INTO `prenda_fotos_pedido` (\n";
        $sql .= "  `prenda_pedido_id`, `ruta_original`, `ruta_webp`, `orden`,\n";
        $sql .= "  `created_at`, `updated_at`, `deleted_at`\n";
        $sql .= ") VALUES (" . implode(", ", $values) . ");\n\n";

        return $sql;
    }

    /**
     * Generar INSERT para tela (color y tela)
     */
    private function generarInsertTela($tela): string
    {
        $values = [
            $tela->prenda_pedido_id ?? 'NULL',
            $tela->color_id ?? 'NULL',
            $tela->tela_id ?? 'NULL',
            $this->escaparString($tela->referencia ?? ''),
            $this->escaparDateTime($tela->created_at ?? now()),
            $this->escaparDateTime($tela->updated_at ?? now()),
        ];

        $sql = "INSERT INTO `prenda_pedido_colores_telas` (\n";
        $sql .= "  `prenda_pedido_id`, `color_id`, `tela_id`, `referencia`, `created_at`, `updated_at`\n";
        $sql .= ") VALUES (" . implode(", ", $values) . ");\n\n";

        return $sql;
    }

    /**
     * Generar INSERT para foto de tela
     */
    private function generarInsertFotoTela($fotoTela): string
    {
        $values = [
            $fotoTela->prenda_pedido_colores_telas_id,
            $this->escaparString($fotoTela->ruta_original),
            $this->escaparString($fotoTela->ruta_webp),
            $fotoTela->orden ?? 0,
            $this->escaparDateTime($fotoTela->created_at),
            $this->escaparDateTime($fotoTela->updated_at),
            $this->escaparDateTime($fotoTela->deleted_at),
        ];

        $sql = "INSERT INTO `prenda_fotos_tela_pedido` (\n";
        $sql .= "  `prenda_pedido_colores_telas_id`, `ruta_original`, `ruta_webp`, `orden`,\n";
        $sql .= "  `created_at`, `updated_at`, `deleted_at`\n";
        $sql .= ") VALUES (" . implode(", ", $values) . ");\n\n";

        return $sql;
    }

    /**
     * Generar INSERT para proceso
     */
    private function generarInsertProceso($proceso): string
    {
        $ubicaciones = isset($proceso->ubicaciones) ? (is_array($proceso->ubicaciones) ? json_encode($proceso->ubicaciones) : $proceso->ubicaciones) : null;
        $tallasDama = isset($proceso->tallas_dama) ? (is_array($proceso->tallas_dama) ? json_encode($proceso->tallas_dama) : $proceso->tallas_dama) : null;
        $tallasCaballero = isset($proceso->tallas_caballero) ? (is_array($proceso->tallas_caballero) ? json_encode($proceso->tallas_caballero) : $proceso->tallas_caballero) : null;

        $values = [
            $proceso->prenda_pedido_id ?? 'NULL',
            $proceso->tipo_proceso_id ?? 'NULL',
            $ubicaciones !== null ? $this->escaparString($ubicaciones) : 'NULL',
            $this->escaparString($proceso->observaciones ?? ''),
            $tallasDama !== null ? $this->escaparString($tallasDama) : 'NULL',
            $tallasCaballero !== null ? $this->escaparString($tallasCaballero) : 'NULL',
            $this->escaparString($proceso->estado ?? 'PENDIENTE'),
            $this->escaparString($proceso->notas_rechazo ?? ''),
            $this->escaparDateTime($proceso->fecha_aprobacion ?? null),
            $proceso->aprobado_por ?? 'NULL',
            $this->escaparDateTime($proceso->created_at ?? now()),
            $this->escaparDateTime($proceso->updated_at ?? now()),
            $this->escaparDateTime($proceso->deleted_at ?? null),
        ];

        $sql = "INSERT INTO `pedidos_procesos_prenda_detalles` (\n";
        $sql .= "  `prenda_pedido_id`, `tipo_proceso_id`, `ubicaciones`, `observaciones`,\n";
        $sql .= "  `tallas_dama`, `tallas_caballero`, `estado`, `notas_rechazo`,\n";
        $sql .= "  `fecha_aprobacion`, `aprobado_por`, `created_at`, `updated_at`, `deleted_at`\n";
        $sql .= ") VALUES (" . implode(", ", $values) . ");\n\n";

        return $sql;
    }

    /**
     * Generar INSERT para EPP
     */
    private function generarInsertEpp($epp): string
    {
        $values = [
            $epp->pedido_produccion_id ?? 'NULL',
            $epp->epp_id ?? 'NULL',
            $epp->cantidad ?? 0,
            $this->escaparString($epp->observaciones ?? ''),
            $this->escaparDateTime($epp->created_at ?? now()),
            $this->escaparDateTime($epp->updated_at ?? now()),
            $this->escaparDateTime($epp->deleted_at ?? null),
        ];

        $sql = "INSERT INTO `pedido_epp` (\n";
        $sql .= "  `pedido_produccion_id`, `epp_id`, `cantidad`, `observaciones`,\n";
        $sql .= "  `created_at`, `updated_at`, `deleted_at`\n";
        $sql .= ") VALUES (" . implode(", ", $values) . ");\n\n";

        return $sql;
    }

    /**
     * Escapar string para SQL
     */
    private function escaparString(?string $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        return "'" . str_replace("'", "''", $value) . "'";
    }

    /**
     * Escapar datetime para SQL
     */
    private function escaparDateTime($value): string
    {
        if ($value === null || $value === '') {
            return 'NULL';
        }
        return "'" . (string)$value . "'";
    }
}
