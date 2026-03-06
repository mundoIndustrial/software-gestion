<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarPrendaCompletaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Models\PrendaPedido;
use App\Models\PrendaPedidoColorTela;
use App\Models\PrendaPedidoTalla;
use App\Models\PrendaFotoTelaPedido;
use App\Models\TipoProceso;
use App\Models\TipoPrenda;
use App\Models\TelaPrenda;
use App\Models\ColorPrenda;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PedidosProcesosPrendaTalla;
use Illuminate\Support\Facades\DB;

/**
 * Use Case para agregar una prenda al pedido con fotos y tallas
 * 
 * Replica EXACTAMENTE la lógica de PedidoWebService::crearItemCompleto()
 * para que el resultado sea idéntico al crear pedido nuevo.
 */
final class AgregarPrendaCompletaUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {}

    public function execute(AgregarPrendaCompletaDTO $dto): PrendaPedido
    {
        return DB::transaction(function () use ($dto) {
            // CENTRALIZADO: Validar pedido existe (trait)
            $this->validarPedidoExiste($dto->pedidoId, $this->pedidoRepository);

            // 0. Crear o obtener TipoPrenda (igual que PedidoWebService::crearOObtenerTipoPrenda)
            $this->crearOObtenerTipoPrenda($dto->nombre_prenda);

            // 1. Crear prenda base
            $prenda = PrendaPedido::create([
                'pedido_produccion_id' => $dto->pedidoId,
                'nombre_prenda' => $dto->nombre_prenda,
                'descripcion' => $dto->descripcion,
                'de_bodega' => $dto->de_bodega,
            ]);

            // 2. Agregar fotos: nuevas + existentes
            $fotos = [];
            
            if (!empty($dto->imagenes)) {
                foreach ($dto->imagenes as $orden => $rutaOriginal) {
                    $fotos[$rutaOriginal] = [
                        'ruta_original' => $rutaOriginal,
                        'ruta_webp' => $this->generarRutaWebp($rutaOriginal),
                        'orden' => $orden + 1,
                    ];
                }
            }
            
            if (!empty($dto->imagenesExistentes)) {
                foreach ($dto->imagenesExistentes as $imagenExistente) {
                    if (is_array($imagenExistente) && isset($imagenExistente['previewUrl'])) {
                        $ruta = $imagenExistente['previewUrl'];
                        if (!isset($fotos[$ruta])) {
                            $fotos[$ruta] = [
                                'ruta_original' => $ruta,
                                'ruta_webp' => $this->generarRutaWebp($ruta),
                                'orden' => count($fotos) + 1,
                            ];
                        }
                    }
                }
            }
            
            if (!empty($fotos)) {
                foreach ($fotos as $datosFoto) {
                    $prenda->fotos()->create($datosFoto);
                }
            }

            // 3. Agregar tallas (IGUAL que PedidoWebService::crearTallasPrenda - con SOBREMEDIDA)
            if (!empty($dto->cantidad_talla)) {
                $this->crearTallasPrenda($prenda, $dto->cantidad_talla);
            }

            // 4. Agregar variantes si existen
            if (!empty($dto->variantes) && is_array($dto->variantes)) {
                $variantes = $dto->variantes;
                $varianteData = [
                    'tipo_manga_id'        => $variantes['tipo_manga_id'] ?? null,
                    'tipo_broche_boton_id' => $variantes['tipo_broche_boton_id'] ?? $variantes['tipo_broche_id'] ?? null,
                    'manga_obs'            => $variantes['obs_manga'] ?? $variantes['manga_obs'] ?? null,
                    'broche_boton_obs'     => $variantes['obs_broche'] ?? $variantes['broche_boton_obs'] ?? null,
                    'tiene_bolsillos'      => $variantes['tiene_bolsillos'] ?? false,
                    'bolsillos_obs'        => $variantes['obs_bolsillos'] ?? $variantes['bolsillos_obs'] ?? null,
                ];

                $prenda->variantes()->create($varianteData);

                \Log::info('[AgregarPrendaCompletaUseCase] Variante creada', [
                    'prenda_id' => $prenda->id,
                    'variante' => $varianteData,
                ]);
            }

            // 5. Agregar telas (IGUAL que PedidoWebService::crearTelasDesdeFormulario)
            if (!empty($dto->telas) && is_array($dto->telas)) {
                $this->crearTelasDesdeFormulario($prenda, $dto->telas, $dto->fotosTelaRutas ?? []);
            }

            // 6. Agregar procesos (IGUAL que PedidoWebService::crearProcesosCompletos)
            if (!empty($dto->procesos) && is_array($dto->procesos)) {
                $this->crearProcesosCompletos($prenda, $dto->procesos, $dto->fotosProcesoNuevo ?? []);
            }

            // 7. Guardar novedad
            $this->guardarNovedad($prenda, $dto);

            return $prenda;
        });
    }

    // ============================================================
    // MÉTODOS PRIVADOS - Replican PedidoWebService exactamente
    // ============================================================

    /**
     * Crear o obtener TipoPrenda (replica PedidoWebService::crearOObtenerTipoPrenda)
     */
    private function crearOObtenerTipoPrenda(string $nombrePrenda): void
    {
        try {
            $nombreUpper = strtoupper(trim($nombrePrenda));
            $tipoPrenda = TipoPrenda::whereRaw('UPPER(nombre) = ?', [$nombreUpper])->first();

            if (!$tipoPrenda) {
                TipoPrenda::create([
                    'nombre' => $nombreUpper,
                    'codigo' => strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $nombrePrenda), 0, 10)),
                    'descripcion' => 'Prenda creada automáticamente desde pedido',
                    'activo' => true,
                    'palabras_clave' => [],
                ]);
                \Log::info('[AgregarPrendaCompletaUseCase] TipoPrenda creado', ['nombre' => $nombreUpper]);
            }
        } catch (\Exception $e) {
            \Log::warning('[AgregarPrendaCompletaUseCase] Error creando TipoPrenda', [
                'error' => $e->getMessage(), 'nombre' => $nombrePrenda
            ]);
        }
    }

    /**
     * Crear tallas para prenda (replica PedidoWebService::crearTallasPrenda)
     * Maneja: SOBREMEDIDA {SOBREMEDIDA: {CABALLERO: 100, DAMA: 50}} y normal {DAMA: {S: 10, M: 20}}
     */
    private function crearTallasPrenda(PrendaPedido $prenda, array $cantidadTalla): void
    {
        foreach ($cantidadTalla as $generoOEspecial => $contenido) {
            if (!is_array($contenido) || empty($contenido)) {
                continue;
            }

            if (strtoupper($generoOEspecial) === 'SOBREMEDIDA') {
                // SOBREMEDIDA: {CABALLERO: 100, DAMA: 50}
                foreach ($contenido as $genero => $cantidad) {
                    if ((int)$cantidad > 0) {
                        PrendaPedidoTalla::create([
                            'prenda_pedido_id' => $prenda->id,
                            'genero' => strtoupper($genero),
                            'talla' => null,
                            'cantidad' => (int)$cantidad,
                            'es_sobremedida' => true,
                        ]);
                    }
                }
                \Log::info('[AgregarPrendaCompletaUseCase] Sobremedida creada', [
                    'prenda_id' => $prenda->id, 'generos_sobremedida' => count($contenido),
                ]);
            } else {
                // Normal: {S: 10, M: 20}
                foreach ($contenido as $talla => $cantidad) {
                    $cantidadInt = (int)($cantidad ?? 0);
                    if ($cantidadInt > 0) {
                        PrendaPedidoTalla::create([
                            'prenda_pedido_id' => $prenda->id,
                            'genero' => strtoupper($generoOEspecial),
                            'talla' => (string)$talla,
                            'cantidad' => $cantidadInt,
                        ]);
                    }
                }
            }
        }

        \Log::info('[AgregarPrendaCompletaUseCase] Tallas creadas', [
            'prenda_id' => $prenda->id, 'cantidad_generos' => count($cantidadTalla),
        ]);
    }

    /**
     * Crear telas (replica PedidoWebService::crearTelasDesdeFormulario)
     * Incluye: resolución IDs por nombre, fallback directo, creación automática de tela
     */
    private function crearTelasDesdeFormulario(PrendaPedido $prenda, array $telas, array $fotosTelaRutas = []): void
    {
        \Log::info('[AgregarPrendaCompletaUseCase] crearTelasDesdeFormulario INICIADA', [
            'prenda_id' => $prenda->id, 'telas_count' => count($telas),
        ]);

        $telasCreadasCount = 0;

        foreach ($telas as $telaIdx => $telaData) {
            // Si IDs ya están presentes y válidos, usar directamente
            if (isset($telaData['tela_id']) && isset($telaData['color_id']) &&
                $telaData['tela_id'] > 0 && $telaData['color_id'] > 0) {

                $colorTela = PrendaPedidoColorTela::create([
                    'prenda_pedido_id' => $prenda->id,
                    'color_id' => $telaData['color_id'],
                    'tela_id' => $telaData['tela_id'],
                    'referencia' => $telaData['referencia'] ?? null,
                    'observaciones' => $telaData['observaciones'] ?? null,
                ]);
                $telasCreadasCount++;
                $this->guardarFotoTela($colorTela, $telaIdx, $fotosTelaRutas);

                \Log::info('[AgregarPrendaCompletaUseCase] Tela creada (directo)', [
                    'prenda_id' => $prenda->id, 'tela_id' => $telaData['tela_id'], 'color_id' => $telaData['color_id'],
                ]);
            } else {
                // Resolver IDs por nombre usando ColorTelaService + fallback
                $telaId = null;
                $colorId = null;

                try {
                    $telaNombre = $telaData['tela'] ?? $telaData['tela_nombre'] ?? null;
                    if ($telaNombre) {
                        $colorTelaService = app(\App\Application\Services\ColorTelaService::class);
                        $telaId = $colorTelaService->obtenerOCrearTela($telaNombre);
                    }

                    $colorNombre = $telaData['color'] ?? $telaData['color_nombre'] ?? null;
                    if ($colorNombre && !empty($colorNombre)) {
                        $colorTelaService = app(\App\Application\Services\ColorTelaService::class);
                        $colorId = $colorTelaService->obtenerOCrearColor($colorNombre);
                    }
                } catch (\Exception $e) {
                    \Log::warning('[AgregarPrendaCompletaUseCase] ColorTelaService falló, usando fallback', [
                        'error' => $e->getMessage(),
                    ]);

                    // FALLBACK: búsqueda directa en BD (igual que PedidoWebService)
                    try {
                        $telaNombre = $telaData['tela'] ?? $telaData['tela_nombre'] ?? null;
                        if ($telaNombre && !$telaId) {
                            $tela = TelaPrenda::where('nombre', $telaNombre)->first();
                            if ($tela) $telaId = $tela->id;
                        }
                        $colorNombre = $telaData['color'] ?? $telaData['color_nombre'] ?? null;
                        if ($colorNombre && !empty($colorNombre) && !$colorId) {
                            $color = ColorPrenda::where('nombre', $colorNombre)->first();
                            if ($color) $colorId = $color->id;
                        }
                    } catch (\Exception $fallbackError) {
                        \Log::error('[AgregarPrendaCompletaUseCase] Error en fallback', [
                            'error' => $fallbackError->getMessage(),
                        ]);
                    }
                }

                // Si no hay telaId pero hay nombre, crear la tela (igual que PedidoWebService)
                $telaNombreGeneral = $telaData['nombre'] ?? $telaData['tela'] ?? $telaData['tela_nombre'] ?? null;
                if (!$telaId && !empty($telaNombreGeneral)) {
                    $telaExistente = TelaPrenda::where('nombre', $telaNombreGeneral)->where('activo', true)->first();
                    if ($telaExistente) {
                        $telaId = $telaExistente->id;
                    } else {
                        $telaPorDefecto = TelaPrenda::create([
                            'nombre' => $telaNombreGeneral ?: 'Tela Genérica',
                            'referencia' => 'GEN-' . time(),
                            'descripcion' => 'Tela creada automáticamente',
                            'activo' => true,
                        ]);
                        $telaId = $telaPorDefecto->id;
                        \Log::info('[AgregarPrendaCompletaUseCase] Tela nueva creada', [
                            'tela_nombre' => $telaPorDefecto->nombre, 'tela_id' => $telaId,
                        ]);
                    }
                }

                if ($telaId || $colorId) {
                    $colorTela = PrendaPedidoColorTela::create([
                        'prenda_pedido_id' => $prenda->id,
                        'color_id' => $colorId ?? null,
                        'tela_id' => $telaId ?? null,
                        'referencia' => $telaData['referencia'] ?? null,
                        'observaciones' => $telaData['observaciones'] ?? null,
                    ]);
                    $telasCreadasCount++;
                    $this->guardarFotoTela($colorTela, $telaIdx, $fotosTelaRutas);

                    \Log::info('[AgregarPrendaCompletaUseCase] Tela/Color registrado', [
                        'prenda_id' => $prenda->id, 'tela_id' => $telaId, 'color_id' => $colorId,
                    ]);
                }
            }
        }

        \Log::info('[AgregarPrendaCompletaUseCase] crearTelasDesdeFormulario TERMINADA', [
            'prenda_id' => $prenda->id, 'telas_creadas' => $telasCreadasCount,
        ]);
    }

    /**
     * Guardar foto de tela en PrendaFotoTelaPedido
     */
    private function guardarFotoTela(PrendaPedidoColorTela $colorTela, int $telaIdx, array $fotosTelaRutas): void
    {
        if (!empty($fotosTelaRutas) && isset($fotosTelaRutas[$telaIdx])) {
            $rutasTela = $fotosTelaRutas[$telaIdx];
            PrendaFotoTelaPedido::create([
                'prenda_pedido_colores_telas_id' => $colorTela->id,
                'ruta_original' => $rutasTela['ruta_original'] ?? null,
                'ruta_webp' => $rutasTela['ruta_webp'] ?? $rutasTela['ruta_original'] ?? null,
                'orden' => 1,
            ]);
        }
    }

    /**
     * Crear procesos completos (replica PedidoWebService::crearProcesosCompletos)
     * Incluye: deduplicación, datos_adicionales, tallas con SOBREMEDIDA
     */
    private function crearProcesosCompletos(PrendaPedido $prenda, array $procesos, array $fotosProcesoNuevo = []): void
    {
        \Log::info('[AgregarPrendaCompletaUseCase] crearProcesosCompletos INICIADA', [
            'prenda_id' => $prenda->id, 'procesos_count' => count($procesos),
        ]);

        foreach ($procesos as $procesoIdx => $proceso) {
            if (!is_array($proceso)) continue;

            // Resolver tipo_proceso_id
            $tipoProcesoId = $proceso['tipo_proceso_id'] ?? null;

            if (!$tipoProcesoId && isset($proceso['tipo'])) {
                $tipoProcesoModel = TipoProceso::where('slug', strtolower($proceso['tipo']))
                    ->orWhere('nombre', $proceso['tipo'])
                    ->first();
                if ($tipoProcesoModel) {
                    $tipoProcesoId = $tipoProcesoModel->id;
                } else {
                    \Log::warning('[AgregarPrendaCompletaUseCase] Tipo de proceso no encontrado', [
                        'tipo_buscado' => $proceso['tipo'], 'prenda_id' => $prenda->id,
                    ]);
                    continue;
                }
            }

            if (!$tipoProcesoId) continue;

            // Deduplicación: eliminar proceso existente del mismo tipo (igual que PedidoWebService)
            $procesoExistente = PedidosProcesosPrendaDetalle::where('prenda_pedido_id', $prenda->id)
                ->where('tipo_proceso_id', $tipoProcesoId)
                ->first();
            if ($procesoExistente) {
                $procesoExistente->delete();
                \Log::warning('[AgregarPrendaCompletaUseCase] Proceso duplicado eliminado', [
                    'prenda_id' => $prenda->id, 'tipo_proceso_id' => $tipoProcesoId,
                ]);
            }

            // Decodificar ubicaciones
            $ubicaciones = $proceso['ubicaciones'] ?? [];
            if (is_string($ubicaciones)) {
                $ubicaciones = json_decode($ubicaciones, true) ?? [];
            }
            if (!is_array($ubicaciones)) {
                $ubicaciones = is_string($ubicaciones) ? [$ubicaciones] : [];
            }

            $observaciones = $proceso['observaciones'] ?? null;
            if (is_string($observaciones)) {
                $observaciones = trim($observaciones);
                $observaciones = empty($observaciones) ? null : $observaciones;
            }

            // Crear proceso
            $procesoCreado = PedidosProcesosPrendaDetalle::create([
                'prenda_pedido_id' => $prenda->id,
                'tipo_proceso_id' => $tipoProcesoId,
                'ubicaciones' => !empty($ubicaciones) ? json_encode($ubicaciones) : json_encode([]),
                'observaciones' => $observaciones,
                'datos_adicionales' => json_encode($proceso),
                'estado' => $proceso['estado'] ?? 'PENDIENTE',
                'modo_tallas' => $proceso['modoTallas'] ?? 'generico',
            ]);

            // Crear tallas del proceso (maneja SOBREMEDIDA, datosExtendidos con ubicaciones/observaciones por talla)
            if (isset($proceso['tallas']) && is_array($proceso['tallas']) && !empty($proceso['tallas'])) {
                $datosExtendidos = $proceso['datosExtendidos'] ?? [];
                $this->crearTallasProceso($procesoCreado, $proceso['tallas'], $datosExtendidos);
            }

            // Agregar fotos del proceso
            if (!empty($fotosProcesoNuevo) && isset($fotosProcesoNuevo[$procesoIdx])) {
                foreach ($fotosProcesoNuevo[$procesoIdx] as $rutasFoto) {
                    $procesoCreado->imagenes()->create([
                        'ruta_original' => $rutasFoto['ruta_original'] ?? null,
                        'ruta_webp' => $rutasFoto['ruta_webp'] ?? $rutasFoto['ruta_original'] ?? null,
                        'orden' => 1,
                    ]);
                }
            }

            \Log::info('[AgregarPrendaCompletaUseCase] Proceso creado', [
                'prenda_id' => $prenda->id,
                'proceso_id' => $procesoCreado->id,
                'tipo_proceso_id' => $tipoProcesoId,
                'tipo' => $proceso['tipo'] ?? 'N/A',
            ]);
        }

        \Log::info('[AgregarPrendaCompletaUseCase] crearProcesosCompletos TERMINADA', [
            'prenda_id' => $prenda->id,
        ]);
    }

    /**
     * Crear tallas para proceso (replica PedidoWebService::crearTallasProceso)
     * Maneja: SOBREMEDIDA, géneros normales con generoMap, datosExtendidos (ubicaciones/observaciones por talla),
     * y formato TALLA__COLOR para crear registros en pedidos_procesos_prenda_talla_colores
     */
    private function crearTallasProceso(PedidosProcesosPrendaDetalle $proceso, array $tallas, array $datosExtendidos = []): void
    {
        $generoMap = ['dama' => 'DAMA', 'caballero' => 'CABALLERO', 'unisex' => 'UNISEX'];

        foreach ($tallas as $generoBD => $tallasCant) {
            if (!is_array($tallasCant) || empty($tallasCant)) {
                continue;
            }

            if (strtolower($generoBD) === 'sobremedida') {
                // SOBREMEDIDA: {CABALLERO: 100, DAMA: 50}
                foreach ($tallasCant as $generoParaSobremedida => $cantidad) {
                    $cantidad = (int)$cantidad;
                    if ($cantidad > 0) {
                        PedidosProcesosPrendaTalla::create([
                            'proceso_prenda_detalle_id' => $proceso->id,
                            'genero' => strtoupper($generoParaSobremedida),
                            'talla' => null,
                            'cantidad' => $cantidad,
                            'es_sobremedida' => true,
                        ]);
                    }
                }
            } else {
                // Normal: género con tallas
                $generoEnum = $generoMap[strtolower($generoBD)] ?? strtoupper($generoBD);

                foreach ($tallasCant as $tallaKey => $cantidad) {
                    $cantidad = (int)$cantidad;
                    if ($cantidad <= 0) continue;

                    // Separar talla y color si viene como "talla__color"
                    $partes = explode('__', (string)$tallaKey);
                    $tallaReal = $partes[0];
                    $colorNombre = isset($partes[1]) ? $partes[1] : null;

                    // Extraer ubicaciones y observaciones del datosExtendidos si existe
                    $ubicacionesTalla = null;
                    $observacionesTalla = null;

                    if (!empty($datosExtendidos)) {
                        $generoLower = strtolower($generoBD);
                        $tallaDatos = $datosExtendidos[$generoLower][$tallaKey] ?? null;

                        if ($tallaDatos) {
                            if (isset($tallaDatos['ubicaciones']) && !empty($tallaDatos['ubicaciones'])) {
                                $ubicacionesTalla = json_encode($tallaDatos['ubicaciones']);
                            }
                            if (isset($tallaDatos['observaciones'])) {
                                $observacionesTalla = $tallaDatos['observaciones'];
                            }
                        }
                    }

                    $tallaCreada = PedidosProcesosPrendaTalla::create([
                        'proceso_prenda_detalle_id' => $proceso->id,
                        'genero' => $generoEnum,
                        'talla' => (string)$tallaReal,
                        'cantidad' => $cantidad,
                        'ubicaciones' => $ubicacionesTalla,
                        'observaciones' => $observacionesTalla,
                    ]);

                    // Si hay color, insertar en pedidos_procesos_prenda_talla_colores
                    if (!empty($colorNombre)) {
                        DB::table('pedidos_procesos_prenda_talla_colores')->insert([
                            'pedidos_procesos_prenda_talla_id' => $tallaCreada->id,
                            'color_nombre' => $colorNombre,
                            'tela_nombre' => null,
                            'cantidad' => $cantidad,
                            'ubicaciones' => $ubicacionesTalla,
                            'observaciones' => $observacionesTalla,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    private function generarRutaWebp(string $rutaOriginal): string
    {
        return preg_replace('/\.[^.]+$/', '.webp', $rutaOriginal);
    }

    private function guardarNovedad(PrendaPedido $prenda, AgregarPrendaCompletaDTO $dto): void
    {
        if (is_null($dto->novedad) || empty(trim($dto->novedad))) {
            return;
        }

        $pedido = $prenda->pedidoProduccion;
        if (!$pedido) {
            \Log::warning('[AgregarPrendaCompletaUseCase] No se encontró pedido para prenda', [
                'prenda_id' => $prenda->id,
            ]);
            return;
        }

        $novedadesActuales = $pedido->novedades ?? '';

        $usuarioAutenticado = \Auth::user();
        $nombreAsesor = $usuarioAutenticado ? $usuarioAutenticado->name : 'Sistema';

        // Obtener el primer rol del usuario (usando Spatie Laravel-permission)
        if ($usuarioAutenticado && method_exists($usuarioAutenticado, 'roles')) {
            $rolAsesor = $usuarioAutenticado->roles()->first()?->name ?? 'Sistema';
        } else {
            $rolAsesor = 'Sistema';
        }

        $nuevaNovedad = trim($dto->novedad);
        $fechaHora = now()->format('d/m/Y h:i A');
        $rolLabel = ucfirst(str_replace('_', ' ', $rolAsesor));
        $nombrePrenda = $prenda->nombre_prenda ?? 'Sin nombre';
        $novedadConInfo = "{$rolLabel}-{$nombreAsesor}-{$fechaHora} - Agregó la prenda \"{$nombrePrenda}\" - {$nuevaNovedad}";

        $novedadesActualizadas = $novedadesActuales . ($novedadesActuales ? "\n\n" : "") . $novedadConInfo;

        $pedido->update([
            'novedades' => $novedadesActualizadas,
        ]);

        \Log::info('[AgregarPrendaCompletaUseCase] Novedad guardada', [
            'prenda_id' => $prenda->id,
            'pedido_id' => $pedido->id,
            'novedad' => $dto->novedad,
            'nombre_asesor' => $nombreAsesor,
        ]);

        // Crear notificación para supervisores
        try {
            \App\Models\News::create([
                'event_type' => 'prenda_agregada',
                'table_name' => 'prendas_pedido',
                'record_id' => $prenda->id,
                'description' => "{$rolLabel} {$nombreAsesor} agregó la prenda \"{$nombrePrenda}\" al Pedido #{$pedido->numero_pedido}",
                'user_id' => $usuarioAutenticado?->id,
                'pedido' => $pedido->numero_pedido,
                'metadata' => [
                    'tipo' => 'prenda_agregada',
                    'prenda_id' => $prenda->id,
                    'prenda_nombre' => $nombrePrenda,
                    'pedido_id' => $pedido->id,
                    'novedad' => $nuevaNovedad,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::warning('[AgregarPrendaCompletaUseCase] Error creando News', ['error' => $e->getMessage()]);
        }
    }
}


