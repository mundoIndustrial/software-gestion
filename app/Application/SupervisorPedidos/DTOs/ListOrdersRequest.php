<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ListOrdersRequest
{
    private array $rawParams = [];
    private ?int $userId = null;
    private ?string $mostrar = null;
    private ?string $aprobacion = null;
    private ?string $tipo = null;
    private ?string $busqueda = null;
    private ?string $numero = null;
    private ?string $cliente = null;
    private ?string $forma_pago = null;
    private ?string $estado = null;
    private ?string $aprobacion_cartera = null;
    private ?string $asesora = null;
    private ?string $fecha = null;
    private ?string $fecha_desde = null;
    private ?string $fecha_hasta = null;
    private bool $verTodosDespacho = false;
    private bool $isVisualizador = false;
    private int $page = 1;
    private int $perPage = 15;

    public function __construct(array $params = [])
    {
        $this->rawParams = $params;
        $this->userId = isset($params['user_id']) ? (int) $params['user_id'] : null;
        $this->mostrar = $params['mostrar'] ?? null;
        $this->aprobacion = $params['aprobacion'] ?? null;
        $this->tipo = $params['tipo'] ?? null;
        $this->busqueda = $params['busqueda'] ?? null;
        $this->numero = $params['numero'] ?? null;
        $this->cliente = $params['cliente'] ?? null;
        $this->forma_pago = $params['forma_pago'] ?? null;
        $this->estado = $params['estado'] ?? null;
        $this->aprobacion_cartera = $params['aprobacion_cartera'] ?? null;
        $this->asesora = $params['asesora'] ?? null;
        $this->fecha = $params['fecha'] ?? null;
        $this->fecha_desde = $params['fecha_desde'] ?? null;
        $this->fecha_hasta = $params['fecha_hasta'] ?? null;
        $this->verTodosDespacho = filter_var($params['ver_todos_despacho'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->isVisualizador = filter_var($params['is_visualizador'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->page = $params['page'] ?? 1;
        $this->perPage = $params['perPage'] ?? 15;
    }

    public function getMostrar(): ?string { return $this->mostrar; }
    public function getAprobacion(): ?string { return $this->aprobacion; }
    public function getTipo(): ?string { return $this->tipo; }
    public function getBusqueda(): ?string { return $this->busqueda; }
    public function getNumero(): ?string { return $this->numero; }
    public function getCliente(): ?string { return $this->cliente; }
    public function getFormaPago(): ?string { return $this->forma_pago; }
    public function getEstado(): ?string { return $this->estado; }
    public function getAprobacionCartera(): ?string { return $this->aprobacion_cartera; }
    public function getAsesora(): ?string { return $this->asesora; }
    public function getFecha(): ?string { return $this->fecha; }
    public function getFechaDesde(): ?string { return $this->fecha_desde; }
    public function getFechaHasta(): ?string { return $this->fecha_hasta; }
    public function shouldIncludeDespacho(): bool { return $this->verTodosDespacho; }
    public function isVisualizador(): bool { return $this->isVisualizador; }
    public function getPage(): int { return $this->page; }
    public function getPerPage(): int { return $this->perPage; }
    public function getUserId(): ?int { return $this->userId; }

    public function getAppends(): array
    {
        return array_filter([
            'mostrar' => $this->rawParams['mostrar'] ?? null,
            'aprobacion' => $this->rawParams['aprobacion'] ?? null,
            'tipo' => $this->rawParams['tipo'] ?? null,
            'busqueda' => $this->rawParams['busqueda'] ?? null,
            'numero' => $this->rawParams['numero'] ?? null,
            'cliente' => $this->rawParams['cliente'] ?? null,
            'forma_pago' => $this->rawParams['forma_pago'] ?? null,
            'estado' => $this->rawParams['estado'] ?? null,
            'aprobacion_cartera' => $this->rawParams['aprobacion_cartera'] ?? null,
            'asesora' => $this->rawParams['asesora'] ?? null,
            'fecha' => $this->rawParams['fecha'] ?? null,
            'fecha_desde' => $this->rawParams['fecha_desde'] ?? null,
            'fecha_hasta' => $this->rawParams['fecha_hasta'] ?? null,
            'ver_todos_despacho' => $this->rawParams['ver_todos_despacho'] ?? null,
        ]);
    }
}
