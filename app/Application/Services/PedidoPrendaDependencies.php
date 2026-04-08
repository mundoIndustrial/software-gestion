<?php

namespace App\Application\Services;

use App\Application\Pedidos\Services\PrendaVarianteContextResolver;
use App\Application\Services\PrendaBaseCreatorService;
use App\Application\Services\PrendaDataNormalizerService;
use App\Application\Services\PrendaTallaService;
use App\Application\Services\PrendaVarianteService;
use App\Application\Services\VariacionesPrendaProcessorService;
use App\Infrastructure\Services\Pedidos\PrendaRelationsPersistenceService;

class PedidoPrendaDependencies
{
    public function __construct(
        public readonly PrendaTallaService $prendaTallaService,
        public readonly PrendaVarianteService $prendaVarianteService,
        public readonly PrendaDataNormalizerService $dataNormalizer,
        public readonly VariacionesPrendaProcessorService $variacionesProcessor,
        public readonly PrendaBaseCreatorService $prendaBaseCreator,
        public readonly PrendaVarianteContextResolver $prendaVarianteContextResolver,
        public readonly PrendaRelationsPersistenceService $prendaRelationsPersistenceService,
    ) {
    }
}
