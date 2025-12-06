<?php

namespace App\Repositories;

use App\Domain\Ordenes\Entities\Orden;
use App\Domain\Ordenes\Entities\Prenda;
use App\Domain\Ordenes\Repositories\OrdenRepositoryInterface;
use App\Domain\Ordenes\ValueObjects\NumeroOrden;
use App\Domain\Ordenes\ValueObjects\EstadoOrden;
use App\Domain\Ordenes\ValueObjects\FormaPago;
use App\Domain\Ordenes\ValueObjects\Area;
use App\Models\TablaOriginal;
use App\Models\PrendaPedido;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repository: EloquentOrdenRepository
 * 
 * Implementa OrdenRepositoryInterface usando Eloquent.
 * Traduce entre Domain Model (Orden) y Persistence Model (TablaOriginal).
 */
class EloquentOrdenRepository implements OrdenRepositoryInterface
{
    /**
     * Guardar orden (crear o actualizar)
     */
    public function save(Orden $orden): void
    {
        DB::transaction(function () use ($orden) {
            $numero = $orden->getNumeroPedido()->toInt();

            // Buscar o crear registro
            $modelo = TablaOriginal::updateOrCreate(
                ['pedido' => $numero],
                [
                    'cliente' => $orden->getCliente(),
                    'estado' => $orden->getEstado()->toString(),
                    'forma_pago' => $orden->getFormaPago()->toString(),
                    'area' => $orden->getArea()->toString(),
                ]
            );

            // Sincronizar prendas
            $this->sincronizarPrendas($modelo, $orden);

            // Persistir eventos (pueden ser procesados después)
            $this->persistirEventos($orden);
        });
    }

    /**
     * Obtener orden por número
     */
    public function findByNumero(NumeroOrden $numero): ?Orden
    {
        $modelo = TablaOriginal::find($numero->toInt());

        if (!$modelo) {
            return null;
        }

        return $this->reconstruirOrdenDesdeModelo($modelo);
    }

    /**
     * Obtener todas las órdenes
     */
    public function findAll(): Collection
    {
        return TablaOriginal::all()
            ->map(fn($modelo) => $this->reconstruirOrdenDesdeModelo($modelo));
    }

    /**
     * Obtener órdenes por cliente
     */
    public function findByCliente(string $cliente): Collection
    {
        return TablaOriginal::where('cliente', $cliente)
            ->get()
            ->map(fn($modelo) => $this->reconstruirOrdenDesdeModelo($modelo));
    }

    /**
     * Obtener órdenes por estado
     */
    public function findByEstado(string $estado): Collection
    {
        return TablaOriginal::where('estado', $estado)
            ->get()
            ->map(fn($modelo) => $this->reconstruirOrdenDesdeModelo($modelo));
    }

    /**
     * Eliminar orden
     */
    public function delete(NumeroOrden $numero): void
    {
        TablaOriginal::destroy($numero->toInt());
        PrendaPedido::where('numero_pedido', $numero->toInt())->delete();
    }

    /**
     * Contar órdenes
     */
    public function count(): int
    {
        return TablaOriginal::count();
    }

    /**
     * Reconstruir agregado Orden desde modelo Eloquent
     * 
     * Este es el paso crítico: convertir datos persistidos en Domain Model.
     */
    private function reconstruirOrdenDesdeModelo(TablaOriginal $modelo): Orden
    {
        // Crear Value Objects
        $numeroOrden = NumeroOrden::desde($modelo->pedido);
        $formaPago = FormaPago::desde($modelo->forma_pago);
        $area = Area::desde($modelo->area);

        // Crear agregado (sin emitir eventos de nuevo)
        $orden = Orden::crear(
            $numeroOrden,
            $modelo->cliente,
            $formaPago,
            $area
        );

        // Cambiar estado si es necesario (sin emitir evento)
        if ($modelo->estado !== 'Borrador') {
            $this->cambiarEstadoSilencioso($orden, $modelo->estado);
        }

        // Cargar prendas
        $prendas = PrendaPedido::where('numero_pedido', $modelo->pedido)->get();

        foreach ($prendas as $prendaModelo) {
            $prenda = Prenda::crear(
                $prendaModelo->nombre_prenda,
                $prendaModelo->cantidad ?? 0,
                $prendaModelo->cantidad_talla ?? []
            );

            if ($prendaModelo->descripcion) {
                $prenda->setDescripcion($prendaModelo->descripcion);
            }

            if ($prendaModelo->color_id) {
                $prenda->setColorId($prendaModelo->color_id);
            }

            if ($prendaModelo->tela_id) {
                $prenda->setTelaId($prendaModelo->tela_id);
            }

            if ($prendaModelo->tipo_manga_id) {
                $prenda->setTipoMangaId($prendaModelo->tipo_manga_id);
            }

            if ($prendaModelo->tipo_broche_id) {
                $prenda->setTipoBrocheId($prendaModelo->tipo_broche_id);
            }

            $prenda->setTieneBolsillos($prendaModelo->tiene_bolsillos ?? false);
            $prenda->setTieneReflectivo($prendaModelo->tiene_reflectivo ?? false);

            $orden->agregarPrenda($prenda);
        }

        // Limpiar eventos para evitar re-procesamiento
        $orden->clearEventos();

        return $orden;
    }

    /**
     * Cambiar estado silenciosamente (sin emitir eventos)
     * Usado al reconstruir desde la BD.
     */
    private function cambiarEstadoSilencioso(Orden $orden, string $estado): void
    {
        $nuevoEstado = EstadoOrden::desde($estado);

        // Usar reflexión para acceder al método privado
        $reflection = new \ReflectionClass($orden);
        $property = $reflection->getProperty('estado');
        $property->setAccessible(true);
        $property->setValue($orden, $nuevoEstado);
    }

    /**
     * Sincronizar colección de prendas con BD
     */
    private function sincronizarPrendas(TablaOriginal $modelo, Orden $orden): void
    {
        $numeroPedido = $orden->getNumeroPedido()->toInt();

        // Obtener IDs de prendas en el agregado
        $prendasDominio = $orden->getPrendas();
        $nombresPrendas = $prendasDominio->pluck('nombrePrenda');

        // Eliminar prendas no existentes en el agregado
        PrendaPedido::where('numero_pedido', $numeroPedido)
            ->whereNotIn('nombre_prenda', $nombresPrendas)
            ->delete();

        // Crear o actualizar prendas
        foreach ($prendasDominio as $prenda) {
            PrendaPedido::updateOrCreate(
                [
                    'numero_pedido' => $numeroPedido,
                    'nombre_prenda' => $prenda->getNombrePrenda(),
                ],
                [
                    'cantidad' => $prenda->getCantidadTotal(),
                    'cantidad_talla' => $prenda->getCantidadTalla(),
                    'descripcion' => $prenda->getDescripcion(),
                    'color_id' => $prenda->getColorId(),
                    'tela_id' => $prenda->getTelaId(),
                    'tipo_manga_id' => $prenda->getTipoMangaId(),
                    'tipo_broche_id' => $prenda->getTipoBrocheId(),
                    'tiene_bolsillos' => $prenda->tieneBolsillos(),
                    'tiene_reflectivo' => $prenda->tieneReflectivo(),
                ]
            );
        }
    }

    /**
     * Persistir eventos de dominio para procesamiento asíncrono
     * (Puede guardarse en tabla para Event Sourcing posterior)
     */
    private function persistirEventos(Orden $orden): void
    {
        // Aquí podrías guardar los eventos en una tabla si necesitas Event Sourcing
        // Por ahora, simplemente se registran en logs
        $eventos = $orden->getEventos();

        foreach ($eventos as $evento) {
            \Log::info('Domain Event: ' . class_basename($evento), [
                'evento' => $evento,
                'timestamp' => now()->toIso8601String(),
            ]);
        }
    }
}
