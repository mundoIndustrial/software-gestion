<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Application\Pedidos\Exceptions\ModoImagenesEppInvalidoException;
use App\Application\Services\ImageUploadService;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PedidoImagenesEppService
{
    public function __construct(
        private ImageUploadService $imageUploadService,
    ) {}

    public function procesarYAsignarEpps($request, int $pedidoId, array $epps): void
    {
        Log::info('[PedidoImagenesService] Procesando EPPs', [
            'pedido_id' => $pedidoId,
            'epps_count' => count($epps),
        ]);

        foreach ($epps as $eppData) {
            if (empty($eppData['epp_id'])) {
                Log::warning('[PedidoImagenesService] EPP sin epp_id');
                continue;
            }

            $eppCatalogo = DB::table('epps')->where('id', $eppData['epp_id'])->first();
            if (!$eppCatalogo) {
                Log::warning('[PedidoImagenesService] EPP no encontrado en catalogo', [
                    'epp_id' => $eppData['epp_id'],
                ]);
                continue;
            }

            $localId = trim((string) ($eppData['_epp_form_identifier'] ?? ''));

            // Idempotencia: si ya existe un EPP con este local_id en el pedido, reusar
            $pedidoEpp = null;
            if ($localId !== '') {
                $pedidoEpp = PedidoEpp::where('pedido_produccion_id', $pedidoId)
                    ->where('local_id', $localId)
                    ->first();

                if ($pedidoEpp) {
                    Log::info('[PedidoImagenesService] EPP nuevo ya existia (idempotencia), reusando', [
                        'pedido_id' => $pedidoId,
                        'local_id' => $localId,
                        'pedido_epp_id' => $pedidoEpp->id,
                    ]);
                }
            }

            if (!$pedidoEpp) {
                $pedidoEpp = PedidoEpp::create([
                    'pedido_produccion_id' => $pedidoId,
                    'epp_id' => $eppData['epp_id'],
                    'cantidad' => $eppData['cantidad'] ?? 1,
                    'observaciones' => $eppData['observaciones'] ?? null,
                    'local_id' => $localId !== '' ? $localId : null,
                ]);
            }

            $eppIdentifier = $localId !== '' ? $localId : $pedidoEpp->id;
            $this->procesarImagenesEpp($request, $pedidoId, $eppIdentifier, $eppData, $pedidoEpp);
        }
    }

    public function procesarImagenesDeEpps($request, int $pedidoId, array $epps): void
    {
        foreach ($epps as $eppData) {
            $eppIdentifier = $eppData['_epp_form_identifier'] ?? $eppData['pedido_epp_id'] ?? null;
            $pedidoEpp = $this->obtenerPedidoEppParaImagenes($eppData, $pedidoId);
            if ($pedidoEpp === null) {
                continue;
            }

            $modoImagenes = $this->resolverModoImagenesEpp($request, $eppData);
            if ($modoImagenes === null) {
                continue;
            }
            if ($modoImagenes === 'upload') {
                $this->procesarImagenesEppModoUpload($request, $pedidoId, $eppIdentifier, $eppData, $pedidoEpp);
                continue;
            }

            $this->procesarImagenesEppModoReuse($pedidoEpp, $eppData, $pedidoId);
        }
    }

    private function procesarImagenesEpp($request, int $pedidoId, $eppIdentifier, array $eppData, PedidoEpp $pedidoEpp): void
    {
        $imagenesGuardadas = $this->procesarArchivosEppDesdeRequest($request, $pedidoId, $eppIdentifier, $eppData, $pedidoEpp);
        if ($imagenesGuardadas === 0) {
            Log::warning('[PedidoImagenesService] EPP sin imagenes procesadas', [
                'pedido_epp_id' => $pedidoEpp->id,
                'epp_id' => $eppData['epp_id'],
            ]);
        }
    }

    private function obtenerPedidoEppParaImagenes(array $eppData, int $pedidoId): ?PedidoEpp
    {
        $pedidoEppId = (int) ($eppData['pedido_epp_id'] ?? 0);
        if ($pedidoEppId > 0) {
            $pedidoEpp = PedidoEpp::where('pedido_produccion_id', $pedidoId)
                ->where('id', $pedidoEppId)
                ->first();

            if ($pedidoEpp) {
                return $pedidoEpp;
            }
        }

        $eppId = $eppData['epp_id'] ?? null;
        if (!$eppId) {
            Log::warning('[PedidoImagenesService] EPP sin epp_id para procesar imagenes', [
                'pedido_id' => $pedidoId,
            ]);
            return null;
        }

        $pedidoEpp = PedidoEpp::where('pedido_produccion_id', $pedidoId)
            ->where('epp_id', $eppId)
            ->orderByDesc('id')
            ->first();
        if (!$pedidoEpp) {
            Log::warning('[PedidoImagenesService] EPP no encontrado para procesar imagenes', [
                'pedido_id' => $pedidoId,
                'epp_id' => $eppId,
                'pedido_epp_id' => $pedidoEppId > 0 ? $pedidoEppId : null,
            ]);
            return null;
        }

        return $pedidoEpp;
    }

    private function procesarArchivosEppDesdeRequest($request, int $pedidoId, $eppIdentifier, array $eppData, PedidoEpp $pedidoEpp): int
    {
        $imagenesGuardadas = 0;
        $imgIdx = 0;

        while (true) {
            $formKey = "epps_{$eppIdentifier}_imagenes_{$imgIdx}";
            if (!$request->hasFile($formKey)) {
                break;
            }

            $this->guardarImagenEppDesdeRequest($request, $pedidoId, $formKey, $eppData, $pedidoEpp, $imgIdx);
            $imagenesGuardadas++;
            $imgIdx++;
        }

        return $imagenesGuardadas;
    }

    private function resolverModoImagenesEpp($request, array $eppData): ?string
    {
        $eppId = (int) ($eppData['epp_id'] ?? 0);
        $modo = strtolower(trim((string) ($eppData['modo_imagenes'] ?? '')));
        $eppIdentifier = $eppData['_epp_form_identifier'] ?? $eppData['pedido_epp_id'] ?? null;
        $tieneArchivos = $eppIdentifier !== null && $request->hasFile("epps_{$eppIdentifier}_imagenes_0");

        if ($modo === '') {
            return $this->inferirModoDesdeContexto($eppId, $eppData, $tieneArchivos);
        }

        if (!in_array($modo, ['upload', 'reuse'], true)) {
            throw ModoImagenesEppInvalidoException::modoNoSoportado($eppId, $modo);
        }

        if ($modo === 'upload' && !$tieneArchivos) {
            return $this->fallbackUploadSinArchivos($eppId, $eppIdentifier, $eppData);
        }

        return $modo;
    }

    private function inferirModoDesdeContexto(int $eppId, array $eppData, bool $tieneArchivos): ?string
    {
        $imagenes = $eppData['imagenes'] ?? [];
        if (!is_array($imagenes) || empty($imagenes)) {
            return null;
        }

        $modo = $tieneArchivos ? 'upload' : 'reuse';
        Log::info('[PedidoImagenesEppService] modo_imagenes vacío, asignando automáticamente', [
            'epp_id' => $eppId,
            'modo_asignado' => $modo,
            'tiene_archivos' => $tieneArchivos,
        ]);

        return $modo;
    }

    private function fallbackUploadSinArchivos(int $eppId, $eppIdentifier, array $eppData): ?string
    {
        $imagenes = $eppData['imagenes'] ?? [];
        if (is_array($imagenes) && !empty($imagenes)) {
            Log::info('[PedidoImagenesEppService] EPP en modo upload sin archivos, cambiando a reuse', [
                'epp_id' => $eppId,
                'epp_identifier' => $eppIdentifier,
                'imagenes_existentes' => count($imagenes),
            ]);
            return 'reuse';
        }

        return null;
    }

    private function guardarImagenEppDesdeRequest($request, int $pedidoId, string $formKey, array $eppData, PedidoEpp $pedidoEpp, int $imgIdx): void
    {
        $archivo = $request->file($formKey);
        $nombreBase = $this->generarNombreBaseImagenEpp($eppData, $imgIdx);
        $resultado = $this->imageUploadService->guardarImagenDirecta(
            $archivo,
            $pedidoId,
            'epps',
            null,
            $nombreBase
        );

        PedidoEppImagen::create([
            'pedido_epp_id' => $pedidoEpp->id,
            'ruta_original' => $resultado['webp'],
            'ruta_web' => $resultado['webp'],
            'orden' => $imgIdx + 1,
            'principal' => $imgIdx === 0 ? 1 : 0,
        ]);
    }

    private function procesarImagenesEppModoUpload($request, int $pedidoId, $eppIdentifier, array $eppData, PedidoEpp $pedidoEpp): void
    {
        $imagenesGuardadas = $this->procesarArchivosEppDesdeRequest($request, $pedidoId, $eppIdentifier, $eppData, $pedidoEpp);
        if ($imagenesGuardadas === 0) {
            $imagenesJson = $eppData['imagenes'] ?? [];
            if (is_array($imagenesJson) && !empty($imagenesJson)) {
                Log::warning('[PedidoImagenesService] EPP en modo upload sin archivos; intentando fallback a reuse', [
                    'pedido_id' => $pedidoId,
                    'pedido_epp_id' => $pedidoEpp->id,
                    'epp_id' => $eppData['epp_id'] ?? null,
                    'imagenes_json_count' => count($imagenesJson),
                ]);
                $this->copiarImagenesEppDesdeUrls($pedidoEpp, $eppData, $imagenesJson, $pedidoId);
                return;
            }

            // Las imágenes de EPP son opcionales; no tumbar la creación del pedido.
            Log::warning('[PedidoImagenesService] EPP en modo upload sin archivos ni URLs reutilizables; se omite guardado de imágenes', [
                'pedido_id' => $pedidoId,
                'pedido_epp_id' => $pedidoEpp->id,
                'epp_id' => $eppData['epp_id'] ?? null,
            ]);
        }
    }

    private function procesarImagenesEppModoReuse(PedidoEpp $pedidoEpp, array $eppData, int $pedidoId): void
    {
        $imagenesJson = $eppData['imagenes'] ?? [];
        if (!is_array($imagenesJson) || empty($imagenesJson)) {
            // Las imágenes de EPP son opcionales.
            Log::warning('[PedidoImagenesService] EPP en modo reuse sin imágenes; se omite guardado de imágenes', [
                'pedido_id' => $pedidoId,
                'pedido_epp_id' => $pedidoEpp->id,
                'epp_id' => $eppData['epp_id'] ?? null,
            ]);
            return;
        }

        $this->copiarImagenesEppDesdeUrls($pedidoEpp, $eppData, $imagenesJson, $pedidoId);
    }

    private function copiarImagenesEppDesdeUrls($pedidoEpp, $eppData, $imagenesJson, int $pedidoId): void
    {
        $destDir = $this->asegurarDirectorioDestinoEpp($pedidoId);
        $orden = 1;
        foreach ($imagenesJson as $img) {
            $url = $this->extraerUrlDesdeImagenJson($img);
            if (!$url) {
                continue;
            }

            $relative = $this->resolverRutaRelativaDesdeUrl($url);
            if (!$this->archivoOrigenExiste($relative)) {
                continue;
            }

            $this->copiarImagenEppIndividual($pedidoEpp, $eppData, $relative, $destDir, $orden);
            $orden++;
        }
    }

    private function asegurarDirectorioDestinoEpp(int $pedidoId): string
    {
        $destDir = "pedidos/{$pedidoId}/epp";
        if (!Storage::disk('public')->exists($destDir)) {
            Storage::disk('public')->makeDirectory($destDir);
        }
        return $destDir;
    }

    private function extraerUrlDesdeImagenJson(mixed $img): ?string
    {
        if (is_string($img)) {
            return $img;
        }
        if (!is_array($img)) {
            return null;
        }
        return $img['url'] ?? $img['preview'] ?? null;
    }

    private function resolverRutaRelativaDesdeUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $pos = strpos($path, '/storage/');
        if ($pos !== false) {
            $relative = ltrim(substr($path, $pos + strlen('/storage/')), '/');
        } else {
            $relative = ltrim($path !== '' ? $path : $url, '/');
        }
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }
        return $relative;
    }

    private function archivoOrigenExiste(string $relative): bool
    {
        return $relative !== '' && Storage::disk('public')->exists($relative);
    }

    private function copiarImagenEppIndividual($pedidoEpp, array $eppData, string $relative, string $destDir, int $orden): void
    {
        $destName = $this->generarNombreBaseImagenEpp($eppData, $orden - 1) . '.webp';
        $destRelative = $destDir . '/' . $destName;

        if (!Storage::disk('public')->copy($relative, $destRelative)) {
            return;
        }

        PedidoEppImagen::create([
            'pedido_epp_id' => $pedidoEpp->id,
            'ruta_original' => $destRelative,
            'ruta_web' => $destRelative,
            'orden' => $orden,
            'principal' => $orden === 1 ? 1 : 0,
        ]);
    }

    private function generarNombreBaseImagenEpp(array $eppData, int $imgIdx): string
    {
        $eppId = (int) ($eppData['epp_id'] ?? 0);
        $timestamp = now()->format('YmdHis');
        $random = substr(md5(uniqid((string) $eppId, true)), 0, 8);

        return "epp_{$eppId}_img_{$imgIdx}_{$timestamp}_{$random}";
    }
}
