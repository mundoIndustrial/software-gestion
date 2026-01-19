<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PedidoProduccion;
use App\Models\User;
use App\Application\Services\Asesores\ObtenerPedidoDetalleService;
use Illuminate\Support\Facades\Auth;

class TestTransformacion extends Command
{
    protected $signature = 'test:transformacion';
    protected $description = 'Verificar transformación de datos para edición';

    public function handle()
    {
        $this->info('🧪 Probando transformación de datos...');
        
        // Buscar el pedido para saber su asesor
        $pedido = PedidoProduccion::find(2596);
        if (!$pedido) {
            $this->error('❌ Pedido 2596 no encontrado');
            return;
        }
        
        // Autenticar como el asesor del pedido
        Auth::loginUsingId($pedido->asesor_id);
        $this->info('✅ Autenticado como asesor ID: ' . $pedido->asesor_id);
        
        // DEBUG: Verificar qué hay en fotosTelas
        $this->line("\n📋 Verificando relación fotosTelas:");
        foreach ($pedido->prendas as $prenda) {
            $this->line("  Prenda: " . $prenda->nombre_prenda);
            $this->line("  - fotosTelas count: " . $prenda->fotosTelas->count());
            $this->line("  - fotosTelas data: " . json_encode($prenda->fotosTelas->toArray()));
        }
        
        $service = app(ObtenerPedidoDetalleService::class);
        $datos = $service->obtenerParaEdicion(2596);
        
        if (isset($datos['pedido']) && isset($datos['pedido']->prendas)) {
            $this->info("\n✅ Prendas transformadas:");
            foreach ($datos['pedido']->prendas as $prenda) {
                $this->line("  📌 " . $prenda['nombre_prenda']);
                $this->line("     telaFotos: " . json_encode($prenda['telaFotos'] ?? []));
                $this->line("     telasAgregadas: " . json_encode($prenda['telasAgregadas'] ?? []));
            }
        }
        
        $this->info("\n✅ Transformación completada");
    }
}
