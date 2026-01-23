<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarVariantePrendaDTO;
use App\Models\PrendaPedido;

/**
 * Use Case para agregar variantes a una prenda
 * 
 * Maneja la creaciÃ³n de registro en prenda_pedido_variantes
 * con todos los detalles de manga, broche y bolsillos
 */
final class AgregarVariantePrendaUseCase
{
    public function execute(AgregarVariantePrendaDTO $dto)
    {
        $prenda = PrendaPedido::findOrFail($dto->prendaId);

        return $prenda->variantes()->create([
            'tipo_manga_id' => $dto->tipoMangaId,
            'tipo_broche_boton_id' => $dto->tipoBrocheBotonId,
            'manga_obs' => $dto->mangaObs,
            'broche_boton_obs' => $dto->brocheBotonObs,
            'tiene_bolsillos' => $dto->tieneBolsillos,
            'bolsillos_obs' => $dto->bolsillosObs,
        ]);
    }
}

