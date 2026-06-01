<?php

namespace App\Console\Commands;

use App\Application\Pedidos\UseCases\ObtenerDetalleCompletoUseCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnoseReciboOpen extends Command
{
    protected $signature = 'diagnose:recibo-open
        {numero : Numero de recibo (consecutivo_actual)}
        {--tipo=REFLECTIVO : Tipo de recibo esperado}';

    protected $description = 'Diagnostica por que un recibo no abre en PedidosRecibosModule';

    public function handle(ObtenerDetalleCompletoUseCase $useCase): int
    {
        $numero = (string) $this->argument('numero');
        $tipo = strtoupper(trim((string) $this->option('tipo')));

        $this->info("Diagnostico recibo {$numero} tipo {$tipo}");

        $select = [
            'id',
            'pedido_produccion_id',
            'prenda_id',
            'tipo_recibo',
            'consecutivo_actual',
            'activo',
            'estado',
            'area',
            'created_at',
            'updated_at',
        ];
        if (Schema::hasColumn('consecutivos_recibos_pedidos', 'pedido_parcial_id')) {
            $select[] = 'pedido_parcial_id';
        }
        if (Schema::hasColumn('consecutivos_recibos_pedidos', 'origen_recibo')) {
            $select[] = 'origen_recibo';
        }

        $crpRows = DB::table('consecutivos_recibos_pedidos')
            ->whereRaw('TRIM(CAST(consecutivo_actual AS CHAR)) = ?', [$numero])
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [$tipo])
            ->orderByDesc('id')
            ->get($select);

        if ($crpRows->isEmpty()) {
            $this->error('No existe fila en consecutivos_recibos_pedidos para ese numero/tipo.');
            return self::FAILURE;
        }

        $this->line('1) Filas consecutivos_recibos_pedidos');
        $this->table(
            ['id', 'pedido_id', 'prenda_id', 'parcial_id', 'tipo', 'numero', 'origen', 'activo', 'estado', 'area'],
            $crpRows->map(fn ($r) => [
                $r->id,
                $r->pedido_produccion_id,
                $r->prenda_id,
                $r->pedido_parcial_id ?? null,
                $r->tipo_recibo,
                $r->consecutivo_actual,
                $r->origen_recibo ?? 'BASE',
                $r->activo,
                $r->estado,
                $r->area,
            ])->all()
        );

        $row = $crpRows->first();
        $pedidoId = (int) ($row->pedido_produccion_id ?? 0);
        $prendaId = (int) ($row->prenda_id ?? 0);
        if ($pedidoId <= 0 || $prendaId <= 0) {
            $this->error('La fila CRP no trae pedido_id/prenda_id validos.');
            return self::FAILURE;
        }

        $response = $useCase->ejecutar($pedidoId, false)->toArray();
        $prendas = is_array($response['prendas'] ?? null) ? $response['prendas'] : [];
        $prenda = collect($prendas)->firstWhere('id', $prendaId);
        if (!$prenda) {
            $this->error("El endpoint recibos-datos no devolvio la prenda {$prendaId} del pedido {$pedidoId}.");
            return self::FAILURE;
        }

        $procesos = is_array($prenda['procesos'] ?? null) ? $prenda['procesos'] : [];
        $recibosMap = is_array($prenda['recibos'] ?? null) ? $prenda['recibos'] : [];
        $parciales = collect(is_array($recibosMap['parciales'] ?? null) ? $recibosMap['parciales'] : []);

        $procesosTipos = collect($procesos)->map(function ($p) {
            return strtoupper(trim((string) ($p['tipo_proceso'] ?? $p['nombre_proceso'] ?? $p['nombre'] ?? '')));
        })->filter()->values();

        $recibosConDatos = collect($recibosMap)
            ->filter(fn ($v, $k) => $k !== 'parciales' && is_array($v) && !empty($v))
            ->keys()
            ->values();

        $this->line('2) Tipos en payload /recibos-datos para la prenda');
        $this->table(
            ['dato', 'valor'],
            [
                ['pedido_id', (string) $pedidoId],
                ['prenda_id', (string) $prendaId],
                ['procesos_tipos', $procesosTipos->implode(', ') ?: '(vacio)'],
                ['recibos_keys_con_datos', $recibosConDatos->implode(', ') ?: '(vacio)'],
                ['existe_proceso_tipo', $procesosTipos->contains($tipo) ? 'SI' : 'NO'],
                ['existe_recibo_key', $recibosConDatos->contains($tipo) ? 'SI' : 'NO'],
                ['parciales_count', (string) $parciales->count()],
            ]
        );

        $parcialMatch = $parciales->first(function ($p) use ($numero, $tipo) {
            $cons = trim((string) ($p['consecutivo_actual'] ?? ''));
            $consAlt = trim((string) ($p['numero_recibo'] ?? ''));
            $tipoParcial = strtoupper(trim((string) ($p['tipo_recibo'] ?? '')));
            $coincideNumero = ($cons === $numero) || ($consAlt === $numero);
            return $coincideNumero && $tipoParcial === $tipo;
        });

        if ($parcialMatch) {
            $this->line('3) Parcial encontrado en payload');
            $this->table(
                ['id', 'tipo_recibo', 'consecutivo_actual', 'estado', 'activo', 'consecutivo_recibo_id'],
                [[
                    $parcialMatch['id'] ?? null,
                    $parcialMatch['tipo_recibo'] ?? null,
                    $parcialMatch['consecutivo_actual'] ?? null,
                    $parcialMatch['estado'] ?? null,
                    $parcialMatch['activo'] ?? null,
                    $parcialMatch['consecutivo_recibo_id'] ?? null,
                ]]
            );
        }

        if (!$procesosTipos->contains($tipo) && $recibosConDatos->contains($tipo)) {
            $this->warn('Diagnostico: El recibo existe en prenda.recibos pero NO existe proceso en prenda.procesos para ese tipo.');
            $this->warn('Con la logica actual de ReceiptBuilder, eso termina en "Recibo no encontrado".');
        }

        if (!$recibosConDatos->contains($tipo)) {
            $this->warn('Diagnostico: ni siquiera hay entrada de ese tipo en prenda.recibos (desde backend).');
        }

        return self::SUCCESS;
    }
}
