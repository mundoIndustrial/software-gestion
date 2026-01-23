<?php

namespace App\Infrastructure\Procesos\Persistence\Eloquent;

use App\Domain\Procesos\Repositories\ProcesoPrendaDetalleRepository;
use App\Domain\Procesos\Entities\ProcesoPrendaDetalle;
use App\Models\ProcesoPrendaDetalle as ProcesoPrendaDetalleModel;
use Illuminate\Support\Facades\DB;

/**
 * Repository Implementation para ProcesoPrendaDetalle
 * 
 * Implementa la persistencia usando Eloquent
 * Convierte entre entity de dominio y modelo Eloquent
 * 
 * TALLAS: Se guardan en tabla relacional `pedidos_procesos_prenda_tallas`
 * ========
 * Representa LO QUE SE VA A PROCESAR para cada prenda en este proceso específico
 * - Un registro por cada (genero, talla, cantidad)
 * - NO como JSON en la propia proceso_prenda_detalle
 * 
 * IMPORTANTE: Estas tallas PUEDEN SER DIFERENTES a las tallas del pedido
 * 
 * Ejemplo:
 *   Proceso: Bordado en Camiseta (proceso_prenda_detalle_id = 5)
 *   Cliente pidió: DAMA XS(5), S(5), M(5)
 *   Pero el bordado solo procesa: DAMA XS(5), S(4), M(5)
 *   (La S con 1 unidad quedó pendiente de aprobación)
 */
class ProcesoPrendaDetalleRepositoryImpl implements ProcesoPrendaDetalleRepository
{
    public function obtenerPorId(int $id): ?ProcesoPrendaDetalle
    {
        $modelo = ProcesoPrendaDetalleModel::find($id);

        if (!$modelo) {
            return null;
        }

        return $this->reconstituir($modelo);
    }

    public function obtenerPorPrenda(int $prendaId): array
    {
        return ProcesoPrendaDetalleModel::where('prenda_pedido_id', $prendaId)
            ->get()
            ->map(fn($modelo) => $this->reconstituir($modelo))
            ->toArray();
    }

    public function obtenerPorPedido(int $numeroPedido): array
    {
        return ProcesoPrendaDetalleModel::whereHas('prendaPedido', function ($query) use ($numeroPedido) {
            $query->where('numero', $numeroPedido);
        })
            ->get()
            ->map(fn($modelo) => $this->reconstituir($modelo))
            ->toArray();
    }

    public function obtenerPorPrendaYTipo(int $prendaId, int $tipoProcesoId): ?ProcesoPrendaDetalle
    {
        $modelo = ProcesoPrendaDetalleModel::where('prenda_pedido_id', $prendaId)
            ->where('tipo_proceso_id', $tipoProcesoId)
            ->first();

        if (!$modelo) {
            return null;
        }

        return $this->reconstituir($modelo);
    }

    public function guardar(ProcesoPrendaDetalle $proceso): ProcesoPrendaDetalle
    {
        return DB::transaction(function () use ($proceso) {
            $datos = [
                'prenda_pedido_id' => $proceso->getPrendaPedidoId(),
                'tipo_proceso_id' => $proceso->getTipoProcesoId(),
                'ubicaciones' => json_encode($proceso->getUbicaciones()),
                'observaciones' => $proceso->getObservaciones(),
                'estado' => $proceso->getEstado(),
                'notas_rechazo' => $proceso->getNotasRechazo(),
                'fecha_aprobacion' => $proceso->getFechaAprobacion(),
                'aprobado_por' => $proceso->getAprobadoPor(),
                'datos_adicionales' => $proceso->getDatosAdicionales() 
                    ? json_encode($proceso->getDatosAdicionales())
                    : null,
            ];

            // Si es nueva (sin ID), se crea. Si tiene ID, se actualiza
            if ($proceso->getId() === null) {
                $modelo = ProcesoPrendaDetalleModel::create($datos);
                $proceso->setId($modelo->id);
            } else {
                $modelo = ProcesoPrendaDetalleModel::findOrFail($proceso->getId());
                $modelo->update($datos);
            }

            // Guardar tallas en tabla relacional
            $this->guardarTallas(
                $modelo->id,
                $proceso->getTallasDama() ?? [],
                $proceso->getTallasCalabrero() ?? []
            );

            return $this->reconstituir($modelo);
        });
    }

    public function actualizar(ProcesoPrendaDetalle $proceso): ProcesoPrendaDetalle
    {
        return $this->guardar($proceso);
    }

    public function eliminar(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            DB::table('pedidos_procesos_prenda_tallas')
                ->where('proceso_prenda_detalle_id', $id)
                ->delete();
            
            return ProcesoPrendaDetalleModel::destroy($id) > 0;
        });
    }

    public function obtenerPendientes(): array
    {
        return $this->obtenerPorEstado(ProcesoPrendaDetalle::ESTADO_PENDIENTE);
    }

    public function obtenerAprobados(): array
    {
        return $this->obtenerPorEstado(ProcesoPrendaDetalle::ESTADO_APROBADO);
    }

    public function obtenerCompletados(): array
    {
        return $this->obtenerPorEstado(ProcesoPrendaDetalle::ESTADO_COMPLETADO);
    }

    private function reconstituir(ProcesoPrendaDetalleModel $modelo): ProcesoPrendaDetalle
    {
        $tallasDama = $this->reconstruirTallas($modelo->id, 'DAMA');
        $tallasCalabrero = $this->reconstruirTallas($modelo->id, 'CABALLERO');

        return new ProcesoPrendaDetalle(
            id: $modelo->id,
            prendaPedidoId: $modelo->prenda_pedido_id,
            tipoProcesoId: $modelo->tipo_proceso_id,
            ubicaciones: json_decode($modelo->ubicaciones, true) ?? [],
            observaciones: $modelo->observaciones,
            tallasDama: $tallasDama,
            tallasCalabrero: $tallasCalabrero,
            estado: $modelo->estado,
            notasRechazo: $modelo->notas_rechazo,
            fechaAprobacion: $modelo->fecha_aprobacion,
            aprobadoPor: $modelo->aprobado_por,
            datosAdicionales: json_decode($modelo->datos_adicionales, true)
        );
    }

    /**
     * Guardar tallas en tabla relacional pedidos_procesos_prenda_tallas
     * 
     * Estructura esperada:
     * - tallasDama: ['XS' => 5, 'S' => 5, 'M' => 5]
     * - tallasCalabrero: ['30' => 3, '32' => 4, '34' => 3]
     */
    private function guardarTallas(int $procesoPrendaDetalleId, array $tallasDama, array $tallasCalabrero): void
    {
        // Limpiar registros anteriores
        DB::table('pedidos_procesos_prenda_tallas')
            ->where('proceso_prenda_detalle_id', $procesoPrendaDetalleId)
            ->delete();

        // Guardar tallas DAMA
        foreach ($tallasDama as $talla => $cantidad) {
            DB::table('pedidos_procesos_prenda_tallas')->insert([
                'proceso_prenda_detalle_id' => $procesoPrendaDetalleId,
                'genero' => 'DAMA',
                'talla' => $talla,
                'cantidad' => $cantidad,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Guardar tallas CABALLERO
        foreach ($tallasCalabrero as $talla => $cantidad) {
            DB::table('pedidos_procesos_prenda_tallas')->insert([
                'proceso_prenda_detalle_id' => $procesoPrendaDetalleId,
                'genero' => 'CABALLERO',
                'talla' => $talla,
                'cantidad' => $cantidad,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reconstruir tallas desde tabla relacional
     * 
     * Devuelve array como: ['XS' => 5, 'S' => 5, 'M' => 5]
     */
    private function reconstruirTallas(int $procesoPrendaDetalleId, string $genero): array
    {
        $registros = DB::table('pedidos_procesos_prenda_tallas')
            ->where('proceso_prenda_detalle_id', $procesoPrendaDetalleId)
            ->where('genero', $genero)
            ->get();

        $tallas = [];
        foreach ($registros as $registro) {
            $tallas[$registro->talla] = $registro->cantidad;
        }

        return $tallas;
    }

    private function obtenerPorEstado(string $estado): array
    {
        return ProcesoPrendaDetalleModel::where('estado', $estado)
            ->get()
            ->map(fn($modelo) => $this->reconstituir($modelo))
            ->toArray();
    }
}
