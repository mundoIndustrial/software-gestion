<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\PrendaCotizacionFriendly;
use App\Services\ImagenCotizacionService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class ContadorController extends Controller
{
    /**
     * Mostrar el dashboard del contador
     */
    public function index(): View
    {
        $cotizaciones = Cotizacion::where('es_borrador', false)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('contador.index', compact('cotizaciones'));
    }

    /**
     * Obtener detalle de una cotización para el modal
     */
    public function getCotizacionDetail($id)
    {
        $cotizacion = Cotizacion::with([
            'prendasCotizaciones',
            'logoCotizacion'
        ])->findOrFail($id);
        
        return view('contador.partials.cotizacion-modal', compact('cotizacion'));
    }

    /**
     * Eliminar una cotización completa con todas sus relaciones e imágenes
     */
    public function deleteCotizacion($id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            
            \Log::info('🗑️ Iniciando eliminación de cotización', [
                'cotizacion_id' => $id,
                'cliente' => $cotizacion->cliente
            ]);
            
            // 1. Eliminar prendas relacionadas (prendasCotizaciones)
            if ($cotizacion->prendasCotizaciones()->exists()) {
                \Log::info('Eliminando prendas relacionadas', [
                    'cantidad' => $cotizacion->prendasCotizaciones()->count()
                ]);
                $cotizacion->prendasCotizaciones()->delete();
            }
            
            // 2. Eliminar logo/bordado/estampado relacionado (logoCotizacion)
            if ($cotizacion->logoCotizacion()->exists()) {
                \Log::info('Eliminando logoCotizacion');
                $cotizacion->logoCotizacion()->delete();
            }
            
            // 3. Eliminar pedido de producción relacionado (si existe)
            if ($cotizacion->pedidoProduccion()->exists()) {
                \Log::info('Eliminando pedido de producción');
                $cotizacion->pedidoProduccion()->delete();
            }
            
            // 4. Eliminar historial de cambios relacionado (si existe)
            if ($cotizacion->historial()->exists()) {
                \Log::info('Eliminando historial de cambios', [
                    'cantidad' => $cotizacion->historial()->count()
                ]);
                $cotizacion->historial()->delete();
            }
            
            // 5. Eliminar carpeta completa de imágenes de la cotización
            \Log::info('Eliminando carpeta de imágenes', [
                'cotizacion_id' => $id,
                'ruta' => "cotizaciones/{$id}"
            ]);
            
            $imagenService = new ImagenCotizacionService();
            $imagenService->eliminarTodasLasImagenes($id);
            
            // Verificar que la carpeta se eliminó
            if (Storage::disk('public')->exists("cotizaciones/{$id}")) {
                \Log::warning('La carpeta aún existe después de eliminarTodasLasImagenes, intentando eliminar directamente');
                Storage::disk('public')->deleteDirectory("cotizaciones/{$id}");
            }
            
            // 6. Eliminar la cotización principal
            \Log::info('Eliminando registro de cotización de BD');
            $cotizacion->delete();
            
            \Log::info('✅ Cotización eliminada completamente', [
                'cotizacion_id' => $id,
                'cliente' => $cotizacion->cliente
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Cotización, imágenes y todos sus registros relacionados han sido eliminados correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error al eliminar cotización', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar notas de tallas para una prenda
     */
    public function guardarNotasTallas($prendaId, Request $request)
    {
        try {
            $prenda = PrendaCotizacionFriendly::findOrFail($prendaId);
            
            // Validar que se envíe el texto de notas
            $request->validate([
                'notas' => 'required|string'
            ]);
            
            // Guardar las notas
            $prenda->notas_tallas = $request->input('notas');
            $prenda->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Notas de tallas guardadas correctamente',
                'notas' => $prenda->notas_tallas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar las notas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar el estado de una cotización
     */
    public function cambiarEstado($id, Request $request)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            
            // Validar que el estado sea uno de los permitidos
            $request->validate([
                'estado' => 'required|in:enviada,entregar,anular'
            ]);
            
            // Actualizar el estado
            $cotizacion->estado = $request->input('estado');
            $cotizacion->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'estado' => $cotizacion->estado
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado: ' . $e->getMessage()
            ], 500);
        }
    }

}
