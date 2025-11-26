<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\LogoCotizacion;
use App\Models\HistorialCotizacion;
use App\Models\TipoCotizacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio para gestionar la lógica de negocio de cotizaciones
 * 
 * Responsabilidades:
 * - Crear cotizaciones
 * - Actualizar borradores
 * - Cambiar estados
 * - Generar números
 * - Gestionar historial
 */
class CotizacionService
{
    /**
     * Crear una nueva cotización
     * 
     * @param array $datosFormulario Datos procesados del formulario
     * @param string $tipo 'borrador' o 'enviada'
     * @param string|null $tipoCodigo Código del tipo de cotización
     * @return \App\Models\Cotizacion
     */
    public function crear(array $datosFormulario, string $tipo = 'borrador', ?string $tipoCodigo = null): Cotizacion
    {
        $tipoCotizacion = null;
        if ($tipoCodigo) {
            $tipoCotizacion = TipoCotizacion::where('codigo', $tipoCodigo)->first();
        }
        
        $numeroCotizacion = null;
        if ($tipo === 'enviada') {
            $numeroCotizacion = $this->generarNumeroCotizacion();
        }
        
        $datos = [
            'user_id' => Auth::id(),
            'numero_cotizacion' => $numeroCotizacion,
            'tipo_cotizacion_id' => $tipoCotizacion?->id,
            'fecha_inicio' => now(),
            'cliente' => $datosFormulario['cliente'] ?? null,
            'asesora' => auth()->user()?->name ?? 'Sin nombre',
            'es_borrador' => ($tipo === 'borrador'),
            'estado' => 'enviada',
            'fecha_envio' => ($tipo === 'enviada') ? now() : null,
            'productos' => $datosFormulario['productos'] ?? null,
            'especificaciones' => $datosFormulario['especificaciones'] ?? null,
            'imagenes' => $datosFormulario['imagenes'] ?? null,
            'tecnicas' => $datosFormulario['tecnicas'] ?? null,
            'observaciones_tecnicas' => $datosFormulario['observaciones_tecnicas'] ?? null,
            'ubicaciones' => $datosFormulario['ubicaciones'] ?? null,
            'observaciones_generales' => $datosFormulario['observaciones'] ?? null
        ];
        
        return Cotizacion::create($datos);
    }

    /**
     * Actualizar un borrador de cotización
     * 
     * @param \App\Models\Cotizacion $cotizacion
     * @param array $datosFormulario Datos procesados del formulario
     * @return \App\Models\Cotizacion
     */
    public function actualizarBorrador(Cotizacion $cotizacion, array $datosFormulario): Cotizacion
    {
        $tipoCodigo = $datosFormulario['tipo_cotizacion'] ?? null;
        
        $tipoCotizacion = null;
        if ($tipoCodigo) {
            $tipoCotizacion = TipoCotizacion::where('codigo', $tipoCodigo)->first();
        }
        
        // Solo actualizar tipo_cotizacion_id si se proporciona un tipo
        $datosActualizar = [
            'cliente' => $datosFormulario['cliente'] ?? null,
            'asesora' => auth()->user()?->name ?? 'Sin nombre',
        ];
        
        // Solo actualizar tipo_cotizacion_id si se proporciona
        if ($tipoCotizacion) {
            $datosActualizar['tipo_cotizacion_id'] = $tipoCotizacion->id;
        }
        
        $cotizacion->update($datosActualizar);
        
        return $cotizacion;
    }

    /**
     * Cambiar estado de una cotización
     * 
     * @param \App\Models\Cotizacion $cotizacion
     * @param string $nuevoEstado
     * @return \App\Models\Cotizacion
     */
    public function cambiarEstado(Cotizacion $cotizacion, string $nuevoEstado): Cotizacion
    {
        $datosActualizar = [
            'estado' => $nuevoEstado,
            'es_borrador' => false
        ];
        
        if ($nuevoEstado === 'enviada' && !$cotizacion->fecha_envio) {
            $datosActualizar['fecha_envio'] = now();
        }
        
        $cotizacion->update($datosActualizar);
        
        // Registrar en historial
        $tipoHistorial = ($nuevoEstado === 'enviada') ? 'envio' : $nuevoEstado;
        $this->registrarEnHistorial($cotizacion, $tipoHistorial, "Estado cambiado a: " . ucfirst($nuevoEstado));
        
        return $cotizacion;
    }

    /**
     * Registrar evento en historial de cotización
     * 
     * @param \App\Models\Cotizacion $cotizacion
     * @param string $tipoEvento
     * @param string $descripcion
     * @return \App\Models\HistorialCotizacion
     */
    public function registrarEnHistorial(
        Cotizacion $cotizacion,
        string $tipoEvento,
        string $descripcion
    ): HistorialCotizacion {
        return HistorialCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'tipo_cambio' => $tipoEvento,
            'descripcion' => $descripcion,
            'usuario_id' => Auth::id(),
            'usuario_nombre' => auth()->user()?->name ?? 'Sin nombre',
            'ip_address' => request()->ip()
        ]);
    }

    /**
     * Crear logo/bordado/estampado para una cotización
     * 
     * @param \App\Models\Cotizacion $cotizacion
     * @param array $datosFormulario
     * @return \App\Models\LogoCotizacion
     */
    public function crearLogoCotizacion(Cotizacion $cotizacion, array $datosFormulario): LogoCotizacion
    {
        $logoCotizacionData = [
            'cotizacion_id' => $cotizacion->id,
            'imagenes' => $datosFormulario['imagenes'] ?? [],
            'tecnicas' => $datosFormulario['tecnicas'] ?? [],
            'observaciones_tecnicas' => $datosFormulario['observaciones_tecnicas'] ?? null,
            'ubicaciones' => $datosFormulario['ubicaciones'] ?? [],
            'observaciones_generales' => $datosFormulario['observaciones_generales'] ?? []
        ];
        
        \Log::info('Creando LogoCotizacion', [
            'cotizacion_id' => $cotizacion->id,
            'tecnicas' => $logoCotizacionData['tecnicas'],
            'observaciones_generales' => $logoCotizacionData['observaciones_generales'],
            'ubicaciones' => $logoCotizacionData['ubicaciones']
        ]);
        
        return LogoCotizacion::create($logoCotizacionData);
    }

    /**
     * Generar número de cotización único
     * 
     * @return string
     */
    public function generarNumeroCotizacion(): string
    {
        $ultimaCotizacion = Cotizacion::where('es_borrador', false)
            ->whereNotNull('numero_cotizacion')
            ->orderBy('id', 'desc')
            ->first();
        
        $ultimoNumero = 0;
        if ($ultimaCotizacion && $ultimaCotizacion->numero_cotizacion) {
            preg_match('/\d+/', $ultimaCotizacion->numero_cotizacion, $matches);
            $ultimoNumero = isset($matches[0]) ? (int)$matches[0] : 0;
        }
        
        $nuevoNumero = $ultimoNumero + 1;
        return 'COT-' . str_pad($nuevoNumero, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Eliminar una cotización completamente
     * 
     * @param \App\Models\Cotizacion $cotizacion
     * @return bool
     */
    public function eliminar(Cotizacion $cotizacion): bool
    {
        DB::beginTransaction();
        
        try {
            // Eliminar imágenes
            $imagenService = new ImagenCotizacionService();
            $imagenService->eliminarTodasLasImagenes($cotizacion->id);
            
            // Eliminar variantes de prendas (null-safe)
            $prendasCotizaciones = $cotizacion->prendasCotizaciones;
            if ($prendasCotizaciones) {
                foreach ($prendasCotizaciones as $prenda) {
                    if ($prenda->variantes) {
                        $prenda->variantes()->delete();
                    }
                }
            }
            
            // Eliminar prendas (null-safe)
            if ($prendasCotizaciones) {
                $cotizacion->prendasCotizaciones()->delete();
            }
            
            // Eliminar logo (null-safe)
            $logo = $cotizacion->logoCotizacion;
            if ($logo) {
                $logo->delete();
            }
            
            // Eliminar historial (null-safe)
            $historial = $cotizacion->historial;
            if ($historial) {
                $cotizacion->historial()->delete();
            }
            
            // Eliminar cotización
            $cotizacion->delete();
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al eliminar cotización', [
                'cotizacion_id' => $cotizacion->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
