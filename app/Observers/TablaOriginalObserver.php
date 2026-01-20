<?php

namespace App\Observers;

use App\Models\TablaOriginal;
use App\Services\FestivosColombiaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TablaOriginalObserver
{
    /**
     * Handle the TablaOriginal "updated" event.
     */
    public function updated(TablaOriginal $orden)
    {
        // Verificar si cambió el campo 'descripcion'
        if ($orden->isDirty('descripcion')) {
            $this->sincronizarPrendasConHijos($orden);
        }

        // Verificar si cambió el campo 'cliente'
        if ($orden->isDirty('cliente')) {
            $this->sincronizarClienteConHijos($orden);
        }

        // Verificar si cambió 'dia_de_entrega' o 'fecha_de_creacion_de_orden'
        if ($orden->isDirty('dia_de_entrega') || $orden->isDirty('fecha_de_creacion_de_orden')) {
            $this->actualizarFechaEstimadaEntrega($orden);
        }
    }

    /**
     * Sincronizar cambios en las prendas del padre con los hijos
     */
    private function sincronizarPrendasConHijos(TablaOriginal $orden)
    {
        try {
            // Obtener descripcion antigua y nueva
            $descripcionAntigua = $orden->getOriginal('descripcion');
            $descripcionNueva = $orden->descripcion;

            // Parsear ambas descripciones
            $prendasAntiguas = $this->parsearDescripcion($descripcionAntigua);
            $prendasNuevas = $this->parsearDescripcion($descripcionNueva);

            // Comparar y actualizar cada prenda que cambió
            foreach ($prendasNuevas as $index => $prendaNueva) {
                $prendaAntigua = $prendasAntiguas[$index] ?? null;

                if (!$prendaAntigua) continue;

                // Verificar si cambió el nombre de la prenda
                if ($prendaAntigua['nombre'] !== $prendaNueva['nombre']) {
                    $actualizados = DB::table('registros_por_orden')
                        ->where('pedido', $orden->pedido)
                        ->where('prenda', $prendaAntigua['nombre'])
                        ->update(['prenda' => $prendaNueva['nombre']]);

                    Log::info("Prenda actualizada en registros hijos", [
                        'pedido' => $orden->pedido,
                        'prenda_antigua' => $prendaAntigua['nombre'],
                        'prenda_nueva' => $prendaNueva['nombre'],
                        'registros_actualizados' => $actualizados
                    ]);
                }

                // Verificar si cambió la descripción de la prenda
                if ($prendaAntigua['descripcion'] !== $prendaNueva['descripcion']) {
                    $actualizados = DB::table('registros_por_orden')
                        ->where('pedido', $orden->pedido)
                        ->where('prenda', $prendaNueva['nombre'])
                        ->update(['descripcion' => $prendaNueva['descripcion']]);

                    Log::info("Descripción actualizada en registros hijos", [
                        'pedido' => $orden->pedido,
                        'prenda' => $prendaNueva['nombre'],
                        'descripcion_antigua' => substr($prendaAntigua['descripcion'], 0, 50),
                        'descripcion_nueva' => substr($prendaNueva['descripcion'], 0, 50),
                        'registros_actualizados' => $actualizados
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error("Error sincronizando prendas con hijos", [
                'pedido' => $orden->pedido,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Parsear el campo descripcion para extraer las prendas
     *
     * Formato esperado:
     * Prenda 1: NOMBRE_PRENDA
     * Descripción: DETALLES_PRENDA
     * Tallas: M:6, L:6, XL:6
     *
     * @param string|null $descripcion
     * @return array Array de prendas con formato: [['nombre' => '...', 'descripcion' => '...'], ...]
     */
    private function parsearDescripcion(?string $descripcion): array
    {
        if (empty($descripcion)) return [];

        $prendas = [];
        
        // Dividir por bloques de prenda usando regex
        // Formato: "Prenda 1: NOMBRE\nDescripción: DETALLES\nTallas: ..."
        $bloques = preg_split('/Prenda \d+: /', $descripcion);

        foreach ($bloques as $bloque) {
            $bloque = trim($bloque);
            if (empty($bloque)) continue;

            // Extraer nombre de prenda (primera línea)
            $lineas = explode("\n", $bloque);
            $nombrePrenda = trim($lineas[0]);

            // Buscar línea de descripción
            $descripcionPrenda = '';
            foreach ($lineas as $linea) {
                if (stripos($linea, 'Descripción:') !== false) {
                    $descripcionPrenda = trim(str_replace('Descripción:', '', $linea));
                    // Limpiar cualquier carácter extra al inicio
                    $descripcionPrenda = trim($descripcionPrenda);
                    break;
                }
            }

            $prendas[] = [
                'nombre' => $nombrePrenda,
                'descripcion' => $descripcionPrenda
            ];
        }

        return $prendas;
    }

    /**
     * Sincronizar cliente con hijos cuando cambia en el padre
     */
    private function sincronizarClienteConHijos(TablaOriginal $orden)
    {
        try {
            $actualizados = DB::table('registros_por_orden')
                ->where('pedido', $orden->pedido)
                ->update(['cliente' => $orden->cliente]);

            Log::info("Cliente actualizado en registros hijos", [
                'pedido' => $orden->pedido,
                'cliente_antiguo' => $orden->getOriginal('cliente'),
                'cliente_nuevo' => $orden->cliente,
                'registros_actualizados' => $actualizados
            ]);

        } catch (\Exception $e) {
            Log::error("Error sincronizando cliente con hijos", [
                'pedido' => $orden->pedido,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar fecha estimada de entrega cuando cambia dia_de_entrega
     * IMPORTANTE: Solo se dispara cuando cambia dia_de_entrega, NO fecha_de_creacion_de_orden
     */
    private function actualizarFechaEstimadaEntrega(TablaOriginal $orden)
    {
        try {
            Log::info("\n========== OBSERVER: INICIANDO CÁLCULO ==========", [
                'pedido' => $orden->pedido,
                'fecha_de_creacion_de_orden' => $orden->fecha_de_creacion_de_orden,
                'dia_de_entrega' => $orden->dia_de_entrega
            ]);
            
            // Si no tiene fecha de creación o días de entrega, limpiar fecha estimada
            if (!$orden->fecha_de_creacion_de_orden || !$orden->dia_de_entrega) {
                DB::table('tabla_original')
                    ->where('pedido', $orden->pedido)
                    ->update(['fecha_estimada_de_entrega' => null]);
                
                Log::info("Fecha estimada limpiada (sin fecha de creación o días)", [
                    'pedido' => $orden->pedido
                ]);
                return;
            }

            // Obtener festivos de Colombia
            $fechaInicio = Carbon::parse($orden->fecha_de_creacion_de_orden);
            $diasRequeridos = intval($orden->dia_de_entrega);
            
            Log::info("OBSERVER: Parámetros iniciales", [
                'pedido' => $orden->pedido,
                'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                'dias_requeridos' => $diasRequeridos
            ]);
            
            // Obtener festivos en el rango estimado
            $fechaFin = $fechaInicio->copy()->addDays($diasRequeridos * 3);
            $festivos = FestivosColombiaService::festivosEnRango($fechaInicio, $fechaFin);
            
            Log::info("OBSERVER: Rango de fechas para obtener festivos", [
                'pedido' => $orden->pedido,
                'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                'fecha_fin' => $fechaFin->format('Y-m-d'),
                'dias_rango' => $fechaInicio->diffInDays($fechaFin)
            ]);
            
            Log::info("OBSERVER: Festivos obtenidos de la API", [
                'pedido' => $orden->pedido,
                'cantidad_festivos' => count($festivos),
                'festivos_lista' => $festivos
            ]);
            
            // Verificar si el festivo del 17/11 está en la lista
            $festivo17Nov = '2025-11-17';
            $tiene17Nov = in_array($festivo17Nov, $festivos);
            Log::info("OBSERVER: Verificación de festivo 17/11/2025", [
                'pedido' => $orden->pedido,
                'fecha_17_nov' => $festivo17Nov,
                'está_en_festivos' => $tiene17Nov ? 'SÍ' : 'NO'
            ]);

            // Comenzar desde el día siguiente a la fecha de creación
            $fechaActual = $fechaInicio->copy()->addDay();
            $diasContados = 0;
            
            Log::info("OBSERVER: Iniciando conteo de días hábiles", [
                'pedido' => $orden->pedido,
                'fecha_inicio_conteo' => $fechaActual->format('Y-m-d')
            ]);

            // Contar días hábiles hasta alcanzar los días requeridos
            $iteracion = 0;
            while ($diasContados < $diasRequeridos) {
                $iteracion++;
                $esWeekend = $fechaActual->isWeekend();
                $esFestivo = in_array($fechaActual->toDateString(), $festivos);
                $dayOfWeek = $fechaActual->dayOfWeek; // 0=Domingo, 1=Lunes, ..., 6=Sábado
                $nombreDia = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'][$dayOfWeek];
                
                Log::info("OBSERVER: Iteración " . $iteracion . " - Verificando día", [
                    'pedido' => $orden->pedido,
                    'fecha' => $fechaActual->format('Y-m-d'),
                    'nombre_dia' => $nombreDia,
                    'es_weekend' => $esWeekend ? 'SÍ' : 'NO',
                    'es_festivo' => $esFestivo ? 'SÍ' : 'NO',
                    'dias_contados_actual' => $diasContados,
                    'dias_requeridos' => $diasRequeridos
                ]);
                
                // Verificar si es fin de semana o festivo
                if (!$esWeekend && !$esFestivo) {
                    $diasContados++;
                    Log::info("OBSERVER:  DÍA HÁBIL CONTADO - Iteración " . $iteracion, [
                        'pedido' => $orden->pedido,
                        'fecha' => $fechaActual->format('Y-m-d'),
                        'nombre_dia' => $nombreDia,
                        'dias_contados_total' => $diasContados . '/' . $diasRequeridos
                    ]);
                } else {
                    $razon = $esWeekend ? 'FIN DE SEMANA' : 'FESTIVO';
                    Log::info("OBSERVER:  DÍA NO HÁBIL SALTADO - Iteración " . $iteracion . " (" . $razon . ")", [
                        'pedido' => $orden->pedido,
                        'fecha' => $fechaActual->format('Y-m-d'),
                        'nombre_dia' => $nombreDia,
                        'razon' => $razon
                    ]);
                }

                // Si aún no hemos contado todos los días, avanzar al siguiente
                if ($diasContados < $diasRequeridos) {
                    $fechaActual->addDay();
                    Log::info("OBSERVER: Avanzando al siguiente día", [
                        'pedido' => $orden->pedido,
                        'proxima_fecha' => $fechaActual->format('Y-m-d'),
                        'dias_contados' => $diasContados
                    ]);
                } else {
                    Log::info("OBSERVER:  SE ALCANZARON LOS DÍAS REQUERIDOS", [
                        'pedido' => $orden->pedido,
                        'fecha_final' => $fechaActual->format('Y-m-d'),
                        'dias_contados' => $diasContados
                    ]);
                }
            }

            // Guardar la fecha estimada en la base de datos (formato YYYY-MM-DD)
            $fechaEstimadaString = $fechaActual->toDateString();
            DB::table('tabla_original')
                ->where('pedido', $orden->pedido)
                ->update(['fecha_estimada_de_entrega' => $fechaEstimadaString]);

            Log::info("========== OBSERVER: FECHA ESTIMADA GUARDADA ==========", [
                'pedido' => $orden->pedido,
                'fecha_creacion' => $orden->fecha_de_creacion_de_orden,
                'dias_entrega' => $orden->dia_de_entrega,
                'fecha_estimada_bd' => $fechaEstimadaString,
                'fecha_estimada_formateada' => $fechaActual->format('d/m/Y'),
                'dia_semana_estimada' => $fechaActual->format('l'),
                'iteraciones_totales' => $iteracion,
                'dias_contados_final' => $diasContados
            ]);
            
            // Resumen final
            $fechaCreacionCarbon = Carbon::parse($orden->fecha_de_creacion_de_orden);
            $diasDiferencia = $fechaCreacionCarbon->diffInDays($fechaActual);
            Log::info("========== OBSERVER: RESUMEN FINAL ==========", [
                'pedido' => $orden->pedido,
                'fecha_creacion' => $orden->fecha_de_creacion_de_orden . ' (' . $fechaCreacionCarbon->format('l') . ')',
                'fecha_estimada' => $fechaEstimadaString . ' (' . $fechaActual->format('l') . ')',
                'dias_calendario_totales' => $diasDiferencia,
                'dias_hábiles_contados' => $diasContados,
                'dias_solicitados' => $diasRequeridos,
                'resultado' => $diasContados === $diasRequeridos ? ' CORRECTO' : ' ERROR'
            ]);

        } catch (\Exception $e) {
            Log::error("Error actualizando fecha estimada de entrega", [
                'pedido' => $orden->pedido,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
