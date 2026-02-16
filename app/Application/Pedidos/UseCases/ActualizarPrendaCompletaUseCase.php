<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarPrendaCompletaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\PrendaPedido;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\TipoProceso;

/**
 * Use Case para actualizar una prenda y fotos
 * 
 * REFACTORIZADO: FASE 3 - Validaciones centralizadas
 * 
 * Responsabilidades:
 * - Validar prenda existe  TRAIT
 * - Actualizar registro en prendas_pedido (nombre, descripción, de_bodega)
 * - Actualizar fotos de referencia (prenda_fotos_pedido)
 * 
 * Responsabilidades SEPARADAS en otros Use Cases:
 * - Actualizar variantes â†’ ActualizarVariantePrendaUseCase
 * - Actualizar colores y telas â†’ ActualizarColorTelaUseCase
 * - Actualizar tallas â†’ ActualizarTallaPrendaUseCase
 * - Actualizar procesos â†’ ActualizarProcesoPrendaUseCase
 * 
 * Antes: 70 lÃ­neas | DespuÃ©s: ~60 lÃ­neas | Reducción: ~14%
 */
final class ActualizarPrendaCompletaUseCase
{
    use ManejaPedidosUseCase;

    public function ejecutar(ActualizarPrendaCompletaDTO $dto): PrendaPedido
    {
        // CENTRALIZADO: Validar prenda existe (trait)
        $prenda = PrendaPedido::find($dto->prendaId);
        
        $this->validarObjetoExiste(
            $prenda,
            'Prenda',
            $dto->prendaId
        );

        \Log::info('[ActualizarPrendaCompletaUseCase] Iniciando actualizacion', [
            'prenda_id' => $dto->prendaId,
            'tiene_variantes' => !is_null($dto->variantes),
            'tiene_colores_telas' => !is_null($dto->coloresTelas),
            'tiene_fotos_telas' => !is_null($dto->fotosTelas),
            'tiene_fotos' => !is_null($dto->fotos),
            'tiene_tallas' => !is_null($dto->cantidadTalla),
            'tiene_imagenes_a_eliminar' => !is_null($dto->imagenesAEliminar),
            'cantidad_imagenes_a_eliminar' => is_array($dto->imagenesAEliminar) ? count($dto->imagenesAEliminar) : 0,
        ]);

        // 1. Actualizar campos bÃ¡sicos
        $this->actualizarCamposBasicos($prenda, $dto);

        // 2. Actualizar fotos de referencia
        $this->actualizarFotos($prenda, $dto);
        // 2.5. Eliminar imágenes marcadas para eliminación
        if (!is_null($dto->imagenesAEliminar)) {
            $this->eliminarImagenes($dto->imagenesAEliminar);
        }
        // 3. Actualizar tallas
        $this->actualizarTallas($prenda, $dto);

        // 3.5. Actualizar asignaciones de colores por talla (prenda_pedido_talla_colores)
        $this->actualizarAsignacionesColores($prenda, $dto);

        // 4. Actualizar variantes (manga, broche, bolsillos)
        $this->actualizarVariantes($prenda, $dto);

        // 5. Actualizar colores y telas
        $this->actualizarColoresTelas($prenda, $dto);

        // 6. Actualizar fotos de telas
        $this->actualizarFotosTelas($prenda, $dto);

        // 7. Actualizar procesos y sus imÃ¡genes
        $this->actualizarProcesos($prenda, $dto);
        // 8. Guardar novedad en pedido_produccion
        $this->guardarNovedad($prenda, $dto);
        // CARGAR RELACIONES COMPLETAS PARA EL FRONTEND
        $prenda->refresh();
        
        // Garantizar que procesos sea siempre un array (incluso si está vacío)
        if (!$prenda->relationLoaded('procesos')) {
            $prenda->load('procesos');
        }
        
        //  FIX CRÍTICO: Cargar fotos (imágenes) para que la respuesta JSON las incluya
        // Esto es necesario para que el frontend pueda sincronizar el snapshot después de guardar
        if (!$prenda->relationLoaded('fotos')) {
            $prenda->load('fotos');
        }
        
        return $prenda;
    }

    private function actualizarCamposBasicos(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        $datosActualizar = [];
        
        if ($dto->nombrePrenda !== null) {
            $datosActualizar['nombre_prenda'] = $dto->nombrePrenda;
        }
        
        if ($dto->descripcion !== null) {
            $datosActualizar['descripcion'] = $dto->descripcion;
        }
        
        if ($dto->deBodega !== null) {
            $datosActualizar['de_bodega'] = $dto->deBodega;
        }

        \Log::info('[ActualizarPrendaCompletaUseCase] Actualizando campos básicos', [
            'prenda_id' => $prenda->id,
            'datos_a_actualizar' => $datosActualizar,
            'de_bodega_tipo' => gettype($dto->deBodega),
            'de_bodega_valor' => $dto->deBodega,
            'de_bodega_es_null' => is_null($dto->deBodega),
        ]);

        if (!empty($datosActualizar)) {
            $prenda->update($datosActualizar);
            // Recargar desde BD para asegurar que tiene el valor guardado
            $prenda->refresh();
            
            \Log::info('[ActualizarPrendaCompletaUseCase] Campos básicos actualizados', [
                'prenda_id' => $prenda->id,
                'de_bodega_guardado_en_bd' => $prenda->de_bodega,
                'de_bodega_tipo' => gettype($prenda->de_bodega),
                'de_bodega_entero' => (int) $prenda->de_bodega,
            ]);
        }
    }

    private function actualizarFotos(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        //  DEBUG: Log detallado de lo que se recibe
        \Log::info('[ActualizarPrendaCompletaUseCase] actualizarFotos - Iniciando', [
            'prenda_id' => $prenda->id,
            'dto->fotos' => $dto->fotos,
            'es_null' => is_null($dto->fotos),
            'es_empty' => empty($dto->fotos),
            'cantidad_fotos' => is_array($dto->fotos) ? count($dto->fotos) : 'N/A'
        ]);
        
        // Patrón SELECTIVO: Si es null, NO tocar (es actualizacion parcial)
        if (is_null($dto->fotos)) {
            \Log::info('[ActualizarPrendaCompletaUseCase] fotos es NULL - NO MODIFICAR imágenes existentes');
            return;
        }

        if (empty($dto->fotos)) {
            // Si viene array vacío, es intención explícita de eliminar TODO
            \Log::info('[ActualizarPrendaCompletaUseCase] fotos es array VACÍO - ELIMINAR todas las imágenes', [
                'prenda_id' => $prenda->id,
                'fotosActuales' => $prenda->fotos()->count()
            ]);
            $prenda->fotos()->delete();
            return;
        }

        //  ACTUALIZACIÃ“N SELECTIVA: Comparar con existentes
        $fotosExistentes = $prenda->fotos()->get()->keyBy(function($f) {
            return $f->ruta_original;
        });

        $fotosNuevas = [];
        
        // Recopilar rutas de fotos nuevas
        foreach ($dto->fotos as $idx => $foto) {
            // Manejar tanto formato simple (string) como completo (array con ruta_original y ruta_webp)
            if (is_string($foto)) {
                // Formato simple: solo ruta
                $ruta = $foto;
                $rutaWebp = $ruta ? $this->generarRutaWebp($ruta) : null;
            } else if (is_array($foto)) {
                // Formato completo: soportar múltiples keys del frontend
                $ruta = $foto['ruta_original'] ?? $foto['url'] ?? $foto['ruta'] ?? $foto['path'] ?? null;
                $rutaWebp = $foto['ruta_webp'] ?? ($ruta ? $this->generarRutaWebp($ruta) : null);
            } else {
                continue;
            }

            if ($ruta) {
                // Normalizar: quitar prefijo /storage/ si existe (BD guarda sin él)
                $rutaNorm = preg_replace('#^/storage/#', '', $ruta);
                $rutaWebpNorm = $rutaWebp ? preg_replace('#^/storage/#', '', $rutaWebp) : null;
                
                $fotosNuevas[$rutaNorm] = [
                    'ruta_original' => $rutaNorm,
                    'ruta_webp' => $rutaWebpNorm,
                    'orden' => $idx + 1,
                ];
            }
        }

        //  CAMBIO CLAVE: En edición, SOLO INSERTAR fotos nuevas sin eliminar las antiguas
        // Las fotos antiguas permanecen, solo se eliminan si el usuario las elimina manualmente
        
        // Crear índice adicional por ruta_webp para buscar por ambas rutas
        $fotosExistentesPorWebp = $prenda->fotos()->get()->keyBy(function($f) {
            return $f->ruta_webp;
        });
        
        // SOLO insertar fotos nuevas (las que ya existen se mantienen)
        foreach ($fotosNuevas as $ruta => $datosFoto) {
            // Verificar si ya existe por ruta_original O por ruta_webp
            if (!isset($fotosExistentes[$ruta]) && !isset($fotosExistentesPorWebp[$ruta])) {
                $prenda->fotos()->create($datosFoto);
            }
        }
    }

    private function actualizarTallas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        // Solo actualizar si se proporcionan tallas
        if (is_null($dto->cantidadTalla)) {
            \Log::info('[ActualizarPrendaCompletaUseCase] actualizarTallas - NO tallas provided (null)', [
                'prenda_id' => $prenda->id
            ]);
            return;
        }

        if (empty($dto->cantidadTalla)) {
            // Si viene vacÃ­o, eliminar todas
            \Log::info('[ActualizarPrendaCompletaUseCase] actualizarTallas - Empty array, deleting all', [
                'prenda_id' => $prenda->id
            ]);
            $prenda->tallas()->delete();
            return;
        }

        \Log::info('[ActualizarPrendaCompletaUseCase] actualizarTallas - Iniciando', [
            'prenda_id' => $prenda->id,
            'dto_cantidad_talla' => $dto->cantidadTalla
        ]);

        // Obtener tallas existentes
        $tallasExistentes = $prenda->tallas()->get()->keyBy(function($t) {
            return "{$t->genero}_{$t->talla}";
        });

        // EDICIÓN SELECTIVA: Solo procesar géneros que tienen datos reales
        // Géneros vacíos ({}) se ignoran → preservan tallas existentes de ese género
        $tallasNuevas = [];
        $generosConDatos = []; // Géneros que el usuario realmente envió con tallas
        
        foreach ($dto->cantidadTalla as $genero => $tallasCantidad) {
            // Skip metadata keys
            if (strpos($genero, '_') === 0) continue;
            
            if (!is_array($tallasCantidad)) continue;

            // Filtrar solo datos reales (excluir metadata keys como _es_sobremedida)
            $tallasReales = array_filter($tallasCantidad, function($v, $k) {
                return strpos($k, '_') !== 0;
            }, ARRAY_FILTER_USE_BOTH);

            // Si el género está vacío, NO lo procesamos → preservar tallas existentes
            if (empty($tallasReales)) {
                \Log::debug('[ActualizarPrendaCompletaUseCase] Género vacío, preservando existentes', ['genero' => $genero]);
                continue;
            }

            $generosConDatos[] = strtoupper($genero);
            $esSobremedida = !empty($tallasCantidad['_es_sobremedida']);
            
            foreach ($tallasReales as $talla => $cantidad) {
                $key = strtoupper($genero) . "_{$talla}";
                $tallasNuevas[$key] = [
                    'genero' => strtoupper($genero),
                    'talla' => $talla,
                    'cantidad' => (int) $cantidad,
                    'es_sobremedida' => (int) $esSobremedida,
                ];
            }
        }

        \Log::info('[ActualizarPrendaCompletaUseCase] Tallas nuevas preparadas (selectivo)', [
            'prenda_id' => $prenda->id,
            'cantidad' => count($tallasNuevas),
            'generos_con_datos' => $generosConDatos,
            'tallas_nuevas' => $tallasNuevas
        ]);

        // SELECTIVO: Solo eliminar tallas de géneros que el usuario envió con datos
        // Tallas de géneros no enviados se preservan intactas
        foreach ($tallasExistentes as $key => $tallaRecord) {
            $generoExistente = $tallaRecord->genero;
            // Solo tocar si el género fue enviado con datos
            if (in_array($generoExistente, $generosConDatos) && !isset($tallasNuevas[$key])) {
                \Log::debug('[ActualizarPrendaCompletaUseCase] Eliminando talla de género modificado', ['key' => $key]);
                $tallaRecord->delete();
            }
        }

        // Insertar o actualizar tallas
        foreach ($tallasNuevas as $key => $dataTalla) {
            if (isset($tallasExistentes[$key])) {
                $tallasExistentes[$key]->update([
                    'cantidad' => $dataTalla['cantidad'],
                    'es_sobremedida' => $dataTalla['es_sobremedida'],
                ]);
            } else {
                $prenda->tallas()->create($dataTalla);
            }
        }
        
        \Log::info('[ActualizarPrendaCompletaUseCase] actualizarTallas - Completado', [
            'prenda_id' => $prenda->id,
            'total_tallas' => $prenda->tallas()->count()
        ]);
    }

    /**
     * Actualizar asignaciones de colores por talla (prenda_pedido_talla_colores)
     * Patrón selectivo: null → no tocar, vacío → eliminar todo, con datos → replace all
     * 🔴 NUEVO: Maneja tanto array vacío [] como objeto vacío {} como señal de eliminar
     */
    private function actualizarAsignacionesColores(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        if (is_null($dto->asignacionesColores)) {
            return;
        }

        // Obtener todas las tallas de la prenda para hacer lookup genero+talla → talla_id
        $tallasMap = $prenda->tallas()->get()->keyBy(function($t) {
            return strtoupper($t->genero) . '_' . $t->talla;
        });

        // 🔴 NUEVO: Verificar si está vacío (array [] o objeto {})
        $estaVacio = empty($dto->asignacionesColores) || 
                     (is_array($dto->asignacionesColores) && count($dto->asignacionesColores) === 0) ||
                     (is_object($dto->asignacionesColores) && count((array)$dto->asignacionesColores) === 0);

        if ($estaVacio) {
            // Eliminar todas las asignaciones de colores de todas las tallas
            foreach ($tallasMap as $talla) {
                \DB::table('prenda_pedido_talla_colores')
                    ->where('prenda_pedido_talla_id', $talla->id)
                    ->delete();
            }
            \Log::info('[ActualizarPrendaCompletaUseCase] Asignaciones colores eliminadas (vacío)', [
                'prenda_id' => $prenda->id,
                'tipo_vacio' => is_array($dto->asignacionesColores) ? 'array' : 'object'
            ]);
            return;
        }

        // El frontend envía formato objeto: { "GENERO-TELA-TALLA": { genero, tela, talla, colores: [{nombre, cantidad}] } }
        // O formato array plano: [ { genero, talla, tela, tela_id, color, color_id, cantidad } ]
        $asignacionesPlanas = [];

        foreach ($dto->asignacionesColores as $key => $asignacion) {
            if (isset($asignacion['colores']) && is_array($asignacion['colores'])) {
                // Formato objeto con colores anidados
                foreach ($asignacion['colores'] as $colorData) {
                    $asignacionesPlanas[] = [
                        'genero' => strtoupper($asignacion['genero'] ?? ''),
                        'talla' => $asignacion['talla'] ?? '',
                        'tela_nombre' => $asignacion['tela'] ?? '',
                        'tela_id' => $asignacion['tela_id'] ?? null,
                        'color_nombre' => $colorData['nombre'] ?? '',
                        'color_id' => $colorData['color_id'] ?? null,
                        'cantidad' => (int)($colorData['cantidad'] ?? 0),
                    ];
                }
            } else {
                // Formato plano
                $asignacionesPlanas[] = [
                    'genero' => strtoupper($asignacion['genero'] ?? ''),
                    'talla' => $asignacion['talla'] ?? '',
                    'tela_nombre' => $asignacion['tela'] ?? $asignacion['tela_nombre'] ?? '',
                    'tela_id' => $asignacion['tela_id'] ?? null,
                    'color_nombre' => $asignacion['color'] ?? $asignacion['color_nombre'] ?? '',
                    'color_id' => $asignacion['color_id'] ?? null,
                    'cantidad' => (int)($asignacion['cantidad'] ?? 0),
                ];
            }
        }

        // Eliminar asignaciones existentes de las tallas afectadas
        $generosAfectados = array_unique(array_column($asignacionesPlanas, 'genero'));
        foreach ($tallasMap as $key => $talla) {
            if (in_array(strtoupper($talla->genero), $generosAfectados)) {
                \DB::table('prenda_pedido_talla_colores')
                    ->where('prenda_pedido_talla_id', $talla->id)
                    ->delete();
            }
        }

        // Insertar nuevas asignaciones
        $insertados = 0;
        foreach ($asignacionesPlanas as $asig) {
            if ($asig['cantidad'] <= 0) continue;

            $tallaKey = $asig['genero'] . '_' . $asig['talla'];
            $tallaRecord = $tallasMap[$tallaKey] ?? null;

            if (!$tallaRecord) {
                \Log::debug('[ActualizarPrendaCompletaUseCase] Talla no encontrada para asignación color', [
                    'key' => $tallaKey, 'prenda_id' => $prenda->id
                ]);
                continue;
            }

            // Resolver tela_id por nombre si no viene
            $telaId = $asig['tela_id'];
            if (!$telaId && !empty($asig['tela_nombre'])) {
                $tela = \App\Models\TelaPrenda::whereRaw('LOWER(nombre) = ?', [strtolower($asig['tela_nombre'])])->first();
                $telaId = $tela?->id;
            }

            // Resolver color_id por nombre si no viene
            $colorId = $asig['color_id'];
            if (!$colorId && !empty($asig['color_nombre'])) {
                $color = \App\Models\ColorPrenda::whereRaw('LOWER(nombre) = ?', [strtolower($asig['color_nombre'])])->first();
                $colorId = $color?->id;
            }

            \DB::table('prenda_pedido_talla_colores')->insert([
                'prenda_pedido_talla_id' => $tallaRecord->id,
                'tela_id' => $telaId ?? 0,
                'tela_nombre' => $asig['tela_nombre'],
                'color_id' => $colorId ?? 0,
                'color_nombre' => $asig['color_nombre'],
                'cantidad' => $asig['cantidad'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $insertados++;
        }

        \Log::info('[ActualizarPrendaCompletaUseCase] Asignaciones colores actualizadas', [
            'prenda_id' => $prenda->id,
            'insertados' => $insertados,
            'total_recibidos' => count($asignacionesPlanas),
        ]);
    }

    private function actualizarVariantes(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        // Patrón SELECTIVO: Si es null, NO tocar (es actualizacion parcial)
        // CRÍTICO: Solo si explícitamente se envía array vacío es intención de eliminar todo
        if (is_null($dto->variantes)) {
            \Log::info('[ActualizarPrendaCompletaUseCase] Variantes = null, NO SE TOCAN las existentes');
            return;
        }

        if (empty($dto->variantes)) {
            \Log::info('[ActualizarPrendaCompletaUseCase] Variantes vacío, ELIMINANDO todas');
            // Si viene array vacío, usuario intenta eliminar
            $prenda->variantes()->delete();
            return;
        }

        // Normalizar variantes: el frontend puede enviar objeto plano o array de arrays
        $variantes = $dto->variantes;

        // Detectar si es objeto plano (tiene keys como tipo_manga, obs_manga, etc.)
        // En ese caso, convertir a array de un solo elemento con nombres de BD
        if (is_array($variantes) && !empty($variantes) && !isset($variantes[0]) && !is_array(reset($variantes))) {
            // Es objeto plano del frontend → mapear nombres frontend → BD
            $variantes = [[
                'tipo_manga_id'        => $variantes['tipo_manga_id'] ?? null,
                'tipo_broche_boton_id' => $variantes['tipo_broche_boton_id'] ?? $variantes['tipo_broche_id'] ?? null,
                'manga_obs'            => $variantes['obs_manga'] ?? $variantes['manga_obs'] ?? null,
                'broche_boton_obs'     => $variantes['obs_broche'] ?? $variantes['broche_boton_obs'] ?? null,
                'tiene_bolsillos'      => $variantes['tiene_bolsillos'] ?? false,
                'bolsillos_obs'        => $variantes['obs_bolsillos'] ?? $variantes['bolsillos_obs'] ?? null,
            ]];
        }

        \Log::info('[ActualizarPrendaCompletaUseCase] Variantes recibidas (normalizadas)', [
            'cantidad' => count($variantes),
            'variantes' => $variantes,
        ]);

        // ACTUALIZACIÓN SELECTIVA
        $varianteExistente = $prenda->variantes()->first();
        if ($varianteExistente) {
            foreach ($variantes as $variante) {
                if (!is_array($variante)) continue;
                // Usar array_key_exists para detectar campos enviados (incluso si son null)
                // Si el campo existe en el payload → actualizarlo (incluso a null = usuario lo desmarcó)
                // Si el campo NO existe en el payload → no tocarlo
                $upd = [];
                if (array_key_exists("tipo_manga_id", $variante)) $upd["tipo_manga_id"] = $variante["tipo_manga_id"];
                if (array_key_exists("tipo_broche_boton_id", $variante)) $upd["tipo_broche_boton_id"] = $variante["tipo_broche_boton_id"];
                if (array_key_exists("manga_obs", $variante)) $upd["manga_obs"] = $variante["manga_obs"];
                if (array_key_exists("broche_boton_obs", $variante)) $upd["broche_boton_obs"] = $variante["broche_boton_obs"];
                if (array_key_exists("tiene_bolsillos", $variante)) $upd["tiene_bolsillos"] = $variante["tiene_bolsillos"];
                if (array_key_exists("bolsillos_obs", $variante)) $upd["bolsillos_obs"] = $variante["bolsillos_obs"];
                if (!empty($upd)) $varianteExistente->update($upd);
            }
        } else {
            foreach ($variantes as $variante) {
                if (!is_array($variante)) continue;
                $prenda->variantes()->create([
                    'tipo_manga_id' => $variante['tipo_manga_id'] ?? null,
                    'tipo_broche_boton_id' => $variante['tipo_broche_boton_id'] ?? null,
                    'manga_obs' => $variante['manga_obs'] ?? null,
                    'broche_boton_obs' => $variante['broche_boton_obs'] ?? null,
                    'tiene_bolsillos' => $variante['tiene_bolsillos'] ?? false,
                    'bolsillos_obs' => $variante['bolsillos_obs'] ?? null,
                ]);
            }
        }
    }

    private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        // Patrón SELECTIVO: Si es null, NO tocar (es actualizacion parcial)
        if (is_null($dto->coloresTelas)) {
            return;
        }

        if (empty($dto->coloresTelas)) {
            // Si viene array vacÃ­o, es intención explÃ­cita de eliminar TODO
            $prenda->coloresTelas()->delete();
            return;
        }

        // 🗑️ RECOPILAR IDs DE TELAS EN EL PAYLOAD PARA IDENTIFICAR CUÁLES ELIMINAR
        $telaIdsEnPayload = [];
        
        //  MERGE PATTERN: UPDATE o CREATE según id
        foreach ($dto->coloresTelas as $colorTela) {
            $colorId = $colorTela['color_id'] ?? null;
            $telaId = $colorTela['tela_id'] ?? null;
            $referencia = $colorTela['referencia'] ?? null;
            $id = $colorTela['id'] ?? null;  // ID de relación existente
            
            // Fallback: buscar por nombres si no hay IDs
            // Frontend puede enviar 'color_nombre' o 'color', y 'tela_nombre' o 'tela'
            $colorNombre = $colorTela['color_nombre'] ?? $colorTela['color'] ?? null;
            $telaNombre = $colorTela['tela_nombre'] ?? $colorTela['tela'] ?? null;
            
            if ($colorNombre && !$colorId) {
                $colorId = $this->obtenerOCrearColor($colorNombre);
            }
            
            if ($telaNombre && !$telaId) {
                $telaId = $this->obtenerOCrearTela($telaNombre);
            }
            
            // Solo requerir tela_id. color_id puede ser 0/null (sin color asignado)
            if (!$telaId) {
                \Log::debug('[ActualizarPrendaCompletaUseCase] Tela sin tela_id, saltando', ['colorTela' => $colorTela]);
                continue;
            }
            
            //  UPDATE: Si viene con ID, actualizar relación existente
            if ($id) {
                $colorTelaExistente = $prenda->coloresTelas()->where('id', $id)->first();
                if ($colorTelaExistente) {
                    $colorTelaExistente->update([
                        'color_id' => $colorId,
                        'tela_id' => $telaId,
                        'referencia' => $referencia
                    ]);
                    $telaIdsEnPayload[] = $id;  //  Guardar ID para no eliminar
                }
            } 
            //  CREATE: Si NO viene con ID, crear nueva relación
            else {
                // Verificar si ya existe esta combinación
                $query = $prenda->coloresTelas()->where('tela_id', $telaId);
                if ($colorId) {
                    $query->where('color_id', $colorId);
                }
                $existente = $query->first();
                
                if (!$existente) {
                    $nueva = $prenda->coloresTelas()->create([
                        'color_id' => $colorId ?: null,
                        'tela_id' => $telaId,
                        'referencia' => $referencia
                    ]);
                    $telaIdsEnPayload[] = $nueva->id;
                } else {
                    // Actualizar referencia si cambió
                    if ($referencia !== null && $existente->referencia !== $referencia) {
                        $existente->update(['referencia' => $referencia]);
                    }
                    $telaIdsEnPayload[] = $existente->id;
                }
            }
        }
        
        // 🗑️ ELIMINAR TELAS QUE NO ESTÁN EN EL PAYLOAD (FUERON ELIMINADAS POR EL USUARIO)
        $prenda->coloresTelas()
            ->whereNotIn('id', $telaIdsEnPayload)
            ->delete();
    }

    private function obtenerOCrearColor(string $nombreColor): ?int
    {
        // Buscar color existente por nombre (case-insensitive)
        $color = \App\Models\ColorPrenda::whereRaw('LOWER(nombre) = ?', [strtolower($nombreColor)])
            ->where('activo', true)
            ->first();

        if ($color) {
            return $color->id;
        }

        // Si no existe, crear el color
        $color = \App\Models\ColorPrenda::create([
            'nombre' => $nombreColor,
            'codigo' => strtoupper(substr(md5($nombreColor), 0, 6)),
            'activo' => true,
        ]);

        return $color->id;
    }

    private function obtenerOCrearTela(string $nombreTela): ?int
    {
        // Buscar tela existente por nombre (case-insensitive)
        $tela = \App\Models\TelaPrenda::whereRaw('LOWER(nombre) = ?', [strtolower($nombreTela)])
            ->where('activo', true)
            ->first();

        if ($tela) {
            return $tela->id;
        }

        // Si no existe, crear la tela
        $tela = \App\Models\TelaPrenda::create([
            'nombre' => $nombreTela,
            'referencia' => strtoupper(substr(md5($nombreTela), 0, 8)),
            'activo' => true,
        ]);

        return $tela->id;
    }

    // 🔴 NUEVO: Método auxiliar para procesar array de fotos de telas
    private function procesarFotosTelasArray(PrendaPedido $prenda, array $fotosTelas): void
    {
        foreach ($fotosTelas as $idx => $foto) {
            $colorTelaId = $foto['prenda_pedido_colores_telas_id'] ?? null;
            $ruta = $foto['ruta_original'] ?? null;
            $rutaWebp = $foto['ruta_webp'] ?? null;
            
            if (!$colorTelaId || !$ruta) {
                \Log::warning('[ActualizarPrendaCompletaUseCase] Foto ignorada (sin color_tela_id o ruta)', [
                    'color_tela_id' => $colorTelaId,
                    'ruta' => $ruta,
                    'indice' => $idx
                ]);
                continue;
            }
            
            // Si no se asignó rutaWebp, generarla
            if (!$rutaWebp) {
                $rutaWebp = $this->generarRutaWebp($ruta);
            }
            
            $datosFoto = [
                'prenda_pedido_colores_telas_id' => $colorTelaId,
                'ruta_original' => $ruta,
                'ruta_webp' => $rutaWebp,
                'orden' => $idx + 1,
            ];
            
            // Verificar que no exista ya esta ruta exacta (evitar duplicados)
            $existente = \App\Models\PrendaFotoTelaPedido::where('prenda_pedido_colores_telas_id', $colorTelaId)
                ->where('ruta_original', $ruta)
                ->first();
            
            if (!$existente) {
                $fotoCreada = \App\Models\PrendaFotoTelaPedido::create($datosFoto);
                \Log::info('[ActualizarPrendaCompletaUseCase] Foto de tela creada', [
                    'foto_id' => $fotoCreada->id,
                    'color_tela_id' => $colorTelaId,
                    'ruta_original' => $ruta
                ]);
            } else {
                \Log::info('[ActualizarPrendaCompletaUseCase] Foto de tela duplicada, ignorada', [
                    'color_tela_id' => $colorTelaId,
                    'ruta_original' => $ruta
                ]);
            }
        }
    }

    private function actualizarFotosTelas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        // 🔴 NUEVO: Si fotosTelas es null pero fotosTelasProcesadas tiene datos, procesarlas
        // Esto ocurre cuando se agregan telas nuevas en la edición
        if (is_null($dto->fotosTelas) && !empty($dto->fotosTelasProcesadas)) {
            \Log::info('[ActualizarPrendaCompletaUseCase] Procesando fotosTelasProcesadas (sin fotosTelas)', [
                'prenda_id' => $prenda->id,
                'cantidad_fotos_procesadas' => count($dto->fotosTelasProcesadas)
            ]);
            
            // Crear estructura de fotosTelas desde fotosTelasProcesadas
            $fotosTelas = [];
            foreach ($dto->fotosTelasProcesadas as $idx => $procesada) {
                // Obtener la tela agregada recientemente (última tela color-tela)
                $ultimaColorTela = $prenda->coloresTelas()->latest('id')->first();
                
                if ($ultimaColorTela) {
                    $fotosTelas[] = [
                        'prenda_pedido_colores_telas_id' => $ultimaColorTela->id,
                        'ruta_original' => $procesada['ruta_original'] ?? null,
                        'ruta_webp' => $procesada['ruta_webp'] ?? null,
                    ];
                }
            }
            
            // Procesar como si vinieran en fotosTelas
            if (!empty($fotosTelas)) {
                $this->procesarFotosTelasArray($prenda, $fotosTelas);
                return;
            }
        }
        
        // Patrón SELECTIVO: Si es null, NO tocar (es actualizacion parcial)
        if (is_null($dto->fotosTelas)) {
            return;
        }

        if (empty($dto->fotosTelas)) {
            // Si viene array vacío, es intención explícita de eliminar TODO
            $prenda->fotosTelas()->delete();
            return;
        }

        \Log::info('[ActualizarPrendaCompletaUseCase] actualizarFotosTelas - Iniciando', [
            'prenda_id' => $prenda->id,
            'cantidad_fotos' => count($dto->fotosTelas),
            'fotos_procesadas_disponibles' => count($dto->fotosTelasProcesadas ?? []),
            'fotos_recibidas' => $dto->fotosTelas
        ]);

        // Contar fotos nuevas encontradas (para mapear a fotosTelasProcesadas)
        $indicePhotoNuevaEncontrada = 0;

        //  MERGE PATTERN: UPDATE o CREATE según id
        foreach ($dto->fotosTelas as $idx => $foto) {
            $id = $foto['id'] ?? null;  // ID de foto existente
            $colorTelaId = $foto['prenda_pedido_colores_telas_id'] ?? $foto['color_tela_id'] ?? null;
            
            \Log::debug('[ActualizarPrendaCompletaUseCase] Procesando foto', [
                'indice' => $idx,
                'foto_id' => $id,
                'color_tela_id_recibido' => $colorTelaId,
                'color_id' => $foto['color_id'] ?? null,
                'tela_id' => $foto['tela_id'] ?? null,
                'ruta_original' => $foto['ruta_original'] ?? null,
            ]);
            
            // Manejar tanto formato simple (string) como completo (array con ruta_original y ruta_webp)
            if (is_string($foto)) {
                // Si es solo una ruta, no podemos procesarla sin color/tela
                continue;
            }
            
            $ruta = $foto['ruta_original'] ?? $foto['path'] ?? null;
            $rutaWebp = null;
            
            // NUEVO: Si es foto nueva (sin ID) pero existe en fotosTelasProcesadas
            // Usar contador de fotos nuevas encontradas, NO el índice absoluto
            if (!$id && !$ruta) {
                if (is_array($dto->fotosTelasProcesadas) && isset($dto->fotosTelasProcesadas[$indicePhotoNuevaEncontrada])) {
                    $procesado = $dto->fotosTelasProcesadas[$indicePhotoNuevaEncontrada];
                    $ruta = $procesado['ruta_original'] ?? null;
                    $rutaWebp = $procesado['ruta_webp'] ?? null;
                    \Log::debug('[ActualizarPrendaCompletaUseCase] Usando ruta procesada para foto nueva', [
                        'indice_absoluto' => $idx,
                        'indice_foto_nueva' => $indicePhotoNuevaEncontrada,
                        'ruta_original' => $ruta,
                        'ruta_webp' => $rutaWebp
                    ]);
                    // Incrementar contador solo si se encontró ruta
                    if ($ruta) {
                        $indicePhotoNuevaEncontrada++;
                    }
                } else {
                    \Log::warning('[ActualizarPrendaCompletaUseCase] Foto nueva sin ruta procesada disponible', [
                        'indice_absoluto' => $idx,
                        'indice_foto_nueva' => $indicePhotoNuevaEncontrada,
                        'fotos_procesadas_totales' => count($dto->fotosTelasProcesadas ?? [])
                    ]);
                }
            }
            
            // Si no existe el colorTelaId pero sí vienen color_id y tela_id, buscar o crear la combinación
            if (!$colorTelaId && isset($foto['color_id']) && isset($foto['tela_id'])) {
                $colorTelaId = $this->obtenerOCrearColorTela(
                    $prenda,
                    $foto['color_id'],
                    $foto['tela_id']
                );
                \Log::info('[ActualizarPrendaCompletaUseCase] Color-Tela creada o encontrada', [
                    'prenda_pedido_colores_telas_id' => $colorTelaId,
                    'color_id' => $foto['color_id'],
                    'tela_id' => $foto['tela_id']
                ]);
            }
            
            if (!$colorTelaId || !$ruta) {
                \Log::warning('[ActualizarPrendaCompletaUseCase] Foto ignorada (sin color_tela_id o ruta)', [
                    'color_tela_id' => $colorTelaId,
                    'ruta' => $ruta,
                    'indice' => $idx
                ]);
                continue;
            }
            
            // Si no se asignó rutaWebp anteriormente (foto existente con rutaWebp en metadata)
            if (!$rutaWebp) {
                $rutaWebp = is_array($foto) && isset($foto['ruta_webp']) 
                    ? $foto['ruta_webp'] 
                    : $this->generarRutaWebp($ruta);
            }
            
            $datosFoto = [
                'prenda_pedido_colores_telas_id' => $colorTelaId,
                'ruta_original' => $ruta,
                'ruta_webp' => $rutaWebp,
                'orden' => $idx + 1,
            ];
            
            //  UPDATE: Si viene con ID, actualizar foto existente
            if ($id) {
                $fotoExistente = $prenda->fotosTelas()->where('prenda_fotos_tela_pedido.id', $id)->first();
                if ($fotoExistente) {
                    $fotoExistente->update($datosFoto);
                    \Log::info('[ActualizarPrendaCompletaUseCase] Foto actualizada', [
                        'foto_id' => $id,
                        'color_tela_id' => $colorTelaId
                    ]);
                } else {
                    \Log::warning('[ActualizarPrendaCompletaUseCase] Foto no encontrada para actualizar', [
                        'foto_id' => $id,
                        'color_tela_id' => $colorTelaId
                    ]);
                }
            }
            //  CREATE: Si NO viene con ID, crear nueva foto
            else {
                // Verificar que no exista ya esta ruta exacta (evitar duplicados)
                $existente = \App\Models\PrendaFotoTelaPedido::where('prenda_pedido_colores_telas_id', $colorTelaId)
                    ->where('ruta_original', $ruta)
                    ->first();
                
                if (!$existente) {
                    //  IMPORTANTE: fotosTelas es HasManyThrough, no permite create() directo
                    // Usar PrendaFotoTelaPedido::create() directamente
                    $fotoCreada = \App\Models\PrendaFotoTelaPedido::create($datosFoto);
                    \Log::info('[ActualizarPrendaCompletaUseCase] Foto creada', [
                        'foto_id' => $fotoCreada->id,
                        'color_tela_id' => $colorTelaId,
                        'ruta_original' => $ruta
                    ]);
                } else {
                    \Log::info('[ActualizarPrendaCompletaUseCase] Foto duplicada, ignorada', [
                        'color_tela_id' => $colorTelaId,
                        'ruta_original' => $ruta
                    ]);
                }
            }
        }
    }

    private function obtenerOCrearColorTela(PrendaPedido $prenda, $colorId, $telaId): ?int
    {
        // Intentar encontrar la combinación existente
        $colorTela = $prenda->coloresTelas()
            ->where('color_id', $colorId)
            ->where('tela_id', $telaId)
            ->first();

        if ($colorTela) {
            return $colorTela->id;
        }

        // Si no existe, crear la combinación
        $colorTela = $prenda->coloresTelas()->create([
            'color_id' => $colorId,
            'tela_id' => $telaId,
        ]);

        return $colorTela->id;
    }

    private function actualizarProcesos(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        //  FIX: Patrón MERGE actualizar procesos existentes
        // - Si proceso tiene ID → UPDATE ubicaciones, observaciones
        // - Si proceso NO tiene ID → CREATE nuevo proceso
        // - Preservar procesos que no están en el payload (no eliminar)
        
        // Solo actualizar si se proporcionan procesos
        if (is_null($dto->procesos) || empty($dto->procesos)) {
            return;
        }

        \Log::info('[ActualizarPrendaCompletaUseCase] Actualizando procesos', [
            'prenda_id' => $prenda->id,
            'cantidad_procesos' => count($dto->procesos),
            'procesos_debug' => json_encode($dto->procesos)
        ]);

        // Procesar procesos: actualizar existentes o crear nuevos
        foreach ($dto->procesos as $proceso) {
            // Decodificar ubicaciones si vienen como JSON string
            $ubicaciones = $proceso['ubicaciones'] ?? null;
            if (is_string($ubicaciones)) {
                try {
                    $ubicacionesDecodificadas = json_decode($ubicaciones, true);
                    if (is_array($ubicacionesDecodificadas)) {
                        $ubicaciones = $ubicacionesDecodificadas;
                    }
                } catch (\Exception $e) {
                    $ubicaciones = null;
                }
            }

            $procesoId = $proceso['id'] ?? null;

            if ($procesoId && $procesoId > 0) {
                // ✏️ ACTUALIZAR: Proceso existente
                $procesoExistente = $prenda->procesos()->where('id', $procesoId)->first();
                if ($procesoExistente) {
                    \Log::info('[ActualizarPrendaCompletaUseCase] Actualizando proceso existente', [
                        'proceso_id' => $procesoId,
                        'ubicaciones_anteriores' => json_encode(json_decode($procesoExistente->ubicaciones, true)),
                        'ubicaciones_nuevas' => $ubicaciones
                    ]);

                    $procesoExistente->update([
                        'tipo_proceso_id' => $proceso['tipo_proceso_id'] ?? $procesoExistente->tipo_proceso_id,
                        'ubicaciones' => !empty($ubicaciones) ? json_encode($ubicaciones) : $procesoExistente->ubicaciones,
                        'observaciones' => $proceso['observaciones'] ?? $procesoExistente->observaciones,
                        'estado' => $proceso['estado'] ?? $procesoExistente->estado,
                    ]);

                    //  ACTUALIZAR TALLAS del proceso si se proporcionan
                    if (isset($proceso['tallas']) && is_array($proceso['tallas']) && !empty($proceso['tallas'])) {
                        // Eliminar tallas existentes del proceso
                        $procesoExistente->tallas()->delete();

                        // Crear nuevas tallas desde el payload
                        foreach ($proceso['tallas'] as $genero => $tallas) {
                            if (!is_array($tallas)) {
                                continue;
                            }

                            foreach ($tallas as $talla => $cantidad) {
                                if ($cantidad > 0) {
                                    $procesoExistente->tallas()->create([
                                        'genero' => strtoupper($genero),
                                        'talla' => strtoupper($talla),
                                        'cantidad' => (int)$cantidad,
                                    ]);
                                }
                            }
                        }

                        \Log::info('[ActualizarPrendaCompletaUseCase] Tallas del proceso actualizadas', [
                            'proceso_id' => $procesoId,
                            'tallas_nuevas' => $proceso['tallas']
                        ]);
                    }

                    // 🔴 NUEVO: Sincronizar imágenes del proceso existente
                    $this->sincronizarImagenesProceso($procesoExistente, $proceso, $dto);

                    \Log::info('[ActualizarPrendaCompletaUseCase] Proceso actualizado correctamente', [
                        'proceso_id' => $procesoId,
                        'ubicaciones_guardadas' => json_encode($ubicaciones)
                    ]);
                } else {
                    \Log::warning('[ActualizarPrendaCompletaUseCase] No se encontró proceso con ID', [
                        'proceso_id' => $procesoId,
                        'prenda_id' => $prenda->id
                    ]);
                }
            } else {
                //  CREAR: Nuevo proceso
                //  FIX CRÍTICO: Si no hay tipo_proceso_id, buscarlo por el tipo/nombre que viene del frontend
                $tipoProceso = $proceso['tipo_proceso_id'] ?? null;
                
                if (!$tipoProceso && isset($proceso['tipo'])) {
                    // Buscar el tipo de proceso por slug o nombre (ambos pueden ser "reflectivo", "bordado", etc)
                    $tipoProcesoModel = TipoProceso::where('slug', strtolower($proceso['tipo']))
                        ->orWhere('nombre', $proceso['tipo'])
                        ->first();
                    
                    if ($tipoProcesoModel) {
                        $tipoProceso = $tipoProcesoModel->id;
                        \Log::info('[ActualizarPrendaCompletaUseCase] Tipo de proceso encontrado por slug/nombre', [
                            'tipo_buscado' => $proceso['tipo'],
                            'tipo_proceso_id' => $tipoProceso,
                            'nombre_encontrado' => $tipoProcesoModel->nombre
                        ]);
                    } else {
                        \Log::error('[ActualizarPrendaCompletaUseCase] Tipo de proceso NO encontrado', [
                            'tipo_buscado' => $proceso['tipo'],
                            'prenda_id' => $prenda->id
                        ]);
                    }
                }
                
                //  FIX CRÍTICO: Verificar si el proceso YA EXISTE antes de crear
                // Evita violación de constraint unique (prenda_pedido_id, tipo_proceso_id)
                if ($tipoProceso) {
                    $procesoExistente = $prenda->procesos()
                        ->where('tipo_proceso_id', $tipoProceso)
                        ->first();
                    
                    if ($procesoExistente) {
                        // El proceso ya existe → ACTUALIZAR en lugar de crear
                        \Log::info('[ActualizarPrendaCompletaUseCase] Proceso del tipo YA EXISTE - actualizando en lugar de crear', [
                            'proceso_id' => $procesoExistente->id,
                            'tipo_proceso_id' => $tipoProceso,
                            'prenda_id' => $prenda->id
                        ]);
                        
                        // Actualizar ubicaciones y observaciones
                        $procesoExistente->update([
                            'ubicaciones' => !empty($ubicaciones) ? json_encode($ubicaciones) : $procesoExistente->ubicaciones,
                            'observaciones' => $proceso['observaciones'] ?? $procesoExistente->observaciones,
                            'estado' => $proceso['estado'] ?? $procesoExistente->estado,
                        ]);
                        
                        // Agregar imágenes del proceso si existen (es actualización, no creación)
                        $this->agregarImagenesProceso($procesoExistente, $proceso, $dto, false);
                        
                        // Actualizar tallas del proceso si se proporcionan
                        if (isset($proceso['tallas']) && is_array($proceso['tallas']) && !empty($proceso['tallas'])) {
                            $procesoExistente->tallas()->delete();
                            foreach ($proceso['tallas'] as $genero => $tallas) {
                                if (!is_array($tallas)) continue;
                                foreach ($tallas as $talla => $cantidad) {
                                    if ($cantidad > 0) {
                                        $procesoExistente->tallas()->create([
                                            'genero' => strtoupper($genero),
                                            'talla' => strtoupper($talla),
                                            'cantidad' => (int)$cantidad,
                                        ]);
                                    }
                                }
                            }
                        }
                        
                        continue;
                    }
                }
                
                \Log::info('[ActualizarPrendaCompletaUseCase] Creando nuevo proceso', [
                    'tipo_proceso_id' => $tipoProceso,
                    'ubicaciones' => $ubicaciones
                ]);

                $procesoCreado = $prenda->procesos()->create([
                    'tipo_proceso_id' => $tipoProceso,
                    'ubicaciones' => !empty($ubicaciones) ? json_encode($ubicaciones) : null,
                    'observaciones' => $proceso['observaciones'] ?? null,
                    'estado' => $proceso['estado'] ?? 'PENDIENTE',
                ]);

                // Crear tallas del proceso nuevo si se proporcionan
                if (isset($proceso['tallas']) && is_array($proceso['tallas']) && !empty($proceso['tallas'])) {
                    foreach ($proceso['tallas'] as $genero => $tallas) {
                        if (!is_array($tallas)) continue;
                        foreach ($tallas as $talla => $cantidad) {
                            if ($cantidad > 0) {
                                $procesoCreado->tallas()->create([
                                    'genero' => strtoupper($genero),
                                    'talla' => strtoupper($talla),
                                    'cantidad' => (int)$cantidad,
                                ]);
                            }
                        }
                    }
                    \Log::info('[ActualizarPrendaCompletaUseCase] Tallas creadas para proceso nuevo', [
                        'proceso_id' => $procesoCreado->id,
                        'tallas' => $proceso['tallas']
                    ]);
                }

                // Agregar imágenes del proceso si existen
                $this->agregarImagenesProceso($procesoCreado, $proceso, $dto, true);
            }
        }
    }

    /**
     * Sincronizar imágenes de un proceso existente:
     * 1. Si hay imagenes_existentes en el payload, eliminar las que ya no están
     * 2. Si hay fotosProcesoNuevo en el DTO, agregar las nuevas
     */
    private function sincronizarImagenesProceso(
        PedidosProcesosPrendaDetalle $procesoExistente,
        array $proceso,
        ActualizarPrendaCompletaDTO $dto
    ): void {
        $imagenesExistentesPayload = $proceso['imagenes_existentes'] ?? null;
        
        // Si el frontend envió imagenes_existentes, sincronizar (eliminar las que ya no están)
        if (is_array($imagenesExistentesPayload)) {
            $idsAConservar = array_filter(array_column($imagenesExistentesPayload, 'id'));
            
            $imagenesActuales = $procesoExistente->imagenes()->get();
            $eliminadas = 0;
            
            foreach ($imagenesActuales as $imgActual) {
                if (!in_array($imgActual->id, $idsAConservar)) {
                    // Eliminar archivo físico del storage
                    if ($imgActual->ruta_original) {
                        $ruta = ltrim(str_replace('/storage/', '', $imgActual->ruta_original), '/');
                        $rutaFisica = storage_path('app/public/' . $ruta);
                        if (file_exists($rutaFisica)) {
                            @unlink($rutaFisica);
                        }
                    }
                    if ($imgActual->ruta_webp && $imgActual->ruta_webp !== $imgActual->ruta_original) {
                        $rutaW = ltrim(str_replace('/storage/', '', $imgActual->ruta_webp), '/');
                        $rutaFisicaWebp = storage_path('app/public/' . $rutaW);
                        if (file_exists($rutaFisicaWebp)) {
                            @unlink($rutaFisicaWebp);
                        }
                    }
                    $imgActual->delete();
                    $eliminadas++;
                }
            }
            
            if ($eliminadas > 0) {
                \Log::info('[ActualizarPrendaCompletaUseCase] Imágenes de proceso eliminadas', [
                    'proceso_id' => $procesoExistente->id,
                    'eliminadas' => $eliminadas,
                    'conservadas' => count($idsAConservar)
                ]);
            }
        }
        
        // Agregar nuevas imágenes (File uploads procesados por el controlador)
        if (!empty($dto->fotosProcesoNuevo)) {
            $orden = $procesoExistente->imagenes()->count() + 1;
            foreach ($dto->fotosProcesoNuevo as $foto) {
                if (!empty($foto) && is_array($foto)) {
                    $procesoExistente->imagenes()->create([
                        'ruta_original' => $foto['ruta_original'] ?? null,
                        'ruta_webp' => $foto['ruta_webp'] ?? null,
                        'orden' => $orden++,
                        'es_principal' => 0,
                    ]);
                    \Log::info('[ActualizarPrendaCompletaUseCase] Nueva imagen agregada a proceso existente', [
                        'proceso_id' => $procesoExistente->id,
                        'ruta_webp' => $foto['ruta_webp'] ?? 'N/A'
                    ]);
                }
            }
        }
    }

    private function agregarImagenesProceso(
        PedidosProcesosPrendaDetalle $procesoCreado,
        array $proceso,
        ActualizarPrendaCompletaDTO $dto,
        bool $esProcesoNuevo = false
    ): void {
        \Log::info('[ActualizarPrendaCompletaUseCase] agregarImagenesProceso', [
            'proceso_id' => $procesoCreado->id,
            'es_proceso_nuevo' => $esProcesoNuevo,
            'tiene_fotosProcesoNuevo' => !empty($dto->fotosProcesoNuevo),
            'fotosProcesoNuevo_count' => count($dto->fotosProcesoNuevo ?? []),
        ]);

        //  IMPORTANTE: Si hay fotosProcesoNuevo, SIEMPRE usarlas
        // Sin importar si es un proceso nuevo o si estamos actualizando uno existente
        // El usuario acaba de cargar estas imágenes en esto request
        if (!empty($dto->fotosProcesoNuevo)) {
            foreach ($dto->fotosProcesoNuevo as $idx => $foto) {
                if (!empty($foto) && is_array($foto)) {
                    $procesoCreado->imagenes()->create([
                        'ruta_original' => $foto['ruta_original'] ?? null,
                        'ruta_webp' => $foto['ruta_webp'] ?? null,
                        'orden' => $idx + 1,
                        'es_principal' => $idx === 0 ? 1 : 0,
                    ]);
                    \Log::info('[ActualizarPrendaCompletaUseCase] Imagen de proceso agregada', [
                        'proceso_id' => $procesoCreado->id,
                        'indice' => $idx,
                        'ruta_webp' => $foto['ruta_webp'] ?? 'N/A'
                    ]);
                }
            }
            return; // No procesar fotosProcesosPorProceso si ya agregamos fotosProcesoNuevo
        }

        // Si es un proceso EXISTENTE (sin fotosProcesoNuevo), usar fotosProcesosPorProceso
        if (empty($dto->fotosProcesosPorProceso)) {
            return;
        }

        foreach ($dto->fotosProcesosPorProceso as $fotoProceso) {
            if ($fotoProceso['proceso_id'] !== $proceso['id'] || empty($fotoProceso['imagenes'])) {
                continue;
            }

            foreach ($fotoProceso['imagenes'] as $idx => $ruta) {
                $procesoCreado->imagenes()->create([
                    'ruta_original' => $ruta,
                    'ruta_webp' => $this->generarRutaWebp($ruta),
                    'orden' => $idx + 1,
                    'es_principal' => $idx === 0 ? 1 : 0,
                ]);
            }
        }
    }

    private function generarRutaWebp(string $rutaOriginal): string
    {
        // Reemplazar extensión por .webp
        return preg_replace('/\.[^.]+$/', '.webp', $rutaOriginal);
    }
    /**
     * Transformar prenda para factura/resumen
     * Traduce IDs a nombres reales (manga, broche, etc.)
     */
    public function transformarPrendaParaFactura(PrendaPedido $prenda): array
    {
        $prenda->load(['variantes', 'tallas', 'coloresTelas.color', 'coloresTelas.tela']);
        
        $variantes = $prenda->variantes->map(function($variante) {
            $mangaNombre = null;
            if ($variante->tipo_manga_id) {
                $manga = \App\Models\TipoManga::find($variante->tipo_manga_id);
                $mangaNombre = $manga?->nombre;
            }
            
            $brocheBotoNombre = null;
            if ($variante->tipo_broche_boton_id) {
                $broche = \App\Models\TipoBrocheBoton::find($variante->tipo_broche_boton_id);
                $brocheBotoNombre = $broche?->nombre;
            }
            
            return [
                'id' => $variante->id,
                'tipo_manga_id' => $variante->tipo_manga_id,
                'tipo_manga_nombre' => $mangaNombre,
                'manga_obs' => $variante->manga_obs,
                'tipo_broche_boton_id' => $variante->tipo_broche_boton_id,
                'tipo_broche_boton_nombre' => $brocheBotoNombre,
                'broche_boton_obs' => $variante->broche_boton_obs,
                'tiene_bolsillos' => (bool) $variante->tiene_bolsillos,
                'bolsillos_obs' => $variante->bolsillos_obs,
            ];
        })->toArray();
        
        return [
            'id' => $prenda->id,
            'nombre_prenda' => $prenda->nombre_prenda,
            'descripcion' => $prenda->descripcion,
            'de_bodega' => (bool) $prenda->de_bodega,
            'origen' => $prenda->de_bodega ? 'bodega' : 'confeccion',  // Incluir origen para frontend
            'variantes' => $variantes,
            'tallas' => $prenda->tallas->groupBy('genero')->map(function($tallasPorGenero) {
                return $tallasPorGenero->mapWithKeys(function($talla) {
                    return [$talla->talla => $talla->cantidad];
                })->toArray();
            })->toArray(),
            'colores_telas' => $prenda->coloresTelas->map(function($ct) {
                return [
                    'color' => $ct->color?->nombre,
                    'tela' => $ct->tela?->nombre,
                ];
            })->toArray(),
        ];
    }

    private function guardarNovedad(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        // Si no hay novedad, no hacer nada
        if (is_null($dto->novedad) || empty(trim($dto->novedad))) {
            return;
        }

        // Obtener el pedido asociado a la prenda
        $pedido = $prenda->pedidoProduccion;
        
        if (!$pedido) {
            \Log::warning('[ActualizarPrendaCompletaUseCase] No se encontró pedido para prenda', [
                'prenda_id' => $prenda->id,
            ]);
            return;
        }

        // Agregar novedad a las novedades existentes (para mantener historial)
        // Usar "\n\n" como separador de bloques (igual que operario)
        $novedadesActuales = $pedido->novedades ?? '';
        
        // Obtener información del usuario autenticado
        $usuarioAutenticado = \Auth::user();
        $nombreAsesor = $usuarioAutenticado ? $usuarioAutenticado->name : 'Sistema';
        
        // Obtener el primer rol del usuario (usando Spatie Laravel-permission)
        if ($usuarioAutenticado && method_exists($usuarioAutenticado, 'roles')) {
            $rolAsesor = $usuarioAutenticado->roles()->first()?->name ?? 'Sistema';
        } else {
            $rolAsesor = 'Sistema';
        }
        
        // Formatear la novedad: Rol-Nombre-Fecha Hora:Min AM/PM - Novedad
        $nuevaNovedad = trim($dto->novedad);
        $fechaHora = now()->format('d/m/Y h:i A'); // 16/02/2026 12:01 AM
        $rolLabel = ucfirst(str_replace('_', ' ', $rolAsesor)); // supervisor_pedidos → Supervisor pedidos
        $novedadConInfo = "{$rolLabel}-{$nombreAsesor}-{$fechaHora} - {$nuevaNovedad}";
        
        $novedadesActualizadas = $novedadesActuales . ($novedadesActuales ? "\n\n" : "") . $novedadConInfo;

        // Actualizar en pedidos_produccion
        $pedido->update([
            'novedades' => $novedadesActualizadas,
        ]);

        \Log::info('[ActualizarPrendaCompletaUseCase] Novedad guardada', [
            'prenda_id' => $prenda->id,
            'pedido_id' => $pedido->id,
            'novedad' => $dto->novedad,
            'nombre_asesor' => $nombreAsesor,
            'rol_asesor' => $rolAsesor,
        ]);
    }

    /**
     *  ACTUALIZAR TALLAS de un proceso existente
     * 
     * Recibe tallas en formato:
     * {
     *   "dama": { "S": 2, "M": 5 },
     *   "caballero": { "L": 3 }
     * }
     * 
     * @param $procesoExistente Proceso a actualizar
     * @param array $tallasNuevas Tallas nuevas por género
     */
    private function actualizarTallasDelProceso($procesoExistente, array $tallasNuevas): void
    {
        try {
            // 1. ELIMINAR todas las tallas existentes del proceso
            $procesoExistente->tallas()->delete();

            // 2. CREAR nuevas tallas desde el payload
            foreach ($tallasNuevas as $genero => $tallas) {
                if (!is_array($tallas)) {
                    continue;
                }

                foreach ($tallas as $talla => $cantidad) {
                    if ($cantidad > 0) {
                        $procesoExistente->tallas()->create([
                            'genero' => strtoupper($genero),
                            'talla' => strtoupper($talla),
                            'cantidad' => (int)$cantidad,
                        ]);
                    }
                }
            }

            \Log::info('[ActualizarPrendaCompletaUseCase] Tallas del proceso actualizadas', [
                'proceso_id' => $procesoExistente->id,
                'tallas_nuevas' => $tallasNuevas
            ]);

        } catch (\Exception $e) {
            \Log::error('[ActualizarPrendaCompletaUseCase] Error actualizando tallas del proceso', [
                'proceso_id' => $procesoExistente->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar imágenes de prenda marcadas para eliminación
     * 
     * @param array $imagenesAEliminar Array de objetos con estructura: { id, ruta_original, ruta_webp }
     */
    private function eliminarImagenes(array $imagenesAEliminar): void
    {
        if (empty($imagenesAEliminar)) {
            return;
        }

        \Log::info('[ActualizarPrendaCompletaUseCase] Iniciando eliminación de imágenes', [
            'cantidad' => count($imagenesAEliminar)
        ]);

        $imagenService = new \App\Domain\Pedidos\Services\ImagenService();
        $imagenesProcesadas = 0;
        $imagenesError = 0;

        foreach ($imagenesAEliminar as $imagen) {
            try {
                // Extraer ID de imagen (puede venir en 'id' o 'prenda_foto_id')
                $imagenId = $imagen['id'] ?? $imagen['prenda_foto_id'] ?? null;
                $rutaOriginal = $imagen['ruta_original'] ?? null;
                $rutaWebp = $imagen['ruta_webp'] ?? null;

                if (!$imagenId) {
                    \Log::warning('[ActualizarPrendaCompletaUseCase] Imagen sin ID para eliminar', [
                        'imagen_data' => $imagen
                    ]);
                    continue;
                }

                // 1. Eliminar registro de BD (soft delete si está configurado)
                $fotoPedido = \App\Models\PrendaFotoPedido::find($imagenId);
                if ($fotoPedido) {
                    $fotoPedido->delete(); // Usa SoftDelete automáticamente
                    $imagenesProcesadas++;

                    \Log::info('[ActualizarPrendaCompletaUseCase] Imagen eliminada de BD', [
                        'imagen_id' => $imagenId,
                        'ruta_original' => $rutaOriginal
                    ]);
                } else {
                    \Log::warning('[ActualizarPrendaCompletaUseCase] Imagen no encontrada en BD', [
                        'imagen_id' => $imagenId
                    ]);
                    continue;
                }

                // 2. Eliminar archivos físicos de storage
                if ($rutaOriginal) {
                    $eliminadoOriginal = $imagenService->eliminarImagen($rutaOriginal);
                    if ($eliminadoOriginal) {
                        \Log::info('[ActualizarPrendaCompletaUseCase] Archivo original eliminado', [
                            'ruta' => $rutaOriginal
                        ]);
                    } else {
                        \Log::warning('[ActualizarPrendaCompletaUseCase] No se pudo eliminar archivo original', [
                            'ruta' => $rutaOriginal
                        ]);
                    }
                }

                if ($rutaWebp && $rutaWebp !== $rutaOriginal) {
                    $eliminadoWebp = $imagenService->eliminarImagen($rutaWebp);
                    if ($eliminadoWebp) {
                        \Log::info('[ActualizarPrendaCompletaUseCase] Archivo WebP eliminado', [
                            'ruta' => $rutaWebp
                        ]);
                    } else {
                        \Log::warning('[ActualizarPrendaCompletaUseCase] No se pudo eliminar archivo WebP', [
                            'ruta' => $rutaWebp
                        ]);
                    }
                }

            } catch (\Exception $e) {
                $imagenesError++;
                \Log::error('[ActualizarPrendaCompletaUseCase] Error eliminando imagen', [
                    'imagen_id' => $imagen['id'] ?? 'UNKNOWN',
                    'error' => $e->getMessage()
                ]);
            }
        }

        \Log::info('[ActualizarPrendaCompletaUseCase] Eliminación de imágenes completada', [
            'procesadas' => $imagenesProcesadas,
            'errores' => $imagenesError,
            'total' => count($imagenesAEliminar)
        ]);
    }
}