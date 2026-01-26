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
 * - Validar prenda existe âœ… TRAIT
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

        if (!empty($datosActualizar)) {
            $prenda->update($datosActualizar);
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

        // âœ… ACTUALIZACIÃ“N SELECTIVA: Comparar con existentes
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
                $rutaWebp = $this->generarRutaWebp($ruta);
            } else if (is_array($foto)) {
                // Formato completo: {'ruta_original': ..., 'ruta_webp': ...}
                $ruta = $foto['ruta_original'] ?? $foto['path'] ?? null;
                $rutaWebp = $foto['ruta_webp'] ?? $this->generarRutaWebp($ruta);
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

        // âœ… Eliminar solo fotos que NO estÃ¡n en la nueva lista
        foreach ($fotosExistentes as $ruta => $fotoRecord) {
            if (!isset($fotosNuevas[$ruta])) {
                $fotoRecord->delete();
            }
        }

        // âœ… Insertar solo fotos nuevas (las que ya existen no se tocan)
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

        // âœ… ACTUALIZACIÃ“N SELECTIVA
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

        // âœ… ACTUALIZACIÃ“N SELECTIVA: Obtener existentes para comparar
        $coloresTelaExistentes = $prenda->coloresTelas()->get()->keyBy(function($ct) {
            return "{$ct->color_id}_{$ct->tela_id}";
        });

        // Nuevas combinaciones a guardar
        $coloresTelaNovas = [];
        
        foreach ($dto->coloresTelas as $colorTela) {
            $colorId = $colorTela['color_id'] ?? null;
            $telaId = $colorTela['tela_id'] ?? null;
            
            // Si vienen color_nombre o tela_nombre, buscar o crear
            if (isset($colorTela['color_nombre']) && !$colorId) {
                $colorId = $this->obtenerOCrearColor($colorTela['color_nombre']);
            }
            
            if (isset($colorTela['tela_nombre']) && !$telaId) {
                $telaId = $this->obtenerOCrearTela($colorTela['tela_nombre']);
            }
            
            // Solo guardar si tenemos ambos IDs
            if ($colorId && $telaId) {
                $key = "{$colorId}_{$telaId}";
                $coloresTelaNovas[$key] = [
                    'color_id' => $colorId,
                    'tela_id' => $telaId,
                ];
            }
        }

        // âœ… Eliminar solo combinaciones que NO estÃ¡n en la nueva lista
        foreach ($coloresTelaExistentes as $key => $colorTelaRecord) {
            if (!isset($coloresTelaNovas[$key])) {
                $colorTelaRecord->delete();
            }
        }

        // âœ… Insertar solo combinaciones nuevas (las que ya existen no se tocan)
        foreach ($coloresTelaNovas as $key => $datosCT) {
            if (!isset($coloresTelaExistentes[$key])) {
                $prenda->coloresTelas()->create($datosCT);
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

        // âœ… ACTUALIZACIÃ“N SELECTIVA: Comparar con existentes
        $fotosTelaExistentes = $prenda->fotosTelas()->get()->keyBy(function($ft) {
            return $ft->ruta_original;
        });

        $fotosTelaNovas = [];
        
        foreach ($dto->fotosTelas as $idx => $foto) {
            $colorTelaId = $foto['prenda_pedido_colores_telas_id'] ?? $foto['color_tela_id'] ?? null;
            
            // Manejar tanto formato simple (string) como completo (array con ruta_original y ruta_webp)
            if (is_string($foto)) {
                // Si es solo una ruta, no podemos procesarla sin color/tela
                continue;
            }
            
            $ruta = $foto['ruta_original'] ?? $foto['path'] ?? null;
            
            // Si no existe el colorTelaId pero sÃ­ vienen color_id y tela_id, buscar o crear la combinación
            if (!$colorTelaId && isset($foto['color_id']) && isset($foto['tela_id'])) {
                $colorTelaId = $this->obtenerOCrearColorTela(
                    $prenda,
                    $foto['color_id'],
                    $foto['tela_id']
                );
            }
            
            if ($colorTelaId && $ruta) {
                $rutaWebp = is_array($foto) && isset($foto['ruta_webp']) 
                    ? $foto['ruta_webp'] 
                    : $this->generarRutaWebp($ruta);
                
                $fotosTelaNovas[$ruta] = [
                    'prenda_pedido_colores_telas_id' => $colorTelaId,
                    'ruta_original' => $ruta,
                    'ruta_webp' => $rutaWebp,
                    'orden' => $idx + 1,
                ];
            }
        }

        // âœ… Eliminar solo fotos que NO estÃ¡n en la nueva lista
        foreach ($fotosTelaExistentes as $ruta => $fotoTelaRecord) {
            if (!isset($fotosTelaNovas[$ruta])) {
                $fotoTelaRecord->delete();
            }
        }

        // âœ… Insertar solo fotos nuevas (las que ya existen no se tocan)
        foreach ($fotosTelaNovas as $ruta => $datosFoto) {
            if (!isset($fotosTelaExistentes[$ruta])) {
                $prenda->fotosTelas()->create($datosFoto);
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
    }}


