<?php

namespace App\Application\Bodega\Services;

use App\Models\BodegaDetalleTalla;
use App\Models\BodegaAuditoria;
use App\Models\PedidoProduccion;
use Illuminate\Http\Request;

class BodegaGuardadoService
{
    /**
     * Guardar una fila individual de bodega_detalles_talla (crear o actualizar)
     */
    public function guardarFilaCompleta(
        string $numeroPedido,
        array $datosValidados,
        Request $request
    ): array {
        try {
            $usuario = auth()->user();
            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            
            if (!$pedido) {
                return [
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ];
            }

            $rowHash = $datosValidados['row_hash'];
            $talla = $datosValidados['talla'];
            $genero = $datosValidados['genero'] ?? null;
            $tallaColorId = $datosValidados['talla_color_id'] ?? null;
            $prendaId = $datosValidados['prenda_id'] ?? null;
            $prendaNombre = $datosValidados['prenda_nombre'] ?? null;
            $cantidad = $datosValidados['cantidad'] ?? null;
            
            // Usar row_hash como criterio único principal
            $criteriosBusqueda = [
                'row_hash' => $rowHash,
            ];
            
            // Buscar registro anterior para auditoría
            $detalleAnterior = BodegaDetalleTalla::where($criteriosBusqueda)->first();
            
            // Preparar datos para crear/actualizar
            $datosGuardar = [
                'pedido_produccion_id' => $pedido->id,
                'numero_pedido' => $numeroPedido,
                'talla' => $talla,
                'genero' => $genero,
                'talla_color_id' => $tallaColorId,
                'recibo_prenda_id' => $datosValidados['recibo_prenda_id'] ?? null,
                'prenda_id' => $prendaId,
                'pedido_epp_id' => $datosValidados['pedido_epp_id'] ?? null,
                'asesor' => $datosValidados['asesor'] ?? null,
                'empresa' => $datosValidados['empresa'] ?? null,
                'cantidad' => $cantidad ?? 0,
                'prenda_nombre' => $prendaNombre,
                'pendientes' => $datosValidados['pendientes'] ?? null,
                'fecha_pedido' => $datosValidados['fecha_pedido'] ?? null,
                'fecha_entrega' => $datosValidados['fecha_entrega'] ?? null,
                'area' => $datosValidados['area'] ?? null,
                'estado_bodega' => $datosValidados['estado_bodega'] ?? null,
                'observaciones_bodega' => $datosValidados['observaciones'] ?? null,
                'usuario_bodega_id' => $usuario->id,
                'usuario_bodega_nombre' => $usuario->name,
            ];
            
            \Log::info('[GUARDAR FILA] Criterios de búsqueda y datos:', [
                'criterios' => $criteriosBusqueda,
                'existe_anterior' => $detalleAnterior ? 'Sí' : 'No',
                'datos_guardar' => $datosGuardar
            ]);
            
            // Crear o actualizar
            $detalleGuardado = BodegaDetalleTalla::updateOrCreate(
                $criteriosBusqueda,
                $datosGuardar
            );
            
            $esNuevo = !$detalleAnterior;
            
            \Log::info('[GUARDAR FILA] Registro guardado', [
                'detalle_id' => $detalleGuardado->id,
                'es_nuevo' => $esNuevo,
                'prenda_id' => $detalleGuardado->prenda_id,
                'talla_color_id' => $detalleGuardado->talla_color_id
            ]);
            
            // Actualizar el timestamp del pedido para que aparezca primero en la lista
            $pedido->touch();
            
            // Registrar cambios en auditoría
            $this->registrarAuditoria($detalleAnterior, $detalleGuardado, $numeroPedido, $talla, $prendaNombre, $request);

            return [
                'success' => true,
                'message' => $esNuevo ? 'Fila creada correctamente' : 'Fila actualizada correctamente'
            ];
        } catch (\Exception $e) {
            \Log::error('[GUARDAR FILA] Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Guardar un detalle individual (validado desde el controller)
     */
    public function guardarDetalles(array $datosValidados): array
    {
        try {
            // Este método delega al bodegaPedidoService como antes
            // Solo consolidamos la estructura, la lógica real está en bodegaPedidoService
            $bodegaPedidoService = app(\App\Application\Bodega\Services\BodegaPedidoService::class);
            return $bodegaPedidoService->guardarDetalles($datosValidados);
        } catch (\Exception $e) {
            \Log::error('Error en guardarDetalles: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Guardar múltiples detalles de un pedido
     */
    public function guardarMultiplesDetalles(
        PedidoProduccion $pedido,
        array $detalles,
        array $camposAuditar
    ): array {
        try {
            $usuario = auth()->user();
            $guardados = 0;
            
            foreach ($detalles as $detalle) {
                \Log::info('[GUARDAR DETALLES] Datos recibidos:', [
                    'numero_pedido' => $pedido->numero_pedido,
                    'detalle_completo' => $detalle,
                    'prenda_id' => $detalle['prenda_id'] ?? 'null',
                    'pedido_epp_id' => $detalle['pedido_epp_id'] ?? 'null',
                    'prenda_nombre' => $detalle['prenda_nombre'] ?? 'null',
                    'talla' => $detalle['talla'] ?? 'null',
                    'cantidad' => $detalle['cantidad'] ?? 'null'
                ]);
                
                $talla = $detalle['talla'];
                $tallaColorId = $detalle['talla_color_id'] ?? null;
                $nombrePrenda = $detalle['prenda_nombre'] ?? null;
                $cantidad = $detalle['cantidad'] ?? 0;
                
                // Obtener registro anterior para auditoría
                $detalleAnterior = BodegaDetalleTalla::where('pedido_produccion_id', $pedido->id)
                    ->where('numero_pedido', $pedido->numero_pedido)
                    ->where('talla', $talla)
                    ->where('talla_color_id', $tallaColorId)
                    ->where('prenda_nombre', $nombrePrenda)
                    ->where('cantidad', $cantidad)
                    ->first();

                \Log::info('[GUARDAR DETALLES] Creación automática desactivada', [
                    'numero_pedido' => $pedido->numero_pedido,
                    'prenda_nombre' => $nombrePrenda,
                    'talla' => $talla,
                    'cantidad' => $cantidad,
                    'nota' => 'El registro solo se creará cuando el usuario lo ingrese manualmente'
                ]);

                $guardados++;
            }

            return [
                'success' => true,
                'message' => "$guardados registro(s) guardado(s) correctamente"
            ];
        } catch (\Exception $e) {
            \Log::error('Error en guardarMultiplesDetalles: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Registrar cambios en auditoría
     */
    private function registrarAuditoria(
        ?BodegaDetalleTalla $detalleAnterior,
        BodegaDetalleTalla $detalleGuardado,
        string $numeroPedido,
        string $talla,
        ?string $prendaNombre,
        Request $request
    ): void {
        $usuario = auth()->user();

        if ($detalleAnterior) {
            // Actualización - registrar solo los cambios
            $camposAuditar = [
                'asesor',
                'empresa',
                'cantidad',
                'prenda_nombre',
                'pendientes',
                'fecha_pedido',
                'fecha_entrega',
                'area',
                'estado_bodega',
                'observaciones_bodega',
            ];
            
            foreach ($camposAuditar as $campo) {
                $valorAnterior = $detalleAnterior->{$campo};
                $valorNuevo = $detalleGuardado->{$campo};
                
                $valorAnteriorDisplay = ($valorAnterior === null || $valorAnterior === '') ? '' : $valorAnterior;
                $valorNuevoDisplay = ($valorNuevo === null || $valorNuevo === '') ? '' : $valorNuevo;
                
                if ($valorAnteriorDisplay !== $valorNuevoDisplay) {
                    BodegaAuditoria::create([
                        'bodega_detalles_talla_id' => $detalleGuardado->id,
                        'numero_pedido' => $numeroPedido,
                        'talla' => $talla,
                        'campo_modificado' => $campo,
                        'valor_anterior' => $valorAnteriorDisplay,
                        'valor_nuevo' => $valorNuevoDisplay,
                        'usuario_id' => $usuario->id,
                        'usuario_nombre' => $usuario->name,
                        'ip_address' => $request->ip(),
                        'accion' => 'update',
                        'descripcion' => ucfirst($campo) . ' cambió de "' . ($valorAnteriorDisplay ?: 'vacío') . '" a "' . ($valorNuevoDisplay ?: 'vacío') . '"',
                    ]);
                }
            }
        } else {
            // Creación nueva - registrar como insert
            BodegaAuditoria::create([
                'bodega_detalles_talla_id' => $detalleGuardado->id,
                'numero_pedido' => $numeroPedido,
                'talla' => $talla,
                'campo_modificado' => 'registro_completo',
                'valor_anterior' => '',
                'valor_nuevo' => 'Registro creado',
                'usuario_id' => $usuario->id,
                'usuario_nombre' => $usuario->name,
                'ip_address' => $request->ip(),
                'accion' => 'create',
                'descripcion' => 'Registro de bodega creado: ' . $prendaNombre . ' - Talla: ' . $talla,
            ]);
        }
    }
}
