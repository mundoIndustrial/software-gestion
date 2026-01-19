<?php

namespace App\Application\Services\Asesores;

use App\Models\LogoPedido;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GuardarPedidoLogoService
{
    /**
     * Guardar un pedido tipo LOGO SOLAMENTE en la tabla logo_pedidos
     * No crea registro en pedidos_produccion
     * 
     * @param array $validated
     * @param array $imagenesProcesadas
     * @return int ID del logo pedido creado
     * @throws \Exception
     */
    public function guardar(array $validated, array $imagenesProcesadas = []): int
    {
        Log::info('💾 [LOGO] Guardando pedido tipo LOGO en logo_pedidos');

        DB::beginTransaction();
        try {
            // Generar número de pedido LOGO automáticamente
            $numeroPedido = LogoPedido::generarNumeroPedido();
            
            // Crear registro en logo_pedidos con TODOS los campos
            $logoPedidoId = DB::table('logo_pedidos')->insertGetId([
                'pedido_id' => null,
                'logo_cotizacion_id' => null,
                'numero_pedido' => $numeroPedido,
                'cliente' => $validated['cliente'],
                'asesora' => Auth::user()?->name,
                'forma_de_pago' => $validated['forma_de_pago'] ?? null,
                'encargado_orden' => Auth::user()?->name,
                'fecha_de_creacion_de_orden' => now(),
                'estado' => 'pendiente',
                'area' => 'creacion_de_orden',
                'descripcion' => $validated['logo.descripcion'] ?? null,
                'tecnicas' => $validated['logo.tecnicas'] ?? null,
                'observaciones_tecnicas' => $validated['logo.observaciones_tecnicas'] ?? null,
                'ubicaciones' => $validated['logo.ubicaciones'] ?? null,
                'observaciones' => $validated['logo.observaciones_generales'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            Log::info('✅ [LOGO] Pedido creado', [
                'numero_pedido' => $numeroPedido,
                'logo_pedido_id' => $logoPedidoId,
                'cliente' => $validated['cliente'],
            ]);

            // Guardar imágenes en logo_pedido_imagenes
            if (!empty($imagenesProcesadas)) {
                $this->guardarImagenes($logoPedidoId, $imagenesProcesadas);
            }

            DB::commit();

            Log::info('✅ [LOGO] Pedido guardado exitosamente', ['id' => $logoPedidoId]);

            return $logoPedidoId;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [LOGO] Error al guardar', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Guardar imágenes del logo
     */
    private function guardarImagenes(int $logoPedidoId, array $imagenesProcesadas): void
    {
        foreach ($imagenesProcesadas as $index => $imagen) {
            DB::table('logo_pedido_imagenes')->insert([
                'logo_pedido_id' => $logoPedidoId,
                'nombre_archivo' => $imagen['nombre_archivo'],
                'url' => $imagen['url'],
                'ruta_original' => $imagen['ruta_original'],
                'ruta_webp' => $imagen['ruta_webp'] ?? null,
                'tipo_archivo' => $imagen['tipo_archivo'],
                'tamaño_archivo' => $imagen['tamaño_archivo'],
                'orden' => $index,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        Log::info('✅ [LOGO-IMAGENES] Guardadas en BD', [
            'logo_pedido_id' => $logoPedidoId,
            'total_imagenes' => count($imagenesProcesadas)
        ]);
    }

    /**
     * Determinar si un pedido debe guardarse como LOGO
     */
    public function esLogoPedido(string $tipoCotizacion = null, int $cotizacionId = null): bool
    {
        // Chequear por tipo_cotizacion explícito
        if ($tipoCotizacion === 'L') {
            Log::info('🎨 [LOGO-CHECK] Tipo cotización explícito: L');
            return true;
        }

        // Si hay cotización_id, verificar en BD
        if ($cotizacionId) {
            $tipoCodigoQuery = DB::table('cotizaciones')
                ->join('tipos_cotizacion', 'cotizaciones.tipo_cotizacion_id', '=', 'tipos_cotizacion.id')
                ->where('cotizaciones.id', $cotizacionId)
                ->select('tipos_cotizacion.codigo')
                ->first();
            
            $esLogo = $tipoCodigoQuery && $tipoCodigoQuery->codigo === 'L';
            
            Log::info('🎨 [LOGO-CHECK] Verificado en cotización', [
                'cotizacion_id' => $cotizacionId,
                'codigo' => $tipoCodigoQuery?->codigo ?? 'NULL',
                'es_logo' => $esLogo
            ]);

            return $esLogo;
        }

        return false;
    }
}
