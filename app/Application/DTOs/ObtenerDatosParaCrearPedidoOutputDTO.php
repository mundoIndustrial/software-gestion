<?php

namespace App\Application\DTOs;

use Illuminate\Support\Collection;

/**
 * ObtenerDatosParaCrearPedidoOutputDTO
 * 
 *  DTO que contiene TODOS los datos compartidos necesarios para crear un pedido
 * 
 * Separa:
 * - UseCase (lógica)
 * - DTO (estructura de datos)
 * - Controller (presentación)
 * - Presenter (formateo para vista)
 * 
 * Flujo:
 * UseCase → OutputDTO → Controller → Presenter → View
 * 
 * Beneficio: Cambiar structure de datos NO afecta el UseCase o Controller
 */
class ObtenerDatosParaCrearPedidoOutputDTO
{
    public function __construct(
        public Collection $pedidos,
        public Collection $clientes,
        public Collection $tallas,
        public Collection $tecnicas,
        public Collection $formasPago,
    ) {}

    /**
     * Convertir a array para serialización
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'pedidos' => $this->pedidos->toArray(),
            'clientes' => $this->clientes->toArray(),
            'tallas' => $this->tallas->toArray(),
            'tecnicas' => $this->tecnicas->toArray(),
            'formasPago' => $this->formasPago->toArray(),
        ];
    }

    /**
     * Obtener tallas agrupadas
     * 
     * @return array
     */
    public function getTallasAgrupadas(): array
    {
        return $this->tallas
            ->groupBy(fn($t) => $t->genero->nombre ?? 'Sin género')
            ->toArray();
    }

    /**
     * Obtener técnicas formateadas
     * 
     * @return array
     */
    public function getTecnicas(): array
    {
        return $this->tecnicas->map(fn($t) => [
            'id' => $t->id,
            'nombre' => $t->nombre,
        ])->toArray();
    }

    /**
     * Obtener formas de pago formateadas
     * 
     * @return array
     */
    public function getFormasPago(): array
    {
        return $this->formasPago->map(fn($f) => [
            'id' => $f->id,
            'nombre' => $f->nombre,
        ])->toArray();
    }
}
