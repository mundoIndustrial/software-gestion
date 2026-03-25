<?php

namespace App\Application\Services\Pedidos;

use App\Models\User;
use App\Models\Talla;
use App\Models\PedidoProduccion;
use App\Models\Cliente;
use App\Domain\Pedidos\PedidoConstants;
use App\Application\Services\Pedidos\Contracts\CargarDatosCompartidosServiceInterface;
use App\Application\Services\TimerService;
use Illuminate\Support\Facades\Log;

/**
 * CargarDatosCompartidosService
 * 
 * PHASE 13 (Marzo 2026): Refactoring responsabilidades
 * 
 * Responsabilidad: Cargar y preparar datos compartidos entre vistas de creación de pedido
 * 
 * Antes: Lógica en controller (40+ líneas duplicadas)
 * Ahora: Servicio reutilizable, testeableIndependiente
 */
class CargarDatosCompartidosService implements CargarDatosCompartidosServiceInterface
{
    public function __construct(
        private TimerService $timerService,
    ) {}

    /**
     * Ejecutar carga de datos compartidos
     * 
     * @param User $user
     * @return array ['tallas' => Collection, 'formas_pago' => array, ...]
     */
    public function ejecutar(User $user): array
    {
        $timerTotal = $this->timerService->iniciar('cargarDatosCompartidos-total');
        $tiempos = [];
        
        // ====== Tallas ======
        $timerTallas = $this->timerService->iniciar('cargarDatos-tallas');
        $tallas = Talla::all();
        $tiempos['tallas'] = $timerTallas->obtenerMs();
        
        // ====== Formas de pago (DESDE CONSTANTES) ======
        $formasPago = PedidoConstants::FORMAS_PAGO;
        $tiempos['formas_pago'] = 0; // ValueObject - no DB
        
        // ====== Técnicas (DESDE CONSTANTES) ======
        $tecnicas = PedidoConstants::TECNICAS_CONFECCION;
        $tiempos['tecnicas'] = 0; // ValueObject - no DB
        
        // ====== Pedidos ======
        $timerPedidos = $this->timerService->iniciar('cargarDatos-pedidos');
        $pedidos = PedidoProduccion::where('asesor_id', $user->id)
            ->where('estado', '!=', PedidoConstants::ESTADO_COMPLETADO)
            ->orderBy('created_at', 'desc')
            ->get();
        $tiempos['pedidos'] = $timerPedidos->obtenerMs();
        
        // ====== Clientes ======
        $timerClientes = $this->timerService->iniciar('cargarDatos-clientes');
        $clientes = Cliente::orderBy('nombre', 'asc')->get();
        $tiempos['clientes'] = $timerClientes->obtenerMs();
        
        $tiempoTotal = $timerTotal->obtenerMs();
        
        Log::info('[CargarDatosCompartidosService] ✨ DATOS CARGADOS (Fase 13)', [
            'usuario_id' => $user->id,
            'tallas' => $tallas->count(),
            'pedidos' => $pedidos->count(),
            'clientes' => $clientes->count(),
            'tiempos_ms' => $tiempos,
            'tiempo_total_ms' => $tiempoTotal,
            'mejora' => 'Lógica extraída del controller',
        ]);
        
        return [
            'tallas' => $tallas,
            'formas_pago' => $formasPago,
            'tecnicas' => $tecnicas,
            'pedidos' => $pedidos,
            'clientes' => $clientes,
            'tiempos' => $tiempos,
        ];
    }
}
