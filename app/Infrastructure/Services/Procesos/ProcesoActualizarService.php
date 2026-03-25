<?php

namespace App\Infrastructure\Services\Procesos;

use App\Infrastructure\Services\Pedidos\ProcesoFotoService;
use App\Models\PedidosProcesosPrendaDetalle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * ProcesoActualizarService
 *
 * Encapsula toda la lógica de negocio para actualizar un proceso específico
 * de prenda, extraída del controller para respetar SRP.
 *
 * Responsabilidades:
 * - Procesar imágenes nuevas subidas (files)
 * - Resolver la lista final de imágenes (merge/reemplazo)
 * - Normalizar ubicaciones doble-encodeadas
 * - Sincronizar imágenes en tabla pedidos_procesos_imagenes
 * - Sincronizar tallas en tabla pedidos_procesos_prenda_tallas
 * - Validar y persistir campos del proceso
 */
class ProcesoActualizarService
{
    public function __construct(
        private readonly ProcesoFotoService $fotoService,
    ) {}

    /**
     * Actualizar proceso específico de prenda.
     *
     * @param PedidosProcesosPrendaDetalle $proceso Modelo recuperado desde BD
     * @param array                        $inputData Datos crudos del request (FormData o JSON)
     * @param array<UploadedFile>          $archivos  Archivos de imágenes subidos
     * @return array Resumen del resultado
     * @throws ValidationException
     */
    public function actualizar(
        PedidosProcesosPrendaDetalle $proceso,
        array $inputData,
        array $archivos = []
    ): array {
        // 1. Procesar archivos de imágenes nuevas
        $imagenesNuevasRutas = $this->procesarArchivosImagenes($archivos);

        // 2. Resolver lista final de imágenes y decidir si guardar
        [$imagenesFinales, $debeActualizarImagenes] = $this->resolverImagenesFinales(
            $inputData,
            $imagenesNuevasRutas
        );

        // Inyectar en inputData solo si debe actualizarse
        if ($debeActualizarImagenes) {
            $inputData['imagenes'] = $imagenesFinales;
        } else {
            unset($inputData['imagenes']);
        }

        // 3. Decodificar JSON strings que llegan como strings desde FormData
        $inputData = $this->decodificarFormData($inputData);

        // 4. Validar
        $validated = $this->validar($inputData);

        // 5. Actualizar campos en el modelo
        if (isset($validated['ubicaciones'])) {
            $limpias = array_filter($this->normalizarUbicaciones($validated['ubicaciones']));
            $proceso->ubicaciones = json_encode(array_values($limpias));
        }

        if (array_key_exists('observaciones', $validated)) {
            $proceso->observaciones = $validated['observaciones'];
        }

        if (isset($validated['tipo_proceso_id'])) {
            $proceso->tipo_proceso_id = $validated['tipo_proceso_id'];
        }

        $proceso->save();

        // 6. Sincronizar imágenes en tabla relacional
        if (isset($validated['imagenes'])) {
            $this->sincronizarImagenes($proceso->id, $validated['imagenes']);
        }

        // 7. Sincronizar tallas en tabla relacional
        if (isset($validated['tallas'])) {
            $this->sincronizarTallas($proceso->id, $validated['tallas']);
        }

        return [
            'id'          => $proceso->id,
            'actualizados' => array_keys($validated),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Imágenes
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Subir y convertir los archivos de imagen al storage, retornar sus rutas webp.
     *
     * @param array<UploadedFile> $archivos
     * @return array<string>
     */
    public function procesarArchivosImagenes(array $archivos): array
    {
        $rutas = [];

        foreach ($archivos as $archivo) {
            if (!($archivo instanceof UploadedFile) || !$archivo->isValid()) {
                continue;
            }

            try {
                $resultado = $this->fotoService->procesarFoto($archivo);
                $rutas[] = $resultado['ruta_webp'] ?? $resultado;
            } catch (\Exception $e) {
                Log::warning('[ProcesoActualizarService] Error procesando imagen', [
                    'archivo' => $archivo->getClientOriginalName(),
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return $rutas;
    }

    /**
     * Determinar la lista final de imágenes y si debe persistirse.
     *
     * Lógica:
     *   - Si llega 'imagenes' en inputData → el usuario cambió imágenes, usar como base.
     *   - Si llega solo 'imagenes_existentes' → usuario no cambió, mantener.
     *   - Si hay archivos nuevos subidos → agregarlos siempre.
     *
     * @return array{0: array<string>, 1: bool} [imagenesFinales, debeActualizar]
     */
    private function resolverImagenesFinales(array $inputData, array $imagenesNuevasRutas): array
    {
        $imagenesFinales    = [];
        $debeActualizar     = false;

        // Imágenes existentes (enviadas por el cliente como JSON string)
        $imagenesExistentes = [];
        if (isset($inputData['imagenes_existentes']) && is_string($inputData['imagenes_existentes'])) {
            $decoded = json_decode($inputData['imagenes_existentes'], true);
            $imagenesExistentes = is_array($decoded) ? $decoded : [];
        }

        if (isset($inputData['imagenes'])) {
            // El cliente envió cambios explícitos
            $base = is_string($inputData['imagenes'])
                ? (json_decode($inputData['imagenes'], true) ?? [])
                : (array)$inputData['imagenes'];

            $imagenesFinales = $base;
            $debeActualizar  = true;
        } elseif (!empty($imagenesExistentes)) {
            // Sin cambios de imágenes; conservar las existentes
            $imagenesFinales = $imagenesExistentes;
            $debeActualizar  = false;
        }

        // Siempre agregar los archivos recién subidos
        if (!empty($imagenesNuevasRutas)) {
            $imagenesFinales = array_merge($imagenesFinales, $imagenesNuevasRutas);
            $debeActualizar  = true;
        }

        // Limpiar y deduplicar
        $imagenesFinales = $this->limpiarImagenes($imagenesFinales);

        return [$imagenesFinales, $debeActualizar];
    }

    /**
     * Limpiar array de imágenes: solo strings no vacíos, sin duplicados.
     *
     * @param array $imagenes
     * @return array<string>
     */
    private function limpiarImagenes(array $imagenes): array
    {
        $limpias = [];

        foreach ($imagenes as $img) {
            if (is_string($img) && $img !== '' && $img !== 'null') {
                $limpias[] = $img;
            } elseif (is_array($img)) {
                $ruta = $img['ruta_webp'] ?? ($img[0] ?? null);
                if (is_string($ruta) && $ruta !== '') {
                    $limpias[] = $ruta;
                }
            } elseif (is_object($img) && isset($img->ruta_webp) && $img->ruta_webp !== '') {
                $limpias[] = (string)$img->ruta_webp;
            }
        }

        return array_values(array_unique($limpias));
    }

    /**
     * Sincronizar imágenes en tabla pedidos_procesos_imagenes (sin eliminar las que se mantienen).
     */
    private function sincronizarImagenes(int $procesoId, array $imagenesNuevas): void
    {
        $imagenesNuevas = array_values(array_filter($imagenesNuevas, fn($i) =>
            is_string($i) && trim($i) !== '' && $i !== 'null'
        ));

        $imagenesActuales = DB::table('pedidos_procesos_imagenes')
            ->where('proceso_prenda_detalle_id', $procesoId)
            ->pluck('ruta_webp')
            ->toArray();

        // Eliminar las que ya no están
        $aEliminar = array_diff($imagenesActuales, $imagenesNuevas);
        if (!empty($aEliminar)) {
            DB::table('pedidos_procesos_imagenes')
                ->where('proceso_prenda_detalle_id', $procesoId)
                ->whereIn('ruta_webp', $aEliminar)
                ->delete();
        }

        // Agregar las que son nuevas
        $aAgregar = array_diff($imagenesNuevas, $imagenesActuales);
        if (!empty($aAgregar)) {
            $proximoOrden = DB::table('pedidos_procesos_imagenes')
                ->where('proceso_prenda_detalle_id', $procesoId)
                ->max('orden') ?? 0;

            foreach (array_values($aAgregar) as $idx => $ruta) {
                DB::table('pedidos_procesos_imagenes')->insert([
                    'proceso_prenda_detalle_id' => $procesoId,
                    'ruta_original'             => null,
                    'ruta_webp'                 => trim($ruta),
                    'orden'                     => $proximoOrden + $idx + 1,
                    'es_principal'              => 0,
                    'created_at'                => now(),
                    'updated_at'                => now(),
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tallas
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Sincronizar tallas de proceso en tabla pedidos_procesos_prenda_tallas.
     * Soporta géneros DAMA y CABALLERO.
     */
    private function sincronizarTallas(int $procesoId, array $tallas): void
    {
        $this->sincronizarTallasGenero($procesoId, 'DAMA', $tallas['dama'] ?? []);
        $this->sincronizarTallasGenero($procesoId, 'CABALLERO', $tallas['caballero'] ?? []);
    }

    /** Sincronizar tallas de un género específico. */
    private function sincronizarTallasGenero(int $procesoId, string $genero, array $tallasNuevas): void
    {
        $tallasActuales = DB::table('pedidos_procesos_prenda_tallas')
            ->where('proceso_prenda_detalle_id', $procesoId)
            ->where('genero', $genero)
            ->pluck('cantidad', 'talla')
            ->toArray();

        // Eliminar las que ya no existen o quedaron en 0
        foreach ($tallasActuales as $talla => $cantidad) {
            if (!isset($tallasNuevas[$talla]) || $tallasNuevas[$talla] == 0) {
                DB::table('pedidos_procesos_prenda_tallas')
                    ->where('proceso_prenda_detalle_id', $procesoId)
                    ->where('genero', $genero)
                    ->where('talla', $talla)
                    ->delete();
            }
        }

        // Insertar/actualizar las que sí tienen cantidad
        foreach ($tallasNuevas as $talla => $cantidad) {
            if ((int)$cantidad > 0) {
                DB::table('pedidos_procesos_prenda_tallas')
                    ->updateOrInsert(
                        ['proceso_prenda_detalle_id' => $procesoId, 'genero' => $genero, 'talla' => $talla],
                        ['cantidad' => (int)$cantidad, 'updated_at' => now()]
                    );
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Normalización de datos
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Decodificar campos que llegan como JSON strings desde FormData.
     */
    private function decodificarFormData(array $data): array
    {
        foreach (['ubicaciones', 'tallas', 'imagenes'] as $campo) {
            if (isset($data[$campo]) && is_string($data[$campo])) {
                $decoded = json_decode($data[$campo], true);
                if (is_array($decoded)) {
                    $data[$campo] = $decoded;
                }
            }
        }

        return $data;
    }

    /**
     * Validar datos del proceso.
     *
     * @throws ValidationException
     */
    private function validar(array $data): array
    {
        $validator = Validator::make($data, [
            'tipo_proceso_id'  => 'nullable|integer|exists:tipos_proceso,id',
            'ubicaciones'      => 'nullable|array',
            'ubicaciones.*'    => 'nullable|string',
            'imagenes'         => 'nullable|array',
            'imagenes.*'       => 'nullable|string',
            'observaciones'    => 'nullable|string|max:1000',
            'tallas'           => 'nullable|array',
            'tallas.dama'      => 'nullable|array',
            'tallas.caballero' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Normalizar ubicaciones que pueden llegar doble-encodeadas desde el cliente.
     *
     * @param array $ubicaciones
     * @return array<string>
     */
    public function normalizarUbicaciones(array $ubicaciones): array
    {
        $normalizadas = [];

        foreach ($ubicaciones as $ub) {
            $valor = $this->extraerValorUbicacion($ub);

            if (is_string($valor) && trim($valor) !== '') {
                $normalizadas[] = trim($valor);
            }
        }

        return $normalizadas;
    }

    /** Extraer el string simple de una ubicación (puede venir como JSON string o array). */
    private function extraerValorUbicacion(mixed $ub): ?string
    {
        if (is_string($ub)) {
            // Quitar comillas escapadas: "\"valor\"" → "valor"
            $ub = preg_replace('/^["\\\\]*|["\\\\]*$/', '', $ub);
            $ub = trim($ub);
        }

        // String que parece JSON array u objeto
        if (is_string($ub) && (str_starts_with($ub, '[') || str_starts_with($ub, '{'))) {
            $parsed = json_decode($ub, true);

            if (is_array($parsed) && !empty($parsed)) {
                return (string)$parsed[0];
            }

            if (is_array($parsed) && isset($parsed['ubicacion'])) {
                return (string)$parsed['ubicacion'];
            }

            return null;
        }

        if (is_array($ub) && isset($ub['ubicacion'])) {
            return (string)$ub['ubicacion'];
        }

        return is_string($ub) ? $ub : null;
    }
}
