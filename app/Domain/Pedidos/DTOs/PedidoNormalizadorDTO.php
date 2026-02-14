<?php

namespace App\Domain\Pedidos\DTOs;

use App\Domain\Pedidos\Services\ItemTransformerService;
use Illuminate\Support\Facades\Log;

/**
 * PedidoNormalizadorDTO
 * 
 * Normaliza el JSON recibido del frontend a un formato consistente
 * que pueda ser procesado por los handlers CQRS
 * 
 * INPUT (desde frontend):
 * {
 *   "cliente": "Acme Corp",
 *   "asesora": "María",
 *   "forma_de_pago": "Contado",
 *   "prendas": [
 *     {
 *       "uid": "uuid-1",
 *       "nombre_prenda": "Camisa",
 *       "cantidad_talla": { "dama": { "S": 10 } },
 *       "telas": [
 *         {
 *           "uid": "tela-uuid-1",
 *           "tela_id": 64,
 *           "color_id": 50,
 *           "nombre": "Algodón",
 *           "imagenes": [
 *             { "uid": "img-uuid-1", "nombre_archivo": "tela_001.jpg" }
 *           ]
 *         }
 *       ],
 *       "procesos": [
 *         {
 *           "uid": "proceso-uuid-1",
 *           "nombre": "bordado",
 *           "ubicaciones": ["pecho", "espalda"],
 *           "imagenes": [{ "uid": "img-uuid-2", "nombre_archivo": "bordado_001.jpg" }]
 *         }
 *       ]
 *     }
 *   ]
 * }
 */

class PedidoNormalizadorDTO
{
    public string $cliente;
    public ?string $asesora;
    public string $forma_de_pago;
    public int $cliente_id;
    
    // Estructura normalizada
    public array $prendas = [];
    public array $epps = [];
    
    // Mapeo de UIDs a IDs de BD (se rellenan después de crear en BD)
    public array $uid_a_prenda_id = [];
    public array $uid_a_tela_id = [];
    public array $uid_a_proceso_id = [];
    public array $uid_a_epp_id = [];
    
    // Mapeo de UIDs a rutas de archivos
    public array $imagen_uid_a_ruta = [];

    /**
     * Crear desde JSON decodificado del frontend
     */
    public static function fromFrontendJSON(array $json, int $clienteId): self
    {
        Log::info('[PedidoNormalizadorDTO]  fromFrontendJSON - JSON crudo recibido:', [
            'cliente' => $json['cliente'] ?? '',
            'prendas_count' => count($json['prendas'] ?? []),
            'epps_count' => count($json['epps'] ?? []),
            'prendas_raw' => json_encode($json['prendas'] ?? []),
        ]);
        
        // Si hay prendas, mostrar la primera para debugging
        if (isset($json['prendas'][0])) {
            Log::info('[PedidoNormalizadorDTO]  Primera prenda cruda:', [
                'prenda_0' => $json['prendas'][0],
                'telas_count' => count($json['prendas'][0]['telas'] ?? []),
                'telas_0_raw' => json_encode($json['prendas'][0]['telas'][0] ?? []),
                'asignacionesColoresPorTalla_exists' => isset($json['prendas'][0]['asignacionesColoresPorTalla']),
                'asignacionesColoresPorTalla_data' => $json['prendas'][0]['asignacionesColoresPorTalla'] ?? 'NO EXISTE'
            ]);
        }
        
        $dto = new self();
        $dto->cliente = $json['cliente'] ?? '';
        $dto->asesora = $json['asesora'] ?? null;
        $dto->forma_de_pago = $json['forma_de_pago'] ?? 'Contado';
        $dto->cliente_id = $clienteId;
        
        // Normalizar prendas
        $dto->prendas = self::normalizarPrendas($json['prendas'] ?? []);
        
        // Normalizar EPPs
        $dto->epps = self::normalizarEpps($json['epps'] ?? []);
        
        return $dto;
    }

    /**
     * Normalizar prendas
     */
    private static function normalizarPrendas(array $prendas): array
    {
        $itemTransformer = app(ItemTransformerService::class);
        
        return array_map(function ($prenda) use ($itemTransformer) {
            return [
                'uid' => $prenda['uid'] ?? null,
                'nombre_prenda' => trim($prenda['nombre_prenda'] ?? ''),
                'descripcion' => trim($prenda['descripcion'] ?? ''),
                'de_bodega' => $itemTransformer->determinardeBodega($prenda),  // Use ItemTransformerService to correctly determine de_bodega from origen
                'cantidad_talla' => $prenda['cantidad_talla'] ?? [],
                'variaciones' => $prenda['variaciones'] ?? [],
                'telas' => self::normalizarTelas($prenda['telas'] ?? []),
                'procesos' => self::normalizarProcesos($prenda['procesos'] ?? []),
                'imagenes' => self::normalizarImagenes($prenda['imagenes'] ?? []),
                'asignacionesColoresPorTalla' => $prenda['asignacionesColoresPorTalla'] ?? []  //  Capture color assignments per talla from frontend
            ];
        }, $prendas);
    }

    /**
     * Normalizar telas
     */
    private static function normalizarTelas(array $telas): array
    {
        Log::info('[PedidoNormalizadorDTO]  normalizarTelas - Datos recibidos:', [
            'telas_count' => count($telas),
            'telas_raw' => json_encode($telas),
        ]);
        
        return array_map(function ($tela) {
            Log::info('[PedidoNormalizadorDTO]  Procesando tela individual:', [
                'tela_raw' => $tela,
                'keys' => array_keys($tela),
                'tiene_tela' => isset($tela['tela']),
                'tiene_color' => isset($tela['color']),
                'tiene_tela_nombre' => isset($tela['tela_nombre']),
                'tiene_color_nombre' => isset($tela['color_nombre']),
                'tiene_nombre' => isset($tela['nombre']),
                'tela_valor' => $tela['tela'] ?? 'NO EXISTE',
                'color_valor' => $tela['color'] ?? 'NO EXISTE',
                'tela_nombre_valor' => $tela['tela_nombre'] ?? 'NO EXISTE',
                'color_nombre_valor' => $tela['color_nombre'] ?? 'NO EXISTE',
                'nombre_valor' => $tela['nombre'] ?? 'NO EXISTE',
            ]);
            
            return [
                'uid' => $tela['uid'] ?? null,
                'tela_id' => intval($tela['tela_id'] ?? 0),
                'color_id' => intval($tela['color_id'] ?? 0),
                'nombre' => trim($tela['tela_nombre'] ?? $tela['tela'] ?? $tela['nombre'] ?? ''),  //  CORREGIDO: buscar 'tela_nombre' primero
                'color' => trim($tela['color_nombre'] ?? $tela['color'] ?? ''),  //  CORREGIDO: buscar 'color_nombre' primero
                'referencia' => trim($tela['referencia'] ?? ''),
                'imagenes' => self::normalizarImagenes($tela['imagenes'] ?? [])
            ];
        }, $telas);
    }

    /**
     * Normalizar procesos
     */
    private static function normalizarProcesos(array $procesos): array
    {
        return array_map(function ($proceso) {
            return [
                'uid' => $proceso['uid'] ?? null,
                'nombre' => strtolower(trim($proceso['nombre'] ?? '')),
                'ubicaciones' => self::normalizarUbicaciones($proceso['ubicaciones'] ?? []),
                'observaciones' => trim($proceso['observaciones'] ?? ''),
                'tallas' => $proceso['tallas'] ?? [],
                'imagenes' => self::normalizarImagenes($proceso['imagenes'] ?? [])
            ];
        }, $procesos);
    }

    /**
     * Normalizar ubicaciones (puede ser array o string)
     */
    private static function normalizarUbicaciones($ubicaciones): array
    {
        if (is_string($ubicaciones)) {
            return [trim($ubicaciones)];
        }
        
        if (!is_array($ubicaciones)) {
            return [];
        }
        
        return array_filter(
            array_map(function ($u) {
                // Si es un array con 'ubicacion', extraer ese valor
                if (is_array($u)) {
                    $ubicacion_text = $u['ubicacion'] ?? ($u['descripcion'] ?? '');
                    return trim((string)$ubicacion_text);
                }
                // Si es un string, trimear directamente
                return trim((string)$u);
            }, $ubicaciones),
            fn($u) => $u !== ''
        );
    }

    /**
     * Normalizar imágenes (extraer solo UIDs, nombres y formdata_key)
     */
    private static function normalizarImagenes(array $imagenes): array
    {
        return array_filter(
            array_map(function ($img) {
                if (!isset($img['uid']) || !isset($img['nombre_archivo'])) {
                    return null;
                }
                
                return [
                    'uid' => $img['uid'],
                    'nombre_archivo' => self::sanitizarNombreArchivo($img['nombre_archivo']),
                    'formdata_key' => $img['formdata_key'] ?? null  // Key en FormData para resolver archivo
                ];
            }, $imagenes),
            fn($img) => $img !== null
        );
    }

    /**
     * Sanitizar nombre de archivo eliminando caracteres especiales
     */
    private static function sanitizarNombreArchivo(string $filename): string
    {
        // Eliminar extensión si existe
        $filename = pathinfo($filename, PATHINFO_FILENAME);
        // Eliminar caracteres especiales, mantener solo alfanuméricos, guiones y guiones bajos
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        return trim($filename);
    }

    /**
     * Normalizar EPPs
     */
    private static function normalizarEpps(array $epps): array
    {
        return array_map(function ($epp) {
            return [
                'uid' => $epp['uid'] ?? null,
                'epp_id' => intval($epp['epp_id'] ?? 0),
                'nombre' => trim($epp['nombre'] ?? $epp['nombre_epp'] ?? ''),
                'cantidad' => intval($epp['cantidad'] ?? 1),
                'observaciones' => trim($epp['observaciones'] ?? ''),
                'descripcion' => trim($epp['descripcion'] ?? ''),
                'imagenes' => self::normalizarImagenes($epp['imagenes'] ?? [])
            ];
        }, $epps);
    }

    /**
     * Registrar mapeo: imagen UID → ruta final
     * 
     * Se llama después de procesar imágenes para poder resolver referencias
     */
    public function registrarImagenUID(string $uid, string $rutaFinal): void
    {
        $this->imagen_uid_a_ruta[$uid] = $rutaFinal;
    }

    /**
     * Registrar mapeo: prenda UID → ID de BD
     */
    public function registrarPrendaUID(string $uid, int $prendaId): void
    {
        $this->uid_a_prenda_id[$uid] = $prendaId;
    }

    /**
     * Registrar mapeo: tela UID → ID de BD
     */
    public function registrarTelaUID(string $uid, int $telaId): void
    {
        $this->uid_a_tela_id[$uid] = $telaId;
    }

    /**
     * Registrar mapeo: proceso UID → ID de BD
     */
    public function registrarProcesoUID(string $uid, int $procesoId): void
    {
        $this->uid_a_proceso_id[$uid] = $procesoId;
    }

    /**
     * Obtener ID de BD a partir de UID de prenda
     */
    public function obtenerPrendaId(string $uid): ?int
    {
        return $this->uid_a_prenda_id[$uid] ?? null;
    }

    /**
     * Obtener ID de BD a partir de UID de tela
     */
    public function obtenerTelaId(string $uid): ?int
    {
        return $this->uid_a_tela_id[$uid] ?? null;
    }

    /**
     * Obtener ID de BD a partir de UID de proceso
     */
    public function obtenerProcesoId(string $uid): ?int
    {
        return $this->uid_a_proceso_id[$uid] ?? null;
    }

    /**
     * Obtener ruta de archivo a partir de UID
     */
    public function obtenerRutaImagen(string $uid): ?string
    {
        return $this->imagen_uid_a_ruta[$uid] ?? null;
    }
}
