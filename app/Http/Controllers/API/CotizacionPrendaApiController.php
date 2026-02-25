<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrendaItemCot;
use App\Models\PrendaImgCot;
use App\Models\PrendaValorUnitario;
use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CotizacionPrendaApiController extends Controller
{
    /**
     * Guardar una prenda nueva
     */
    public function guardarPrenda(Request $request)
    {
        try {
            $cotizacionId = $request->input('cotizacion_id');
            
            if (!$cotizacionId) {
                return response()->json(['success' => false, 'error' => 'ID de cotización requerido'], 400);
            }

            // Verificar que la cotización existe
            $cotizacion = Cotizacion::findOrFail($cotizacionId);

            // Crear el item de prenda
            $prendaItem = PrendaItemCot::create([
                'cotizacion_id' => $cotizacionId,
                'descripcion' => $request->input('descripcion', ''),
                'cantidad' => $request->input('cantidad', 1),
                'observaciones' => $request->input('observaciones'),
            ]);

            // Guardar valor unitario si existe
            if ($request->has('valor_unitario') && $request->input('valor_unitario') !== null) {
                PrendaValorUnitario::create([
                    'prenda_item_id' => $prendaItem->id,
                    'valor_unitario' => $request->input('valor_unitario'),
                ]);
            }

            // Guardar imágenes
            $imagenes = $request->input('imagenes', []);
            foreach ($imagenes as $imagen) {
                $ruta = $this->guardarImagen($imagen);
                if ($ruta) {
                    PrendaImgCot::create([
                        'prenda_item_id' => $prendaItem->id,
                        'ruta' => $ruta,
                    ]);
                }
            }

            Log::info('Prenda guardada exitosamente', [
                'prenda_id' => $prendaItem->id,
                'cotizacion_id' => $cotizacionId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Prenda guardada correctamente',
                'prenda_id' => $prendaItem->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al guardar prenda', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al guardar prenda: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Guardar una imagen (se usa para base64 principalmente)
     */
    private function guardarImagen($imagen)
    {
        try {
            // Si es base64
            if (is_string($imagen) && str_starts_with($imagen, 'data:image')) {
                $data = explode(',', $imagen);
                if (count($data) < 2) {
                    return null;
                }

                $blob = base64_decode($data[1]);
                $filename = 'prenda_' . time() . '_' . uniqid() . '.webp';
                Storage::disk('public')->put('cotizaciones/prendas/' . $filename, $blob);
                return 'cotizaciones/prendas/' . $filename;
            }
            
            // Si ya es una URL (blob o http)
            if (is_string($imagen) && (str_starts_with($imagen, 'http') || str_starts_with($imagen, 'blob:'))) {
                // Para blob URLs, no se pueden guardar directamente
                // Se guardarán en el frontend durante el envío del formulario
                return null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error guardando imagen de prenda', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Obtener prendas de una cotización
     */
    public function obtenerPrendas($cotizacionId)
    {
        try {
            $prendas = PrendaItemCot::where('cotizacion_id', $cotizacionId)
                ->with(['imagenes', 'valorUnitario'])
                ->get()
                ->map(function ($prenda) {
                    return [
                        'id' => $prenda->id,
                        'descripcion' => $prenda->descripcion,
                        'cantidad' => $prenda->cantidad,
                        'observaciones' => $prenda->observaciones,
                        'valor_unitario' => $prenda->valorUnitario?->valor_unitario,
                        'imagenes' => $prenda->imagenes->map(fn($img) => Storage::disk('public')->url($img->ruta))->toArray(),
                    ];
                });

            return response()->json(['success' => true, 'prendas' => $prendas]);
        } catch (\Exception $e) {
            Log::error('Error al obtener prendas', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar una prenda
     */
    public function eliminarPrenda($prendaId)
    {
        try {
            $prenda = PrendaItemCot::findOrFail($prendaId);

            // Eliminar imágenes del almacenamiento
            foreach ($prenda->imagenes as $imagen) {
                Storage::disk('public')->delete($imagen->ruta);
            }

            // Eliminar prenda (eliminará automáticamente imágenes y valor unitario por cascada)
            $prenda->delete();

            Log::info('Prenda eliminada', ['prenda_id' => $prendaId]);

            return response()->json(['success' => true, 'message' => 'Prenda eliminada correctamente']);
        } catch (\Exception $e) {
            Log::error('Error al eliminar prenda', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
