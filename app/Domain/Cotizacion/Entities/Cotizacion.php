<?php

namespace App\Domain\Cotizacion\Entities;

use App\Domain\Cotizacion\ValueObjects\{
    Asesora,
    Cliente,
    CotizacionId,
    EstadoCotizacion,
    NumeroCotizacion,
    TipoCotizacion
};
use App\Domain\Shared\ValueObjects\UserId;
use DateTimeImmutable;

/**
 * Cotizacion - Aggregate Root
 *
 * Representa una cotización comercial con:
 * - Información básica (cliente, asesora, tipo)
 * - Estado y transiciones
 * - Prendas y logo
 * - Historial de cambios
 */
final class Cotizacion
{
    private CotizacionId $id;
    private UserId $usuarioId;
    private NumeroCotizacion $numero;
    private TipoCotizacion $tipo;
    private EstadoCotizacion $estado;
    private Cliente $cliente;
    private Asesora $asesora;
    private bool $esBorrador;
    private DateTimeImmutable $fechaInicio;
    private ?DateTimeImmutable $fechaEnvio = null;
    private array $prendas = [];
    private ?LogoCotizacion $logo = null;
    private array $eventos = [];

    private function __construct(
        CotizacionId $id,
        UserId $usuarioId,
        NumeroCotizacion $numero,
        TipoCotizacion $tipo,
        Cliente $cliente,
        Asesora $asesora
    ) {
        $this->id = $id;
        $this->usuarioId = $usuarioId;
        $this->numero = $numero;
        $this->tipo = $tipo;
        $this->cliente = $cliente;
        $this->asesora = $asesora;
        $this->esBorrador = $numero->estaVacio();
        $this->estado = $this->esBorrador
            ? EstadoCotizacion::BORRADOR
            : EstadoCotizacion::ENVIADA_CONTADOR;
        $this->fechaInicio = new DateTimeImmutable();
    }

    /**
     * Factory method: Crear una nueva cotización como borrador
     */
    public static function crearBorrador(
        UserId $usuarioId,
        TipoCotizacion $tipo,
        Cliente $cliente,
        Asesora $asesora
    ): self {
        $cotizacion = new self(
            CotizacionId::crear(0),
            $usuarioId,
            NumeroCotizacion::vacio(),
            $tipo,
            $cliente,
            $asesora
        );

        return $cotizacion;
    }

    /**
     * Factory method: Crear una cotización enviada (con número)
     */
    public static function crearEnviada(
        UserId $usuarioId,
        TipoCotizacion $tipo,
        Cliente $cliente,
        Asesora $asesora,
        int $secuencial
    ): self {
        $cotizacion = new self(
            CotizacionId::crear(0),
            $usuarioId,
            NumeroCotizacion::generar($secuencial),
            $tipo,
            $cliente,
            $asesora
        );

        $cotizacion->fechaEnvio = new DateTimeImmutable();

        return $cotizacion;
    }

    /**
     * Obtener ID
     */
    public function id(): CotizacionId
    {
        return $this->id;
    }

    /**
     * Obtener usuario propietario
     */
    public function usuarioId(): UserId
    {
        return $this->usuarioId;
    }

    /**
     * Obtener número de cotización
     */
    public function numero(): NumeroCotizacion
    {
        return $this->numero;
    }

    /**
     * Obtener tipo
     */
    public function tipo(): TipoCotizacion
    {
        return $this->tipo;
    }

    /**
     * Obtener estado
     */
    public function estado(): EstadoCotizacion
    {
        return $this->estado;
    }

    /**
     * Obtener cliente
     */
    public function cliente(): Cliente
    {
        return $this->cliente;
    }

    /**
     * Obtener asesora
     */
    public function asesora(): Asesora
    {
        return $this->asesora;
    }

    /**
     * Verificar si es borrador
     */
    public function esBorrador(): bool
    {
        return $this->esBorrador;
    }

    /**
     * Obtener fecha de inicio
     */
    public function fechaInicio(): DateTimeImmutable
    {
        return $this->fechaInicio;
    }

    /**
     * Obtener fecha de envío
     */
    public function fechaEnvio(): ?DateTimeImmutable
    {
        return $this->fechaEnvio;
    }

    /**
     * Obtener prendas
     */
    public function prendas(): array
    {
        return $this->prendas;
    }

    /**
     * Obtener logo
     */
    public function logo(): ?LogoCotizacion
    {
        return $this->logo;
    }

    /**
     * Agregar prenda
     */
    public function agregarPrenda(PrendaCotizacion $prenda): void
    {
        $this->prendas[] = $prenda;
    }

    /**
     * Agregar logo
     */
    public function agregarLogo(LogoCotizacion $logo): void
    {
        $this->logo = $logo;
    }

    /**
     * Cambiar estado
     */
    public function cambiarEstado(EstadoCotizacion $nuevoEstado): void
    {
        if (!$this->estado->puedeTransicionarA($nuevoEstado)) {
            throw new \DomainException(
                "No se puede transicionar de {$this->estado->label()} a {$nuevoEstado->label()}"
            );
        }

        $estadoAnterior = $this->estado;
        $this->estado = $nuevoEstado;

        // Registrar evento
        $this->registrarEvento([
            'tipo' => 'EstadoCambiado',
            'estado_anterior' => $estadoAnterior->value,
            'estado_nuevo' => $nuevoEstado->value,
        ]);
    }

    /**
     * Aceptar cotización (cliente aceptó)
     */
    public function aceptar(): void
    {
        if (!$this->puedeSerAceptada()) {
            throw new \DomainException('Esta cotización no puede ser aceptada');
        }

        $this->cambiarEstado(EstadoCotizacion::ACEPTADA);

        // Registrar evento
        $this->registrarEvento([
            'tipo' => 'CotizacionAceptada',
            'cotizacion_id' => $this->id->valor(),
            'cliente' => $this->cliente->valor(),
        ]);
    }

    /**
     * Rechazar cotización
     */
    public function rechazar(): void
    {
        if ($this->estado->esEstadoFinal()) {
            throw new \DomainException('No se puede rechazar una cotización en estado final');
        }

        $this->cambiarEstado(EstadoCotizacion::RECHAZADA);

        // Registrar evento
        $this->registrarEvento([
            'tipo' => 'CotizacionRechazada',
            'cotizacion_id' => $this->id->valor(),
        ]);
    }

    /**
     * Verificar si puede ser aceptada
     */
    public function puedeSerAceptada(): bool
    {
        return $this->estado === EstadoCotizacion::APROBADA_APROBADOR;
    }

    /**
     * Verificar si puede ser eliminada
     */
    public function puedeSerEliminada(): bool
    {
        return $this->esBorrador;
    }

    /**
     * Verificar si puede ser actualizada
     */
    public function puedeSerActualizada(): bool
    {
        return $this->esBorrador;
    }

    /**
     * Verificar si es propietario
     */
    public function esPropietarioDe(UserId $usuarioId): bool
    {
        return $this->usuarioId->equals($usuarioId);
    }

    /**
     * Registrar evento de dominio
     */
    private function registrarEvento(array $evento): void
    {
        $this->eventos[] = array_merge($evento, [
            'ocurrido_en' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Obtener eventos registrados
     */
    public function eventos(): array
    {
        return $this->eventos;
    }

    /**
     * Limpiar eventos después de despacharlos
     */
    public function limpiarEventos(): void
    {
        $this->eventos = [];
    }

    /**
     * Convertir a array para persistencia
     */
    public function toArray(): array
    {
        // Mapear tipo a tipo_cotizacion_id
        $tipoCotizacionId = match($this->tipo->value) {
            'P' => 3,   // Prenda
            'B' => 2,   // Logo
            'PB' => 1,  // Prenda/Logo
            default => 3,
        };

        return [
            'id' => $this->id->valor(),
            'usuario_id' => $this->usuarioId->valor(),
            'numero_cotizacion' => $this->numero->valor(),
            'tipo' => $this->tipo->value,
            'tipo_cotizacion_id' => $tipoCotizacionId,
            'estado' => $this->estado->value,
            'cliente' => $this->cliente->valor(),
            'asesora' => $this->asesora->valor(),
            'es_borrador' => $this->esBorrador,
            'fecha_inicio' => $this->fechaInicio->format('Y-m-d H:i:s'),
            'fecha_envio' => $this->fechaEnvio?->format('Y-m-d H:i:s'),
            'prendas' => array_map(fn($p) => $p->toArray(), $this->prendas),
            'logo' => $this->logo?->toArray(),
        ];
    }
}
