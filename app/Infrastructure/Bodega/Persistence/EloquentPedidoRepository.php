<?php

namespace App\Infrastructure\Bodega\Persistence;

use App\Domain\Bodega\Entities\Pedido;
use App\Domain\Bodega\Repositories\PedidoRepositoryInterface;
use App\Domain\Bodega\ValueObjects\EstadoPedido;
use App\Domain\Bodega\ValueObjects\AreaBodega;
use App\Models\ReciboPrenda;
use App\Models\PedidoProduccion;
use App\Models\BodegaDetallesTalla;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

/**
 * Implementación Eloquent del Repository de Pedidos
 * Convierte entre modelos Eloquent y Entities del dominio
 */
class EloquentPedidoRepository implements PedidoRepositoryInterface
{
    /**
     * Convertir modelo Eloquent a Entity del dominio
     */
    private function modelToEntity(ReciboPrenda $modelo): Pedido
    {
        $pedidoProduccion = PedidoProduccion::where('numero_pedido', $modelo->numero_pedido)->first();
        
        return Pedido::desdeArray([
            'id' => $modelo->id,
            'numero_pedido' => $modelo->numero_pedido,
            'cliente' => $modelo->cliente ?? 'N/A',
            'asesor_nombre' => $modelo->asesor?->nombre ?? $modelo->asesor?->name ?? null,
            'estado' => $pedidoProduccion?->estado ?? $modelo->estado ?? 'NO INICIADO',
            'fecha_pedido' => $modelo->created_at?->toDateTimeString(),
            'fecha_estimada_entrega' => $pedidoProduccion?->fecha_estimada_de_entrega?->toDateTimeString(),
            'fecha_entrega_real' => $modelo->fecha_entrega_real?->toDateTimeString(),
            'novedades' => $modelo->novedades ?? ''
        ]);
    }

    public function findById(int $id): ?Pedido
    {
        $modelo = ReciboPrenda::with(['asesor'])->find($id);
        return $modelo ? $this->modelToEntity($modelo) : null;
    }

    public function findByNumero(string $numeroPedido): ?Pedido
    {
        $modelo = ReciboPrenda::with(['asesor'])
            ->where('numero_pedido', $numeroPedido)
            ->first();
            
        return $modelo ? $this->modelToEntity($modelo) : null;
    }

    public function save(Pedido $pedido): void
    {
        // Buscar si ya existe
        $modelo = ReciboPrenda::find($pedido->getId());
        
        if (!$modelo) {
            // Crear nuevo
            $modelo = new ReciboPrenda();
            $modelo->id = $pedido->getId();
            $modelo->numero_pedido = $pedido->getNumeroPedido();
        }

        // Actualizar datos
        $modelo->cliente = $pedido->getCliente();
        $modelo->novedades = $pedido->getNovedades();
        $modelo->fecha_entrega_real = $pedido->getFechaEntregaReal();
        
        $modelo->save();

        // Actualizar también en PedidoProducción si existe
        $pedidoProduccion = PedidoProduccion::where('numero_pedido', $pedido->getNumeroPedido())->first();
        if ($pedidoProduccion) {
            $pedidoProduccion->estado = $pedido->getEstado()->getValor();
            $pedidoProduccion->novedades = $pedido->getNovedades();
            $pedidoProduccion->save();
        }
    }

    public function delete(Pedido $pedido): void
    {
        $modelo = ReciboPrenda::find($pedido->getId());
        if ($modelo) {
            $modelo->delete();
        }
    }

    public function findByEstado(EstadoPedido $estado): Collection
    {
        return $this->findByEstados([$estado]);
    }

    public function findByEstados(array $estados): Collection
    {
        $estadosStrings = array_map(fn($e) => $e->getValor(), $estados);
        
        $modelos = ReciboPrenda::with(['asesor'])
            ->whereHas('pedidoProduccion', function($query) use ($estadosStrings) {
                $query->whereIn('estado', $estadosStrings);
            })
            ->orWhereIn('estado', $estadosStrings)
            ->get();

        return $modelos->map(fn($modelo) => $this->modelToEntity($modelo));
    }

    public function findByAreas(array $areas): Collection
    {
        $areasStrings = array_map(fn($a) => $a->getValor(), $areas);
        
        // Obtener números de pedido que tienen detalles en esas áreas
        $numerosPedidos = BodegaDetallesTalla::whereIn('area', $areasStrings)
            ->pluck('numero_pedido')
            ->unique();

        $modelos = ReciboPrenda::with(['asesor'])
            ->whereIn('numero_pedido', $numerosPedidos)
            ->get();

        return $modelos->map(fn($modelo) => $this->modelToEntity($modelo));
    }

    public function findWithFilters(
        ?array $estados = null,
        ?array $areas = null,
        ?string $cliente = null,
        ?string $asesor = null,
        ?\DateTime $fechaDesde = null,
        ?\DateTime $fechaHasta = null
    ): Collection {
        $query = ReciboPrenda::with(['asesor']);

        // Filtrar por estados
        if ($estados) {
            $estadosStrings = array_map(fn($e) => $e->getValor(), $estados);
            $query->whereHas('pedidoProduccion', function($q) use ($estadosStrings) {
                $q->whereIn('estado', $estadosStrings);
            })->orWhereIn('estado', $estadosStrings);
        }

        // Filtrar por áreas
        if ($areas) {
            $areasStrings = array_map(fn($a) => $a->getValor(), $areas);
            $numerosPedidos = BodegaDetallesTalla::whereIn('area', $areasStrings)
                ->pluck('numero_pedido')
                ->unique();
            $query->whereIn('numero_pedido', $numerosPedidos);
        }

        // Otros filtros
        if ($cliente) {
            $query->where('cliente', 'like', "%{$cliente}%");
        }

        if ($asesor) {
            $query->whereHas('asesor', function($q) use ($asesor) {
                $q->where('name', 'like', "%{$asesor}%")
                  ->orWhere('nombre', 'like', "%{$asesor}%");
            });
        }

        if ($fechaDesde) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }

        if ($fechaHasta) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }

        $modelos = $query->get();
        return $modelos->map(fn($modelo) => $this->modelToEntity($modelo));
    }

    public function findPaginated(int $pagina = 1, int $porPagina = 20, array $filtros = []): array
    {
        $query = ReciboPrenda::with(['asesor']);

        // Aplicar filtros (similar a findWithFilters)
        if (isset($filtros['estados'])) {
            $estadosStrings = array_map(fn($e) => $e instanceof EstadoPedido ? $e->getValor() : $e, $filtros['estados']);
            $query->whereHas('pedidoProduccion', function($q) use ($estadosStrings) {
                $q->whereIn('estado', $estadosStrings);
            })->orWhereIn('estado', $estadosStrings);
        }

        $total = $query->count();
        $offset = ($pagina - 1) * $porPagina;
        
        $modelos = $query->offset($offset)->limit($porPagina)->get();
        $pedidos = $modelos->map(fn($modelo) => $this->modelToEntity($modelo));

        $paginador = new LengthAwarePaginator(
            $pedidos,
            $total,
            $porPagina,
            $pagina,
            ['path' => request()->url()]
        );

        return [
            'pedidos' => $pedidos,
            'paginador' => $paginador,
            'total' => $total
        ];
    }

    public function exists(int $id): bool
    {
        return ReciboPrenda::where('id', $id)->exists();
    }

    public function existsByNumero(string $numeroPedido): bool
    {
        return ReciboPrenda::where('numero_pedido', $numeroPedido)->exists();
    }

    public function countByEstado(EstadoPedido $estado): int
    {
        return PedidoProduccion::where('estado', $estado->getValor())->count();
    }

    public function countRetrasados(): int
    {
        return PedidoProduccion::where('estado', '!=', 'ENTREGADO')
            ->where('fecha_estimada_de_entrega', '<', Carbon::now())
            ->count();
    }

    public function getEstadisticas(): array
    {
        $estados = EstadoPedido::getEstadosValidos();
        $estadisticas = [];

        foreach ($estados as $estado) {
            $estadisticas['por_estado'][$estado] = $this->countByEstado(EstadoPedido::desdeString($estado));
        }

        $estadisticas['total'] = array_sum($estadisticas['por_estado']);
        $estadisticas['retrasados'] = $this->countRetrasados();
        $estadisticas['hoy'] = ReciboPrenda::whereDate('created_at', Carbon::today())->count();

        return $estadisticas;
    }

    public function getSiguienteNumeroPedido(): string
    {
        $ultimo = PedidoProduccion::orderBy('id', 'desc')->first();
        if (!$ultimo) {
            return 'PED-00001';
        }

        // Extraer número y sumar 1
        preg_match('/(\d+)$/', $ultimo->numero_pedido, $matches);
        $siguienteNumero = isset($matches[1]) ? (int)$matches[1] + 1 : 1;

        return 'PED-' . str_pad($siguienteNumero, 5, '0', STR_PAD_LEFT);
    }
}
