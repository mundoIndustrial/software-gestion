<?php

namespace App\Domain\Pedidos\Services;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaVariante;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PrendaPedidoColorTela;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PedidosProcessImagenes;
use App\Models\TipoProceso;
use App\Models\CatalogoTela;
use App\Models\CatalogoColor;
use App\Models\TipoManga;
use App\Models\TipoBrocheBoton;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

/**
 * Servicio de Dominio: Guardar Pedido desde JSON
 * 
 * Responsabilidad:
 * - Recibir JSON del frontend (estado temporal)
 * - Descomponer JSON en tablas relacionales normalizadas
 * - Guardar todo dentro de transacción DB
 * - Procesamiento de imÃ¡genes (conversión a WebP)
 * 
 * Arquitectura:
 * - SRP: Solo responsable de persistencia de pedidos
 * - Transaccional: Todo o nada
 * - Resiliente: Rollback automÃ¡tico en errores
 * 
 * @author Senior Developer
 */
class GuardarPedidoDesdeJSONService
{
    public function __construct(
        private ImagenService $imagenService,
    ) {}

    /**
     * Guardar pedido completo desde JSON
     * 
     * JSON esperado:
     * {
     *   pedido_produccion_id: number,
     *   prendas: [
     *     {
     *       nombre_prenda: string,
     *       descripcion: string,
     *       genero: string | null,
     *       de_bodega: boolean,
     *       fotos_prenda: File[],
     *       fotos_tela: [{tela_id, color_id, archivo, ...}],
     *       variantes: [{talla, cantidad, color_id, tela_id, ...}],
     *       procesos: [{tipo_proceso_id, ubicaciones, observaciones, imagenes: File[]}]
     *     }
     *   ]
     * }
     * 
     * @param int $pedidoId - ID del pedido de producción
     * @param array $prendas - Array de prendas desde JSON
     * @return array - Resultado del guardado
     * @throws \Exception
     */
    public function guardar(int $pedidoId, array $prendas): array
    {
        return DB::transaction(function () use ($pedidoId, $prendas) {
            $pedido = $this->obtenerPedido($pedidoId);
            $prendasGuardadas = [];
            $cantidadTotal = 0;

            foreach ($prendas as $prendaData) {
                $resultado = $this->guardarPrenda($pedido, $prendaData);
                $prendasGuardadas[] = $resultado;
                $cantidadTotal += $resultado['cantidad_items'];
            }

            // Actualizar cantidad total en pedido
            $pedido->update(['cantidad_total' => $cantidadTotal]);

            return [
                'success' => true,
                'message' => 'Pedido guardado correctamente',
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cantidad_prendas' => count($prendasGuardadas),
                'cantidad_items' => $cantidadTotal,
                'prendas' => $prendasGuardadas,
            ];
        });
    }

    /**
     * Guardar una prenda completa dentro de una transacción
     */
    private function guardarPrenda(PedidoProduccion $pedido, array $prendaData): array
    {
        // 1. Crear registro de prenda base
        $prendaPedido = $this->crearPrendaPedido($pedido, $prendaData);

        // 2. Guardar fotos de prenda
        $cantidadFotosPrenda = $this->guardarFotosPrenda($prendaPedido, $prendaData['fotos_prenda'] ?? []);

        // 3. Guardar fotos de telas
        $cantidadFotosTelas = $this->guardarFotosTelas($prendaPedido, $prendaData['fotos_tela'] ?? []);

        // 4. Guardar variantes (tallas, colores, telas, etc.)
        $cantidadVariantes = $this->guardarVariantes($prendaPedido, $prendaData['variantes'] ?? []);

        // 5. Guardar procesos especiales
        $cantidadProcesos = $this->guardarProcesos($prendaPedido, $prendaData['procesos'] ?? []);

        return [
            'prenda_pedido_id' => $prendaPedido->id,
            'nombre_prenda' => $prendaPedido->nombre_prenda,
            'cantidad_variantes' => $cantidadVariantes,
            'cantidad_procesos' => $cantidadProcesos,
            'cantidad_fotos_prenda' => $cantidadFotosPrenda,
            'cantidad_fotos_telas' => $cantidadFotosTelas,
            'cantidad_items' => $cantidadVariantes,
        ];
    }

    /**
     * Crear registro de prenda base en BD
     */
    private function crearPrendaPedido(PedidoProduccion $pedido, array $prendaData): PrendaPedido
    {
        return $pedido->prendas()->create([
            'nombre_prenda' => $prendaData['nombre_prenda'],
            'descripcion' => $prendaData['descripcion'] ?? '',
            'genero' => $prendaData['genero'],
            'de_bodega' => (bool)($prendaData['de_bodega'] ?? true),
        ]);
    }

    /**
     * Guardar fotos generales de prenda
     */
    private function guardarFotosPrenda(PrendaPedido $prendaPedido, array $archivos): int
    {
        $contador = 0;

        foreach ($archivos as $index => $archivo) {
            if (!$archivo || !method_exists($archivo, 'isValid') || !$archivo->isValid()) {
                continue;
            }

            try {
                $rutasGuardadas = $this->imagenService->guardarImagenComoWebp(
                    $archivo,
                    "prendas/{$prendaPedido->id}",
                    $prendaPedido->id
                );

                if ($rutasGuardadas) {
                    $prendaPedido->fotos()->create([
                        'ruta_original' => $rutasGuardadas['original'] ?? null,
                        'ruta_webp' => $rutasGuardadas['webp'],
                        'orden' => $index + 1,
                    ]);
                    $contador++;
                }
            } catch (\Exception $e) {
                \Log::warning(" Error al guardar foto de prenda: {$e->getMessage()}");
            }
        }

        return $contador;
    }

    /**
     * Guardar fotos de telas especificadas
     * Ahora crea registros en prenda_pedido_colores_telas primero, luego las fotos
     */
    private function guardarFotosTelas(PrendaPedido $prendaPedido, array $fotosTelas): int
    {
        $contador = 0;

        foreach ($fotosTelas as $fotoData) {
            $archivo = $fotoData['archivo'] ?? null;

            if (!$archivo || !method_exists($archivo, 'isValid') || !$archivo->isValid()) {
                continue;
            }

            try {
                // Obtener o crear la combinación color-tela
                $colorTelaId = $fotoData['color_tela_id'] ?? null;
                
                if (!$colorTelaId) {
                    // Si no viene el ID, crear la combinación
                    $colorTela = $prendaPedido->coloresTelas()->firstOrCreate([
                        'color_id' => $fotoData['color_id'] ?? null,
                        'tela_id' => $fotoData['tela_id'] ?? null,
                    ]);
                    $colorTelaId = $colorTela->id;
                }

                $rutasGuardadas = $this->imagenService->guardarImagenComoWebp(
                    $archivo,
                    "telas/{$prendaPedido->id}",
                    $prendaPedido->id
                );

                if ($rutasGuardadas) {
                    // Crear foto asociada a la combinación color-tela
                    \DB::table('prenda_fotos_tela_pedido')->insert([
                        'prenda_pedido_colores_telas_id' => $colorTelaId,
                        'ruta_original' => $rutasGuardadas['original'] ?? null,
                        'ruta_webp' => $rutasGuardadas['webp'],
                        'orden' => $fotoData['orden'] ?? $contador + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $contador++;
                }
            } catch (\Exception $e) {
                \Log::warning(" Error al guardar foto de tela: {$e->getMessage()}");
            }
        }

        return $contador;
    }

    /**
     * Guardar variantes de prenda (talla, cantidad, color, tela, etc.)
     */
    private function guardarVariantes(PrendaPedido $prendaPedido, array $variantes): int
    {
        $contador = 0;

        foreach ($variantes as $varianteData) {
            //  [19/01/2026] Talla y cantidad YA se guardan en prendas_pedido.cantidad_talla (JSON)
            // Las variantes son ahora SOLO combinaciones de caracterÃ­sticas (color, tela, manga, broche, bolsillos)

            $prendaPedido->variantes()->create([
                'color_id' => $varianteData['color_id'] ?? null,
                'tela_id' => $varianteData['tela_id'] ?? null,
                'tipo_manga_id' => $varianteData['tipo_manga_id'] ?? null,
                'manga_obs' => $varianteData['manga_obs'] ?? '',
                'tipo_broche_boton_id' => $varianteData['tipo_broche_boton_id'] ?? null,
                'broche_boton_obs' => $varianteData['broche_boton_obs'] ?? '',
                'tiene_bolsillos' => (bool)($varianteData['tiene_bolsillos'] ?? false),
                'bolsillos_obs' => $varianteData['bolsillos_obs'] ?? '',
            ]);

            $contador++;
        }

        return $contador;
    }

    /**
     * Guardar procesos especiales de prenda
     */
    private function guardarProcesos(PrendaPedido $prendaPedido, array $procesos): int
    {
        $contador = 0;

        foreach ($procesos as $procesoData) {
            if (empty($procesoData['tipo_proceso_id'])) {
                \Log::warning(' Proceso sin tipo_proceso_id, omitiendo');
                continue;
            }

            // Decodificar ubicaciones si vienen como JSON string
            $ubicaciones = $procesoData['ubicaciones'] ?? [];
            if (is_string($ubicaciones)) {
                try {
                    $ubicacionesDecodificadas = json_decode($ubicaciones, true);
                    if (is_array($ubicacionesDecodificadas)) {
                        $ubicaciones = $ubicacionesDecodificadas;
                    }
                } catch (\Exception $e) {
                    $ubicaciones = [];
                }
            }

            // Crear registro de proceso
            $proceso = $prendaPedido->procesos()->create([
                'tipo_proceso_id' => $procesoData['tipo_proceso_id'],
                'ubicaciones' => json_encode($ubicaciones),
                'observaciones' => $procesoData['observaciones'] ?? '',
                'tallas_dama' => !empty($procesoData['tallas_dama']) 
                    ? json_encode($procesoData['tallas_dama']) 
                    : null,
                'tallas_caballero' => !empty($procesoData['tallas_caballero']) 
                    ? json_encode($procesoData['tallas_caballero']) 
                    : null,
                'estado' => 'PENDIENTE',
                'datos_adicionales' => !empty($procesoData['datos_adicionales']) 
                    ? json_encode($procesoData['datos_adicionales']) 
                    : null,
            ]);

            \Log::info('[GuardarPedidoDesdeJSONService] Proceso creado, ahora guardando imágenes', [
                'proceso_id' => $proceso->id,
                'tipo_proceso_id' => $procesoData['tipo_proceso_id'],
                'cantidad_imagenes_en_array' => count($procesoData['imagenes'] ?? []),
                'has_imagenes_key' => isset($procesoData['imagenes']),
            ]);

            // Guardar imÃ¡genes del proceso
            $this->guardarImagenesProceso($proceso, $procesoData['imagenes'] ?? []);

            $contador++;
        }

        return $contador;
    }

    /**
     * Guardar imÃ¡genes de un proceso
     */
    private function guardarImagenesProceso(PedidosProcesosPrendaDetalle $proceso, array $archivos): void
    {
        \Log::info('[GuardarPedidoDesdeJSONService] guardarImagenesProceso INICIADO', [
            'proceso_id' => $proceso->id,
            'cantidad_archivos' => count($archivos),
            'tipo_proceso_id' => $proceso->tipo_proceso_id,
        ]);

        if (empty($archivos)) {
            \Log::info('[GuardarPedidoDesdeJSONService] No hay archivos para guardar', [
                'proceso_id' => $proceso->id,
            ]);
            return;
        }

        $orden = 1;

        foreach ($archivos as $indice => $archivo) {
            \Log::debug('[GuardarPedidoDesdeJSONService] Procesando archivo', [
                'proceso_id' => $proceso->id,
                'indice' => $indice,
                'archivo_class' => get_class($archivo ?? 'null'),
                'es_null' => $archivo === null,
                'tiene_isValid' => method_exists($archivo, 'isValid'),
            ]);

            if (!$archivo || !method_exists($archivo, 'isValid') || !$archivo->isValid()) {
                \Log::warning('[GuardarPedidoDesdeJSONService] Archivo inválido saltado', [
                    'proceso_id' => $proceso->id,
                    'indice' => $indice,
                ]);
                continue;
            }

            try {
                \Log::info('[GuardarPedidoDesdeJSONService] Guardando imagen WebP', [
                    'proceso_id' => $proceso->id,
                    'nombre_archivo' => $archivo->getClientOriginalName(),
                ]);

                $rutasGuardadas = $this->imagenService->guardarImagenComoWebp(
                    $archivo,
                    "procesos/{$proceso->id}",
                    $proceso->id
                );

                \Log::info('[GuardarPedidoDesdeJSONService] Imagen guardada en storage', [
                    'proceso_id' => $proceso->id,
                    'rutas_guardadas' => $rutasGuardadas,
                ]);

                if ($rutasGuardadas) {
                    $esImagenPrincipal = $orden === 1; // Primera imagen es principal
                    
                    \Log::info('[GuardarPedidoDesdeJSONService] Creando registro en DB', [
                        'proceso_id' => $proceso->id,
                        'orden' => $orden,
                        'es_principal' => $esImagenPrincipal,
                        'ruta_original' => $rutasGuardadas['original'] ?? null,
                        'ruta_webp' => $rutasGuardadas['webp'],
                    ]);

                    $registro = $proceso->imagenes()->create([
                        'ruta_original' => $rutasGuardadas['original'] ?? null,
                        'ruta_webp' => $rutasGuardadas['webp'],
                        'orden' => $orden++,
                        'es_principal' => $esImagenPrincipal,
                    ]);

                    \Log::info('[GuardarPedidoDesdeJSONService] Registro creado exitosamente en DB', [
                        'proceso_id' => $proceso->id,
                        'imagen_id' => $registro->id ?? null,
                    ]);
                } else {
                    \Log::warning('[GuardarPedidoDesdeJSONService] guardarImagenComoWebp retornó null', [
                        'proceso_id' => $proceso->id,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('[GuardarPedidoDesdeJSONService] Error al guardar imagen de proceso', [
                    'proceso_id' => $proceso->id,
                    'error' => $e->getMessage(),
                    'exception' => $e,
                ]);
            }
        }

        \Log::info('[GuardarPedidoDesdeJSONService] guardarImagenesProceso COMPLETADO', [
            'proceso_id' => $proceso->id,
        ]);
    }

    /**
     * Obtener pedido o lanzar excepción
     */
    private function obtenerPedido(int $pedidoId): PedidoProduccion
    {
        $pedido = PedidoProduccion::find($pedidoId);

        if (!$pedido) {
            throw new \Exception("Pedido con ID {$pedidoId} no encontrado");
        }

        return $pedido;
    }
}

