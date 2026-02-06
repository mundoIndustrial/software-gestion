<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Application\Operario\Services\ObtenerPrendasRecibosService;
use App\Models\User;

class DebugDashboard extends Command
{
    protected $signature = 'debug:dashboard';
    protected $description = 'Debug dashboard data';

    public function handle()
    {
        $this->info('=== DEBUG DASHBOARD DATA ===');
        
        // Obtener el usuario costura-reflectivo por ID (conocemos que existe)
        $usuario = User::find(2); // Ajusta el ID según sea necesario
        
        if (!$usuario) {
            $this->error('User not found');
            return;
        }
        
        $this->line("Usuario: {$usuario->name}");
        $this->newLine();
        
        // Obtener las prendas
        $service = new ObtenerPrendasRecibosService();
        $prendas = $service->obtenerPrendasConRecibos($usuario);
        
        $this->info("Total prendas: " . $prendas->count());
        $this->newLine();
        
        foreach ($prendas as $index => $prenda) {
            $this->line("Prenda #{" . ($index + 1) . "}:");
            $this->line("  ID: {$prenda['prenda_id']}");
            $this->line("  Nombre: {$prenda['nombre_prenda']}");
            $this->line("  Número Pedido: {$prenda['numero_pedido']}");
            $this->line("  Total Recibos: {$prenda['total_recibos']}");
            $this->line("  Recibos:");
            
            foreach ($prenda['recibos'] as $recibo) {
                $this->line("    - Tipo: {$recibo['tipo_recibo']}, Consecutivo: {$recibo['consecutivo_actual']}");
            }
            
            // Determinar data-tipo-recibo
            $primerRecibo = $prenda['recibos'][0] ?? null;
            $tipoReciboPrimero = $primerRecibo ? strtoupper($primerRecibo['tipo_recibo']) : 'COSTURA';
            $esReflectivo = $tipoReciboPrimero === 'REFLECTIVO' ? 'reflectivo' : 'costura';
            
            $this->line("  DATA-TIPO-RECIBO: $esReflectivo");
            $this->newLine();
        }
    }
}
