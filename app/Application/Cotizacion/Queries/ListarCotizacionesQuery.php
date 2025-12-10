<?php

namespace App\Application\Cotizacion\Queries;

/**
 * ListarCotizacionesQuery - Query para listar cotizaciones
 *
 * Caso de uso: Obtener listado de cotizaciones del usuario
 */
final readonly class ListarCotizacionesQuery
{
    public function __construct(
        public int $usuarioId,
        public bool $soloEnviadas = false,
        public bool $soloBorradores = false,
        public int $pagina = 1,
        public int $porPagina = 15,
    ) {
    }

    /**
     * Factory method
     */
    public static function crear(
        int $usuarioId,
        bool $soloEnviadas = false,
        bool $soloBorradores = false,
        int $pagina = 1,
        int $porPagina = 15
    ): self {
        return new self($usuarioId, $soloEnviadas, $soloBorradores, $pagina, $porPagina);
    }
}
