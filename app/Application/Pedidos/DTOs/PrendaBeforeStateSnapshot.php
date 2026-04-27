<?php

namespace App\Application\Pedidos\DTOs;

class PrendaBeforeStateSnapshot
{
    public function __construct(
        public readonly int $prendaId,
        public readonly ?string $nombrePrenda,
        public readonly ?string $descripcion,
        public readonly ?string $origen,
        public readonly int $deBodega,
        public readonly array $tallasArray,
        public readonly array $fotosArray,
        public readonly array $coloresTelasArray,
        public readonly array $procesosArray,
    ) {
    }

    public static function fromPrenda($prenda): self
    {
        return new self(
            prendaId: $prenda->id,
            nombrePrenda: $prenda->nombre_prenda,
            descripcion: $prenda->descripcion,
            origen: $prenda->origen,
            deBodega: $prenda->de_bodega ?? 0,
            tallasArray: $prenda->tallas()->get()->toArray(),
            fotosArray: $prenda->fotos()->get()->toArray(),
            coloresTelasArray: $prenda->coloresTelas()->get()->toArray(),
            procesosArray: $prenda->procesos()->get()->toArray(),
        );
    }
}
