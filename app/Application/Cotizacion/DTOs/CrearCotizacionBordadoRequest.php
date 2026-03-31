<?php

namespace App\Application\Cotizacion\DTOs;

class CrearCotizacionBordadoRequest
{
    public function __construct(
        public readonly ?int $cliente_id,
        public readonly ?string $cliente,
        public readonly string $tipo_venta,
        public readonly array $especificaciones,
        public readonly array $tecnicas,
        public readonly array $observaciones_generales,
        public readonly ?string $descripcion,
        public readonly ?string $observaciones_tecnicas,
        public readonly bool $es_borrador,
        public readonly array $archivos_tecnicas = [],
        public readonly array $archivos_telas = [],
        public readonly array $tecnicas_fotos_a_borrar = [],
    ) {
    }

    public static function fromRequest($request): self
    {
        $tecnicas = $request->input('tecnicas', '[]');
        if (is_string($tecnicas)) {
            $tecnicas = json_decode($tecnicas, true) ?? [];
        }

        $observacionesGenerales = $request->input('observaciones_generales', '[]');
        if (is_string($observacionesGenerales)) {
            $observacionesGenerales = json_decode($observacionesGenerales, true) ?? [];
        }

        $especificaciones = $request->input('especificaciones', []);
        if (is_string($especificaciones)) {
            $especificaciones = json_decode($especificaciones, true) ?? [];
        }

        return new self(
            cliente_id: $request->input('cliente_id'),
            cliente: $request->input('cliente'),
            tipo_venta: $request->input('tipo_venta', 'M'),
            especificaciones: $especificaciones,
            tecnicas: $tecnicas,
            observaciones_generales: $observacionesGenerales,
            descripcion: $request->input('descripcion'),
            observaciones_tecnicas: $request->input('observaciones_tecnicas'),
            es_borrador: ($request->input('action') ?? $request->input('accion')) === 'borrador',
            archivos_tecnicas: $request->files->all(),
            archivos_telas: $request->files->all(),
        );
    }
}
