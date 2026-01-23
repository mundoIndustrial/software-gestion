<?php

namespace App\Domain\Pedidos\Services;

use App\Models\Cotizacion;
use Illuminate\Support\Collection;

class TransformadorCotizacionService
{
    public function transformarCotizacionesParaFrontend(Collection $cotizaciones): array
    {
        return $cotizaciones->map(function (Cotizacion $cot) {
            return [
                'id' => $cot->id,
                'numero_cotizacion' => $cot->numero_cotizacion,
                'numero' => $cot->numero_cotizacion ?: 'COT-' . $cot->id,
                'cliente' => $cot->cliente?->nombre ?? '',
                'asesora' => $cot->asesor?->name ?? auth()->user()->name,
                'formaPago' => $this->extraerFormaPago($cot),
                'prendasCount' => $cot->prendasCotizaciones->count(),
            ];
        })->toArray();
    }

    public function transformarCotizacionDetalle(Cotizacion $cot): array
    {
        return [
            'id' => $cot->id,
            'numero' => $cot->numero_cotizacion,
            'cliente' => $cot->cliente?->nombre ?? '',
            'asesora' => $cot->asesor?->name ?? '',
            'estado' => $cot->estado,
            'formaPago' => $this->extraerFormaPago($cot),
            'prendas' => $cot->prendasCotizaciones->map(fn($prenda) => [
                'id' => $prenda->id,
                'nombre' => $prenda->nombre,
                'descripcion' => $prenda->descripcion,
                'origen' => $prenda->origen,
            ])->toArray(),
        ];
    }

    private function extraerFormaPago(Cotizacion $cot): string
    {
        if (!is_array($cot->especificaciones)) {
            return '';
        }

        $formaPagoArray = $cot->especificaciones['forma_pago'] ?? [];
        
        if (is_array($formaPagoArray) && count($formaPagoArray) > 0) {
            return $formaPagoArray[0]['valor'] ?? '';
        }

        return '';
    }
}

