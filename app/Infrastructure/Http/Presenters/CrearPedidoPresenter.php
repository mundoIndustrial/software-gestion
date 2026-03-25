<?php

namespace App\Infrastructure\Http\Presenters;

use Illuminate\Support\Collection;

/**
 * CrearPedidoPresenter
 * 
 * ✅ RESPONSABILIDAD ÚNICA: Formatear datos para presentación en vistas Blade
 * 
 * Separa:
 * - Lógica de negocio (UseCases)
 * - Datos crudos (DTOs)
 * - Presentación (Presenter)
 * 
 * Este patrón es clave en Clean Architecture:
 * UseCase → OutputDTO → Presenter → View
 * 
 * Beneficios:
 * ✅ Controller sin lógica de formateo
 * ✅ Presenter reutilizable en múltiples controladores
 * ✅ Fácil cambiar estructura de datos sin tocar lógica
 * ✅ Testeable (presenter = puro formateo)
 */
class CrearPedidoPresenter
{
    /**
     * Preparar datos para la vista crear-pedido-desde-cotizacion
     * 
     * @param object $datosCompartidos Output del UseCase
     * @param Collection|null $cotizaciones Colección de cotizaciones
     * @param bool $modoEdicion Si está en modo edición
     * @param array $datosEdicion Datos específicos de edición
     * @return array
     */
    public function prepararParaVista(
        object $datosCompartidos,
        ?Collection $cotizaciones = null,
        bool $modoEdicion = false,
        array $datosEdicion = []
    ): array {
        return [
            // === COTIZACIONES ===
            'cotizacionesData' => $cotizaciones 
                ? $this->formatearCotizaciones($cotizaciones)
                : [],

            // === DATOS COMPARTIDOS ===
            'pedidos' => $this->formatearPedidos($datosCompartidos->pedidos ?? []),
            'clientes' => $this->formatearClientes($datosCompartidos->clientes ?? []),
            'tallas' => $this->formatearTallas($datosCompartidos->tallas ?? []),
            'tecnicas' => $this->formatearTecnicas($datosCompartidos->tecnicas ?? []),
            'formasPago' => $this->formatearFormasPago($datosCompartidos->formasPago ?? []),

            // === MODO EDICIÓN ===
            'modoEdicion' => $modoEdicion,
            'pedidoEditarId' => $datosEdicion['pedido_id'] ?? null,
            'pedido' => $datosEdicion['pedido'] ?? null,
            'epps' => $datosEdicion['epps'] ?? [],

            // === PLACEHOLDERS ===
            'estados' => [],
            'areas' => [],
        ];
    }

    /**
     * Formatear cotizaciones para la vista
     * 
     * Transforma modelo Eloquent → estructura de vista
     * 
     * @param Collection $cotizaciones
     * @return array
     */
    private function formatearCotizaciones(Collection $cotizaciones): array
    {
        return $cotizaciones->map(function ($cotizacion) {
            return [
                'id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero_cotizacion ?? 'N/A',
                'cliente' => $this->formatearClienteSimple($cotizacion->cliente),
                'tipo_cotizacion' => $cotizacion->tipoCotizacion?->nombre ?? 'Sin tipo',
                'prendas' => $this->formatearPrendasCotizacion($cotizacion->prendas ?? []),
                'logo' => $cotizacion->logoCotizacion 
                    ? $this->formatearLogo($cotizacion->logoCotizacion)
                    : null,
                'fecha_creacion' => $cotizacion->created_at?->format('Y-m-d H:i') ?? null,
                'estado' => $cotizacion->estado ?? 'pendiente',
            ];
        })->toArray();
    }

    /**
     * Formatear prendas de una cotización
     * 
     * @param Collection $prendas
     * @return array
     */
    private function formatearPrendasCotizacion(Collection $prendas): array
    {
        return $prendas->map(function ($prenda) {
            return [
                'id' => $prenda->id,
                'nombre_producto' => $prenda->nombre_producto ?? 'Sin nombre',
                'descripcion' => $prenda->descripcion ?? '',
                'cantidad' => $prenda->cantidad ?? 0,
                'fotos' => $this->formatearFotos($prenda->fotos ?? []),
                'telas' => $this->formatearTelasConFotos($prenda->telaFotos ?? []),
                'variantes' => $this->formatearVariantes($prenda->variantes ?? []),
                'tallajes' => $this->formatearTallajesCotizacion($prenda->tallas ?? []),
            ];
        })->toArray();
    }

    /**
     * Formatear logo de cotización
     * 
     * @param object $logo
     * @return array
     */
    private function formatearLogo(object $logo): array
    {
        return [
            'id' => $logo->id,
            'nombre' => $logo->nombre ?? 'Logo',
            'fotos' => $this->formatearFotos($logo->fotos ?? []),
            'telas' => $this->formatearTelasConFotos($logo->telasPrendas ?? []),
        ];
    }

    /**
     * Formatear fotos
     * 
     * @param Collection|array $fotos
     * @return array
     */
    private function formatearFotos($fotos): array
    {
        if (is_callable([$fotos, 'map'])) {
            $fotos = $fotos->toArray();
        }

        return collect($fotos)->map(function ($foto) {
            return [
                'id' => $foto->id ?? null,
                'ruta' => $foto->ruta ?? $foto->url ?? null,
                'nombre' => $foto->nombre ?? 'Foto',
                'orden' => $foto->orden ?? 1,
            ];
        })->toArray();
    }

    /**
     * Formatear telas con fotos
     * 
     * @param Collection|array $telas
     * @return array
     */
    private function formatearTelasConFotos($telas): array
    {
        if (is_callable([$telas, 'map'])) {
            $telas = $telas->toArray();
        }

        return collect($telas)->map(function ($tela) {
            return [
                'id' => $tela->id ?? null,
                'nombre' => $tela->nombre ?? 'Sin nombre',
                'referencia' => $tela->referencia ?? null,
                'color' => $tela->color ?? 'Sin color',
                'fotos' => $this->formatearFotos($tela->fotos ?? []),
            ];
        })->toArray();
    }

    /**
     * Formatear variantes
     * 
     * @param Collection|array $variantes
     * @return array
     */
    private function formatearVariantes($variantes): array
    {
        if (is_callable([$variantes, 'map'])) {
            $variantes = $variantes->toArray();
        }

        return collect($variantes)->map(function ($variante) {
            return [
                'id' => $variante->id ?? null,
                'nombre' => $variante->nombre ?? 'Variante',
                'valor' => $variante->valor ?? null,
            ];
        })->toArray();
    }

    /**
     * Formatear tallajes de una prenda en cotización
     * 
     * @param Collection|array $tallas
     * @return array
     */
    private function formatearTallajesCotizacion($tallas): array
    {
        if (is_callable([$tallas, 'map'])) {
            $tallas = $tallas->toArray();
        }

        return collect($tallas)->groupBy('genero.nombre')->map(function ($group) {
            return [
                'genero' => $group[0]['genero']['nombre'] ?? 'Sin género',
                'tallas' => collect($group)->map(fn($t) => [
                    'id' => $t['id'] ?? null,
                    'nombre' => $t['nombre'] ?? $t['talla'] ?? null,
                    'cantidad' => $t['cantidad'] ?? 0,
                ])->toArray(),
            ];
        })->values()->toArray();
    }

    /**
     * Formatear pedidos para dropdown/listado
     * 
     * @param Collection|array $pedidos
     * @return array
     */
    private function formatearPedidos($pedidos): array
    {
        if (is_callable([$pedidos, 'map'])) {
            $pedidos = $pedidos->toArray();
        }

        return collect($pedidos)->map(function ($pedido) {
            return [
                'id' => $pedido->id ?? null,
                'numero' => $pedido->numero_pedido ?? $pedido->numero ?? 'N/A',
                'cliente' => $pedido->cliente ?? 'Sin cliente',
                'estado' => $pedido->estado ?? 'pendiente',
                'fecha' => $pedido->created_at?->format('Y-m-d') ?? null,
            ];
        })->toArray();
    }

    /**
     * Formatear clientes para dropdown
     * 
     * @param Collection|array $clientes
     * @return array
     */
    private function formatearClientes($clientes): array
    {
        if (is_callable([$clientes, 'map'])) {
            $clientes = $clientes->toArray();
        }

        return collect($clientes)->map(function ($cliente) {
            return [
                'id' => $cliente->id ?? null,
                'nombre' => $cliente->nombre ?? 'Sin nombre',
                'razon_social' => $cliente->razon_social ?? null,
                'nit' => $cliente->nit ?? null,
                'email' => $cliente->email ?? null,
                'telefono' => $cliente->telefono ?? null,
            ];
        })->toArray();
    }

    /**
     * Formatear cliente simple (para cotizaciones)
     * 
     * @param object|null $cliente
     * @return array
     */
    private function formatearClienteSimple(?object $cliente): array
    {
        if (!$cliente) {
            return ['id' => null, 'nombre' => 'Sin cliente'];
        }

        return [
            'id' => $cliente->id ?? null,
            'nombre' => $cliente->nombre ?? 'Sin nombre',
        ];
    }

    /**
     * Formatear tallas (agrupadas por género)
     * 
     * @param Collection|array $tallas
     * @return array
     */
    private function formatearTallas($tallas): array
    {
        if (is_callable([$tallas, 'map'])) {
            $tallas = $tallas->toArray();
        }

        return collect($tallas)
            ->groupBy(fn($t) => $t->genero?->nombre ?? 'Sin género')
            ->map(function ($group, $genero) {
                return [
                    'genero' => $genero,
                    'tallas' => collect($group)->map(fn($t) => [
                        'id' => $t->id ?? null,
                        'nombre' => $t->nombre ?? 'N/A',
                        'codigo' => $t->codigo ?? null,
                    ])->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Formatear técnicas para dropdown
     * 
     * @param Collection|array $tecnicas
     * @return array
     */
    private function formatearTecnicas($tecnicas): array
    {
        if (is_callable([$tecnicas, 'map'])) {
            $tecnicas = $tecnicas->toArray();
        }

        return collect($tecnicas)->map(function ($tecnica) {
            return [
                'id' => $tecnica->id ?? null,
                'nombre' => $tecnica->nombre ?? 'Sin nombre',
                'codigo' => $tecnica->codigo ?? null,
                'icono' => $tecnica->icono ?? null,
            ];
        })->toArray();
    }

    /**
     * Formatear formas de pago
     * 
     * @param Collection|array $formasPago
     * @return array
     */
    private function formatearFormasPago($formasPago): array
    {
        if (is_callable([$formasPago, 'map'])) {
            $formasPago = $formasPago->toArray();
        }

        return collect($formasPago)->map(function ($forma) {
            return [
                'id' => $forma->id ?? null,
                'nombre' => $forma->nombre ?? 'Sin nombre',
                'codigo' => $forma->codigo ?? null,
                'plazo_dias' => $forma->plazo_dias ?? 0,
            ];
        })->toArray();
    }
}
