<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarPrendaCompletaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\PrendaPedido;
use App\Models\PedidosProcesosPrendaDetalle;

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
        
        // 🔴 FIX CRÍTICO: Cargar fotos (imágenes) para que la respuesta JSON las incluya
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
        // 🔍 DEBUG: Log detallado de lo que se recibe
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
                // Formato completo: {'ruta_original': ..., 'ruta_webp': ...}
                $ruta = $foto['ruta_original'] ?? $foto['path'] ?? null;
                $rutaWebp = $foto['ruta_webp'] ?? ($ruta ? $this->generarRutaWebp($ruta) : null);
            } else {
                continue;
            }

            if ($ruta) {
                $fotosNuevas[$ruta] = [
                    'ruta_original' => $ruta,
                    'ruta_webp' => $rutaWebp,
                    'orden' => $idx + 1,
                ];
            }
        }

        //  CAMBIO CLAVE: En edición, SOLO INSERTAR fotos nuevas sin eliminar las antiguas
        // Las fotos antiguas permanecen, solo se eliminan si el usuario las elimina manualmente
        // desde el modal de galería o si vienen explícitamente marcadas para eliminar
        
        // SOLO insertar fotos nuevas (las que ya existen se mantienen)
        foreach ($fotosNuevas as $ruta => $datosFoto) {
            if (!isset($fotosExistentes[$ruta])) {
                $prenda->fotos()->create($datosFoto);
            }
        }
    }

    private function actualizarTallas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        // Solo actualizar si se proporcionan tallas
        if (is_null($dto->cantidadTalla)) {
            return;
        }

        if (empty($dto->cantidadTalla)) {
            // Si viene vacÃ­o, eliminar todas
            $prenda->tallas()->delete();
            return;
        }

        // Obtener tallas existentes
        $tallasExistentes = $prenda->tallas()->get()->keyBy(function($t) {
            return "{$t->genero}_{$t->talla}";
        });

        // Nuevas tallas a guardar
        $tallasNuevas = [];
        foreach ($dto->cantidadTalla as $genero => $tallasCantidad) {
            if (is_array($tallasCantidad)) {
                foreach ($tallasCantidad as $talla => $cantidad) {
                    $key = "{$genero}_{$talla}";
                    $tallasNuevas[$key] = [
                        'genero' => $genero,
                        'talla' => $talla,
                        'cantidad' => (int) $cantidad,
                    ];
                }
            }
        }

        // Eliminar tallas que ya no estÃ¡n en la nueva lista
        foreach ($tallasExistentes as $key => $tallaRecord) {
            if (!isset($tallasNuevas[$key])) {
                $tallaRecord->delete();
            }
        }

        // Insertar o actualizar tallas
        foreach ($tallasNuevas as $key => $dataTalla) {
            if (isset($tallasExistentes[$key])) {
                // Actualizar existente
                $tallasExistentes[$key]->update([
                    'cantidad' => $dataTalla['cantidad'],
                ]);
            } else {
                // Crear nueva
                $prenda->tallas()->create($dataTalla);
            }
        }
    }

    private function actualizarVariantes(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        // Patrón SELECTIVO: Si es null, NO tocar (es actualizacion parcial)
        if (is_null($dto->variantes)) {
            return;
        }

        if (empty($dto->variantes)) {
            // Si viene array vacÃ­o, es intención explÃ­cita de eliminar TODO
            $prenda->variantes()->delete();
            return;
        }

        \Log::info('[ActualizarPrendaCompletaUseCase] Variantes recibidas', [
            'cantidad' => count($dto->variantes),
            'variantes' => $dto->variantes,
        ]);

        //  ACTUALIZACIÃ“N SELECTIVA
        // Si hay variantes nuevas, reemplazar TODAS (no hay ID Ãºnico para actualizar parcialmente)
        // Pero solo si explÃ­citamente se envÃ­a el array con datos
        $varianteExistente = $prenda->variantes()->first();
        if ($varianteExistente) {
            foreach ($dto->variantes as $variante) {
                $upd = [];
                // SOLO actualizar si el valor NO es null (preservar datos existentes)
                if (array_key_exists("tipo_manga_id", $variante) && $variante["tipo_manga_id"] !== null) $upd["tipo_manga_id"] = $variante["tipo_manga_id"];
                if (array_key_exists("tipo_broche_boton_id", $variante) && $variante["tipo_broche_boton_id"] !== null) $upd["tipo_broche_boton_id"] = $variante["tipo_broche_boton_id"];
                if (array_key_exists("manga_obs", $variante) && $variante["manga_obs"] !== null) $upd["manga_obs"] = $variante["manga_obs"];
                if (array_key_exists("broche_boton_obs", $variante) && $variante["broche_boton_obs"] !== null) $upd["broche_boton_obs"] = $variante["broche_boton_obs"];
                if (array_key_exists("tiene_bolsillos", $variante) && $variante["tiene_bolsillos"] !== null) $upd["tiene_bolsillos"] = $variante["tiene_bolsillos"];
                if (array_key_exists("bolsillos_obs", $variante) && $variante["bolsillos_obs"] !== null) $upd["bolsillos_obs"] = $variante["bolsillos_obs"];
                if (!empty($upd)) $varianteExistente->update($upd);
            }
        } else {
            foreach ($dto->variantes as $variante) {
                $creada = $prenda->variantes()->create([
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
            if (isset($colorTela['color_nombre']) && !$colorId) {
                $colorId = $this->obtenerOCrearColor($colorTela['color_nombre']);
            }
            
            if (isset($colorTela['tela_nombre']) && !$telaId) {
                $telaId = $this->obtenerOCrearTela($colorTela['tela_nombre']);
            }
            
            if (!$colorId || !$telaId) {
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
                    $telaIdsEnPayload[] = $id;  // 📍 Guardar ID para no eliminar
                }
            } 
            //  CREATE: Si NO viene con ID, crear nueva relación
            else {
                // Verificar si ya existe esta combinación
                $existente = $prenda->coloresTelas()
                    ->where('color_id', $colorId)
                    ->where('tela_id', $telaId)
                    ->first();
                
                if (!$existente) {
                    $nueva = $prenda->coloresTelas()->create([
                        'color_id' => $colorId,
                        'tela_id' => $telaId,
                        'referencia' => $referencia
                    ]);
                    $telaIdsEnPayload[] = $nueva->id;  // 📍 Guardar ID de la nueva tela
                } else {
                    $telaIdsEnPayload[] = $existente->id;  // 📍 Guardar ID de la existente
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

    private function actualizarFotosTelas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
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
                    // ⚠️ IMPORTANTE: fotosTelas es HasManyThrough, no permite create() directo
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
                \Log::info('[ActualizarPrendaCompletaUseCase] Creando nuevo proceso', [
                    'tipo_proceso_id' => $proceso['tipo_proceso_id'] ?? null,
                    'ubicaciones' => $ubicaciones
                ]);

                $procesoCreado = $prenda->procesos()->create([
                    'tipo_proceso_id' => $proceso['tipo_proceso_id'] ?? null,
                    'ubicaciones' => !empty($ubicaciones) ? json_encode($ubicaciones) : null,
                    'observaciones' => $proceso['observaciones'] ?? null,
                    'estado' => $proceso['estado'] ?? 'PENDIENTE',
                ]);

                // Agregar imágenes del proceso si existen
                $this->agregarImagenesProceso($procesoCreado, $proceso, $dto);
            }
        }
    }

    private function agregarImagenesProceso(
        PedidosProcesosPrendaDetalle $procesoCreado,
        array $proceso,
        ActualizarPrendaCompletaDTO $dto
    ): void {
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
        
        // Formatear la novedad con información del asesor
        $nuevaNovedad = trim($dto->novedad);
        $novedadConInfo = "[{$rolAsesor} - {$nombreAsesor} - " . now()->format('d/m/Y H:i') . "]\n{$nuevaNovedad}";
        
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