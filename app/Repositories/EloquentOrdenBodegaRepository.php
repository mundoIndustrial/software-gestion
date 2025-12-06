<?php

namespace App\Repositories;

use App\Domain\Bodega\Entities\OrdenBodega;
use App\Domain\Bodega\Entities\PrendaBodega;
use App\Domain\Bodega\ValueObjects\NumeroPedidoBodega;
use App\Domain\Bodega\ValueObjects\EstadoBodega;
use App\Domain\Bodega\ValueObjects\AreaBodega;
use App\Domain\Bodega\ValueObjects\FormaPagoBodega;
use App\Domain\Bodega\Repositories\OrdenBodegaRepositoryInterface;
use App\Models\TablaOriginalBodega;
use App\Models\RegistrosPorOrdenBodega;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Infrastructure Layer: Eloquent Repository for OrdenBodega Aggregate
 * 
 * Traduce entre el Domain Model y la Persistencia (Eloquent ORM)
 */
class EloquentOrdenBodegaRepository implements OrdenBodegaRepositoryInterface
{
    public function obtener(NumeroPedidoBodega $numeroPedido): ?OrdenBodega
    {
        $registro = TablaOriginalBodega::where('pedido', $numeroPedido->valor())->first();
        
        if (!$registro) {
            return null;
        }

        return $this->mapearAAggregate($registro);
    }

    public function obtenerTodas(): Collection
    {
        return TablaOriginalBodega::all()->map(
            fn($registro) => $this->mapearAAggregate($registro)
        );
    }

    public function obtenerPorCliente(string $cliente): Collection
    {
        return TablaOriginalBodega::where('cliente', 'like', "%{$cliente}%")
            ->get()
            ->map(fn($registro) => $this->mapearAAggregate($registro));
    }

    public function obtenerPorEstado(string $estado): Collection
    {
        return TablaOriginalBodega::where('estado', $estado)
            ->get()
            ->map(fn($registro) => $this->mapearAAggregate($registro));
    }

    public function guardar(OrdenBodega $orden): void
    {
        $datos = [
            'pedido' => $orden->numeroPedido()->valor(),
            'estado' => $orden->estado()->valor(),
            'cliente' => $orden->cliente(),
            'area' => $orden->area()->valor(),
            'fecha_de_creacion_de_orden' => $orden->fechaCreacion()->toDateString(),
            'encargado_orden' => $orden->encargado(),
            'forma_de_pago' => $orden->formaPago()?->valor(),
            'descripcion' => $orden->descripcion(),
            'cantidad' => $orden->cantidadTotal(),
        ];

        TablaOriginalBodega::create($datos);

        // Guardar prendas en registros_por_orden_bodega
        $this->guardarPrendas($orden->numeroPedido()->valor(), $orden->prendas(), $orden->cliente());
    }

    public function actualizar(OrdenBodega $orden): void
    {
        $datos = [
            'estado' => $orden->estado()->valor(),
            'cliente' => $orden->cliente(),
            'area' => $orden->area()->valor(),
            'encargado_orden' => $orden->encargado(),
            'forma_de_pago' => $orden->formaPago()?->valor(),
            'descripcion' => $orden->descripcion(),
            'cantidad' => $orden->cantidadTotal(),
        ];

        TablaOriginalBodega::where('pedido', $orden->numeroPedido()->valor())
            ->update($datos);

        // Actualizar prendas
        RegistrosPorOrdenBodega::where('pedido', $orden->numeroPedido()->valor())->delete();
        $this->guardarPrendas($orden->numeroPedido()->valor(), $orden->prendas(), $orden->cliente());
    }

    public function eliminar(NumeroPedidoBodega $numeroPedido): void
    {
        TablaOriginalBodega::where('pedido', $numeroPedido->valor())->delete();
        RegistrosPorOrdenBodega::where('pedido', $numeroPedido->valor())->delete();
    }

    public function obtenerProximoNumero(): int
    {
        $ultimo = TablaOriginalBodega::max('pedido');
        return $ultimo ? $ultimo + 1 : 1;
    }

    public function existeNumero(int $numero): bool
    {
        return TablaOriginalBodega::where('pedido', $numero)->exists();
    }

    private function mapearAAggregate(TablaOriginalBodega $registro): OrdenBodega
    {
        $numeroPedido = NumeroPedidoBodega::desde($registro->pedido);
        $fechaCreacion = Carbon::parse($registro->fecha_de_creacion_de_orden);

        $orden = OrdenBodega::crear(
            $numeroPedido,
            $registro->cliente,
            $fechaCreacion
        );

        // Restaurar estado
        $orden->cambiarEstado(EstadoBodega::desde($registro->estado));

        // Restaurar área
        $orden->cambiarArea(AreaBodega::desde($registro->area));

        // Restaurar encargado y forma de pago
        if ($registro->encargado_orden) {
            $orden->establecerEncargado($registro->encargado_orden);
        }

        if ($registro->forma_de_pago) {
            $orden->establecerFormaPago(FormaPagoBodega::desde($registro->forma_de_pago));
        }

        // Restaurar descripción
        $orden->actualizarDescripcion($registro->descripcion ?? '');

        // Restaurar prendas
        $prendas = RegistrosPorOrdenBodega::where('pedido', $registro->pedido)->get();
        foreach ($prendas as $prendasDelMismoTipo) {
            // Agrupar por prenda
            $prendaGrouped = $prendas->where('prenda', $prendasDelMismoTipo->prenda)->groupBy('prenda');
            
            foreach ($prendaGrouped as $prendaNombre => $tallasPrenda) {
                $tallas = $tallasPrenda->map(fn($p) => [
                    'talla' => $p->talla,
                    'cantidad' => $p->cantidad
                ])->toArray();

                $prenda = PrendaBodega::crear(
                    $prendaNombre,
                    $tallasPrenda->first()->descripcion ?? '',
                    $tallas
                );

                $orden->agregarPrenda($prenda);
            }
            break; // Solo necesitamos iterar una vez para restaurar todas las prendas
        }

        return $orden;
    }

    private function guardarPrendas(int $numeroPedido, array $prendas, string $cliente): void
    {
        foreach ($prendas as $prenda) {
            foreach ($prenda->tallas() as $talla) {
                RegistrosPorOrdenBodega::create([
                    'pedido' => $numeroPedido,
                    'cliente' => $cliente,
                    'prenda' => $prenda->nombre(),
                    'descripcion' => $prenda->descripcion(),
                    'talla' => $talla['talla'],
                    'cantidad' => $talla['cantidad'],
                    'total_pendiente_por_talla' => $talla['cantidad'],
                ]);
            }
        }
    }
}
