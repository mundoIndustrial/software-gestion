<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrdenAsesor extends Model
{
    use HasFactory;

    protected $table = 'ordenes_asesores';

    protected $fillable = [
        'numero_orden',
        'pedido',
        'asesor_id',
        'cliente',
        'telefono',
        'email',
        'descripcion',
        'monto_total',
        'cantidad_prendas',
        'estado',
        'estado_pedido',
        'es_borrador',
        'fecha_confirmacion',
        'prioridad',
        'fecha_entrega',
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
        'monto_total' => 'decimal:2',
        'cantidad_prendas' => 'integer',
        'es_borrador' => 'boolean',
        'fecha_confirmacion' => 'datetime',
    ];

    /**
     * Relación con el asesor (usuario)
     */
    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    /**
     * Relación con productos
     */
    public function productos()
    {
        return $this->hasMany(ProductoPedido::class, 'pedido', 'id');
    }

    /**
     * Scope para filtrar por asesor
     */
    public function scopeDelAsesor($query, $asesorId)
    {
        return $query->where('asesor_id', $asesorId);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para órdenes del día
     */
    public function scopeDelDia($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope para órdenes del mes
     */
    public function scopeDelMes($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    /**
     * Scope para órdenes del año
     */
    public function scopeDelAnio($query)
    {
        return $query->whereYear('created_at', now()->year);
    }

    /**
     * Scope para borradores
     */
    public function scopeBorradores($query)
    {
        return $query->where('es_borrador', true)
                     ->where('estado_pedido', 'borrador')
                     ->whereNull('pedido');
    }

    /**
     * Scope para confirmados
     */
    public function scopeConfirmados($query)
    {
        return $query->where('es_borrador', false)
                     ->where('estado_pedido', 'confirmado')
                     ->whereNotNull('pedido');
    }

    /**
     * Verificar si es borrador
     */
    public function esBorrador()
    {
        return $this->es_borrador === true && $this->pedido === null;
    }

    /**
     * Obtener identificador (BORRADOR-id o PEDIDO-numero)
     */
    public function getIdentificadorAttribute()
    {
        if ($this->esBorrador()) {
            return 'BORRADOR-' . $this->id;
        }
        return 'PEDIDO-' . $this->pedido;
    }

    /**
     * Confirmar un borrador y asignar número de pedido
     */
    public function confirmar()
    {
        if (!$this->esBorrador()) {
            throw new \Exception('El pedido ya está confirmado');
        }

        // Usar transacción para evitar problemas de concurrencia
        return DB::transaction(function () {
            // Obtener el siguiente número de pedido
            $ultimoPedido = DB::table('ordenes_asesores')
                ->whereNotNull('pedido')
                ->max('pedido');
            
            $siguientePedido = $ultimoPedido ? $ultimoPedido + 1 : 1;

            // Actualizar la orden
            $this->update([
                'pedido' => $siguientePedido,
                'es_borrador' => false,
                'estado_pedido' => 'confirmado',
                'fecha_confirmacion' => now(),
            ]);

            return $this->fresh();
        });
    }

    /**
     * Eliminar una orden (borrador o confirmada)
     */
    public function cancelar()
    {
        return DB::transaction(function () {
            // Eliminar productos asociados
            DB::table('productos_pedido')
                ->where('pedido', $this->id)
                ->delete();

            // Eliminar la orden
            $this->delete();

            return true;
        });
    }
}
