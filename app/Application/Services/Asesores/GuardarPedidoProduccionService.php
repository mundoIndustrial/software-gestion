<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use App\Enums\EstadoPedido;
use App\Application\Services\PedidoLogoService;
use App\Application\Services\PedidoPrendaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GuardarPedidoProduccionService
{
    protected ProcesarFotosTelasService $procesarFotosService;

    public function __construct(ProcesarFotosTelasService $procesarFotosService)
    {
        $this->procesarFotosService = $procesarFotosService;
    }

    /**
     * Guardar un pedido normal en pedidos_produccion con prendas
     * Flujo para P, PL, RF, etc.
     * 
     * @param array $validated
     * @param array $productos
     * @return PedidoProduccion
     * @throws \Exception
     */
    public function guardar(array $validated, array $productos): PedidoProduccion
    {
        Log::info('💾 [PRODUCCION] Guardando pedido en pedidos_produccion', [
            'cliente' => $validated['cliente'],
            'productos_count' => count($productos)
        ]);

        DB::beginTransaction();
        try {
            // Crear pedido en PedidoProduccion
            $pedido = PedidoProduccion::create([
                'numero_pedido' => null,
                'cliente' => $validated['cliente'],
                'asesor_id' => Auth::id(),
                'forma_de_pago' => $validated['forma_de_pago'] ?? null,
                'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
            ]);

            Log::info(' [PRODUCCION] Pedido base creado', ['pedido_id' => $pedido->id]);

            // Guardar prendas COMPLETAS
            $this->guardarPrendas($pedido, $productos);

            // Guardar logo si existe
            $this->guardarLogo($pedido, $validated);

            DB::commit();

            Log::info(' [PRODUCCION] Pedido guardado completamente', ['pedido_id' => $pedido->id]);

            return $pedido;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error(' [PRODUCCION] Error al guardar', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Guardar prendas del pedido
     */
    private function guardarPrendas(PedidoProduccion $pedido, array $productos): void
    {
        Log::info('🧵 [PRENDAS] Guardando ' . count($productos) . ' prendas');

        try {
            $pedidoPrendaService = new PedidoPrendaService();
            $pedidoPrendaService->guardarPrendasEnPedido($pedido, $productos);
            
            Log::info(' [PRENDAS] Guardadas exitosamente');
        } catch (\Exception $e) {
            Log::error(' [PRENDAS] Error al guardar', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Guardar logo del pedido si existe
     */
    private function guardarLogo(PedidoProduccion $pedido, array $validated): void
    {
        // Verificar si hay datos de logo
        $tieneDataLogo = !empty($validated['logo.descripcion'] ?? null)
            || !empty($validated['logo.tecnicas'] ?? null)
            || !empty($validated['logo.ubicaciones'] ?? null)
            || !empty($validated['logo.observaciones_generales'] ?? null);

        if (!$tieneDataLogo) {
            Log::info(' [LOGO] No hay datos de logo para este pedido');
            return;
        }

        Log::info('🎨 [LOGO] Guardando logo en pedido', ['pedido_id' => $pedido->id]);

        try {
            $logoService = new PedidoLogoService();
            
            // Normalizar observaciones generales
            $observacionesGenerales = $validated['logo.observaciones_generales'] ?? null;
            if (is_string($observacionesGenerales)) {
                $observacionesGenerales = json_decode($observacionesGenerales, true) ?? [];
            } elseif (!is_array($observacionesGenerales)) {
                $observacionesGenerales = [];
            }

            // Preparar datos del logo
            $logoData = [
                'descripcion' => $validated['logo.descripcion'] ?? null,
                'ubicacion' => null,
                'observaciones_generales' => $observacionesGenerales,
                'fotos' => []
            ];
            
            // Guardar logo en el pedido
            $logoService->guardarLogoEnPedido($pedido, $logoData);
            
            Log::info(' [LOGO] Guardado exitosamente');
        } catch (\Exception $e) {
            Log::error(' [LOGO] Error al guardar', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Detectar tipo de pedido (logo o producción)
     */
    public static function detectarTipo(string $tipoCotizacion = null, int $cotizacionId = null): string
    {
        // Usar el servicio de logo para detectar
        $logoService = new GuardarPedidoLogoService();
        
        if ($logoService->esLogoPedido($tipoCotizacion, $cotizacionId)) {
            return 'logo';
        }

        return 'produccion';
    }
}
