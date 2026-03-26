<?php

namespace App\Domain\Pedidos\DTOs;

class ProcesoPrendaDTO
{
    public function __construct(
        public readonly array $procesos_procesados,
        public readonly array $configuracion_ui,
        public readonly bool $es_valido,
        public readonly array $errores,
        public readonly bool $tiene_procesos,
        public readonly array $resumen
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            procesos_procesados: $data['procesos_procesados'] ?? [],
            configuracion_ui: $data['configuracion_ui'] ?? [],
            es_valido: $data['es_valido'] ?? true,
            errores: $data['errores'] ?? [],
            tiene_procesos: $data['tiene_procesos'] ?? false,
            resumen: $data['resumen'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'procesos_procesados' => $this->procesos_procesados,
            'configuracion_ui' => $this->configuracion_ui,
            'es_valido' => $this->es_valido,
            'errores' => $this->errores,
            'tiene_procesos' => $this->tiene_procesos,
            'resumen' => $this->resumen,
        ];
    }
}
