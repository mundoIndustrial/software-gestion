<?php

namespace App\Models;

use App\Traits\HasLegibleEstado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Cliente;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoAnchoGeneral;
use App\Models\PedidoMetrajeColor;
use App\Models\PedidoAnexoHistorial;
use App\Models\Cotizacion;
use App\Models\HistorialCambiosPedido;
use App\Models\PrendaPedido;
use App\Models\MaterialesOrdenInsumos;
use App\Models\PedidoEpp;
use App\Domain\Pedidos\PedidoConstants;
use App\Models\PedidoAuditoria;
use Carbon\Carbon;

/**
 * @property int $id
 * @property int|null $cotizacion_id
 * @property string|null $numero_cotizacion
 * @property string|null $numero_pedido
 * @property string $cliente
 * @property int|null $cliente_id
 * @property string|null $novedades
 * @property string|null $observaciones
 * @property int|null $asesor_id
 * @property string|null $forma_de_pago
 * @property string|null $estado
 * @property string|null $area
 * @property \Carbon\Carbon|null $fecha_ultimo_proceso
 * @property int|null $dia_de_entrega
 * @property \Carbon\Carbon|null $fecha_estimada_de_entrega
 * @property \Carbon\Carbon|null $aprobado_por_supervisor_en
 * @property string|null $motivo_anulacion
 * @property \Carbon\Carbon|null $fecha_anulacion
 * @property int|null $usuario_anulacion
 * @property int|null $cantidad_total
 * @property int|null $aprobado_por_usuario_cartera
 * @property \Carbon\Carbon|null $aprobado_por_cartera_en
 * @property int|null $rechazado_por_usuario_cartera
 * @property \Carbon\Carbon|null $rechazado_por_cartera_en
 * @property string|null $motivo_rechazo_cartera
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Carbon\Carbon|null $ocultado_en
 * @property int|null $usuario_ocultado_por
 * @property string $descripcion_prendas
 */
class PedidoProduccion extends Model
{
    use SoftDeletes, HasLegibleEstado;

    protected $table = 'pedidos_produccion';

    protected $fillable = [
        'cotizacion_id',
        'numero_cotizacion',
        'numero_pedido',
        'orden_compra',
        'cliente',
        'cliente_id',
        'novedades',
        'observaciones',
        'asesor_id',
        'forma_de_pago',
        'estado',
        'area',
        'fecha_ultimo_proceso',
        'dia_de_entrega',
        'fecha_estimada_de_entrega',
        'aprobado_por_supervisor_en',
        'motivo_anulacion',
        'fecha_anulacion',
        'usuario_anulacion',
        'cantidad_total',
        'aprobado_por_usuario_cartera',
        'aprobado_por_cartera_en',
        'rechazado_por_usuario_cartera',
        'rechazado_por_cartera_en',
        'motivo_rechazo_cartera',
        'ocultado_en',
        'usuario_ocultado_por',
    ];

    protected $casts = [
        'fecha_estimada_de_entrega' => 'datetime',
        'estado' => 'string',
    ];

    protected $appends = [
        // 'numero_pedido_mostrable', // @deprecated La tabla logo_pedidos ha sido eliminada (23/01/2026)
        // 'descripcion_prendas', // Movido a PedidoDescriptionService - usar servicio directamente
        // 'cantidad_total', // Movido a PedidoDescriptionService - usar servicio directamente
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-calcular fecha_estimada_de_entrega cuando se guarda la orden
        static::saving(function ($model) {
            // Si se está actualizando dia_de_entrega y fecha_estimada_de_entrega está vacía o debe recalcularse
            if ($model->isDirty('dia_de_entrega') && $model->created_at) {
                $service = app(\App\Domain\Pedidos\Services\PedidoProduccionCalculatorService::class);
                $fechaEstimada = $service->calcularFechaEstimada($model->created_at, $model->dia_de_entrega);
                if ($fechaEstimada) {
                    $model->fecha_estimada_de_entrega = $fechaEstimada;
                }
            }
        });
    }

    /**
     * Relación: Un pedido pertenece a un usuario (asesora)
     */
    public function asesora(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    /**
     * Alias para asesora (compatibilidad)
     */
    public function asesor(): BelongsTo
    {
        return $this->asesora();
    }

    /**
     * Relación: Un pedido pertenece a un cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Relación: Un pedido pertenece a una cotización
     */
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    /**
     * Relación: Un pedido tiene historial de cambios de estado
     */
    public function historialCambios(): HasMany
    {
        return $this->hasMany(HistorialCambiosPedido::class, 'pedido_id');
    }

    /**
     * Relación: Un pedido tiene muchos prendas
     * 
     * ACTUALIZACIÓN [16/01/2026]:
     * - Foreign Key: pedido_produccion_id (antes numero_pedido)
     * - Las prendas se crean con $pedido->prendas()->create($data)
     * - Esto asegura que pedido_produccion_id se asigna automáticamente
     */
    public function prendas(): HasMany
    {
        return $this->hasMany(PrendaPedido::class, 'pedido_produccion_id');
    }

    /**
     * Relación: Historial de anexos agregados al pedido (prendas parciales y EPP)
     */
    public function anexosHistorial(): HasMany
    {
        return $this->hasMany(PedidoAnexoHistorial::class, 'pedido_produccion_id');
    }

    /**
     * Relación: Un pedido tiene muchos consecutivos de recibos
     * Conecta con la tabla consecutivos_recibos_pedidos
     * Usada para obtener números de consecutivos por tipo de recibo
     */
    public function consecutivosRecibos(): HasMany
    {
        return $this->hasMany(ConsecutivoReciboPedido::class, 'pedido_produccion_id');
    }

    /**
     * Relación: Un pedido tiene muchos materiales de insumos
     * Usa numero_pedido como foreign key (según tabla materiales_orden_insumos)
     */
    public function materiales(): HasMany
    {
        return $this->hasMany(MaterialesOrdenInsumos::class, 'numero_pedido', 'numero_pedido');
    }

    /**
     * Alias para la relación de materiales (para consistencia)
     */
    public function materialesOrdenInsumos(): HasMany
    {
        return $this->materiales();
    }

    /**
     * Relación: Un pedido tiene muchos registros de ancho general
     */
    public function anchoGenerales()
    {
        return $this->hasMany(PedidoAnchoGeneral::class, 'pedido_produccion_id');
    }

    /**
     * Relación: Un pedido tiene muchos registros de metraje por color
     */
    public function metrajesPorColor()
    {
        return $this->hasMany(PedidoMetrajeColor::class, 'pedido_produccion_id');
    }

    /**
     * Relación: Un pedido tiene muchos EPP agregados
     */
    public function epps()
    {
        return $this->hasMany(PedidoEpp::class, 'pedido_produccion_id')
            ->with('epp');  // Cargar también los datos del EPP
    }

    /**
     * Relación: Un pedido tiene muchos EPP incluidos soft-deleted (para homologaciones)
     */
    public function eppsConTrashed()
    {
        return $this->hasMany(PedidoEpp::class, 'pedido_produccion_id')
            ->withTrashed()
            ->with('epp');  // Cargar también los datos del EPP
    }

    /**
     * Relación: Acceso directo a registros de pedido_epp
     */
    public function pedidoEpps(): HasMany
    {
        return $this->hasMany(PedidoEpp::class, 'pedido_id');
    }

    /**
     * Relación: Historial de auditoría del pedido
     */
    public function auditoria(): HasMany
    {
        return $this->hasMany(PedidoAuditoria::class, 'pedidos_produccion_id');
    }

    /**
     * Relación: Un pedido tiene muchos procesos (directa via numero_pedido)
     */
    public function procesos(): HasMany
    {
        return $this->hasMany(ProcesoPrenda::class, 'numero_pedido', 'numero_pedido');
    }

    /**
     * Relación: Un pedido tiene muchas prendas normalizadas (nuevas tablas DDD)
     * 
     * REFACTORIZADO: Ahora usa pedido_produccion_id en lugar de numero_pedido
     */
    public function prendasPed(): HasMany
    {
        return $this->hasMany(PrendaPedido::class, 'pedido_produccion_id', 'id');
    }

    /**
     * Relación anterior (mantener por compatibilidad)
     */
    public function logo(): HasMany
    {
        return $this->hasMany(LogoPed::class, 'pedido_produccion_id');
    }

    /**
     * Constantes de estados y opciones
     */
    const ESTADOS = PedidoConstants::ESTADOS;
    const DIAS_ENTREGA = PedidoConstants::DIAS_ENTREGA;

    /**
     * Estados con nombres formateados para display
     * 
     * @return array
     */
    public static function getEstadosDisplay(): array
    {
        return PedidoConstants::getEstadosDisplay();
    }
}
