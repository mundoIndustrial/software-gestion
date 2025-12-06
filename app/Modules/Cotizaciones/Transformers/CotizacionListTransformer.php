<?php

namespace App\Modules\Cotizaciones\Transformers;

use App\Modules\Cotizaciones\Contracts\CotizacionTransformerInterface;
use App\Modules\Cotizaciones\DTOs\CotizacionListDto;
use Illuminate\Support\Collection;

/**
 * CotizacionListTransformer
 * 
 * Transformador de datos para lista de cotizaciones
 * Responsabilidad única: transformación de datos
 * Principio: Open/Closed (OCP)
 */
class CotizacionListTransformer implements CotizacionTransformerInterface
{
    /**
     * Transformar una cotización para vista
     */
    public function transform($cotizacion): array
    {
        $dto = CotizacionListDto::fromModel($cotizacion);
        
        return [
            'id' => $dto->id,
            'numero_cotizacion' => $dto->numero_cotizacion,
            'cliente' => $dto->cliente,
            'tipo' => $this->mapTipo($dto->tipo),
            'tipo_raw' => $dto->tipo,
            'estado' => $dto->estado,
            'estado_label' => $this->mapEstado($dto->estado),
            'created_at' => $dto->created_at->format('d/m/Y'),
            'created_at_full' => $dto->created_at,
            'imageUrl' => $dto->imageUrl,
        ];
    }

    /**
     * Transformar colección de cotizaciones
     */
    public function transformCollection($cotizaciones): array
    {
        if ($cotizaciones instanceof Collection || is_array($cotizaciones)) {
            return collect($cotizaciones)
                ->map(fn($cotizacion) => $this->transform($cotizacion))
                ->toArray();
        }

        return array_map(fn($cot) => $this->transform($cot), $cotizaciones->all());
    }

    /**
     * Mapear tipo de cotización a etiqueta legible
     */
    private function mapTipo(string $tipo): string
    {
        return match ($tipo) {
            'P' => 'Prenda',
            'B' => 'Logo',
            'PB' => 'Prenda/Bordado',
            default => 'Desconocido',
        };
    }

    /**
     * Mapear estado a etiqueta legible
     */
    private function mapEstado(string $estado): string
    {
        return match ($estado) {
            'ENVIADA_ASESOR' => 'Enviada (Asesor)',
            'APROBADA_CONTADOR' => 'Aprobada (Contador)',
            'APROBADA_COTIZACIONES' => 'Aprobada (Cotizaciones)',
            'RECHAZADA' => 'Rechazada',
            'BORRADOR' => 'Borrador',
            default => ucfirst(strtolower($estado)),
        };
    }
}
