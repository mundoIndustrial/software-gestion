<?php

namespace App\Application\Pedidos\DTOs;

use App\Application\Pedidos\Services\ItemTransformerService;
use Illuminate\Support\Facades\Log;

/**
 * Normaliza el JSON recibido del frontend a un formato consistente
 * para los flujos de aplicacion de Pedidos.
 */
class PedidoNormalizadorDTO
{
    public string $cliente;
    public ?string $asesora;
    public string $forma_de_pago;
    public ?string $observaciones;
    public int $cliente_id;

    public array $prendas = [];
    public array $epps = [];

    public array $uid_a_prenda_id = [];
    public array $uid_a_tela_id = [];
    public array $uid_a_proceso_id = [];
    public array $uid_a_epp_id = [];

    public array $imagen_uid_a_ruta = [];

    public static function fromFrontendJSON(array $json, int $clienteId): self
    {
        Log::info('[PedidoNormalizadorDTO]  fromFrontendJSON - JSON crudo recibido:', [
            'cliente' => $json['cliente'] ?? '',
            'prendas_count' => count($json['prendas'] ?? []),
            'epps_count' => count($json['epps'] ?? []),
            'prendas_raw' => json_encode($json['prendas'] ?? []),
        ]);

        if (isset($json['prendas'][0])) {
            Log::info('[PedidoNormalizadorDTO]  Primera prenda cruda:', [
                'prenda_0' => $json['prendas'][0],
                'telas_count' => count($json['prendas'][0]['telas'] ?? []),
                'telas_0_raw' => json_encode($json['prendas'][0]['telas'][0] ?? []),
                'asignacionesColoresPorTalla_exists' => isset($json['prendas'][0]['asignacionesColoresPorTalla']),
                'asignacionesColoresPorTalla_data' => $json['prendas'][0]['asignacionesColoresPorTalla'] ?? 'NO EXISTE',
            ]);
        }

        $dto = new self();
        $dto->cliente = $json['cliente'] ?? '';
        $dto->asesora = $json['asesora'] ?? null;
        $dto->forma_de_pago = $json['forma_de_pago'] ?? 'Contado';
        $dto->observaciones = $json['observaciones'] ?? null;
        $dto->cliente_id = $clienteId;
        $dto->prendas = self::normalizarPrendas($json['prendas'] ?? []);
        $dto->epps = self::normalizarEpps($json['epps'] ?? []);

        return $dto;
    }

    private static function normalizarPrendas(array $prendas): array
    {
        $itemTransformer = app(ItemTransformerService::class);

        return array_map(function ($prenda) use ($itemTransformer) {
            $cantidadTalla = $prenda['cantidad_talla'] ?? [];
            $procesosNorm = self::normalizarProcesos($prenda['procesos'] ?? []);

            return [
                'uid' => $prenda['uid'] ?? null,
                'nombre_prenda' => trim($prenda['nombre_prenda'] ?? ''),
                'descripcion' => self::normalizarTextoMultilinea($prenda['descripcion'] ?? ''),
                'de_bodega' => $itemTransformer->determinardeBodega($prenda),
                'cantidad_talla' => $cantidadTalla,
                'variaciones' => $prenda['variaciones'] ?? [],
                'telas' => self::normalizarTelas($prenda['telas'] ?? []),
                'procesos' => $procesosNorm,
                'imagenes' => self::normalizarImagenes($prenda['imagenes'] ?? []),
                'asignacionesColoresPorTalla' => $prenda['asignacionesColoresPorTalla'] ?? [],
                'flujo' => $prenda['flujo'] ?? 'simple',
            ];
        }, $prendas);
    }

    /**
     * Normaliza saltos de línea para almacenamiento consistente en BD:
     * - Convierte CRLF/CR a LF
     * - Preserva saltos internos (Enter del usuario)
     * - Solo recorta espacios al inicio/fin del bloque completo
     */
    private static function normalizarTextoMultilinea(mixed $valor): string
    {
        $texto = (string) ($valor ?? '');
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);

        // Si no hay etiquetas HTML, tratar como texto plano.
        if (!preg_match('/<[^>]+>/', $texto)) {
            return trim($texto);
        }

        return trim(self::sanitizarDescripcionHtmlPermitida($texto));
    }

    /**
     * Sanitiza HTML de descripción con whitelist estricta:
     * - Etiquetas permitidas: <span>, <br>
     * - Atributo permitido en span: style con propiedad color segura
     */
    private static function sanitizarDescripcionHtmlPermitida(string $html): string
    {
        // 1) Mantener solo span y br
        $sanitizado = strip_tags($html, '<span><br>');

        // 2) Normalizar variantes de <br> a <br>
        $sanitizado = preg_replace('/<br\s*\/?>/i', '<br>', $sanitizado) ?? $sanitizado;

        // 3) Reconstruir cada <span ...> para permitir solo color seguro
        $sanitizado = preg_replace_callback('/<span\b([^>]*)>/i', function ($matches) {
            $attrs = (string) ($matches[1] ?? '');
            $color = null;

            if (preg_match('/style\s*=\s*([\'"])(.*?)\1/i', $attrs, $styleMatch)) {
                $style = $styleMatch[2] ?? '';
                if (preg_match('/(?:^|;)\s*color\s*:\s*([^;]+)/i', $style, $colorMatch)) {
                    $candidate = trim((string) ($colorMatch[1] ?? ''));
                    if (self::esColorSeguro($candidate)) {
                        $color = $candidate;
                    }
                }
            }

            return $color ? '<span style="color:' . $color . ';">' : '<span>';
        }, $sanitizado) ?? $sanitizado;

        return $sanitizado;
    }

    /**
     * Valida formatos de color permitidos:
     * - #RGB, #RRGGBB
     * - rgb(...)
     * - nombres básicos (letras)
     */
    private static function esColorSeguro(string $value): bool
    {
        $value = trim($value);

        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
            return true;
        }

        if (preg_match('/^rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\)$/i', $value)) {
            return true;
        }

        if (preg_match('/^[a-zA-Z]+$/', $value)) {
            return true;
        }

        return false;
    }

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
                'nombre' => trim($tela['tela_nombre'] ?? $tela['tela'] ?? $tela['nombre'] ?? ''),
                'color' => trim($tela['color_nombre'] ?? $tela['color'] ?? ''),
                'referencia' => trim($tela['referencia'] ?? ''),
                'imagenes' => self::normalizarImagenes($tela['imagenes'] ?? []),
            ];
        }, $telas);
    }

    private static function normalizarProcesos(array $procesos): array
    {
        $resultado = [];
        foreach ($procesos as $key => $proceso) {
            $modoTallas = $proceso['modo_tallas'] ?? 'generico';
            if (!in_array($modoTallas, ['generico', 'general', 'especifico'], true)) {
                $modoTallas = 'generico';
            }
            $datosExtendidos = $proceso['datosExtendidos'] ?? $proceso['datos_extendidos'] ?? null;
            $nombreProceso = strtolower(trim($proceso['tipo'] ?? $proceso['nombre'] ?? ''));
            $claveReal = is_numeric($key) ? ($nombreProceso ?: (string) $key) : $key;

            $imagenesPorTalla = [];
            if ($modoTallas === 'especifico' && $datosExtendidos && is_array($datosExtendidos)) {
                foreach ($datosExtendidos as $genero => $tallasDatos) {
                    if (!is_array($tallasDatos)) {
                        continue;
                    }

                    foreach ($tallasDatos as $talla => $tallaData) {
                        if (!is_array($tallaData)) {
                            continue;
                        }

                        $tallaKey = "{$genero}__{$talla}";
                        if (!empty($tallaData['imagenesFiles']) && is_array($tallaData['imagenesFiles'])) {
                            $imagenesPorTalla[$tallaKey] = $tallaData['imagenesFiles'];
                        }
                    }
                }
            }

            $resultado[$claveReal] = [
                'uid' => $proceso['uid'] ?? null,
                'nombre' => $nombreProceso,
                'ubicaciones' => self::normalizarUbicaciones($proceso['ubicaciones'] ?? []),
                'observaciones' => trim($proceso['observaciones'] ?? ''),
                'tallas' => $proceso['tallas'] ?? [],
                'imagenes' => self::normalizarImagenes($proceso['imagenes'] ?? []),
                'modo_tallas' => $modoTallas,
                'imagenes_por_talla' => $imagenesPorTalla,
                'datos_extendidos' => $datosExtendidos,
            ];
        }

        return $resultado;
    }

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
                if (is_array($u)) {
                    $ubicacionText = $u['ubicacion'] ?? ($u['descripcion'] ?? '');
                    return trim((string) $ubicacionText);
                }

                return trim((string) $u);
            }, $ubicaciones),
            fn ($u) => $u !== ''
        );
    }

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
                    'formdata_key' => $img['formdata_key'] ?? null,
                ];
            }, $imagenes),
            fn ($img) => $img !== null
        );
    }

    private static function sanitizarNombreArchivo(string $filename): string
    {
        $filename = pathinfo($filename, PATHINFO_FILENAME);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);

        return trim($filename);
    }

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
                'imagenes' => self::normalizarImagenes($epp['imagenes'] ?? []),
            ];
        }, $epps);
    }

    public function registrarImagenUID(string $uid, string $rutaFinal): void
    {
        $this->imagen_uid_a_ruta[$uid] = $rutaFinal;
    }

    public function registrarPrendaUID(string $uid, int $prendaId): void
    {
        $this->uid_a_prenda_id[$uid] = $prendaId;
    }

    public function registrarTelaUID(string $uid, int $telaId): void
    {
        $this->uid_a_tela_id[$uid] = $telaId;
    }

    public function registrarProcesoUID(string $uid, int $procesoId): void
    {
        $this->uid_a_proceso_id[$uid] = $procesoId;
    }

    public function obtenerPrendaId(string $uid): ?int
    {
        return $this->uid_a_prenda_id[$uid] ?? null;
    }

    public function obtenerTelaId(string $uid): ?int
    {
        return $this->uid_a_tela_id[$uid] ?? null;
    }

    public function obtenerProcesoId(string $uid): ?int
    {
        return $this->uid_a_proceso_id[$uid] ?? null;
    }

    public function obtenerRutaImagen(string $uid): ?string
    {
        return $this->imagen_uid_a_ruta[$uid] ?? null;
    }
}
