<?php

namespace App\Observers;

use App\Models\TablaOriginal;
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
}
