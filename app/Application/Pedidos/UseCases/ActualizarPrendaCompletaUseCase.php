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
        ]);

        // 1. Actualizar campos bÃ¡sicos
        $this->actualizarCamposBasicos($prenda, $dto);

        // 2. Actualizar fotos de referencia
        $this->actualizarFotos($prenda, $dto);

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
        
        // Garantizar que procesos sea siempre un array (incluso si estÃ¡ vacÃ­o)
        if (!$prenda->relationLoaded('procesos')) {
            $prenda->load('procesos');
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
        // Patrón SELECTIVO: Si es null, NO tocar (es actualizacion parcial)
        if (is_null($dto->fotos)) {
            return;
        }

        if (empty($dto->fotos)) {
            // Si viene array vacÃ­o, es intención explÃ­cita de eliminar TODO
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

        // 🔧 CAMBIO CLAVE: En edición, SOLO INSERTAR fotos nuevas sin eliminar las antiguas
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

        // ✅ MERGE PATTERN: UPDATE o CREATE según id
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
            
            // ✅ UPDATE: Si viene con ID, actualizar relación existente
            if ($id) {
                $colorTelaExistente = $prenda->coloresTelas()->where('id', $id)->first();
                if ($colorTelaExistente) {
                    $colorTelaExistente->update([
                        'color_id' => $colorId,
                        'tela_id' => $telaId,
                        'referencia' => $referencia
                    ]);
                }
            } 
            // ✅ CREATE: Si NO viene con ID, crear nueva relación
            else {
                // Verificar si ya existe esta combinación
                $existente = $prenda->coloresTelas()
                    ->where('color_id', $colorId)
                    ->where('tela_id', $telaId)
                    ->first();
                
                if (!$existente) {
                    $prenda->coloresTelas()->create([
                        'color_id' => $colorId,
                        'tela_id' => $telaId,
                        'referencia' => $referencia
                    ]);
                }
            }
        }
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
            // Si viene array vacÃ­o, es intención explÃ­cita de eliminar TODO
            $prenda->fotosTelas()->delete();
            return;
        }

        // ✅ MERGE PATTERN: UPDATE o CREATE según id
        foreach ($dto->fotosTelas as $idx => $foto) {
            $id = $foto['id'] ?? null;  // ID de foto existente
            $colorTelaId = $foto['prenda_pedido_colores_telas_id'] ?? $foto['color_tela_id'] ?? null;
            
            // Manejar tanto formato simple (string) como completo (array con ruta_original y ruta_webp)
            if (is_string($foto)) {
                // Si es solo una ruta, no podemos procesarla sin color/tela
                continue;
            }
            
            $ruta = $foto['ruta_original'] ?? $foto['path'] ?? null;
            
            // Si no existe el colorTelaId pero sí vienen color_id y tela_id, buscar o crear la combinación
            if (!$colorTelaId && isset($foto['color_id']) && isset($foto['tela_id'])) {
                $colorTelaId = $this->obtenerOCrearColorTela(
                    $prenda,
                    $foto['color_id'],
                    $foto['tela_id']
                );
            }
            
            if (!$colorTelaId || !$ruta) {
                continue;
            }
            
            $rutaWebp = is_array($foto) && isset($foto['ruta_webp']) 
                ? $foto['ruta_webp'] 
                : $this->generarRutaWebp($ruta);
            
            $datosFoto = [
                'prenda_pedido_colores_telas_id' => $colorTelaId,
                'ruta_original' => $ruta,
                'ruta_webp' => $rutaWebp,
                'orden' => $idx + 1,
            ];
            
            // ✅ UPDATE: Si viene con ID, actualizar foto existente
            if ($id) {
                $fotoExistente = $prenda->fotosTelas()->where('id', $id)->first();
                if ($fotoExistente) {
                    $fotoExistente->update($datosFoto);
                }
            }
            // ✅ CREATE: Si NO viene con ID, crear nueva foto
            else {
                // Verificar que no exista ya esta ruta exacta (evitar duplicados)
                $existente = $prenda->fotosTelas()
                    ->where('prenda_pedido_colores_telas_id', $colorTelaId)
                    ->where('ruta_original', $ruta)
                    ->first();
                
                if (!$existente) {
                    $prenda->fotosTelas()->create($datosFoto);
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
        // PATTERN MERGE: No eliminar procesos automáticamente
        // Los procesos se preservan hasta que el usuario los elimine explícitamente
        
        // Solo actualizar si se proporcionan procesos
        if (is_null($dto->procesos) || empty($dto->procesos)) {
            return;
        }

        // Crear NUEVOS procesos si se envían (sin eliminar los existentes)
        foreach ($dto->procesos as $proceso) {
            $procesoCreado = $prenda->procesos()->create([
                'tipo_proceso_id' => $proceso['tipo_proceso_id'] ?? null,
                'ubicaciones' => !empty($proceso['ubicaciones']) ? json_encode($proceso['ubicaciones']) : null,
                'observaciones' => $proceso['observaciones'] ?? null,
                'estado' => $proceso['estado'] ?? 'PENDIENTE',
            ]);

            // Agregar imÃ¡genes del proceso si existen
            $this->agregarImagenesProceso($procesoCreado, $proceso, $dto);
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
        $novedadesActualizadas = $novedadesActuales . ($novedadesActuales ? "\n\n" : "") . trim($dto->novedad);

        // Actualizar en pedidos_produccion
        $pedido->update([
            'novedades' => $novedadesActualizadas,
        ]);

        \Log::info('[ActualizarPrendaCompletaUseCase] Novedad guardada', [
            'prenda_id' => $prenda->id,
            'pedido_id' => $pedido->id,
            'novedad' => $dto->novedad,
        ]);
    }
}


