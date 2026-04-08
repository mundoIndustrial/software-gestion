<?php

namespace App\Application\SupervisorPedidos\Services;

use App\Application\Pedidos\Services\PrendaPedidoDescriptionFormatter;
use App\Models\PedidoProduccion;
use App\Repositories\EloquentProcesoPrendaDetalleRepository;

class OrderDescriptionBuilder
{
    private EloquentProcesoPrendaDetalleRepository $procesoRepository;
    private PrendaPedidoDescriptionFormatter $prendaDescriptionFormatter;

    public function __construct(
        EloquentProcesoPrendaDetalleRepository $procesoRepository,
        PrendaPedidoDescriptionFormatter $prendaDescriptionFormatter
    )
    {
        $this->procesoRepository = $procesoRepository;
        $this->prendaDescriptionFormatter = $prendaDescriptionFormatter;
    }

    public function build(PedidoProduccion $order): string
    {
        $totalPrendas = $order->prendas->count();

        $descripciones = $order->prendas
            ->map(fn ($prenda, $index) => $this->buildPrendaDescription($prenda, $index + 1, $totalPrendas))
            ->toArray();

        return implode("\n\n", $descripciones);
    }

    private function buildPrendaDescription($prenda, int $position, int $totalPrendas): string
    {
        $base = $this->prendaDescriptionFormatter->formatDetailed($prenda, $position);
        $lineasProc = $this->buildProcesosLines((int) $prenda->id);

        if (!empty($lineasProc)) {
            $base .= "\n" . implode("\n", $lineasProc);
        }

        return $base;
    }

    private function buildProcesosLines(int $prendaId): array
    {
        $procesos = $this->procesoRepository->obtenerProcesosConTallasParaPrenda($prendaId);
        $lineas = [];

        foreach ($procesos as $proc) {
            $modo = $proc['modo_tallas'] ?? null;
            $tipo = strtoupper((string) ($proc['tipo_proceso_nombre'] ?? ''));

            if ($modo === 'general') {
                $this->appendGeneralModeLines($lineas, $proc, $tipo);
                continue;
            }

            if ($modo === 'especifico') {
                $this->appendSpecificModeLines($lineas, $proc, $tipo);
            }
        }

        return $lineas;
    }

    private function appendGeneralModeLines(array &$lineas, array $proc, string $tipo): void
    {
        $tallasObs = $proc['tallas_observaciones'] ?? [];
        if (empty($tallasObs)) {
            return;
        }

        $lineas[] = "\nOBSERVACIONES POR TALLA - {$tipo}:";

        foreach ($tallasObs as $row) {
            $genero = strtoupper((string) ($row['genero'] ?? ''));
            $talla = ($row['talla'] ?? null) !== null ? (string) $row['talla'] : 'SOBREMEDIDA';
            $obs = trim((string) ($row['observaciones'] ?? ''));

            if ($obs !== '') {
                $lineas[] = "- {$genero} {$talla}: {$obs}";
            }
        }
    }

    private function appendSpecificModeLines(array &$lineas, array $proc, string $tipo): void
    {
        $ubicaciones = $this->parseUbicaciones($proc['ubicaciones'] ?? null);
        if (empty($ubicaciones)) {
            return;
        }

        $lineas[] = "\nUBICACIONES - {$tipo}:";

        foreach ($ubicaciones as $ubicacion) {
            $linea = $this->formatUbicacionLine($ubicacion);
            if ($linea !== null) {
                $lineas[] = $linea;
            }
        }
    }

    private function parseUbicaciones($rawUbicaciones): array
    {
        if (empty($rawUbicaciones)) {
            return [];
        }

        $decoded = json_decode($rawUbicaciones, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function formatUbicacionLine($ubicacion): ?string
    {
        if (is_string($ubicacion)) {
            $nombre = trim($ubicacion);
            return $nombre !== '' ? "- {$nombre}" : null;
        }

        if (!is_array($ubicacion)) {
            return null;
        }

        $nombre = trim((string) ($ubicacion['nombre'] ?? $ubicacion['ubicacion'] ?? ''));
        $obs = trim((string) ($ubicacion['observaciones'] ?? $ubicacion['obs'] ?? ''));

        if ($nombre === '' && $obs === '') {
            return null;
        }

        return $obs !== '' ? "- {$nombre}: {$obs}" : "- {$nombre}";
    }
}
