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

        foreach ($epps as $eppIdx => $eppData) {
            if (empty($eppData['epp_id'])) {
                Log::warning('[PedidoImagenesService] EPP sin epp_id', ['epp_idx' => $eppIdx]);
                continue;
            }

            $eppCatalogo = DB::table('epps')->where('id', $eppData['epp_id'])->first();
            if (!$eppCatalogo) {
                Log::warning('[PedidoImagenesService] EPP no encontrado en catalogo', [
                    'epp_id' => $eppData['epp_id'],
                ]);
                continue;
            }

            $pedidoEpp = PedidoEpp::create([
                'pedido_produccion_id' => $pedidoId,
                'epp_id' => $eppData['epp_id'],
                'cantidad' => $eppData['cantidad'] ?? 1,
                'observaciones' => $eppData['observaciones'] ?? null,
            ]);

            $this->procesarImagenesEpp($request, $pedidoId, $eppIdx, $eppData, $pedidoEpp);
        }
    }

    public function procesarImagenesDeEpps($request, int $pedidoId, array $epps): void
    {
        Log::info('[PedidoImagenesService] Procesando imagenes de EPPs', [
            'pedido_id' => $pedidoId,
            'epps_count' => count($epps),
        ]);

        $eppIds = array_map(fn($e) => $e['epp_id'], $epps);
        $pedidosEppMapeados = PedidoEpp::where('pedido_produccion_id', $pedidoId)
            ->whereIn('epp_id', $eppIds)
            ->get()
            ->keyBy('epp_id');

        foreach ($epps as $eppIdx => $eppData) {
            $pedidoEpp = $this->obtenerPedidoEppParaImagenes($pedidosEppMapeados, $eppData, $pedidoId);
            if ($pedidoEpp === null) {
                continue;
            }

            $modoImagenes = $this->resolverModoImagenesEpp($eppData);
            if ($modoImagenes === null) {
                continue;
            }
            if ($modoImagenes === 'upload') {
                $this->procesarImagenesEppModoUpload($request, $pedidoId, $eppIdx, $eppData, $pedidoEpp);
                continue;
            }

            $this->procesarImagenesEppModoReuse($pedidoEpp, $eppData, $pedidoId);
        }
    }

    private function procesarImagenesEpp($request, int $pedidoId, int $eppIdx, array $eppData, PedidoEpp $pedidoEpp): void
    {
        $imagenesGuardadas = $this->procesarArchivosEppDesdeRequest($request, $pedidoId, $eppIdx, $eppData, $pedidoEpp);
        if ($imagenesGuardadas === 0) {
            Log::warning('[PedidoImagenesService] EPP sin imagenes procesadas', [
                'pedido_epp_id' => $pedidoEpp->id,
                'epp_id' => $eppData['epp_id'],
            ]);
        }
    }

    private function obtenerPedidoEppParaImagenes($pedidosEppMapeados, array $eppData, int $pedidoId): ?PedidoEpp
    {
        $eppId = $eppData['epp_id'] ?? null;
        if (!$eppId) {
            Log::warning('[PedidoImagenesService] EPP sin epp_id para procesar imagenes', [
                'pedido_id' => $pedidoId,
            ]);
            return null;
        }

        $pedidoEpp = $pedidosEppMapeados->get($eppId);
        if (!$pedidoEpp) {
            Log::warning('[PedidoImagenesService] EPP no encontrado para procesar imagenes', [
                'pedido_id' => $pedidoId,
                'epp_id' => $eppId,
            ]);
            return null;
        }

        return $pedidoEpp;
    }

    private function procesarArchivosEppDesdeRequest($request, int $pedidoId, int $eppIdx, array $eppData, PedidoEpp $pedidoEpp): int
    {
        $imagenesGuardadas = 0;
        $imgIdx = 0;

        while (true) {
            $formKey = "epps_{$eppIdx}_imagenes_{$imgIdx}";
            if (!$request->hasFile($formKey)) {
                break;
            }

            $this->guardarImagenEppDesdeRequest($request, $pedidoId, $formKey, $eppData, $pedidoEpp, $imgIdx);
            $imagenesGuardadas++;
            $imgIdx++;
        }

        return $imagenesGuardadas;
    }

    private function resolverModoImagenesEpp(array $eppData): ?string
    {
        $eppId = (int) ($eppData['epp_id'] ?? 0);
        $modo = strtolower(trim((string) ($eppData['modo_imagenes'] ?? '')));

        if ($modo === '') {
            $imagenes = $eppData['imagenes'] ?? [];
            if (!is_array($imagenes) || empty($imagenes)) {
                return null;
            }
            throw ModoImagenesEppInvalidoException::modoRequerido($eppId);
        }

        if (!in_array($modo, ['upload', 'reuse'], true)) {
            throw ModoImagenesEppInvalidoException::modoNoSoportado($eppId, $modo);
        }

        return $modo;
    }

    private function guardarImagenEppDesdeRequest($request, int $pedidoId, string $formKey, array $eppData, PedidoEpp $pedidoEpp, int $imgIdx): void
    {
        $archivo = $request->file($formKey);
        $resultado = $this->imageUploadService->guardarImagenDirecta(
            $archivo,
            $pedidoId,
            'epps',
            null,
            "epp_{$eppData['epp_id']}_img_{$imgIdx}"
        );

        PedidoEppImagen::create([
            'pedido_epp_id' => $pedidoEpp->id,
            'ruta_original' => $resultado['webp'],
            'ruta_web' => $resultado['webp'],
            'orden' => $imgIdx + 1,
            'principal' => $imgIdx === 0 ? 1 : 0,
        ]);
    }

    private function procesarImagenesEppModoUpload($request, int $pedidoId, int $eppIdx, array $eppData, PedidoEpp $pedidoEpp): void
    {
        $imagenesGuardadas = $this->procesarArchivosEppDesdeRequest($request, $pedidoId, $eppIdx, $eppData, $pedidoEpp);
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
        $destName = "epp_{$eppData['epp_id']}_img_" . ($orden - 1) . '.webp';
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
}
