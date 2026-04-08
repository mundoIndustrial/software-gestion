<?php

namespace App\Infrastructure\Http\Mappers;

use App\Application\Pedidos\DTOs\GuardarPedidoInputDTO;
use App\Domain\Pedidos\ValueObjects\TipoPedido;
use Illuminate\Http\Request;

/**
 * GuardarPedidoRequestMapper
 * 
 * Convierte Request HTTP → GuardarPedidoInputDTO
 * 
 * Vive en Infrastructure porque:
 * - Solo Infrastructure conoce sobre Request de Laravel
 * - Application solo trabaja con DTOs
 * - Centraliza la extracción de datos del Request
 * 
 * Uso en Controller:
 * ```php
 * $input = GuardarPedidoRequestMapper::fromRequest($request);
 * $output = $this->guardarPedidoUseCase->ejecutar($input);
 * ```
 */
final class GuardarPedidoRequestMapper
{
    /**
     * Mapear Request → DTO
     * 
     * El Controller valida el Request con FormRequest
     * Este mapper solo transforma datos estructurados
     */
    public static function fromRequest(Request $request): GuardarPedidoInputDTO
    {
        // Procesar imágenes (Responsabilidad de Infrastructure)
        $imagenesProcesadas = self::procesarImagenes($request);
        
        // Determinar tipo de pedido (se hace en el Domain mediante Value Object)
        $tipoPedido = TipoPedido::fromCotizacion(
            $request->input('tipo_cotizacion'),
            $request->input('cotizacion_id')
        );

        // Obtener key de productos según el contexto
        $productosKey = $request->has('productos_friendly') ? 'productos_friendly' : 'productos';
        
        return new GuardarPedidoInputDTO(
            clienteId: (string) $request->input('cliente_id'),
            tipoPedido: $tipoPedido,
            datosCliente: self::extraerDatosCliente($request),
            imagenesProcesadas: $imagenesProcesadas,
            productos: $request->input($productosKey, []),
        );
    }

    /**
     * Extraer datos del cliente del Request
     */
    private static function extraerDatosCliente(Request $request): array
    {
        return [
            'cliente' => $request->input('cliente'),
            'cliente_id' => $request->input('cliente_id'),
            'forma_de_pago' => $request->input('forma_de_pago'),
            'tipo_cotizacion' => $request->input('tipo_cotizacion'),
            'cotizacion_id' => $request->input('cotizacion_id'),
            'novedades' => $request->input('novedades'),
            // Agregar otros campos según sea necesario
        ];
    }

    /**
     * Procesar imágenes del Request
     * 
     * Este es un ejemplo - adaptar según tu lógica actual
     * Responsabilidad: convertir UploadedFile → rutas almacenadas
     */
    private static function procesarImagenes(Request $request): ?array
    {
        // Si no hay imágenes para procesar, retornar null
        if (!$request->hasAny(['fotos_color', 'fotos_logo'])) {
            return null;
        }

        // La lógica de procesamiento de archivos sigue aquí
        // pero INDEPENDIENTE del UseCase
        // Ejemplo simplificado:
        $imagenes = [];
        
        if ($request->has('fotos_logo')) {
            $imagenes['logo'] = array_map(fn($file) => $file->store('logos'), $request->file('fotos_logo', []));
        }

        return !empty($imagenes) ? $imagenes : null;
    }
}
