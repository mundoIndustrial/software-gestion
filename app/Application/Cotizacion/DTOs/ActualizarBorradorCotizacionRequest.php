<?php

namespace App\Application\Cotizacion\DTOs;

class ActualizarBorradorCotizacionRequest
{
    public function __construct(
        public readonly int $cotizacion_id,
        public readonly ?int $cliente_id,
        public readonly ?string $cliente,
        public readonly array $especificaciones,
        public readonly array $tecnicas,
        public readonly array $observaciones_generales,
        public readonly ?string $descripcion,
        public readonly ?string $observaciones_tecnicas,
        public readonly string $tipo_venta,
        public readonly bool $es_envio,
        public readonly array $imagenes_a_borrar = [],
        public readonly array $tecnicas_fotos_a_borrar = [],
        public readonly array $archivos_tecnicas = [],
        public readonly bool $editar_cotizacion = false,
    ) {
    }

    public static function fromRequest($request, int $cotizacionId): self
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

        $imagenesABorrar = $request->input('imagenes_a_borrar', '[]');
        if (is_string($imagenesABorrar)) {
            $imagenesABorrar = json_decode($imagenesABorrar, true) ?? [];
        }

        $tecnicasFotosABorrar = $request->input('tecnicas_fotos_a_borrar', '[]');
        if (is_string($tecnicasFotosABorrar)) {
            $tecnicasFotosABorrar = json_decode($tecnicasFotosABorrar, true) ?? [];
        }

        $action = $request->input('action') ?? $request->input('accion');
        $esEnvio = $action === 'enviar';

        return new self(
            cotizacion_id: $cotizacionId,
            cliente_id: $request->input('cliente_id'),
            cliente: $request->input('cliente'),
            especificaciones: $especificaciones,
            tecnicas: $tecnicas,
            observaciones_generales: $observacionesGenerales,
            descripcion: $request->input('descripcion'),
            observaciones_tecnicas: $request->input('observaciones_tecnicas'),
            tipo_venta: $request->input('tipo_venta_bordado') ?? $request->input('tipo_venta') ?? null,
            es_envio: $esEnvio,
            imagenes_a_borrar: $imagenesABorrar,
            tecnicas_fotos_a_borrar: $tecnicasFotosABorrar,
            archivos_tecnicas: $request->files->all(),
            editar_cotizacion: $request->boolean('editar_cotizacion'),
        );
    }
}
