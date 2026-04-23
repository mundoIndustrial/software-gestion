<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarPrendaCompletaDTO;
use App\Application\Pedidos\UseCases\ActualizarPrendaCompletaUseCase as LegacyActualizarPrendaCompletaUseCase;

class ActualizarPrendaCompletaBridge
{
    public function __construct(
        private LegacyActualizarPrendaCompletaUseCase $legacyUseCase,
    ) {}

    public function ejecutarDesdePayload(
        int $prendaId,
        array $prendaPayload,
        array $imagenesProcesadas
    ): void {
        $prendaPayload = $this->inyectarRutasFotosTelaEnAsignaciones(
            $prendaPayload,
            $imagenesProcesadas['fotos_telas_procesadas'] ?? []
        );

        $dto = ActualizarPrendaCompletaDTO::fromRequest(
            $prendaId,
            $prendaPayload,
            $imagenesProcesadas['imagenes_guardadas'] ?? [],
            $imagenesProcesadas['imagenes_existentes'] ?? [],
            $imagenesProcesadas['fotos_telas_procesadas'] ?? [],
            $imagenesProcesadas['fotos_proceso_nuevo'] ?? [],
            $imagenesProcesadas['fotos_color_procesadas'] ?? [],
            $imagenesProcesadas['fotos_proceso_tallas_nuevo'] ?? [],
        );

        $this->legacyUseCase->ejecutar($dto);
    }

    /**
     * En el flujo de actualización de borrador, las imágenes de color/tela llegan como `fotos_telas_procesadas`
     * y los metadatos de vínculo viajan en `fotos_telas` + `asignaciones_colores`.
     * Aquí sincronizamos ambas estructuras para que `imagen_ruta` quede persistida en asignaciones.
     *
     * @param array<string, mixed> $prendaPayload
     * @param array<int, array<string, mixed>> $fotosTelasProcesadas
     * @return array<string, mixed>
     */
    private function inyectarRutasFotosTelaEnAsignaciones(array $prendaPayload, array $fotosTelasProcesadas): array
    {
        if (empty($prendaPayload['asignaciones_colores']) || empty($prendaPayload['fotos_telas']) || empty($fotosTelasProcesadas)) {
            return $prendaPayload;
        }

        $asignaciones = is_string($prendaPayload['asignaciones_colores'])
            ? (json_decode($prendaPayload['asignaciones_colores'], true) ?? [])
            : (is_array($prendaPayload['asignaciones_colores']) ? $prendaPayload['asignaciones_colores'] : []);

        $fotosTelas = is_string($prendaPayload['fotos_telas'])
            ? (json_decode($prendaPayload['fotos_telas'], true) ?? [])
            : (is_array($prendaPayload['fotos_telas']) ? $prendaPayload['fotos_telas'] : []);

        if (empty($asignaciones) || empty($fotosTelas)) {
            return $prendaPayload;
        }

        $placeholdersNuevos = [];
        foreach ($fotosTelas as $fotoMeta) {
            if (!is_array($fotoMeta)) {
                continue;
            }

            $id = $fotoMeta['id'] ?? null;
            $ruta = $fotoMeta['ruta_original'] ?? $fotoMeta['ruta_webp'] ?? null;

            // Los archivos nuevos entran como placeholders sin id/ruta, en el mismo orden de upload.
            if (!empty($id) || !empty($ruta)) {
                continue;
            }

            $placeholdersNuevos[] = $fotoMeta;
        }

        if (empty($placeholdersNuevos)) {
            return $prendaPayload;
        }

        $rutasAsignadas = 0;

        foreach ($placeholdersNuevos as $idx => $fotoMeta) {
            $rutaProcesada = $fotosTelasProcesadas[$idx]['ruta_webp'] ?? $fotosTelasProcesadas[$idx]['ruta_original'] ?? null;
            if (!$rutaProcesada) {
                continue;
            }

            $colorIdMeta = (int) ($fotoMeta['color_id'] ?? 0);
            $telaIdMeta = (int) ($fotoMeta['tela_id'] ?? 0);
            $colorNombreMeta = $this->normalizarTexto($fotoMeta['color_nombre'] ?? '');
            $telaNombreMeta = $this->normalizarTexto($fotoMeta['tela_nombre'] ?? '');

            foreach ($asignaciones as &$asignacion) {
                if (!is_array($asignacion) || !isset($asignacion['colores']) || !is_array($asignacion['colores'])) {
                    continue;
                }

                $telaAsignacionNombre = $this->normalizarTexto($asignacion['tela'] ?? $asignacion['tela_nombre'] ?? '');
                $telaAsignacionId = (int) ($asignacion['tela_id'] ?? 0);

                foreach ($asignacion['colores'] as &$colorData) {
                    if (!is_array($colorData) || !empty($colorData['imagen_ruta'])) {
                        continue;
                    }

                    $colorAsignacionId = (int) ($colorData['color_id'] ?? 0);
                    $colorAsignacionNombre = $this->normalizarTexto($colorData['nombre'] ?? $colorData['color_nombre'] ?? '');

                    $matchPorId = $colorIdMeta > 0
                        && $telaIdMeta > 0
                        && $colorAsignacionId === $colorIdMeta
                        && $telaAsignacionId === $telaIdMeta;

                    $matchPorNombre = !empty($colorNombreMeta)
                        && !empty($telaNombreMeta)
                        && $colorAsignacionNombre === $colorNombreMeta
                        && $telaAsignacionNombre === $telaNombreMeta;

                    if (!$matchPorId && !$matchPorNombre) {
                        continue;
                    }

                    $colorData['imagen_ruta'] = $rutaProcesada;
                    $rutasAsignadas++;
                    continue 3;
                }
                unset($colorData);
            }
            unset($asignacion);
        }

        if ($rutasAsignadas > 0) {
            \Log::info('[ActualizarPrendaCompletaBridge] Rutas de fotos_tela inyectadas en asignaciones_colores', [
                'prenda_id' => $prendaPayload['prenda_id'] ?? $prendaPayload['id'] ?? null,
                'rutas_asignadas' => $rutasAsignadas,
                'placeholders_nuevos' => count($placeholdersNuevos),
                'archivos_procesados' => count($fotosTelasProcesadas),
            ]);
        }

        $prendaPayload['asignaciones_colores'] = $asignaciones;

        return $prendaPayload;
    }

    private function normalizarTexto(mixed $valor): string
    {
        if (!is_string($valor)) {
            return '';
        }

        $valor = trim($valor);
        if ($valor === '') {
            return '';
        }

        $sinAcentos = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);
        if ($sinAcentos === false) {
            $sinAcentos = $valor;
        }

        return strtoupper($sinAcentos);
    }
}
