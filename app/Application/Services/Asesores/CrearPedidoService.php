<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use App\Models\LogoPedido;
use App\Enums\EstadoPedido;
use App\Application\Services\PedidoPrendaService;
use App\Application\Services\PedidoLogoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

/**
 * CrearPedidoService
 * 
 * Servicio de aplicación para crear pedidos (producción o logo).
 * Encapsula la lógica de negocio de creación de pedidos separando:
 * - Pedidos de prendas (Pedidos)
 * - Pedidos de logo (LogoPedido)
 */
class CrearPedidoService
{
    protected PedidoPrendaService $pedidoPrendaService;
    protected PedidoLogoService $pedidoLogoService;

    public function __construct(
        PedidoPrendaService $pedidoPrendaService,
        PedidoLogoService $pedidoLogoService
    ) {
        $this->pedidoPrendaService = $pedidoPrendaService;
        $this->pedidoLogoService = $pedidoLogoService;
    }

    /**
     * Crear pedido (identificar tipo y delegar)
     */
    public function crear(array $datos, $tipoCotizacion = null): PedidoProduccion|int
    {
        DB::beginTransaction();
        try {
            $esPedidoLogo = $this->esPedidoLogo($tipoCotizacion, $datos['cotizacion_id'] ?? null);

            if ($esPedidoLogo) {
                return $this->crearPedidoLogo($datos);
            }

            $resultado = $this->crearPedidos($datos);
            
            DB::commit();
            return $resultado;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creando pedido:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Crear pedido de prendas (Pedidos)
     */
    protected function crearPedidos(array $datos): PedidoProduccion
    {
        // Crear pedido base PRIMERO para obtener ID
        $pedido = PedidoProduccion::create([
            'numero_pedido' => null,
            'cliente' => $datos['cliente'],
            'asesor_id' => Auth::id(),
            'forma_de_pago' => $datos['forma_de_pago'] ?? null,
            'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
        ]);

        \Log::info('Pedido creado en Pedidos', ['pedido_id' => $pedido->id]);

        //  CREAR ESTRUCTURA DE CARPETAS PARA IMÁGENES
        $this->crearEstructuraCarpetas($pedido->id);

        // Procesar imágenes de telas CON el ID del pedido
        $productosKey = isset($datos['productos']) ? 'productos' : 'productos_friendly';
        $productosConTelasProcessadas = $this->procesarFotosTelas(
            $datos[$productosKey] ?? [],
            $datos['archivos'] ?? [],
            $pedido->id
        );

        // Guardar prendas
        $this->pedidoPrendaService->guardarPrendasEnPedido($pedido, $productosConTelasProcessadas);

        // Guardar logo si existe
        $tieneDataLogo = !empty($datos['logo']['descripcion'])
            || !empty($datos['logo']['imagenes'])
            || !empty($datos['logo']['tecnicas'])
            || !empty($datos['logo']['ubicaciones'])
            || !empty($datos['logo']['observaciones_generales']);

        if ($tieneDataLogo) {
            $imagenesProcesadas = $this->procesarImagenesLogo($datos['logo']['imagenes'] ?? [], $pedido->id);
            $this->pedidoLogoService->guardarLogoEnPedido($pedido, [
                'descripcion' => $datos['logo']['descripcion'] ?? null,
                'ubicacion' => null,
                'observaciones_generales' => $datos['logo']['observaciones_generales'] ?? [],
                'fotos' => $imagenesProcesadas
            ]);
        }

        return $pedido;
    }

    /**
     * Crear pedido de logo (LogoPedido)
     */
    protected function crearPedidoLogo(array $datos): int
    {
        $imagenesProcesadas = $this->procesarImagenesLogo($datos['logo']['imagenes'] ?? []);
        
        $numeroPedido = LogoPedido::generarNumeroPedido();

        $logoPedidoId = DB::table('logo_pedidos')->insertGetId([
            'pedido_id' => null,
            'logo_cotizacion_id' => null,
            'numero_pedido' => $numeroPedido,
            'cliente' => $datos['cliente'],
            'asesora' => Auth::user()?->name,
            'forma_de_pago' => $datos['forma_de_pago'] ?? null,
            'encargado_orden' => Auth::user()?->name,
            'fecha_de_creacion_de_orden' => now(),
            'estado' => 'pendiente',
            'area' => 'creacion_de_orden',
            'descripcion' => $datos['logo']['descripcion'] ?? null,
            'tecnicas' => $datos['logo']['tecnicas'] ?? null,
            'observaciones_tecnicas' => $datos['logo']['observaciones_tecnicas'] ?? null,
            'ubicaciones' => $datos['logo']['ubicaciones'] ?? null,
            'observaciones' => $datos['logo']['observaciones_generales'] ?? null,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Guardar imÃ¡genes
        foreach ($imagenesProcesadas as $index => $imagen) {
            DB::table('logo_pedido_imagenes')->insert([
                'logo_pedido_id' => $logoPedidoId,
                'nombre_archivo' => $imagen['nombre_archivo'],
                'url' => $imagen['url'],
                'ruta_original' => $imagen['ruta_original'],
                'ruta_webp' => $imagen['ruta_webp'],
                'tipo_archivo' => $imagen['tipo_archivo'],
                'tamaÃ±o_archivo' => $imagen['tamaÃ±o_archivo'],
                'orden' => $index,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        \Log::info('Logo pedido creado', ['logo_pedido_id' => $logoPedidoId, 'numero' => $numeroPedido]);
        
        return $logoPedidoId;
    }

    /**
     * Determinar si es pedido de logo
     */
    protected function esPedidoLogo($tipoCotizacion, $cotizacionId = null): bool
    {
        if ($tipoCotizacion === 'L') {
            return true;
        }

        if ($cotizacionId) {
            $tipoCodigoQuery = DB::table('cotizaciones')
                ->join('tipos_cotizacion', 'cotizaciones.tipo_cotizacion_id', '=', 'tipos_cotizacion.id')
                ->where('cotizaciones.id', $cotizacionId)
                ->select('tipos_cotizacion.codigo')
                ->first();

            return $tipoCodigoQuery && $tipoCodigoQuery->codigo === 'L';
        }

        return false;
    }

    /**
     * Procesar fotos de telas
     */
    protected function procesarFotosTelas(array $productos, array $archivos, int $pedidoId): array
    {
        $productosProcessados = [];

        foreach ($productos as $productoIndex => $producto) {
            $productosProcessados[$productoIndex] = $producto;

            if (!empty($producto['telas']) && is_array($producto['telas'])) {
                $telasProcessadas = [];

                foreach ($producto['telas'] as $telaIndex => $tela) {
                    $telasProcessadas[$telaIndex] = $tela;
                    $fotosProcessadas = [];

                    $fotosKey = "productos_friendly.{$productoIndex}.telas.{$telaIndex}.fotos";

                    if (!empty($archivos[$fotosKey])) {
                        foreach ($archivos[$fotosKey] as $fotoIndex => $archivoFoto) {
                            if ($archivoFoto->isValid()) {
                                $rutaGuardada = $archivoFoto->store("pedido/{$pedidoId}/telas", 'public');
                                $fotosProcessadas[$fotoIndex] = [
                                    'nombre_archivo' => $archivoFoto->getClientOriginalName(),
                                    'ruta_original' => Storage::url($rutaGuardada),
                                    'ruta_webp' => Storage::url(str_replace('.png', '.webp', $rutaGuardada)),
                                    'tipo_archivo' => $archivoFoto->getMimeType(),
                                    'tamaÃ±o_archivo' => $archivoFoto->getSize(),
                                ];
                            }
                        }
                    }

                    if (!empty($fotosProcessadas)) {
                        $telasProcessadas[$telaIndex]['fotos'] = $fotosProcessadas;
                    }
                }

                $productosProcessados[$productoIndex]['telas'] = $telasProcessadas;
            }
        }

        return $productosProcessados;
    }

    /**
     * Procesar imágenes del logo
     */
    protected function procesarImagenesLogo(array $imagenes, int $pedidoId = null): array
    {
        $imagenesProcesadas = [];

        foreach ($imagenes as $imagen) {
            if ($imagen->isValid()) {
                // Si hay pedidoId, guardar en pedido/{id}/logo, sino en logos/pedidos
                $rutaBase = $pedidoId ? "pedido/{$pedidoId}/logo" : 'logos/pedidos';
                $rutaGuardada = $imagen->store($rutaBase, 'public');
                $rutaWebp = str_replace(
                    ['.' . $imagen->getClientOriginalExtension()],
                    ['.webp'],
                    $rutaGuardada
                );

                $imagenesProcesadas[] = [
                    'nombre_archivo' => $imagen->getClientOriginalName(),
                    'ruta_original' => Storage::url($rutaGuardada),
                    'ruta_webp' => Storage::url($rutaWebp),
                    'url' => Storage::url($rutaWebp),
                    'tipo_archivo' => $imagen->getMimeType(),
                    'tamaÃ±o_archivo' => $imagen->getSize(),
                ];
            }
        }

        return $imagenesProcesadas;
    }

    /**
     * Crear estructura de carpetas para un pedido
     * 
     * Crea:
     * - storage/app/public/pedido/{pedido_id}/prendas/
     * - storage/app/public/pedido/{pedido_id}/telas/
     * - storage/app/public/pedido/{pedido_id}/procesos/
     * - storage/app/public/pedido/{pedido_id}/epp/
     */
    private function crearEstructuraCarpetas(int $pedidoId): void
    {
        $basePath = "pedido/{$pedidoId}";
        $carpetas = ['prendas', 'telas', 'procesos', 'epp'];
        
        try {
            foreach ($carpetas as $carpeta) {
                $rutaCompleta = "{$basePath}/{$carpeta}";
                
                if (!Storage::disk('public')->exists($rutaCompleta)) {
                    Storage::disk('public')->makeDirectory($rutaCompleta, 0755, true);
                    \Log::info('[CrearPedidoService] Carpeta creada', [
                        'pedido_id' => $pedidoId,
                        'carpeta' => $rutaCompleta,
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::warning('[CrearPedidoService] Error creando carpetas', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);
            // No fallar si hay error en carpetas
        }
    }
}

