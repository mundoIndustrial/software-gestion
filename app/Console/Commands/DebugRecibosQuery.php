<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;
use App\Infrastructure\Insumos\ReadModels\RecibosCosturaReadRepository;

class DebugRecibosQuery extends Command
{
    protected $signature = 'debug:recibos-query {--pedido=73}';
    protected $description = 'Debuggea por qué los recibos no aparecen en /insumos/materiales';

    public function handle()
    {
        $numeroPedido = $this->option('pedido');
        
        $this->info("\n====== ANÁLISIS DEL PEDIDO {$numeroPedido} Y SUS RECIBOS ======\n");

        // 1. Buscar el pedido
        $this->line("1. BUSCANDO PEDIDO {$numeroPedido}:");
        $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

        if (!$pedido) {
            $this->error("❌ No se encontró pedido con número {$numeroPedido}");
            return 1;
        }

        $this->info("✓ Pedido encontrado:");
        $this->line("  - ID: {$pedido->id}");
        $this->line("  - Número: {$pedido->numero_pedido}");
        $this->line("  - Estado: {$pedido->estado}");
        $this->line("  - Área: {$pedido->area}");
        $this->line("  - Cliente: {$pedido->cliente}\n");

        // 2. Recibos directos
        $this->line("2. RECIBOS DE COSTURA PARA PEDIDO {$numeroPedido}:");
        $recibos = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedido->id)
            ->where('tipo_recibo', 'COSTURA')
            ->get();

        $this->line("Total recibos: {$recibos->count()}");
        foreach ($recibos as $r) {
            $this->line(
                "  - ID: {$r->id}, Consecutivo: {$r->consecutivo_actual}, Estado: {$r->estado}, Area: {$r->area}, Activo: {$r->activo}"
            );
        }
        $this->line("");

        // 3. Verificar condiciones de estado
        $this->line("3. VERIFICANDO CONDICIONES DE FILTRO (ESTADO DE RECIBOS):");
        
        // Contar recibos en cada estado
        $recibosPendienteInsumos = $recibos->where('estado', 'PENDIENTE_INSUMOS')->count();
        $this->line("\n  Recibos en PENDIENTE_INSUMOS: {$recibosPendienteInsumos}");
        
        if ($recibosPendienteInsumos > 0) {
            $this->info("  ✓ EL PEDIDO TIENE RECIBOS EN PENDIENTE_INSUMOS");
        } else {
            $this->error("  ❌ EL PEDIDO NO TIENE RECIBOS EN PENDIENTE_INSUMOS");
        }
        
        $tieneAreaCorte = str_contains($pedido->area ?? '', 'Corte');
        $tieneAreaCreacion = str_contains($pedido->area ?? '', 'Creacion') && str_contains($pedido->area ?? '', 'orden');
        
        if ($tieneAreaCorte || $tieneAreaCreacion) {
            $this->info("  ✓ El área del pedido cumple criterios adicionales");
        }

        $this->line("\nVerificación de exclusión:");
        $noExcluido = $pedido->estado !== 'PENDIENTE_SUPERVISOR';
        $this->line("  Estado del pedido: {$pedido->estado}");
        $this->line("  ¿No está excluido por PENDIENTE_SUPERVISOR? " . ($noExcluido ? "✓ SÍ" : "❌ NO"));

        // 3.1 Verificar condiciones exactas del WHERE de buildBaseQuery(COSTURA)
        $this->line("3.1. VERIFICANDO WHERE REAL DE /insumos/materiales (COSTURA):");
        $recibosQueCumplenEstadoArea = $recibos->filter(function ($r) {
            $estado = strtoupper(trim((string) ($r->estado ?? '')));
            $area = strtoupper(trim((string) ($r->area ?? '')));

            $estadosPermitidos = [
                'PENDIENTE_INSUMOS',
                'PENDIENTE_TELA',
                'PENDIENTE_PLOTTER',
                'INSUMOS_PEDIDOS',
                'DEVUELTO_ASESOR',
                'ANULADO',
                'ANULADA',
            ];

            $areasPermitidas = ['CORTE', 'COSTURA', 'ANULADO'];

            $cumpleGrupoEstadoArea = in_array($estado, $estadosPermitidos, true)
                || in_array($area, $areasPermitidas, true);

            $cumpleAreaVisible = in_array($area, ['INSUMOS', 'ANULADO'], true);

            return $cumpleGrupoEstadoArea && $cumpleAreaVisible;
        });

        $this->line("  - Recibos COSTURA que cumplen TODO el WHERE real: {$recibosQueCumplenEstadoArea->count()}");
        foreach ($recibos as $r) {
            $estado = strtoupper(trim((string) ($r->estado ?? '')));
            $area = strtoupper(trim((string) ($r->area ?? '')));
            $estadosPermitidos = [
                'PENDIENTE_INSUMOS',
                'PENDIENTE_TELA',
                'PENDIENTE_PLOTTER',
                'INSUMOS_PEDIDOS',
                'DEVUELTO_ASESOR',
                'ANULADO',
                'ANULADA',
            ];
            $areasPermitidas = ['CORTE', 'COSTURA', 'ANULADO'];
            $cumpleGrupo = in_array($estado, $estadosPermitidos, true) || in_array($area, $areasPermitidas, true);
            $cumpleAreaVisible = in_array($area, ['INSUMOS', 'ANULADO'], true);
            $this->line(
                "    · Recibo {$r->consecutivo_actual}: grupo(estado/area)=" . ($cumpleGrupo ? 'SI' : 'NO')
                . ", area visible (INSUMOS/ANULADO)=" . ($cumpleAreaVisible ? 'SI' : 'NO')
            );
        }

        // Verdict
        $this->line("\n" . str_repeat("=", 50));
        if (
            (($recibosPendienteInsumos > 0 || $tieneAreaCorte || $tieneAreaCreacion) && $noExcluido)
            || $recibosQueCumplenEstadoArea->count() > 0
        ) {
            $this->info("✓ EL PEDIDO DEBERÍA APARECER EN LA QUERY");
        } else {
            $this->error("❌ EL PEDIDO NO DEBERÍA APARECER EN LA QUERY");
        }

        // 4. Query actual
        $this->line("\n4. EJECUTANDO QUERY ACTUAL:");
        $repo = new RecibosCosturaReadRepository();
        $query = $repo->buildBaseQuery()->where('pedidos_produccion.numero_pedido', $numeroPedido);
        
        $count = $query->count();
        $this->line("Resultados: {$count}");
        
        if ($count > 0) {
            $this->info("✓ El pedido {$numeroPedido} SÍ aparece en la query");
            foreach ($query->get() as $row) {
                $this->line("  - Recibo: {$row->consecutivo_actual}, Estado: {$row->recibo_estado}");
            }
        } else {
            $this->error("❌ El pedido {$numeroPedido} NO aparece en la query");
        }

        $this->line("\n====== FIN DEL ANÁLISIS ======\n");

        return 0;
    }
}
