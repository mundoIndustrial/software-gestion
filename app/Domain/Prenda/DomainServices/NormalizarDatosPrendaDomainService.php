<?php

namespace App\Domain\Prenda\DomainServices;

use App\Domain\Prenda\Entities\Prenda;

class NormalizarDatosPrendaDomainService
{
    /**
     * Transforma Prenda al formato que el frontend espera
     */
    public function normalizarParaFrontend(Prenda $prenda): array
    {
        return [
            'exito' => true,
            'datos' => [
                'id' => $prenda->id()->valor(),
                'nombre_prenda' => $prenda->nombre()->valor(),
                'descripcion' => $prenda->descripcion()->valor(),
                'genero' => $prenda->genero()->nombre(),
                'genero_id' => $prenda->genero()->id(),
                'origen' => $prenda->origen()->valor(),
                'tipo_cotizacion' => $prenda->tipoCotizacion()->valor(),
                'telas' => $prenda->telas()->paraArray(),
                'procesos' => $prenda->procesos()->paraArray(),
                'variaciones' => $prenda->variaciones()->paraArray(),
            ],
            'errores' => [],
        ];
    }

    /**
     * Transforma Prenda en formato para guardar en BD
     */
    public function normalizarParaPersistencia(Prenda $prenda): array
    {
        return [
            'id' => $prenda->id()->valor(),
            'nombre' => $prenda->nombre()->valor(),
            'descripcion' => $prenda->descripcion()->valor(),
            'genero' => $prenda->genero()->id(),
            'origen' => $prenda->origen()->valor(),
            'tipo_cotizacion' => $prenda->tipoCotizacion()->valor(),
        ];
    }

    /**
     * Normaliza errores de validación para frontend
     */
    public function normalizarErrores(array $erroresValidacion): array
    {
        return [
            'exito' => false,
            'datos' => null,
            'errores' => $erroresValidacion,
        ];
    }

    /**
     * Combina Prenda con datos de relaciones para respuesta completa
     */
    public function detalleCompleto(Prenda $prenda, array $relaciones = []): array
    {
        $base = $this->normalizarParaFrontend($prenda);

        // Agregar relaciones si vienen
        if (isset($relaciones['usuario'])) {
            $base['datos']['usuario'] = $relaciones['usuario'];
        }

        if (isset($relaciones['historial'])) {
            $base['datos']['historial'] = $relaciones['historial'];
        }

        if (isset($relaciones['estado_actual'])) {
            $base['datos']['estado_actual'] = $relaciones['estado_actual'];
        }

        return $base;
    }

    /**
     * Normaliza lista de prendas para listados
     */
    public function normalizarListado(array $prendas): array
    {
        return array_map(function (Prenda $prenda) {
            return [
                'id' => $prenda->id()->valor(),
                'nombre' => $prenda->nombre()->valor(),
                'genero' => $prenda->genero()->nombre(),
                'origen' => $prenda->origen()->valor(),
                'tipo_cotizacion' => $prenda->tipoCotizacion()->valor(),
                'telas' => $prenda->telas()->contar(),
                'procesos' => $prenda->procesos()->contar(),
                'variaciones' => $prenda->variaciones()->contar(),
            ];
        }, $prendas);
    }
}
