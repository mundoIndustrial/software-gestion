<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ImagenCotizacionService
{
    /**
     * Ruta base para cotizaciones
     */
    private const BASE_PATH = 'cotizaciones';

    /**
     * Tipos de imágenes permitidas
     */
    private const TIPOS_PERMITIDOS = ['bordado', 'estampado', 'tela', 'prenda', 'logo'];

    /**
     * Procesar imagen para almacenamiento (convertir a WebP si es posible)
     * 
     * @param int $cotizacionId
     * @param UploadedFile $archivo
     * @param string $tipo
     * @return array [exito => bool, rutaTemporal => string, nombreFinal => string]
     */
    public function procesarImagenParaAlmacenamiento(int $cotizacionId, UploadedFile $archivo, string $tipo): array
    {
        $extensionOriginal = strtolower($archivo->getClientOriginalExtension());
        $nombreWebP = "{$cotizacionId}_{$tipo}_" . uniqid() . ".webp";
        $nombreFallback = "{$cotizacionId}_{$tipo}_" . uniqid() . ".{$extensionOriginal}";

        $carpeta = $this->getCarpetaTipo($cotizacionId, $tipo);
        $rutaTemporal = storage_path("app/public/{$carpeta}/{$nombreWebP}");
        $rutaOriginal = $archivo->getRealPath();
        $archivoGuardado = false;
        $nombreFinal = $nombreWebP;

        try {
            // Asegurar que la carpeta existe
            if (!Storage::disk('public')->exists($carpeta)) {
                Storage::disk('public')->makeDirectory($carpeta);
            }

            // Intentar usar cwebp si está disponible
            if ($this->comandoDisponible('cwebp')) {
                $archivoGuardado = $this->convertirImagenAWebP($rutaOriginal, $rutaTemporal);
            }

            // Si cwebp no funcionó, intentar con GD
            if (!$archivoGuardado && extension_loaded('gd')) {
                $archivoGuardado = $this->convertirConGD($rutaOriginal, $rutaTemporal);
            }

            // Si nada funcionó, guardar en formato original
            if (!$archivoGuardado) {
                $rutaTemporal = storage_path("app/public/{$carpeta}/{$nombreFallback}");
                $archivo->storeAs($carpeta, $nombreFallback, 'public');
                $nombreFinal = $nombreFallback;
                $archivoGuardado = true;
            }

            return [
                'exito' => $archivoGuardado,
                'rutaTemporal' => $rutaTemporal,
                'nombreFinal' => $nombreFinal
            ];
        } catch (\Exception $e) {
            \Log::error("Error procesando imagen para almacenamiento", [
                'error' => $e->getMessage()
            ]);
            return [
                'exito' => false,
                'rutaTemporal' => '',
                'nombreFinal' => ''
            ];
        }
    }

    /**
     * Guardar imagen de cotización
     * 
     * @param int $cotizacionId
     * @param UploadedFile $archivo
     * @param string $tipo (bordado, estampado, tela, prenda, general)
     * @return string|null Ruta pública de la imagen
     */
    public function guardarImagen(int $cotizacionId, UploadedFile $archivo, string $tipo = 'general'): ?string
    {
        try {
            \Log::info('guardarImagen iniciado', [
                'cotizacion_id' => $cotizacionId,
                'archivo' => $archivo->getClientOriginalName(),
                'tipo_solicitado' => $tipo
            ]);

            // Validar tipo
            if (!in_array($tipo, self::TIPOS_PERMITIDOS)) {
                \Log::warning('Tipo no permitido, usando general', ['tipo' => $tipo]);
                $tipo = 'general';
            }

            // Crear carpeta si no existe
            $carpeta = $this->getCarpetaTipo($cotizacionId, $tipo);
            \Log::info('Carpeta destino', ['carpeta' => $carpeta]);

            if (!Storage::disk('public')->exists($carpeta)) {
                \Log::info('Creando carpeta', ['carpeta' => $carpeta]);
                Storage::disk('public')->makeDirectory($carpeta);
            }

            // Generar nombre único
            $nombreArchivo = $this->generarNombreArchivo($cotizacionId, $tipo, $archivo);
            \Log::info('Nombre archivo generado', ['nombre' => $nombreArchivo]);

            // Redimensionar imagen si es necesario
            $archivoRedimensionado = $this->redimensionarImagen($archivo);
            
            // Guardar archivo
            \Log::info('Guardando archivo en storage', [
                'carpeta' => $carpeta,
                'nombre' => $nombreArchivo
            ]);

            $ruta = Storage::disk('public')->putFileAs(
                $carpeta,
                $archivoRedimensionado,
                $nombreArchivo
            );

            \Log::info('Archivo guardado en storage', ['ruta' => $ruta]);

            // Convertir a WebP si es imagen
            $rutaCompleta = Storage::disk('public')->path($ruta);
            $nombreWebP = pathinfo($nombreArchivo, PATHINFO_FILENAME) . '.webp';
            $rutaWebP = Storage::disk('public')->path($carpeta . '/' . $nombreWebP);

            if (file_exists($rutaCompleta)) {
                if ($this->convertirConGD($rutaCompleta, $rutaWebP)) {
                    // Eliminar archivo original después de conversión exitosa
                    @unlink($rutaCompleta);
                    $nombreArchivo = $nombreWebP;
                    $ruta = $carpeta . '/' . $nombreWebP;
                    \Log::info('Imagen convertida a WebP', ['ruta' => $ruta]);
                } else {
                    \Log::warning('No se pudo convertir a WebP, usando formato original', ['archivo' => $nombreArchivo]);
                }
            }

            // Retornar ruta relativa (sin URL base, sin prefijo storage/)
            // Los accessors en los modelos agregarán /storage/ automáticamente
            // Formato: cotizaciones/37/prenda/37_prenda_20251119174859_197.webp
            \Log::info('Ruta relativa generada', ['ruta_relativa' => $ruta]);

            return $ruta;
        } catch (\Exception $e) {
            \Log::error('Error al guardar imagen de cotización', [
                'cotizacion_id' => $cotizacionId,
                'tipo' => $tipo,
                'archivo' => $archivo->getClientOriginalName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Guardar múltiples imágenes
     * 
     * @param int $cotizacionId
     * @param array $archivos Array de UploadedFile
     * @param string $tipo
     * @return array Array de rutas públicas
     */
    public function guardarMultiples(int $cotizacionId, array $archivos, string $tipo = 'general'): array
    {
        \Log::info('guardarMultiples iniciado', [
            'cotizacion_id' => $cotizacionId,
            'cantidad_archivos' => count($archivos),
            'tipo' => $tipo
        ]);

        $rutas = [];

        foreach ($archivos as $index => $archivo) {
            \Log::info('Procesando archivo', [
                'index' => $index,
                'es_uploaded_file' => $archivo instanceof UploadedFile,
                'nombre' => $archivo->getClientOriginalName() ?? 'N/A'
            ]);

            if ($archivo instanceof UploadedFile) {
                $ruta = $this->guardarImagen($cotizacionId, $archivo, $tipo);
                \Log::info('Imagen guardada', [
                    'ruta' => $ruta,
                    'guardada' => $ruta !== null
                ]);

                if ($ruta) {
                    $rutas[] = $ruta;
                }
            }
        }

        \Log::info('guardarMultiples finalizado', [
            'rutas_retornadas' => count($rutas),
            'rutas' => $rutas
        ]);

        return $rutas;
    }

    /**
     * Obtener todas las imágenes de una cotización
     * 
     * @param int $cotizacionId
     * @return array Array de rutas organizadas por tipo
     */
    public function obtenerImagenes(int $cotizacionId): array
    {
        $imagenes = [];

        foreach (self::TIPOS_PERMITIDOS as $tipo) {
            $carpeta = $this->getCarpetaTipo($cotizacionId, $tipo);
            
            if (Storage::disk('public')->exists($carpeta)) {
                $archivos = Storage::disk('public')->files($carpeta);
                $imagenes[$tipo] = array_map(function ($archivo) {
                    return '/storage/' . $archivo;
                }, $archivos);
            } else {
                $imagenes[$tipo] = [];
            }
        }

        return $imagenes;
    }

    /**
     * Obtener imágenes de un tipo específico
     * 
     * @param int $cotizacionId
     * @param string $tipo
     * @return array
     */
    public function obtenerImagenesPorTipo(int $cotizacionId, string $tipo): array
    {
        if (!in_array($tipo, self::TIPOS_PERMITIDOS)) {
            return [];
        }

        $carpeta = $this->getCarpetaTipo($cotizacionId, $tipo);
        
        if (!Storage::disk('public')->exists($carpeta)) {
            return [];
        }

        $archivos = Storage::disk('public')->files($carpeta);
        return array_map(function ($archivo) {
            return '/storage/' . $archivo;
        }, $archivos);
    }

    /**
     * Eliminar imagen específica
     * 
     * @param string $rutaPublica Ruta pública de la imagen
     * @return bool
     */
    public function eliminarImagen(string $rutaPublica): bool
    {
        try {
            // Convertir URL pública a ruta de storage
            $ruta = str_replace('/storage/', '', $rutaPublica);
            
            if (Storage::disk('public')->exists($ruta)) {
                Storage::disk('public')->delete($ruta);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            \Log::error('Error al eliminar imagen', [
                'ruta' => $rutaPublica,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Eliminar todas las imágenes de una cotización
     * 
     * @param int $cotizacionId
     * @return bool
     */
    public function eliminarTodasLasImagenes(int $cotizacionId): bool
    {
        try {
            $carpetaPrincipal = self::BASE_PATH . '/' . $cotizacionId;
            
            if (Storage::disk('public')->exists($carpetaPrincipal)) {
                Storage::disk('public')->deleteDirectory($carpetaPrincipal);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            \Log::error('Error al eliminar carpeta de cotización', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtener ruta de carpeta para un tipo
     * 
     * @param int $cotizacionId
     * @param string $tipo
     * @return string
     */
    private function getCarpetaTipo(int $cotizacionId, string $tipo): string
    {
        return self::BASE_PATH . '/' . $cotizacionId . '/' . $tipo;
    }

    /**
     * Generar nombre único para archivo
     * 
     * @param int $cotizacionId
     * @param string $tipo
     * @param UploadedFile $archivo
     * @return string
     */
    private function generarNombreArchivo(int $cotizacionId, string $tipo, UploadedFile $archivo): string
    {
        $extension = $archivo->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        return "{$cotizacionId}_{$tipo}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Validar archivo
     * 
     * @param UploadedFile $archivo
     * @return bool
     */
    public function validarArchivo(UploadedFile $archivo): bool
    {
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower($archivo->getClientOriginalExtension());
        $tamanioMaximo = 5 * 1024 * 1024; // 5MB

        if (!in_array($extension, $extensionesPermitidas)) {
            return false;
        }

        if ($archivo->getSize() > $tamanioMaximo) {
            return false;
        }

        return true;
    }

    /**
     * Obtener información de almacenamiento
     * 
     * @param int $cotizacionId
     * @return array
     */
    public function obtenerInfo(int $cotizacionId): array
    {
        $carpetaPrincipal = self::BASE_PATH . '/' . $cotizacionId;
        $imagenes = $this->obtenerImagenes($cotizacionId);
        
        $totalImagenes = 0;
        $tamanioTotal = 0;

        foreach ($imagenes as $tipo => $archivos) {
            $totalImagenes += count($archivos);
            
            foreach ($archivos as $url) {
                $ruta = str_replace('/storage/', '', $url);
                if (Storage::disk('public')->exists($ruta)) {
                    $tamanioTotal += Storage::disk('public')->size($ruta);
                }
            }
        }

        return [
            'cotizacion_id' => $cotizacionId,
            'total_imagenes' => $totalImagenes,
            'tamanio_total' => $tamanioTotal,
            'tamanio_total_mb' => round($tamanioTotal / 1024 / 1024, 2),
            'imagenes_por_tipo' => array_map('count', $imagenes),
            'existe_carpeta' => Storage::disk('public')->exists($carpetaPrincipal)
        ];
    }

    /**
     * Verificar si un comando está disponible en el servidor
     * 
     * @param string $comando
     * @return bool
     */
    public function comandoDisponible(string $comando): bool
    {
        try {
            $output = [];
            $return = 0;
            
            if (stripos(PHP_OS, 'WIN') === 0) {
                // Windows
                exec("where $comando", $output, $return);
            } else {
                // Linux/Mac
                exec("which $comando", $output, $return);
            }
            
            return $return === 0;
        } catch (\Exception $e) {
            \Log::warning("Error verificando disponibilidad de comando: $comando", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Convertir imagen a WebP usando cwebp
     * 
     * @param string $rutaOrigen
     * @param string $rutaDestino
     * @return bool
     */
    public function convertirImagenAWebP(string $rutaOrigen, string $rutaDestino): bool
    {
        try {
            if (!file_exists($rutaOrigen)) {
                \Log::error("Archivo origen no existe: $rutaOrigen");
                return false;
            }

            $comando = "cwebp \"$rutaOrigen\" -o \"$rutaDestino\" -q 80";
            
            $output = [];
            $return = 0;
            exec($comando, $output, $return);

            if ($return !== 0) {
                \Log::warning("Error al ejecutar cwebp", [
                    'comando' => $comando,
                    'salida' => implode("\n", $output),
                    'codigo_retorno' => $return
                ]);
                return false;
            }

            if (!file_exists($rutaDestino)) {
                \Log::error("Archivo destino no se creó", [
                    'destino' => $rutaDestino
                ]);
                return false;
            }

            \Log::info("Imagen convertida a WebP exitosamente", [
                'origen' => $rutaOrigen,
                'destino' => $rutaDestino,
                'tamanio_kb' => round(filesize($rutaDestino) / 1024, 2)
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error("Excepción al convertir a WebP", [
                'origen' => $rutaOrigen,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Convertir imagen usando GD library
     * 
     * @param string $rutaOrigen
     * @param string $rutaDestino
     * @return bool
     */
    public function convertirConGD(string $rutaOrigen, string $rutaDestino): bool
    {
        try {
            if (!extension_loaded('gd')) {
                \Log::error("Extensión GD no está disponible");
                return false;
            }

            if (!file_exists($rutaOrigen)) {
                \Log::error("Archivo origen no existe: $rutaOrigen");
                return false;
            }

            $info = @getimagesize($rutaOrigen);
            if (!$info) {
                \Log::error("No se pudo obtener información de la imagen", [
                    'origen' => $rutaOrigen
                ]);
                return false;
            }

            $tipo = $info[2];
            $imagen = null;

            switch ($tipo) {
                case IMAGETYPE_JPEG:
                    $imagen = @imagecreatefromjpeg($rutaOrigen);
                    if (!$imagen) return false;
                    imagejpeg($imagen, $rutaDestino, 80);
                    break;
                case IMAGETYPE_PNG:
                    $imagen = @imagecreatefrompng($rutaOrigen);
                    if (!$imagen) return false;
                    imagepng($imagen, $rutaDestino, 8);
                    break;
                case IMAGETYPE_GIF:
                    $imagen = @imagecreatefromgif($rutaOrigen);
                    if (!$imagen) return false;
                    imagegif($imagen, $rutaDestino);
                    break;
                default:
                    return false;
            }

            if ($imagen) {
                @imagedestroy($imagen);
            }

            if (!file_exists($rutaDestino)) {
                \Log::error("No se pudo guardar imagen con GD", [
                    'destino' => $rutaDestino
                ]);
                return false;
            }

            \Log::info("Imagen procesada con GD exitosamente", [
                'origen' => $rutaOrigen,
                'destino' => $rutaDestino,
                'tamanio_kb' => round(filesize($rutaDestino) / 1024, 2)
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error("Excepción al convertir con GD", [
                'origen' => $rutaOrigen,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Redimensionar imagen a un tamaño mínimo
     * Asegura que todas las imágenes tengan al menos 1024x768px
     * 
     * @param UploadedFile $archivo
     * @return UploadedFile
     */
    private function redimensionarImagen(UploadedFile $archivo)
    {
        try {
            // Verificar que GD está disponible
            if (!extension_loaded('gd')) {
                \Log::warning('Extensión GD no disponible, saltando redimensionamiento');
                return $archivo;
            }

            $rutaTemporal = $archivo->getRealPath();
            
            if (!file_exists($rutaTemporal)) {
                \Log::warning('Archivo temporal no existe');
                return $archivo;
            }

            $info = @getimagesize($rutaTemporal);
            
            if (!$info) {
                \Log::warning('No se pudo obtener información de la imagen');
                return $archivo;
            }

            $ancho = $info[0];
            $alto = $info[1];
            $tipo = $info[2];

            // Tamaño mínimo deseado
            $anchoMinimo = 1024;
            $altoMinimo = 768;

            // Si la imagen es más pequeña que el mínimo, redimensionarla
            if ($ancho < $anchoMinimo || $alto < $altoMinimo) {
                \Log::info('Redimensionando imagen', [
                    'tamaño_original' => "{$ancho}x{$alto}",
                    'tamaño_minimo' => "{$anchoMinimo}x{$altoMinimo}"
                ]);

                // Calcular nuevas dimensiones manteniendo proporción
                if ($ancho < $anchoMinimo && $alto < $altoMinimo) {
                    // Ambas dimensiones son pequeñas, usar la que necesita más escala
                    $escalaAncho = $anchoMinimo / $ancho;
                    $escalaAlto = $altoMinimo / $alto;
                    $escala = max($escalaAncho, $escalaAlto);
                } elseif ($ancho < $anchoMinimo) {
                    $escala = $anchoMinimo / $ancho;
                } else {
                    $escala = $altoMinimo / $alto;
                }

                $nuevoAncho = (int)($ancho * $escala);
                $nuevoAlto = (int)($alto * $escala);

                // Crear imagen redimensionada
                $imagenOriginal = null;
                $imagenNueva = null;

                try {
                    switch ($tipo) {
                        case IMAGETYPE_JPEG:
                            $imagenOriginal = @imagecreatefromjpeg($rutaTemporal);
                            if ($imagenOriginal === false) {
                                \Log::warning('No se pudo cargar JPEG');
                                return $archivo;
                            }
                            $imagenNueva = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
                            imagecopyresampled($imagenNueva, $imagenOriginal, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
                            imagejpeg($imagenNueva, $rutaTemporal, 90);
                            break;
                        case IMAGETYPE_PNG:
                            $imagenOriginal = @imagecreatefrompng($rutaTemporal);
                            if ($imagenOriginal === false) {
                                \Log::warning('No se pudo cargar PNG');
                                return $archivo;
                            }
                            $imagenNueva = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
                            imagealphablending($imagenNueva, false);
                            imagesavealpha($imagenNueva, true);
                            imagecopyresampled($imagenNueva, $imagenOriginal, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
                            imagepng($imagenNueva, $rutaTemporal);
                            break;
                        case IMAGETYPE_GIF:
                            $imagenOriginal = @imagecreatefromgif($rutaTemporal);
                            if ($imagenOriginal === false) {
                                \Log::warning('No se pudo cargar GIF');
                                return $archivo;
                            }
                            $imagenNueva = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
                            imagecopyresampled($imagenNueva, $imagenOriginal, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
                            imagegif($imagenNueva, $rutaTemporal);
                            break;
                        default:
                            \Log::warning('Tipo de imagen no soportado', ['tipo' => $tipo]);
                            return $archivo;
                    }

                    if ($imagenOriginal) @imagedestroy($imagenOriginal);
                    if ($imagenNueva) @imagedestroy($imagenNueva);

                    \Log::info('Imagen redimensionada exitosamente', [
                        'tamaño_nuevo' => "{$nuevoAncho}x{$nuevoAlto}"
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Error durante redimensionamiento GD', [
                        'error' => $e->getMessage()
                    ]);
                    if ($imagenOriginal) @imagedestroy($imagenOriginal);
                    if ($imagenNueva) @imagedestroy($imagenNueva);
                    return $archivo;
                }
            }

            return $archivo;
        } catch (\Exception $e) {
            \Log::warning('Error al redimensionar imagen', [
                'error' => $e->getMessage()
            ]);
            return $archivo;
        }
    }
}
