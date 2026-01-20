<?php

namespace App\Domain\PedidoProduccion\Services;

use App\Domain\PedidoProduccion\Strategies\CreacionPrendaStrategy;
use App\Domain\PedidoProduccion\Strategies\CreacionPrendaSinCtaStrategy;
use App\Domain\PedidoProduccion\Strategies\CreacionPrendaReflectivoStrategy;
use App\Domain\PedidoProduccion\Events\PrendaPedidoAgregada;
use App\Domain\Shared\DomainEventDispatcher;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;

/**
 * Servicio Orquestador de Creación de Prendas
 * 
 * Responsabilidades:
 * - Seleccionar la estrategia correcta según tipo de prenda
 * - Coordinar la creación de prendas sin cotización
 * - Manejar errores y logging
 * 
 * Patrón: Strategy + Factory
 * 
 * Encapsula la lógica de orquestación que estaba repartida en el controller
 * Métodos refactorizados:
 * - crearPrendaSinCotizacion() -> Usa CreacionPrendaSinCtaStrategy
 * - crearPrendaReflectivo() -> Usa CreacionPrendaReflectivoStrategy
 */
class PrendaCreationService
{
    public function __construct(
        private DescripcionService $descripcionService,
        private ImagenService $imagenService,
        private UtilitariosService $utilitariosService,
        private DomainEventDispatcher $eventDispatcher,
    ) {}

    /**
     * Crear prenda sin cotización usando estrategia
     * 
     * Encapsula la lógica de controller::crearPrendaSinCotizacion() (~400 líneas)
     * El controlador solo valida y responde HTTP, la lógica está aquí
     * 
     * @param array $prendaData Datos de la prenda del request
     * @param string $numeroPedido Número del pedido
     * @return PrendaPedido Prenda creada
     * @throws \Exception
     */
    public function crearPrendaSinCotizacion(
        array $prendaData,
        string $numeroPedido
    ): PrendaPedido {
        Log::info('📦 [PrendaCreationService::crearPrendaSinCotizacion] Iniciando con estrategia', [
            'nombre' => $prendaData['nombre_producto'] ?? 'Sin nombre',
            'numero_pedido' => $numeroPedido,
        ]);

        // Usar estrategia para sin cotización
        $strategy = new CreacionPrendaSinCtaStrategy();

        // Inyectar servicios
        $servicios = [
            'descripcionService' => $this->descripcionService,
            'imagenService' => $this->imagenService,
        ];

        try {
            $prenda = $strategy->procesar($prendaData, $numeroPedido, $servicios);

            // Emitir evento de prenda agregada
            $pedidoId = $prendaData['pedido_id'] ?? null;
            if ($pedidoId) {
                $event = new PrendaPedidoAgregada(
                    pedidoId: (int) $pedidoId,
                    prendaId: $prenda->id,
                    nombrePrenda: $prenda->nombre_prendas,
                    cantidad: (int) $prendaData['cantidad_inicial'] ?? 1,
                    genero: $prenda->genero,
                    colorId: $prenda->color_id,
                    telaId: $prenda->tela_id,
                    tipoMangaId: $prenda->tipo_manga_id,
                    tipoBrocheId: $prenda->tipo_broche_id,
                );
                $this->eventDispatcher->dispatch($event);
                Log::info(' Evento PrendaPedidoAgregada emitido', [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prenda->id,
                    'nombre' => $prenda->nombre_prendas,
                ]);
            }

            Log::info(' [PrendaCreationService::crearPrendaSinCotizacion] Prenda creada exitosamente', [
                'prenda_id' => $prenda->id,
                'estrategia' => $strategy->getNombre(),
            ]);

            return $prenda;

        } catch (\Exception $e) {
            Log::error(' [PrendaCreationService::crearPrendaSinCotizacion] Error', [
                'error' => $e->getMessage(),
                'estrategia' => $strategy->getNombre(),
            ]);

            throw $e;
        }
    }

    /**
     * Crear prenda reflectivo sin cotización usando estrategia
     * 
     * Encapsula la lógica de controller::crearReflectivoSinCotizacion() (~300 líneas)
     * 
     * @param array $prendaData Datos de la prenda del request
     * @param string $numeroPedido Número del pedido
     * @return PrendaPedido Prenda creada
     * @throws \Exception
     */
    public function crearPrendaReflectivo(
        array $prendaData,
        string $numeroPedido
    ): PrendaPedido {
        Log::info('📦 [PrendaCreationService::crearPrendaReflectivo] Iniciando con estrategia', [
            'nombre' => $prendaData['nombre_producto'] ?? 'Sin nombre',
            'numero_pedido' => $numeroPedido,
        ]);

        // Usar estrategia para reflectivo
        $strategy = new CreacionPrendaReflectivoStrategy();

        // Inyectar servicios
        $servicios = [
            'imagenService' => $this->imagenService,
            'utilitariosService' => $this->utilitariosService,
        ];

        try {
            $prenda = $strategy->procesar($prendaData, $numeroPedido, $servicios);

            // Emitir evento de prenda agregada
            $pedidoId = $prendaData['pedido_id'] ?? null;
            if ($pedidoId) {
                $event = new PrendaPedidoAgregada(
                    pedidoId: (int) $pedidoId,
                    prendaId: $prenda->id,
                    nombrePrenda: $prenda->nombre_prendas,
                    cantidad: (int) $prendaData['cantidad_inicial'] ?? 1,
                    genero: $prenda->genero,
                    colorId: $prenda->color_id,
                    telaId: $prenda->tela_id,
                    tipoMangaId: $prenda->tipo_manga_id,
                    tipoBrocheId: $prenda->tipo_broche_id,
                );
                $this->eventDispatcher->dispatch($event);
                Log::info(' Evento PrendaPedidoAgregada emitido (reflectivo)', [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prenda->id,
                    'nombre' => $prenda->nombre_prendas,
                ]);
            }

            Log::info(' [PrendaCreationService::crearPrendaReflectivo] Prenda creada exitosamente', [
                'prenda_id' => $prenda->id,
                'estrategia' => $strategy->getNombre(),
            ]);

            return $prenda;

        } catch (\Exception $e) {
            Log::error(' [PrendaCreationService::crearPrendaReflectivo] Error', [
                'error' => $e->getMessage(),
                'estrategia' => $strategy->getNombre(),
            ]);

            throw $e;
        }
    }

    /**
     * Factory method para obtener estrategia según tipo
     * Extensible para futuros tipos de prendas
     * 
     * @param string $tipo Tipo de prenda: 'sin_cotizacion', 'reflectivo', etc
     * @return CreacionPrendaStrategy
     * @throws \InvalidArgumentException Si tipo no es soportado
     */
    public function obtenerEstrategia(string $tipo): CreacionPrendaStrategy
    {
        return match ($tipo) {
            'sin_cotizacion' => new CreacionPrendaSinCtaStrategy(),
            'reflectivo' => new CreacionPrendaReflectivoStrategy(),
            default => throw new \InvalidArgumentException("Tipo de prenda no soportado: $tipo"),
        };
    }
}
