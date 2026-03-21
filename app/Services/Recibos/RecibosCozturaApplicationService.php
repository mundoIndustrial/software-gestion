<?php

namespace App\Services\Recibos;

use App\Models\ConsecutivoReciboPedido;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Recibos\Services\RecibosCozturaService;
use App\Domain\Recibos\Services\FiltrosRecibosService;
use Illuminate\Pagination\Paginator;

/**
 * Application Service para casos de uso de Recibos de Costura
 * 
 * Responsabilidades:
 * - Orquestar servicios de dominio
 * - Manejar transacciones
 * - Coordinar acceso a repositorios
 * 
 * Stack:
 * - Domain Service: RecibosCozturaService
 * - Domain Service: FiltrosRecibosService
 * - Model: ConsecutivoReciboPedido
 */
class RecibosCozturaApplicationService
{
    protected $recibosService;
    protected $filtrosService;
    protected $reciboRepository;

    public function __construct(
        RecibosCozturaService $recibosService = null,
        FiltrosRecibosService $filtrosService = null,
        ConsecutivoReciboPedidoRepository $reciboRepository = null
    ) {
        $this->recibosService = $recibosService ?? app(RecibosCozturaService::class);
        $this->filtrosService = $filtrosService ?? app(FiltrosRecibosService::class);
        $this->reciboRepository = $reciboRepository ?? app(ConsecutivoReciboPedidoRepository::class);
    }

    /**
     * Obtener recibos filtrados y enriquecidos
     * 
     * @param array $filtros Criterios de filtrado
     * @return array ['datos' => paginator, 'total' => int, 'totalCantidades' => int]
     */
    public function obtenerRecibos(array $filtros = []): array
    {
        // Validar filtros
        $validacion = $this->filtrosService->validar($filtros);
        if (!$validacion['valido']) {
            throw new \InvalidArgumentException(implode('; ', $validacion['errores']));
        }

        // Construir query base con eager loading de relaciones
        $query = ConsecutivoReciboPedido::with(['pedido.cliente', 'pedido.usuario', 'prenda', 'procesos'])
            ->where('activo', true);

        // Aplicar filtros
        $query = $this->filtrosService->aplicar($query, $filtros);

        // Obtener total antes de paginar
        $totalRegistros = $query->count();

        // Paginar
        $perPage = $filtros['per_page'] ?? 50;
        $recibos = $query->paginate($perPage);

        // Enriquecer cada recibo
        $recibosEnriquecidos = $recibos->map(function($recibo) {
            return $this->recibosService->enriquecer($recibo);
        });

        // Calcular total de cantidad global
        $totalCantidades = $recibos->sum(function($recibo) {
            return $this->recibosService->obtenerCantidadTotal($recibo);
        });

        return [
            'datos' => $recibosEnriquecidos,
            'paginacion' => [
                'total' => $recibos->total(),
                'page' => $recibos->currentPage(),
                'per_page' => $recibos->perPage(),
                'last_page' => $recibos->lastPage(),
                'from' => $recibos->firstItem(),
                'to' => $recibos->lastItem(),
            ],
            'totalCantidadGlobal' => $totalCantidades,
        ];
    }

    /**
     * Obtener recibo individual con detalles completos
     * 
     * @param int $reciboId
     * @return array Recibo enriquecido
     */
    public function obtenerRecibo(int $reciboId): array
    {
        $recibo = ConsecutivoReciboPedido::with(['pedido.cliente', 'pedido.usuario', 'prenda', 'procesos'])
            ->findOrFail($reciboId);

        return $this->recibosService->enriquecer($recibo);
    }

    /**
     * Obtener opciones de filtro dinámicas
     * 
     * @return array Opciones globales de filtrado
     */
    public function obtenerOpcionesFilttro(): array
    {
        return $this->filtrosService->obtenerOpciones();
    }

    /**
     * Buscar recibos en tiempo real
     * 
     * @param string $termino Término de búsqueda
     * @param int $limit Límite de resultados
     * @return array Recibos que coinciden
     */
    public function buscar(string $termino, int $limit = 10): array
    {
        $recibos = $this->reciboRepository->search($termino, $limit);

        return array_map(function($recibo) {
            if ($recibo instanceof ConsecutivoReciboPedido) {
                return $this->recibosService->enriquecer($recibo);
            }
            return null;
        }, $recibos);
    }

    /**
     * Validar recibo
     * 
     * @param int $reciboId
     * @return array ['valido' => bool, 'errores' => array]
     */
    public function validar(int $reciboId): array
    {
        $recibo = ConsecutivoReciboPedido::findOrFail($reciboId);
        return $this->recibosService->validar($recibo);
    }

    /**
     * Verificar si recibo está en estado crítico
     * 
     * @param int $reciboId
     * @param int $diasCriticos
     * @return bool
     */
    public function esCritico(int $reciboId, int $diasCriticos = 30): bool
    {
        $recibo = ConsecutivoReciboPedido::findOrFail($reciboId);
        return $this->recibosService->esCritico($recibo, $diasCriticos);
    }
}
