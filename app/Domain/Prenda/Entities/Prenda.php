<?php

namespace App\Domain\Prenda\Entities;

use App\Domain\Prenda\ValueObjects\{
    PrendaId,
    PrendaNombre,
    Descripcion,
    Genero,
    Origen,
    TipoCotizacion,
    Telas,
    Procesos,
    Variaciones
};

class Prenda
{
    /** @var array<string, mixed> */
    private array $domainEvents = [];

    private function __construct(
        private PrendaId $id,
        private PrendaNombre $nombre,
        private Descripcion $descripcion,
        private Genero $genero,
        private Origen $origen,
        private TipoCotizacion $tipoCotizacion,
        private Telas $telas,
        private Procesos $procesos,
        private Variaciones $variaciones,
    ) {}

    /**
     * Factory: Crear prenda nueva para cotización
     */
    public static function crearParaCotizacion(
        PrendaNombre $nombre,
        Genero $genero,
        TipoCotizacion $tipoCotizacion,
        Telas $telas,
        ?Descripcion $descripcion = null
    ): self {
        $descripcion = $descripcion ?? Descripcion::vacia();
        
        // CORE BUSINESS RULE: Apply origin based on quotation type
        $origen = Origen::segunTipoCotizacion($tipoCotizacion);

        $prenda = new self(
            PrendaId::generar(),
            $nombre,
            $descripcion,
            $genero,
            $origen,
            $tipoCotizacion,
            $telas,
            Procesos::vacia(),
            Variaciones::vacia()
        );

        $prenda->registrarEvento('PrendaCreada', [
            'id' => $prenda->id->valor(),
            'nombre' => $prenda->nombre->valor(),
            'origen' => $prenda->origen->valor(),
            'tipo_cotizacion' => $prenda->tipoCotizacion->valor(),
        ]);

        return $prenda;
    }

    /**
     * Factory: Crear desde datos persistidos
     */
    public static function desdeArray(array $datos): self
    {
        return new self(
            PrendaId::desde($datos['id']),
            PrendaNombre::desde($datos['nombre']),
            Descripcion::desde($datos['descripcion'] ?? null),
            Genero::desde($datos['genero']),
            Origen::desde($datos['origen']),
            TipoCotizacion::desde($datos['tipo_cotizacion']),
            Telas::desdeArray($datos['telas'] ?? []),
            Procesos::desdeArray($datos['procesos'] ?? []),
            Variaciones::desdeArray($datos['variaciones'] ?? [])
        );
    }

    // ============= QUERIES =============

    public function id(): PrendaId
    {
        return $this->id;
    }

    public function nombre(): PrendaNombre
    {
        return $this->nombre;
    }

    public function descripcion(): Descripcion
    {
        return $this->descripcion;
    }

    public function genero(): Genero
    {
        return $this->genero;
    }

    public function origen(): Origen
    {
        return $this->origen;
    }

    public function tipoCotizacion(): TipoCotizacion
    {
        return $this->tipoCotizacion;
    }

    public function telas(): Telas
    {
        return $this->telas;
    }

    public function procesos(): Procesos
    {
        return $this->procesos;
    }

    public function variaciones(): Variaciones
    {
        return $this->variaciones;
    }

    // ============= COMMANDS =============

    public function establecerProcesos(Procesos $procesos): void
    {
        $this->procesos = $procesos;

        $this->registrarEvento('ProcesosEstablecidos', [
            'prendaId' => $this->id->valor(),
            'procesos' => $procesos->paraArray(),
        ]);
    }

    public function establecerVariaciones(Variaciones $variaciones): void
    {
        if ($variaciones->contar() === 0) {
            throw new \InvalidArgumentException("Prenda debe tener al menos una variación");
        }

        $this->variaciones = $variaciones;

        $this->registrarEvento('VariacionesEstablecidas', [
            'prendaId' => $this->id->valor(),
            'variaciones' => $variaciones->paraArray(),
        ]);
    }

    public function reasignarOrigen(Origen $nuevoOrigen): void
    {
        if ($this->origen->esIgual($nuevoOrigen)) {
            return; // No cambió
        }

        $this->origen = $nuevoOrigen;

        $this->registrarEvento('OrigenReasignado', [
            'prendaId' => $this->id->valor(),
            'nuevoOrigen' => $nuevoOrigen->valor(),
            'anterior' => $this->origen->valor(),
        ]);
    }

    // ============= VALIDATIONS =============

    /**
     * Validar que Prenda es consistente y lista para persistir
     * @return array<string> Errores, vacío si válida
     */
    public function validar(): array
    {
        $errores = [];

        // Nombre sin excepciones (ya validado en VO)
        // Pero podemos hacer validaciones a nivel agregado

        // Debe tener telas
        if (!$this->telas->contar() > 0) {
            $errores[] = "Prenda debe tener al menos una tela";
        }

        // Si origen es bodega, debe tener variaciones
        if ($this->origen->esBodega() && !$this->variaciones->tieneAlguna()) {
            $errores[] = "Prendas de bodega deben tener variaciones (talla/color)";
        }

        // Reglas específicas de procesos según tipo cotización
        if ($this->tipoCotizacion->esReflectivo()) {
            if (!$this->procesos->contiene(3)) { // TEJIDA
                // En reflectivo, tejida es requerida
                // Pero podría ser opcional, depende regla negocio
            }
        }

        return $errores;
    }

    public function esValida(): bool
    {
        return count($this->validar()) === 0;
    }

    // ============= DOMAIN EVENTS =============

    private function registrarEvento(string $tipo, array $datos): void
    {
        $this->domainEvents[] = [
            'tipo' => $tipo,
            'datos' => $datos,
            'timestamp' => now(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function obtenerEventosDominio(): array
    {
        return $this->domainEvents;
    }

    public function limpiarEventosDominio(): void
    {
        $this->domainEvents = [];
    }

    // ============= EXPORT =============

    /**
     * Serializar para persistencia/API
     */
    public function paraArray(): array
    {
        return [
            'id' => $this->id->valor(),
            'nombre' => $this->nombre->valor(),
            'descripcion' => $this->descripcion->valor(),
            'genero' => $this->genero->id(),
            'genero_nombre' => $this->genero->nombre(),
            'origen' => $this->origen->valor(),
            'tipo_cotizacion' => $this->tipoCotizacion->valor(),
            'telas' => $this->telas->paraArray(),
            'procesos' => $this->procesos->paraArray(),
            'variaciones' => $this->variaciones->paraArray(),
        ];
    }
}
