<?php

namespace App\Application\EntregasTalleres\UseCases;

use App\Models\EntregaReciboCostura;
use Illuminate\Support\Facades\DB;

class EliminarEntregaTallerUseCase
{
    public function execute(int $entregaId)
    {
        $entrega = EntregaReciboCostura::findOrFail($entregaId);
        
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
}
