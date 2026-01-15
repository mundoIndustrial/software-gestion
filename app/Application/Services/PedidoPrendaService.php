<?php

namespace App\Application\Services;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ColorPrenda;
use App\Models\TelaPrenda;
use App\Models\TipoManga;
use App\Helpers\DescripcionPrendaHelper;
use App\Helpers\DescripcionPrendaLegacyFormatter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * PedidoPrendaService
 * 
 * Responsabilidad: Guardar prendas de pedidos en tablas normalizadas
 * Equivalente a CotizacionPrendaService pero para pedidos
 * 
 * Cumple:
 * - SRP: Solo guarda prendas
 * - DIP: Inyecta dependencias
 * - OCP: Fácil de extender
 */
class PedidoPrendaService
{
    private ColorGeneroMangaBrocheService $colorGeneroService;

    public function __construct(ColorGeneroMangaBrocheService $colorGeneroService)
    {
        $this->colorGeneroService = $colorGeneroService;
    }

    /**
     * Guardar prendas en pedido
     */
    public function guardarPrendasEnPedido(PedidoProduccion $pedido, array $prendas): void
    {
        Log::info('📦 [PedidoPrendaService::guardarPrendasEnPedido] INICIO - Análisis completo', [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'cantidad_prendas' => count($prendas),
            'prendas_completas' => $prendas,
        ]);
        
        if (empty($prendas)) {
            Log::warning('⚠️ [PedidoPrendaService] No hay prendas para guardar', [
                'pedido_id' => $pedido->id,
            ]);
            return;
        }

        DB::beginTransaction();
        try {
            $index = 1;
            foreach ($prendas as $prendaIndex => $prendaData) {
                Log::info("📋 [PedidoPrendaService] Procesando prenda #{$index}", [
                    'prenda_index' => $prendaIndex,
                    'prenda_data_type' => gettype($prendaData),
                    'prenda_data' => $prendaData,
                    'tiene_telas' => isset($prendaData['telas']),
                    'cantidad_telas' => isset($prendaData['telas']) ? count($prendaData['telas']) : 0,
                ]);
                
                // CRÍTICO: Convertir DTO a array si es necesario
                if (is_object($prendaData) && method_exists($prendaData, 'toArray')) {
                    $prendaData = $prendaData->toArray();
                }
                
                $this->guardarPrenda($pedido, $prendaData, $index);
                $index++;
            }
            DB::commit();
            Log::info('✅ [PedidoPrendaService] Prendas guardadas correctamente', [
                'pedido_id' => $pedido->id,
                'cantidad' => count($prendas),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [PedidoPrendaService] Error guardando prendas', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Guardar una prenda con sus relaciones
     * Genera descripción formateada usando DescripcionPrendaLegacyFormatter
     * (Formato compatible con pedidos legacy como 45452)
     */
    private function guardarPrenda(PedidoProduccion $pedido, mixed $prendaData, int $index = 1): void
    {
        // DEFENSA: Convertir DTO a array si llega un objeto
        if (is_object($prendaData) && method_exists($prendaData, 'toArray')) {
            $prendaData = $prendaData->toArray();
        } elseif (is_object($prendaData)) {
            // Conversión forzada de objeto a array como último recurso
            $prendaData = (array)$prendaData;
        }
        
        // Validar que sea array después de conversión
        if (!is_array($prendaData)) {
            throw new \InvalidArgumentException(
                'guardarPrenda: prendaData debe ser un array o DTO con toArray(). Recibido: ' . gettype($prendaData)
            );
        }

        // 🔍 LOG: Ver qué datos llegan
        \Log::info('🔍 [PedidoPrendaService] Datos recibidos para guardar prenda', [
            'nombre_producto' => $prendaData['nombre_producto'] ?? null,
            'tela_id' => $prendaData['tela_id'] ?? null,
            'color_id' => $prendaData['color_id'] ?? null,
            'tipo_manga_id' => $prendaData['tipo_manga_id'] ?? null,
            'tipo_broche_id' => $prendaData['tipo_broche_id'] ?? null,
            'manga' => $prendaData['manga'] ?? null,
            'broche' => $prendaData['broche'] ?? null,
        ]);

        // ✅ PROCESAR VARIACIONES: Crear si no existen
        // Si recibimos nombres (strings) en lugar de IDs, crear o buscar
        
        // MANGA: Si viene nombre, crear/obtener; si viene ID, usar directamente
        if (!empty($prendaData['manga']) && empty($prendaData['tipo_manga_id'])) {
            $manga = $this->colorGeneroService->obtenerOCrearManga($prendaData['manga']);
            if ($manga) {
                $prendaData['tipo_manga_id'] = $manga->id;
                Log::info('✅ [PedidoPrendaService] Manga creada/obtenida', [
                    'nombre' => $prendaData['manga'],
                    'id' => $manga->id,
                ]);
            }
        }
        
        // BROCHE: Si viene nombre, crear/obtener; si viene ID, usar directamente
        if (!empty($prendaData['broche']) && empty($prendaData['tipo_broche_id'])) {
            $broche = $this->colorGeneroService->obtenerOCrearBroche($prendaData['broche']);
            if ($broche) {
                $prendaData['tipo_broche_id'] = $broche->id;
                Log::info('✅ [PedidoPrendaService] Broche creado/obtenido', [
                    'nombre' => $prendaData['broche'],
                    'id' => $broche->id,
                ]);
            }
        }

        // ✅ SOLO GUARDAR LA DESCRIPCIÓN QUE ESCRIBIÓ EL USUARIO
        // NO formatear ni armar descripciones automáticas
        $descripcionFinal = $prendaData['descripcion'] ?? '';
        
        // Obtener la PRIMERA tela de múltiples telas para los campos principales
        // (tela_id, color_id se guardan en la prenda para referencia rápida)
        $primeraTela = $this->obtenerPrimeraTela($prendaData);
        
        // 🔍 LOG: Antes de guardar
        \Log::info('✅ [PedidoPrendaService] Guardando prenda con observaciones', [
            'numero_pedido' => $pedido->numero_pedido,
            'nombre_prenda' => $prendaData['nombre_producto'] ?? 'Sin nombre',
            'genero' => $prendaData['genero'] ?? '',
            'descripcion_usuario' => $prendaData['descripcion'] ?? null,
            'manga_obs' => $prendaData['obs_manga'] ?? $prendaData['manga_obs'] ?? '',
            'bolsillos_obs' => $prendaData['obs_bolsillos'] ?? $prendaData['bolsillos_obs'] ?? '',
            'broche_obs' => $prendaData['obs_broche'] ?? $prendaData['broche_obs'] ?? '',
            'reflectivo_obs' => $prendaData['obs_reflectivo'] ?? $prendaData['reflectivo_obs'] ?? '',
            'tela_id_principal' => $primeraTela['tela_id'] ?? null,
            'color_id_principal' => $primeraTela['color_id'] ?? null,
            'total_telas' => !empty($prendaData['telas']) ? count($prendaData['telas']) : 0,
            'tipo_manga_id' => $prendaData['tipo_manga_id'] ?? null,
            'tipo_broche_id' => $prendaData['tipo_broche_id'] ?? null,
        ]);
        
        // ✅ PROCESAR GÉNEROS (puede ser single string o array de múltiples géneros)
        $generoProcesado = [];
        $generoInput = $prendaData['genero'] ?? '';
        
        if (is_array($generoInput)) {
            // Si ya es array, filtrar vacíos
            $generoProcesado = array_filter($generoInput, fn($g) => !empty($g));
        } elseif (is_string($generoInput)) {
            // Si es string, intentar decodificar JSON o usar directamente
            if (str_starts_with($generoInput, '[')) {
                $decoded = json_decode($generoInput, true);
                $generoProcesado = is_array($decoded) ? array_filter($decoded) : (!empty($generoInput) ? [$generoInput] : []);
            } else {
                $generoProcesado = !empty($generoInput) ? [$generoInput] : [];
            }
        }
        
        // ✅ PROCESAR CANTIDADES: Soportar múltiples géneros
        $cantidadTallaFinal = [];
        $cantidadesInput = $prendaData['cantidades'] ?? $prendaData['cantidades_por_genero'] ?? null;
        
        if ($cantidadesInput) {
            if (is_string($cantidadesInput)) {
                $cantidadesInput = json_decode($cantidadesInput, true) ?? [];
            }
            
            if (is_array($cantidadesInput)) {
                // Verificar si es estructura por género: {genero: {talla: cantidad}}
                $esEstructuraGenero = false;
                foreach ($cantidadesInput as $key => $valor) {
                    if (is_array($valor)) {
                        // Es probablemente {genero: {talla: cantidad}}
                        $esEstructuraGenero = true;
                        break;
                    }
                }
                
                $cantidadTallaFinal = $esEstructuraGenero ? $cantidadesInput : $cantidadesInput;
            }
        }
        
        // Crear prenda principal usando PrendaPedido (tabla correcta)
        // NOTA: 'cantidad' se calcula dinámicamente desde cantidad_talla via accessor
        $prenda = PrendaPedido::create([
            'numero_pedido' => $pedido->numero_pedido,
            'nombre_prenda' => $prendaData['nombre_producto'] ?? 'Sin nombre',
            'descripcion' => $descripcionFinal, // ✅ SOLO LA DESCRIPCIÓN DEL USUARIO
            'cantidad' => 0, // Será ignorado por el mutador, se calcula desde cantidad_talla
            'cantidad_talla' => !empty($cantidadTallaFinal) ? json_encode($cantidadTallaFinal) : '{}',
            'descripcion_variaciones' => $this->armarDescripcionVariaciones($prendaData),
            // ✅ GENERO (array de múltiples géneros)
            'genero' => json_encode($generoProcesado),
            // Campos de variaciones (se asigna la PRIMERA tela como referencia)
            'color_id' => $primeraTela['color_id'] ?? $prendaData['color_id'] ?? null,
            'tela_id' => $primeraTela['tela_id'] ?? $prendaData['tela_id'] ?? null,
            'tipo_manga_id' => $prendaData['tipo_manga_id'] ?? null,
            'tipo_broche_id' => $prendaData['tipo_broche_id'] ?? null,
            'tiene_bolsillos' => $prendaData['tiene_bolsillos'] ?? false,
            'tiene_reflectivo' => $prendaData['tiene_reflectivo'] ?? false,
            // ✅ NUEVOS CAMPOS: Observaciones de variaciones
            'manga_obs' => $prendaData['obs_manga'] ?? $prendaData['manga_obs'] ?? '',
            'bolsillos_obs' => $prendaData['obs_bolsillos'] ?? $prendaData['bolsillos_obs'] ?? '',
            'broche_obs' => $prendaData['obs_broche'] ?? $prendaData['broche_obs'] ?? '',
            'reflectivo_obs' => $prendaData['obs_reflectivo'] ?? $prendaData['reflectivo_obs'] ?? '',
            // ✅ NUEVO CAMPO: de_bodega (mapeo desde origen)
            'de_bodega' => (int)($prendaData['de_bodega'] ?? 1), // 1=bodega, 0=confeccion
        ]);

        // 🔍 LOG: Después de guardar
        \Log::info('✅ [PedidoPrendaService] Prenda guardada exitosamente', [
            'prenda_id' => $prenda->id,
            'numero_pedido' => $prenda->numero_pedido,
            'genero' => $prenda->genero,
            'cantidad_dinamica' => $prenda->cantidad, // Ahora usa el accessor
            'cantidad_talla_guardada' => $prenda->cantidad_talla,
            'tela_id_guardado' => $prenda->tela_id,
            'color_id_guardado' => $prenda->color_id,
            'tipo_manga_id_guardado' => $prenda->tipo_manga_id,
            'tipo_broche_id_guardado' => $prenda->tipo_broche_id,
            'manga_obs_guardado' => $prenda->manga_obs,
            'bolsillos_obs_guardado' => $prenda->bolsillos_obs,
            'broche_obs_guardado' => $prenda->broche_obs,
            'reflectivo_obs_guardado' => $prenda->reflectivo_obs,
            'de_bodega_guardado' => $prenda->de_bodega, // ✅ NUEVO: Registrar de_bodega
        ]);

        // 2. ✅ GUARDAR TALLAS CON CANTIDADES en prenda_tallas_ped
        if (!empty($prendaData['cantidades'])) {
            $this->guardarTallasPrenda($prenda, $prendaData['cantidades']);
        }

        // 3. Guardar fotos de la prenda (copiar URLs de cotización)
        if (!empty($prendaData['fotos'])) {
            $this->guardarFotosPrenda($prenda, $prendaData['fotos']);
        }

        // 4. Guardar logos de la prenda (si existen)
        if (!empty($prendaData['logos'])) {
            $this->guardarLogosPrenda($prenda, $prendaData['logos']);
        }

        // 5. Guardar fotos de telas/colores (si existen)
        Log::info('🔍 [PedidoPrendaService::guardarPrenda] Verificando si hay telas para guardar', [
            'prenda_id' => $prenda->id,
            'tiene_telas' => !empty($prendaData['telas']),
            'cantidad_telas' => !empty($prendaData['telas']) ? count($prendaData['telas']) : 0,
            'telas_data' => $prendaData['telas'] ?? null,
        ]);
        
        if (!empty($prendaData['telas'])) {
            $this->guardarFotosTelas($prenda, $prendaData['telas']);
        } else {
            Log::warning('⚠️ [PedidoPrendaService] No hay telas para guardar en esta prenda', [
                'prenda_id' => $prenda->id,
                'prenda_data_keys' => array_keys($prendaData),
            ]);
        }

        // 6. ✅ NUEVO: Guardar procesos de la prenda (si existen)
        Log::info('🔍 [PedidoPrendaService::guardarPrenda] Verificando si hay procesos para guardar', [
            'prenda_id' => $prenda->id,
            'tiene_procesos' => !empty($prendaData['procesos']),
            'cantidad_procesos' => !empty($prendaData['procesos']) ? count($prendaData['procesos']) : 0,
            'procesos_data' => $prendaData['procesos'] ?? null,
        ]);
        
        if (!empty($prendaData['procesos'])) {
            $this->guardarProcesosPrenda($prenda, $prendaData['procesos']);
        } else {
            Log::info('ℹ️ [PedidoPrendaService] No hay procesos para guardar en esta prenda', [
                'prenda_id' => $prenda->id,
            ]);
        }
    }

    /**
     * Construir array de datos formateado para el DescripcionPrendaLegacyFormatter
     * Convierte los datos del frontend a la estructura esperada por el formatter
     */
    private function obtenerPrimeraTela(array $prendaData): array
    {
        // Si hay un array de telas, obtener la primera
        if (!empty($prendaData['telas']) && is_array($prendaData['telas'])) {
            $primeraTela = reset($prendaData['telas']);
            if (is_array($primeraTela)) {
                return [
                    'tela_id' => $primeraTela['tela_id'] ?? null,
                    'color_id' => $primeraTela['color_id'] ?? null,
                ];
            }
        }
        
        // Si no hay telas múltiples, usar los campos de variantes individuales
        return [
            'tela_id' => $prendaData['tela_id'] ?? null,
            'color_id' => $prendaData['color_id'] ?? null,
        ];
    }

    /**
     * Construir array de datos formateado para el DescripcionPrendaLegacyFormatter
    {
        // Obtener relaciones si están disponibles, sino buscar en BD
        $color = '';
        if ($prendaData['color_id'] ?? null) {
            $colorObj = ColorPrenda::find($prendaData['color_id']);
            $color = $colorObj?->nombre ?? '';
        }
        
        $tela = '';
        $ref = '';
        if ($prendaData['tela_id'] ?? null) {
            $telaObj = TelaPrenda::find($prendaData['tela_id']);
            if ($telaObj) {
                $tela = $telaObj->nombre ?? '';
                $ref = $telaObj->referencia ? $telaObj->referencia : '';
            }
        }
        
        $manga = '';
        if ($prendaData['tipo_manga_id'] ?? null) {
            $mangaObj = TipoManga::find($prendaData['tipo_manga_id']);
            $manga = $mangaObj?->nombre ?? '';
        }
        
        // Parsear tallas desde cantidades
        $tallas = [];
        if (is_array($prendaData['cantidades'] ?? null)) {
            $tallas = $prendaData['cantidades'];
        } elseif (is_string($prendaData['cantidades'] ?? null)) {
            try {
                $tallas = json_decode($prendaData['cantidades'], true) ?? [];
            } catch (\Exception $e) {
                $tallas = [];
            }
        }
        
        return [
            'numero' => $index,
            'tipo' => $prendaData['nombre_producto'] ?? '',
            'descripcion' => $prendaData['descripcion'] ?? '', // La descripción es el logo/detalles
            'tela' => $tela,
            'ref' => $ref,
            'color' => $color,
            'manga' => $manga,
            'tiene_bolsillos' => $prendaData['tiene_bolsillos'] ?? false,
            'bolsillos_obs' => $prendaData['bolsillos_obs'] ?? '',
            'tiene_reflectivo' => $prendaData['tiene_reflectivo'] ?? false,
            'reflectivo_obs' => $prendaData['reflectivo_obs'] ?? '',
            'broche_obs' => $prendaData['broche_obs'] ?? '',
            'tallas' => $tallas,
        ];
    }

    /**
     * Armar descripción de variaciones a partir de los datos
     */
    private function armarDescripcionVariaciones(array $prendaData): ?string
    {
        $partes = [];
        
        if (!empty($prendaData['manga'])) {
            $partes[] = "Manga: " . $prendaData['manga'];
        }
        if (!empty($prendaData['manga_obs'])) {
            $partes[] = "Obs Manga: " . $prendaData['manga_obs'];
        }
        if (!empty($prendaData['bolsillos_obs'])) {
            $partes[] = "Bolsillos: " . $prendaData['bolsillos_obs'];
        }
        if (!empty($prendaData['broche'])) {
            $partes[] = "Broche: " . $prendaData['broche'];
        }
        if (!empty($prendaData['reflectivo_obs'])) {
            $partes[] = "Reflectivo: " . $prendaData['reflectivo_obs'];
        }
        
        return !empty($partes) ? implode(" | ", $partes) : null;
    }

    /**
     * Guardar fotos de la prenda 
     * Espera rutas de archivos ya procesados desde el endpoint de upload
     */
    private function guardarFotosPrenda(PrendaPedido $prenda, array $fotos): void
    {
        foreach ($fotos as $index => $foto) {
            // Las fotos pueden venir como strings (rutas JSON) o arrays
            if (is_string($foto)) {
                $fotoData = json_decode($foto, true);
                if (!is_array($fotoData)) {
                    $fotoData = ['ruta_original' => $foto];
                }
            } else {
                $fotoData = $foto;
            }
            
            DB::table('prenda_fotos_pedido')->insert([
                'prenda_pedido_id' => $prenda->id,
                'ruta_original' => $fotoData['ruta_original'] ?? null,
                'ruta_webp' => $fotoData['ruta_webp'] ?? null,
                'ruta_miniatura' => $fotoData['ruta_miniatura'] ?? null,
                'orden' => $index + 1,
                'ancho' => $fotoData['ancho'] ?? null,
                'alto' => $fotoData['alto'] ?? null,
                'tamaño' => $fotoData['tamaño'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Guardar logos de la prenda
     */
    private function guardarLogosPrenda(PrendaPedido $prenda, array $logos): void
    {
        foreach ($logos as $index => $logo) {
            DB::table('prenda_fotos_logo_pedido')->insert([
                'prenda_pedido_id' => $prenda->id,
                'ruta_original' => $logo['ruta_original'] ?? $logo['url'] ?? null,
                'ruta_webp' => $logo['ruta_webp'] ?? null,
                'ruta_miniatura' => $logo['ruta_miniatura'] ?? null,
                'orden' => $index + 1,
                'ubicacion' => $logo['ubicacion'] ?? null,
                'ancho' => $logo['ancho'] ?? null,
                'alto' => $logo['alto'] ?? null,
                'tamaño' => $logo['tamaño'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Guardar fotos de telas seleccionadas
     */
    private function guardarFotosTelas(PrendaPedido $prenda, array $telas): void
    {
        Log::info('🧵 [PedidoPrendaService] guardarFotosTelas - Iniciando', [
            'prenda_id' => $prenda->id,
            'numero_pedido' => $prenda->numero_pedido,
            'cantidad_telas' => count($telas),
            'telas_data' => $telas,
        ]);

        foreach ($telas as $telaIndex => $tela) {
            Log::info('🧵 [PedidoPrendaService] Procesando tela', [
                'tela_index' => $telaIndex,
                'tela_data' => $tela,
                'tiene_fotos' => !empty($tela['fotos']),
                'cantidad_fotos' => !empty($tela['fotos']) ? count($tela['fotos']) : 0,
            ]);
            
            if (!empty($tela['fotos'])) {
                foreach ($tela['fotos'] as $index => $foto) {
                    Log::info('📸 [PedidoPrendaService] Guardando foto de tela en prenda_fotos_tela_pedido', [
                        'prenda_pedido_id' => $prenda->id,
                        'tela_index' => $telaIndex,
                        'foto_index' => $index,
                        'foto_data' => $foto,
                        'tela_id' => $tela['tela_id'] ?? null,
                        'color_id' => $tela['color_id'] ?? null,
                        'ruta' => $foto['ruta_original'] ?? $foto['url'] ?? null,
                    ]);
                    
                    DB::table('prenda_fotos_tela_pedido')->insert([
                        'prenda_pedido_id' => $prenda->id,
                        'tela_id' => $tela['tela_id'] ?? null,
                        'color_id' => $tela['color_id'] ?? null,
                        'ruta_original' => $foto['ruta_original'] ?? $foto['url'] ?? null,
                        'ruta_webp' => $foto['ruta_webp'] ?? null,
                        'ruta_miniatura' => $foto['ruta_miniatura'] ?? null,
                        'orden' => $index + 1,
                        'ancho' => $foto['ancho'] ?? null,
                        'alto' => $foto['alto'] ?? null,
                        'tamaño' => $foto['tamaño'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info('✅ [PedidoPrendaService] Foto de tela guardada en BD (prenda_fotos_tela_pedido)', [
                        'prenda_pedido_id' => $prenda->id,
                        'tela_id' => $tela['tela_id'] ?? null,
                        'color_id' => $tela['color_id'] ?? null,
                        'orden' => $index + 1,
                        'ruta' => $foto['ruta_original'] ?? $foto['url'] ?? null,
                    ]);
                }
            } else {
                Log::warning('⚠️ [PedidoPrendaService] Tela sin fotos', [
                    'tela_index' => $telaIndex,
                    'tela_id' => $tela['tela_id'] ?? null,
                    'color_id' => $tela['color_id'] ?? null,
                ]);
            }
        }
        
        Log::info('🧵 [PedidoPrendaService] guardarFotosTelas - Completado', [
            'prenda_id' => $prenda->id,
            'telas_procesadas' => count($telas),
        ]);
    }

    /**
     * Copiar imágenes de la cotización al pedido
     * 
     * Cuando se convierte una cotización a pedido, copia las URLs de las imágenes
     * sin duplicar archivos en storage (solo copia las rutas)
     */
    public function copiarImagenesDeCotizacion(PedidoProduccion $pedido, int $cotizacionId): void
    {
        try {
            // Obtener prendas de la cotización
            $prendasCot = DB::table('prendas_cot')
                ->where('cotizacion_id', $cotizacionId)
                ->get();

            if ($prendasCot->isEmpty()) {
                Log::warning('PedidoPrendaService: No hay prendas en cotización para copiar', [
                    'cotizacion_id' => $cotizacionId,
                    'pedido_id' => $pedido->id,
                ]);
                return;
            }

            DB::beginTransaction();
            try {
                foreach ($prendasCot as $prendaCot) {
                    // Obtener la prenda correspondiente en el pedido
                    $prendaPedido = PrendaPedido::where('numero_pedido', $pedido->numero_pedido)
                        ->orderBy('id')
                        ->first();

                    if (!$prendaPedido) {
                        continue; // Saltar si no hay prenda correspondiente
                    }

                    // 1. Copiar fotos de prendas
                    $this->copiarFotosPrendaDeCotizacion($prendaPedido, $prendaCot->id);

                    // 2. Copiar fotos de telas
                    $this->copiarFotosTelasDeCotizacion($prendaPedido, $prendaCot->id);

                    // 3. Copiar logos (si existen)
                    $this->copiarLogosDePrenadaDeCotizacion($prendaPedido, $prendaCot->id);
                }

                DB::commit();
                Log::info('PedidoPrendaService: Imágenes copiadas de cotización a pedido', [
                    'cotizacion_id' => $cotizacionId,
                    'pedido_id' => $pedido->id,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('PedidoPrendaService: Error copiando imágenes de cotización', [
                'cotizacion_id' => $cotizacionId,
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);
            // No lanzar excepción, solo registrar el error
        }
    }

    /**
     * Copiar fotos de prenda desde cotización al pedido
     */
    private function copiarFotosPrendaDeCotizacion(PrendaPedido $prendaPedido, int $prendaCotId): void
    {
        $fotosCot = DB::table('prenda_fotos_cot')
            ->where('prenda_cot_id', $prendaCotId)
            ->orderBy('orden')
            ->get();

        foreach ($fotosCot as $foto) {
            DB::table('prenda_fotos_pedido')->insert([
                'prenda_pedido_id' => $prendaPedido->id,
                'ruta_original' => $foto->ruta_original,
                'ruta_webp' => $foto->ruta_webp,
                'ruta_miniatura' => $foto->ruta_miniatura,
                'orden' => $foto->orden,
                'ancho' => $foto->ancho,
                'alto' => $foto->alto,
                'tamaño' => $foto->tamaño,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Log::info('PedidoPrendaService: Fotos de prenda copiadas', [
            'prenda_pedido_id' => $prendaPedido->id,
            'prenda_cot_id' => $prendaCotId,
            'cantidad' => $fotosCot->count(),
        ]);
    }

    /**
     * Copiar fotos de telas desde cotización al pedido
     */
    private function copiarFotosTelasDeCotizacion(PrendaPedido $prendaPedido, int $prendaCotId): void
    {
        $fotosTelaCot = DB::table('prenda_tela_fotos_cot')
            ->where('prenda_cot_id', $prendaCotId)
            ->orderBy('orden')
            ->get();

        foreach ($fotosTelaCot as $foto) {
            DB::table('prenda_fotos_tela_pedido')->insert([
                'prenda_pedido_id' => $prendaPedido->id,
                'tela_id' => null, // No se copia tela_id, solo las fotos
                'color_id' => null,
                'ruta_original' => $foto->ruta_original,
                'ruta_webp' => $foto->ruta_webp,
                'ruta_miniatura' => $foto->ruta_miniatura,
                'orden' => $foto->orden,
                'ancho' => $foto->ancho,
                'alto' => $foto->alto,
                'tamaño' => $foto->tamaño,
                'observaciones' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Log::info('PedidoPrendaService: Fotos de telas copiadas', [
            'prenda_pedido_id' => $prendaPedido->id,
            'prenda_cot_id' => $prendaCotId,
            'cantidad' => $fotosTelaCot->count(),
        ]);
    }

    /**
     * Copiar logos de prenda desde cotización al pedido
     */
    private function copiarLogosDePrenadaDeCotizacion(PrendaPedido $prendaPedido, int $prendaCotId): void
    {
        // Buscar logo cotización asociado a la prenda
        $logosCot = DB::table('logo_fotos_cot')
            ->join('logo_cotizacion', 'logo_fotos_cot.logo_cotizacion_id', '=', 'logo_cotizacion.id')
            ->where('logo_cotizacion.prenda_cot_id', $prendaCotId)
            ->select('logo_fotos_cot.*')
            ->orderBy('logo_fotos_cot.orden')
            ->get();

        foreach ($logosCot as $logo) {
            DB::table('prenda_fotos_logo_pedido')->insert([
                'prenda_pedido_id' => $prendaPedido->id,
                'ruta_original' => $logo->ruta_original,
                'ruta_webp' => $logo->ruta_webp,
                'ruta_miniatura' => $logo->ruta_miniatura,
                'orden' => $logo->orden,
                'ubicacion' => null, // Se puede llenar después si es necesario
                'ancho' => $logo->ancho,
                'alto' => $logo->alto,
                'tamaño' => $logo->tamaño,
                'observaciones' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($logosCot->isNotEmpty()) {
            Log::info('PedidoPrendaService: Logos de prenda copiados', [
                'prenda_pedido_id' => $prendaPedido->id,
                'prenda_cot_id' => $prendaCotId,
                'cantidad' => $logosCot->count(),
            ]);
        }
    }

    /**
     * Guardar tallas con cantidades en prenda_tallas_ped
     * Puede recibir:
     * - Array asociativo: ['S' => 10, 'M' => 20, 'L' => 15]
     * - String JSON: '{"S":10,"M":20,"L":15}'
     * - String simple: 'S, M, L'
     */
    private function guardarTallasPrenda(PrendaPedido $prenda, mixed $cantidades): void
    {
        try {
            $tallasCantidades = [];

            // Parsear según el tipo de dato recibido
            if (is_array($cantidades)) {
                $tallasCantidades = $cantidades;
            } elseif (is_string($cantidades)) {
                // Intentar parsear como JSON
                if (str_starts_with(trim($cantidades), '{') || str_starts_with(trim($cantidades), '[')) {
                    $tallasCantidades = json_decode($cantidades, true) ?? [];
                } else {
                    // Si es una lista separada por comas, crear array con cantidad 1
                    $tallas = array_map('trim', explode(',', $cantidades));
                    $tallasCantidades = array_fill_keys($tallas, 1);
                }
            }

            if (empty($tallasCantidades)) {
                Log::info('ℹ️ [PedidoPrendaService::guardarTallasPrenda] No hay tallas para guardar', [
                    'prenda_ped_id' => $prenda->id,
                ]);
                return;
            }

            // Guardar cada talla con su cantidad
            $registros = [];
            foreach ($tallasCantidades as $talla => $cantidad) {
                if ($talla && $cantidad > 0) {
                    $registros[] = [
                        'prenda_ped_id' => $prenda->id,
                        'talla' => (string)$talla,
                        'cantidad' => (int)$cantidad,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($registros)) {
                \App\Models\PrendaTalaPed::insert($registros);
                
                Log::info('✅ [PedidoPrendaService::guardarTallasPrenda] Tallas guardadas correctamente', [
                    'prenda_ped_id' => $prenda->id,
                    'total_tallas' => count($registros),
                    'tallas' => array_keys($tallasCantidades),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ [PedidoPrendaService::guardarTallasPrenda] Error al guardar tallas', [
                'prenda_ped_id' => $prenda->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Guardar procesos de la prenda con sus tallas y ubicaciones
     * Estructura esperada: 
     * {
     *   'reflectivo': {
     *     'tipo': 'reflectivo',
     *     'ubicaciones': ['frente', 'espalda'],
     *     'observaciones': 'Aplicar en tiras de 5cm',
     *     'tallas': {
     *       'dama': {'S': 40, 'M': 30, 'L': 30},
     *       'caballero': {'M': 20}
     *     }
     *   }
     * }
     */
    private function guardarProcesosPrenda(PrendaPedido $prenda, array $procesos): void
    {
        Log::info('📋 [PedidoPrendaService::guardarProcesosPrenda] INICIO - Guardando procesos', [
            'prenda_id' => $prenda->id,
            'numero_pedido' => $prenda->numero_pedido,
            'cantidad_procesos' => count($procesos),
            'procesos_tipos' => array_keys($procesos),
        ]);

        try {
            foreach ($procesos as $tipoProceso => $procesoData) {
                Log::info("📋 [PedidoPrendaService] Procesando tipo: {$tipoProceso}", [
                    'prenda_id' => $prenda->id,
                    'tipo_proceso' => $tipoProceso,
                    'proceso_data_keys' => array_keys($procesoData),
                    'tiene_tipo' => isset($procesoData['tipo']),
                    'tiene_datos' => isset($procesoData['datos']),
                ]);

                // Extraer datos - pueden venir en .datos o directamente
                $datosProc = $procesoData;
                if (isset($procesoData['datos']) && is_array($procesoData['datos'])) {
                    $datosProc = $procesoData['datos'];
                    Log::info("📋 [PedidoPrendaService] Datos encontrados en .datos", ['tipo_proceso' => $tipoProceso]);
                }

                // Validar que tenga los campos requeridos
                if (empty($datosProc['tipo'])) {
                    Log::warning("⚠️ [PedidoPrendaService] Proceso sin tipo, saltando", [
                        'prenda_id' => $prenda->id,
                        'tipo_proceso' => $tipoProceso,
                    ]);
                    continue;
                }

                // Buscar el tipo_proceso_id en la base de datos
                $tipoProcesoId = DB::table('tipos_procesos')
                    ->where('nombre', 'like', "%{$datosProc['tipo']}%")
                    ->value('id');

                if (!$tipoProcesoId) {
                    Log::warning("⚠️ [PedidoPrendaService] No encontró tipo_proceso_id para: {$datosProc['tipo']}", [
                        'prenda_id' => $prenda->id,
                        'tipo_buscado' => $datosProc['tipo'],
                    ]);
                    // Crear el tipo de proceso si no existe
                    $tipoProcesoId = DB::table('tipos_procesos')->insertGetId([
                        'nombre' => $datosProc['tipo'],
                        'descripcion' => "Proceso: {$datosProc['tipo']}",
                        'activo' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Log::info("✅ [PedidoPrendaService] Tipo de proceso creado automáticamente", [
                        'tipo_proceso_id' => $tipoProcesoId,
                        'nombre' => $datosProc['tipo'],
                    ]);
                }

                // Preparar datos para insertar
                $tallasDama = $datosProc['tallas']['dama'] ?? [];
                $tallasCapallero = $datosProc['tallas']['caballero'] ?? [];
                $ubicaciones = $datosProc['ubicaciones'] ?? [];
                $observaciones = $datosProc['observaciones'] ?? '';
                $imagenes = $datosProc['imagenes'] ?? [];

                Log::info("📍 [PedidoPrendaService] Datos del proceso antes de guardar", [
                    'prenda_id' => $prenda->id,
                    'tipo_proceso_id' => $tipoProcesoId,
                    'tallas_dama' => $tallasDama,
                    'tallas_caballero' => $tallasCapallero,
                    'ubicaciones' => $ubicaciones,
                    'observaciones' => $observaciones,
                    'cantidad_imagenes' => count($imagenes),
                ]);

                // Insertar en la tabla pedidos_procesos_prenda_detalles
                $procesoDetalleId = DB::table('pedidos_procesos_prenda_detalles')->insertGetId([
                    'prenda_pedido_id' => $prenda->id,
                    'tipo_proceso_id' => $tipoProcesoId,
                    'ubicaciones' => json_encode($ubicaciones),
                    'observaciones' => $observaciones,
                    'tallas_dama' => json_encode($tallasDama),
                    'tallas_caballero' => json_encode($tallasCapallero),
                    'estado' => 'PENDIENTE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info("✅ [PedidoPrendaService] Proceso guardado en BD", [
                    'prenda_id' => $prenda->id,
                    'tipo_proceso_id' => $tipoProcesoId,
                    'tipo_proceso' => $datosProc['tipo'],
                    'proceso_detalle_id' => $procesoDetalleId,
                ]);

                // Guardar imágenes si existen
                if (!empty($imagenes) && is_array($imagenes)) {
                    $this->guardarProcesosImagenes($procesoDetalleId, $imagenes);
                }
            }

            Log::info('✅ [PedidoPrendaService::guardarProcesosPrenda] Todos los procesos guardados', [
                'prenda_id' => $prenda->id,
                'cantidad_procesos' => count($procesos),
            ]);
        } catch (\Exception $e) {
            Log::error('❌ [PedidoPrendaService::guardarProcesosPrenda] Error guardando procesos', [
                'prenda_id' => $prenda->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Guardar imágenes de procesos
     */
    private function guardarProcesosImagenes(int $procesoDetalleId, array $imagenes): void
    {
        Log::info('📸 [PedidoPrendaService::guardarProcesosImagenes] Guardando imágenes', [
            'proceso_detalle_id' => $procesoDetalleId,
            'cantidad' => count($imagenes),
        ]);

        foreach ($imagenes as $index => $imagenData) {
            try {
                // Si es un data URI, guardar como archivo base64
                if (is_string($imagenData) && strpos($imagenData, 'data:image') === 0) {
                    $nombreArchivo = $this->guardarImagenBase64($imagenData, $procesoDetalleId, $index);
                    
                    DB::table('pedidos_procesos_imagenes')->insert([
                        'proceso_prenda_detalle_id' => $procesoDetalleId,
                        'ruta' => $nombreArchivo,
                        'nombre_original' => "imagen_proceso_{$index}.png",
                        'tipo_mime' => 'image/png',
                        'orden' => $index,
                        'es_principal' => $index === 0 ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info("✅ [PedidoPrendaService] Imagen guardada", [
                        'proceso_detalle_id' => $procesoDetalleId,
                        'index' => $index,
                        'nombre' => $nombreArchivo,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning("⚠️ [PedidoPrendaService] Error guardando imagen", [
                    'proceso_detalle_id' => $procesoDetalleId,
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Guardar imagen base64 en disco
     */
    private function guardarImagenBase64(string $imagenData, int $procesoDetalleId, int $index): string
    {
        // Extraer el contenido base64
        if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $imagenData, $matches)) {
            $extension = $matches[1];
            $contenido = base64_decode($matches[2]);
            
            $directorio = storage_path('app/procesos-imagenes/' . $procesoDetalleId);
            if (!is_dir($directorio)) {
                mkdir($directorio, 0755, true);
            }

            $nombreArchivo = "imagen_{$index}_{$procesoDetalleId}.{$extension}";
            $rutaCompleta = $directorio . '/' . $nombreArchivo;
            
            file_put_contents($rutaCompleta, $contenido);
            
            return "procesos-imagenes/{$procesoDetalleId}/{$nombreArchivo}";
        }

        throw new \Exception('No se pudo procesar imagen base64');
    }
}
