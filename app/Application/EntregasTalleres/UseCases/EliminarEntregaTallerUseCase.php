<?php

namespace App\Application\EntregasTalleres\UseCases;

use App\Models\EntregaReciboCostura;
use App\Models\NovedadEntrega;
use Illuminate\Support\Facades\DB;

class EliminarEntregaTallerUseCase
{
    public function execute(int $entregaId)
    {
        // Intentar buscar en entregas primero
        $entrega = EntregaReciboCostura::find($entregaId);
        
        if ($entrega) {
            return $this->eliminarEntrega($entrega);
        }

        // Si no está en entregas, buscar en novedades
        $novedad = NovedadEntrega::find($entregaId);
        
        if ($novedad) {
            return $this->eliminarNovedad($novedad);
        }

        return [
            'success' => false,
            'message' => 'Entrega o novedad no encontrada.'
        ];
    }

    private function eliminarEntrega(EntregaReciboCostura $entrega)
    {
        $reciboId = $entrega->consecutivo_recibo_id;
        $parcialId = $entrega->recibo_parcial_id;
        $esParcial = !is_null($parcialId);

        // 1. Eliminar la entrega
        $entrega->delete();

        // 2. Al eliminar una entrega, el recibo automáticamente deja de estar completado
        // (ya que se entregó menos de lo requerido)
        
        if ($esParcial) {
            DB::table('prenda_recibo_completado')
                ->where('id_parcial', $parcialId)
                ->where('area', 'Costura')
                ->delete();
        } else {
            DB::table('prenda_recibo_completado')
                ->where('id_recibo', $reciboId)
                ->where('area', 'Costura')
                ->delete();
        }

        return [
            'success' => true,
            'message' => 'Entrega eliminada y estado de completado actualizado.'
        ];
    }

    private function eliminarNovedad(NovedadEntrega $novedad)
    {
        $novedad->delete();

        return [
            'success' => true,
            'message' => 'Novedad eliminada correctamente.'
        ];
    }
}
