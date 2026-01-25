<?php

namespace App\Domain\Pedidos\Services;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaPedidoTalla;
use App\Models\PrendaVariantePed;
use App\Models\PrendaPedidoColorTela;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PedidosProcesosPrendaTalla;
use App\Models\PedidosProcessImagenes;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * PedidoWebService
 * 
 * Servicio unificado para crear pedidos completos con todas sus relaciones
 * Guarda en todas las tablas: prendas, tallas, variantes, procesos, imágenes
 */
class PedidoWebService
{
    private const STORAGE_DISK = 'public';
    private const IMAGEN_PATH_PRENDA = 'prendas/fotos';
    private const IMAGEN_PATH_TELA = 'telas/fotos';
    private const IMAGEN_PATH_PROCESOS = 'procesos/fotos';
    private const IMAGEN_PATH_EPP = 'epp/fotos';

    /**
     * Crear pedido completo con todas sus prendas, procesos e imágenes
     */
    public function crearPedidoCompleto(array $datosValidados, int $asesorId): PedidoProduccion
    {
        return DB::transaction(function () use ($datosValidados, $asesorId) {
            // 1. Crear pedido base
            $pedido = $this->crearPedidoBase($datosValidados, $asesorId);

            Log::info('[PedidoWebService] Pedido base creado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            // 2. Crear prendas con todas sus relaciones
            if (isset($datosValidados['items']) && is_array($datosValidados['items'])) {
                foreach ($datosValidados['items'] as $itemIndex => $itemData) {
                    $this->crearItemCompleto($pedido, $itemData, $itemIndex);
                }
            }

            Log::info('[PedidoWebService] Pedido completo creado', [
                'pedido_id' => $pedido->id,
                'cantidad_prendas' => $pedido->prendas()->count(),
            ]);

            return $pedido;
        });
    }

    /**
     * Crear pedido base
     */
    private function crearPedidoBase(array $datos, int $asesorId): PedidoProduccion
    {
        $numeroPedido = $this->generarNumeroPedido();

        return PedidoProduccion::create([
            'numero_pedido' => $numeroPedido,
            'cliente' => $datos['cliente'] ?? 'SIN NOMBRE',
            'asesor_id' => $asesorId,
            'cliente_id' => $datos['cliente_id'] ?? null,
            'forma_de_pago' => $datos['forma_de_pago'] ?? 'CONTADO',
            'novedades' => $datos['descripcion'] ?? null,
            'estado' => 'Pendiente',
            'cantidad_total' => 0,
            'area' => null,
        ]);
    }

    /**
     * Crear item (prenda) completo con tallas, variantes, procesos e imágenes
     */
    private function crearItemCompleto(PedidoProduccion $pedido, array $itemData, int $itemIndex): PrendaPedido
    {
        $prenda = PrendaPedido::create([
            'pedido_produccion_id' => $pedido->id,
            'nombre_prenda' => $itemData['nombre_prenda'] ?? 'SIN NOMBRE',
            'descripcion' => $itemData['descripcion'] ?? null,
            'de_bodega' => $itemData['de_bodega'] ?? 0,
        ]);

        Log::info('[PedidoWebService] Prenda creada', [
            'prenda_id' => $prenda->id,
            'nombre' => $prenda->nombre_prenda,
        ]);

        // Crear tallas
        if (isset($itemData['cantidad_talla']) && is_array($itemData['cantidad_talla'])) {
            $this->crearTallasPrenda($prenda, $itemData['cantidad_talla']);
        }

        // Crear variantes
        if (isset($itemData['variaciones']) && is_array($itemData['variaciones'])) {
            $this->crearVariantesPrenda($prenda, $itemData['variaciones']);
        }

        // 🔍 DEBUG: Verificar telas
        $tieneTelas = isset($itemData['telas']) && is_array($itemData['telas']) && count($itemData['telas']) > 0;
        $tieneTelasAntiguo = isset($itemData['prenda_pedido_colores_telas']) && is_array($itemData['prenda_pedido_colores_telas']) && count($itemData['prenda_pedido_colores_telas']) > 0;
        
        if ($tieneTelas || $tieneTelasAntiguo) {
            \Log::info('[PedidoWebService] 🧵 Creando telas', [
                'prenda_id' => $prenda->id,
                'telas_count' => $tieneTelas ? count($itemData['telas']) : ($tieneTelasAntiguo ? count($itemData['prenda_pedido_colores_telas']) : 0),
                'tipo' => $tieneTelasAntiguo ? 'ANTIGUO' : 'NUEVO',
            ]);
        } else {
            \Log::warning('[PedidoWebService] ⚠️ SIN TELAS para prenda ' . $prenda->id);
        }

        // Crear colores y telas - intenta tanto del formulario antiguo como del nuevo
        if (isset($itemData['prenda_pedido_colores_telas']) && is_array($itemData['prenda_pedido_colores_telas'])) {
            $this->crearColoresTelas($prenda, $itemData['prenda_pedido_colores_telas']);
        } elseif (isset($itemData['telas']) && is_array($itemData['telas'])) {
            $this->crearTelasDesdeFormulario($prenda, $itemData['telas']);
        }

        // Crear imágenes de prenda
        if (isset($itemData['imagenes']) && is_array($itemData['imagenes'])) {
            $this->guardarImagenesPrenda($prenda, $itemData['imagenes']);
        }

        // 🔍 DEBUG: Verificar procesos
        $tieneProc = isset($itemData['procesos']) && is_array($itemData['procesos']) && count($itemData['procesos']) > 0;
        if ($tieneProc) {
            \Log::info('[PedidoWebService] ⚙️ Creando procesos', [
                'prenda_id' => $prenda->id,
                'procesos_count' => count($itemData['procesos']),
                'procesos_keys' => array_keys($itemData['procesos']),
            ]);
        } else {
            \Log::warning('[PedidoWebService] ⚠️ SIN PROCESOS para prenda ' . $prenda->id);
        }

        // Crear procesos
        if (isset($itemData['procesos']) && is_array($itemData['procesos'])) {
            $this->crearProcesosCompletos($prenda, $itemData['procesos']);
        }

        return $prenda;
    }

    /**
     * Crear tallas para una prenda
     */
    private function crearTallasPrenda(PrendaPedido $prenda, array $cantidadTalla): void
    {
        // cantidadTalla: { DAMA: {S: 10, M: 20}, CABALLERO: {...} }
        foreach ($cantidadTalla as $genero => $tallas) {
            if (is_array($tallas)) {
                foreach ($tallas as $talla => $cantidad) {
                    if ($cantidad > 0) {
                        PrendaPedidoTalla::create([
                            'prenda_pedido_id' => $prenda->id,
                            'genero' => $genero,
                            'talla' => $talla,
                            'cantidad' => $cantidad,
                        ]);
                    }
                }
            }
        }

        Log::info('[PedidoWebService] Tallas creadas', [
            'prenda_id' => $prenda->id,
            'cantidad_generos' => count($cantidadTalla),
        ]);
    }

    /**
     * Crear variantes para una prenda
     */
    private function crearVariantesPrenda(PrendaPedido $prenda, array $variaciones): void
    {
        PrendaVariantePed::create([
            'prenda_pedido_id' => $prenda->id,
            'tipo_manga_id' => $variaciones['tipo_manga_id'] ?? null,
            'tipo_broche_boton_id' => $variaciones['tipo_broche_boton_id'] ?? null,
            'manga_obs' => $variaciones['obs_manga'] ?? null,
            'broche_boton_obs' => $variaciones['obs_broche'] ?? null,
            'tiene_bolsillos' => $variaciones['tiene_bolsillos'] ?? 0,
            'bolsillos_obs' => $variaciones['obs_bolsillos'] ?? null,
        ]);

        Log::info('[PedidoWebService] Variantes creadas', [
            'prenda_id' => $prenda->id,
        ]);
    }

    /**
     * Crear colores y telas
     */
    private function crearColoresTelas(PrendaPedido $prenda, array $coloresTelas): void
    {
        foreach ($coloresTelas as $colorTela) {
            PrendaPedidoColorTela::create([
                'prenda_pedido_id' => $prenda->id,
                'color_id' => $colorTela['color_id'] ?? null,
                'tela_id' => $colorTela['tela_id'] ?? null,
            ]);
        }
    }

    /**
     * Crear telas desde formulario frontend (mapeo de nombres a IDs)
     */
    private function crearTelasDesdeFormulario(PrendaPedido $prenda, array $telas): void
    {
        \Log::info('[PedidoWebService] 🧵 crearTelasDesdeFormulario INICIADA', [
            'prenda_id' => $prenda->id,
            'telas_count' => count($telas),
            'telas_estructura' => array_keys($telas[0] ?? []),
            'telas_data' => json_encode($telas)
        ]);

        $telasCreadasCount = 0;

        foreach ($telas as $telaData) {
            // Si tela_id y color_id ya están presentes, usarlos directamente
            if (isset($telaData['tela_id']) && isset($telaData['color_id'])) {
                $colorTela = PrendaPedidoColorTela::create([
                    'prenda_pedido_id' => $prenda->id,
                    'color_id' => $telaData['color_id'],
                    'tela_id' => $telaData['tela_id'],
                ]);
                $telasCreadasCount++;

                \Log::info('[PedidoWebService] ✅ Tela creada (directo)', [
                    'prenda_id' => $prenda->id,
                    'tela_id' => $telaData['tela_id'],
                    'color_id' => $telaData['color_id'],
                    'color_tela_id' => $colorTela->id,
                ]);

                // Guardar imágenes de tela si existen
                if (isset($telaData['imagenes']) && is_array($telaData['imagenes'])) {
                    $this->guardarImagenesTela($colorTela, $telaData['imagenes']);
                }
            } else {
                // Buscar por nombre/referencia si solo hay nombres
                $telaId = null;
                $colorId = null;

                if (isset($telaData['tela'])) {
                    // Buscar tela por nombre o referencia
                    $telaModel = \App\Models\TelaPrenda::where('nombre', $telaData['tela'])
                        ->orWhere('referencia', $telaData['referencia'] ?? null)
                        ->first();
                    $telaId = $telaModel->id ?? null;
                }

                if (isset($telaData['color'])) {
                    // Buscar color por nombre
                    $colorModel = \App\Models\ColorPrenda::where('nombre', $telaData['color'])->first();
                    $colorId = $colorModel->id ?? null;
                }

                if ($telaId && $colorId) {
                    $colorTela = PrendaPedidoColorTela::create([
                        'prenda_pedido_id' => $prenda->id,
                        'color_id' => $colorId,
                        'tela_id' => $telaId,
                    ]);
                    $telasCreadasCount++;

                    \Log::info('[PedidoWebService] ✅ Tela creada (búsqueda)', [
                        'prenda_id' => $prenda->id,
                        'tela_nombre' => $telaData['tela'] ?? 'N/A',
                        'color_nombre' => $telaData['color'] ?? 'N/A',
                        'tela_id' => $telaId,
                        'color_id' => $colorId,
                    ]);

                    // Guardar imágenes de tela si existen
                    if (isset($telaData['imagenes']) && is_array($telaData['imagenes'])) {
                        $this->guardarImagenesTela($colorTela, $telaData['imagenes']);
                    }
                }
            }
        }

        \Log::info('[PedidoWebService] 🧵 crearTelasDesdeFormulario TERMINADA', [
            'prenda_id' => $prenda->id,
            'telas_creadas' => $telasCreadasCount,
        ]);
    }

    /**
     * Guardar imágenes de tela
     * Nota: imagenes son rutas guardadas (strings), no UploadedFile
     */
    private function guardarImagenesTela(PrendaPedidoColorTela $colorTela, array $imagenes): void
    {
        foreach ($imagenes as $index => $imagen) {
            if (is_string($imagen)) {
                $rutaWebp = $this->convertirAWebp($imagen);
                PrendaFotoTelaPedido::create([
                    'prenda_pedido_colores_telas_id' => $colorTela->id,
                    'ruta_original' => $imagen,
                    'ruta_webp' => $rutaWebp,
                    'orden' => $index + 1,
                ]);
                Log::info('[PedidoWebService] ✅ Imagen tela guardada', [
                    'color_tela_id' => $colorTela->id,
                    'ruta' => $imagen,
                    'index' => $index,
                ]);
            }
        }
    }

    /**
     * Guardar imágenes de prenda
     * Nota: imagenes son rutas guardadas (strings), no UploadedFile
     */
    private function guardarImagenesPrenda(PrendaPedido $prenda, array $imagenes): void
    {
        foreach ($imagenes as $index => $imagen) {
            if (is_string($imagen)) {
                $rutaWebp = $this->convertirAWebp($imagen);
                PrendaFotoPedido::create([
                    'prenda_pedido_id' => $prenda->id,
                    'ruta_original' => $imagen,
                    'ruta_webp' => $rutaWebp,
                    'orden' => $index + 1,
                ]);
                Log::info('[PedidoWebService] ✅ Imagen prenda guardada', [
                    'prenda_id' => $prenda->id,
                    'ruta' => $imagen,
                    'index' => $index,
                ]);
            }
        }
    }

    /**
     * Crear procesos completos con detalles e imágenes
     * 
     * Los procesos llegan ya deserializados desde CrearPedidoCompletoRequest
     * Estructura esperada: { reflectivo: { tipo: 'reflectivo', datos: { ubicaciones, tallas, imagenes, ... } } }
     */
    private function crearProcesosCompletos(PrendaPedido $prenda, array $procesos): void
    {
        \Log::info('[PedidoWebService] ⚙️ crearProcesosCompletos INICIADA', [
            'prenda_id' => $prenda->id,
            'procesos_count' => count($procesos),
            'procesos_keys' => array_keys($procesos),
        ]);

        foreach ($procesos as $tipoProceso => $procesoData) {
            // Validar que procesoData sea array
            if (!is_array($procesoData)) {
                Log::warning('[PedidoWebService] Datos de proceso no es array', [
                    'tipo' => $tipoProceso,
                    'tipo_datos' => gettype($procesoData),
                ]);
                continue;
            }

            \Log::info('[PedidoWebService] 🔍 Procesando tipo: ' . $tipoProceso, [
                'estructura_procesar' => array_keys($procesoData),
                'tiene_datos_key' => isset($procesoData['datos']) ? 'SÍ' : 'NO',
                'tiene_tallas_en_datos' => isset($procesoData['datos']['tallas']) ? 'SÍ' : 'NO',
                'tiene_tallas_directo' => isset($procesoData['tallas']) ? 'SÍ' : 'NO',
            ]);

            // Extraer datos del proceso - AHORA PRIMERO INTENTA DIRECTO, LUEGO EN 'datos'
            $datosProceso = $procesoData['datos'] ?? $procesoData;
            
            // Validar que datosProceso sea array
            if (!is_array($datosProceso)) {
                Log::warning('[PedidoWebService] datosProceso no es array', [
                    'tipo' => $tipoProceso,
                    'tipo_datos' => gettype($datosProceso),
                ]);
                continue;
            }
            
            // Obtener tipo_proceso_id
            $tipoProcesoId = $this->obtenerTipoProcesoId($tipoProceso);
            if (!$tipoProcesoId) {
                Log::warning('[PedidoWebService] Tipo de proceso no encontrado', [
                    'tipo' => $tipoProceso,
                ]);
                continue;
            }

            Log::debug('[PedidoWebService] Creando proceso', [
                'tipo' => $tipoProceso,
                'ubicaciones' => $datosProceso['ubicaciones'] ?? null,
                'tallas_count' => isset($datosProceso['tallas']) ? count($datosProceso['tallas']) : 0,
                'imagenes_count' => isset($datosProceso['imagenes']) ? count($datosProceso['imagenes']) : 0,
            ]);

            // Crear detalle del proceso
            $procesoPrenda = PedidosProcesosPrendaDetalle::create([
                'prenda_pedido_id' => $prenda->id,
                'tipo_proceso_id' => $tipoProcesoId,
                'ubicaciones' => json_encode($datosProceso['ubicaciones'] ?? []),
                'observaciones' => $datosProceso['observaciones'] ?? null,
                'datos_adicionales' => json_encode($datosProceso),
                'estado' => 'PENDIENTE',
            ]);

            Log::info('[PedidoWebService] Proceso creado', [
                'proceso_id' => $procesoPrenda->id,
                'tipo' => $tipoProceso,
            ]);

            // Crear tallas del proceso
            if (isset($datosProceso['tallas']) && is_array($datosProceso['tallas'])) {
                \Log::info('[PedidoWebService] 📏 Llamando crearTallasProceso', [
                    'proceso_id' => $procesoPrenda->id,
                    'tallas_estructura' => array_keys($datosProceso['tallas']),
                ]);
                $this->crearTallasProceso($procesoPrenda, $datosProceso['tallas']);
            } else {
                \Log::warning('[PedidoWebService] ⚠️ NO HAY TALLAS para proceso ' . $tipoProceso, [
                    'tiene_tallas_key' => isset($datosProceso['tallas']) ? 'SÍ' : 'NO',
                    'es_array' => is_array($datosProceso['tallas'] ?? null) ? 'SÍ' : 'NO',
                ]);
            }

            // Crear imágenes del proceso
            if (isset($datosProceso['imagenes']) && is_array($datosProceso['imagenes'])) {
                $this->guardarImagenesProceso($procesoPrenda, $datosProceso['imagenes']);
            }
        }

        \Log::info('[PedidoWebService] ⚙️ crearProcesosCompletos TERMINADA', [
            'prenda_id' => $prenda->id,
        ]);
    }

    /**
     * Crear tallas para un proceso
     */
    private function crearTallasProceso(PedidosProcesosPrendaDetalle $proceso, array $tallas): void
    {
        \Log::info('[PedidoWebService] 📏 crearTallasProceso INICIADA', [
            'proceso_id' => $proceso->id,
            'tallas_estructura' => json_encode($tallas),
        ]);

        $tallasCreadas = 0;

        foreach ($tallas as $genero => $tallasCant) {
            if (is_array($tallasCant)) {
                foreach ($tallasCant as $talla => $cantidad) {
                    if ($cantidad > 0) {
                        PedidosProcesosPrendaTalla::create([
                            'proceso_prenda_detalle_id' => $proceso->id,
                            'genero' => $genero,
                            'talla' => $talla,
                            'cantidad' => $cantidad,
                        ]);
                        $tallasCreadas++;
                    }
                }
            }
        }

        \Log::info('[PedidoWebService] 📏 crearTallasProceso TERMINADA', [
            'proceso_id' => $proceso->id,
            'tallas_creadas' => $tallasCreadas,
        ]);
    }

    /**
     * Guardar imágenes de proceso
     * Nota: imagenes son rutas guardadas (strings), no UploadedFile
     */
    private function guardarImagenesProceso(PedidosProcesosPrendaDetalle $proceso, array $imagenes): void
    {
        foreach ($imagenes as $index => $imagen) {
            if (is_string($imagen)) {
                $rutaWebp = $this->convertirAWebp($imagen);
                PedidosProcessImagenes::create([
                    'proceso_prenda_detalle_id' => $proceso->id,
                    'ruta_original' => $imagen,
                    'ruta_webp' => $rutaWebp,
                    'orden' => $index + 1,
                    'es_principal' => $index === 0 ? 1 : 0,
                ]);
                Log::info('[PedidoWebService] ✅ Imagen proceso guardada', [
                    'proceso_id' => $proceso->id,
                    'ruta' => $imagen,
                ]);
            }
        }
    }

    /**
     * Guardar archivo en storage
     */
    private function guardarArchivo(UploadedFile $archivo, string $carpeta): string
    {
        $nombreArchivo = time() . '_' . uniqid() . '.' . $archivo->getClientOriginalExtension();
        $ruta = $archivo->storeAs("{$carpeta}/" . date('Y/m'), $nombreArchivo, self::STORAGE_DISK);

        return $ruta;
    }

    /**
     * Convertir imagen a WebP
     */
    private function convertirAWebp(string $ruta): string
    {
        // Por ahora retornar la misma ruta
        // En producción, usar intervención image o similar
        return str_replace(
            '.' . pathinfo($ruta, PATHINFO_EXTENSION),
            '.webp',
            $ruta
        );
    }

    /**
     * Obtener ID del tipo de proceso
     */
    private function obtenerTipoProcesoId(string $tipoProceso): ?int
    {
        $tipos = [
            'reflectivo' => 1,
            'bordado' => 2,
            'estampado' => 3,
        ];

        return $tipos[strtolower($tipoProceso)] ?? null;
    }

    /**
     * Generar número de pedido único
     */
    private function generarNumeroPedido(): int
    {
        $ultimoPedido = PedidoProduccion::max('numero_pedido') ?? 100000;
        return $ultimoPedido + 1;
    }
}
