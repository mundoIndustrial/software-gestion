<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo para prendas tipo REFLECTIVO
 * 
 * Una prenda reflectivo contiene información especializada:
 * - Múltiples géneros (Dama, Caballero)
 * - Tallas organizadas por género
 * - Ubicaciones del reflectivo
 * - Observaciones por ubicación
 */
class PrendaReflectivo extends Model
{
    use SoftDeletes;

    protected $table = 'prendas_reflectivo';

    protected $fillable = [
        'prenda_pedido_id',
        'nombre_producto',
        'descripcion',
        'generos',
        'cantidad_talla',
        'ubicaciones',
        'observaciones_generales',
        'cantidad_total',
    ];

    protected $casts = [
        'generos' => 'json',           // Array: ["dama", "caballero"]
        'cantidad_talla' => 'json',    // {genero: {talla: cantidad}}
        'ubicaciones' => 'json',        // [{nombre, observaciones}, ...]
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============================================================
    // RELACIONES
    // ============================================================

    /**
     * Relación a PrendaPedido
     */
    public function prendaPedido()
    {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }

    /**
     * Relación a Pedido (a través de PrendaPedido)
     */
    public function pedido()
    {
        return $this->hasOneThrough(
            PedidoProduccion::class,
            PrendaPedido::class,
            'id',                    // Foreign key en prendas_pedido
            'numero_pedido',         // Foreign key en pedidos_produccion
            'prenda_pedido_id',      // Local key en prendas_reflectivo
            'numero_pedido'          // Local key en prendas_pedido
        );
    }

    // ============================================================
    // MÉTODOS ÚTILES
    // ============================================================

    /**
     * Obtener cantidad total de prendas para todas las tallas y géneros
     */
    public function obtenerCantidadTotal(): int
    {
        $total = 0;
        
        if ($this->cantidad_talla && is_array($this->cantidad_talla)) {
            foreach ($this->cantidad_talla as $genero => $tallas) {
                if (is_array($tallas)) {
                    $total += array_sum($tallas);
                }
            }
        }
        
        return $total;
    }

    /**
     * Obtener cantidad para un género específico
     */
    public function obtenerCantidadPorGenero(string $genero): int
    {
        if (!$this->cantidad_talla || !isset($this->cantidad_talla[$genero])) {
            return 0;
        }
        
        return array_sum($this->cantidad_talla[$genero]);
    }

    /**
     * Obtener todas las tallas de un género
     */
    public function obtenerTallasPorGenero(string $genero): array
    {
        if (!$this->cantidad_talla || !isset($this->cantidad_talla[$genero])) {
            return [];
        }
        
        return array_keys($this->cantidad_talla[$genero]);
    }

    /**
     * Obtener cantidad de una talla específica en un género
     */
    public function obtenerCantidadTalla(string $genero, string $talla): int
    {
        if (!$this->cantidad_talla || !isset($this->cantidad_talla[$genero][$talla])) {
            return 0;
        }
        
        return (int)$this->cantidad_talla[$genero][$talla];
    }

    /**
     * Verificar si un género está seleccionado
     */
    public function tieneGenero(string $genero): bool
    {
        return $this->generos && in_array(strtolower($genero), array_map('strtolower', $this->generos));
    }

    /**
     * Obtener todas las ubicaciones
     */
    public function obtenerUbicaciones(): array
    {
        return $this->ubicaciones ?? [];
    }

    /**
     * Agregar una ubicación
     */
    public function agregarUbicacion(string $nombre, string $observaciones = ''): void
    {
        $ubicaciones = $this->ubicaciones ?? [];
        
        $ubicaciones[] = [
            'id' => uniqid(),
            'nombre' => $nombre,
            'observaciones' => $observaciones,
            'created_at' => now()->toIso8601String(),
        ];
        
        $this->ubicaciones = $ubicaciones;
        $this->save();
    }

    /**
     * Eliminar una ubicación por ID
     */
    public function eliminarUbicacion(string $ubicacionId): void
    {
        if (!$this->ubicaciones) {
            return;
        }
        
        $this->ubicaciones = array_filter(
            $this->ubicaciones,
            fn($u) => ($u['id'] ?? null) !== $ubicacionId
        );
        
        $this->save();
    }

    /**
     * Actualizar cantidad de una talla
     */
    public function actualizarCantidadTalla(string $genero, string $talla, int $cantidad): void
    {
        $cantidadesTalla = $this->cantidad_talla ?? [];
        
        if (!isset($cantidadesTalla[$genero])) {
            $cantidadesTalla[$genero] = [];
        }
        
        $cantidadesTalla[$genero][$talla] = $cantidad;
        
        $this->cantidad_talla = $cantidadesTalla;
        $this->cantidad_total = $this->obtenerCantidadTotal();
        $this->save();
    }

    /**
     * Generar descripción formateada para el modal
     */
    public function generarDescripcionFormateada(): string
    {
        $lineas = [];

        // Nombre del producto
        if (!empty($this->nombre_producto)) {
            $lineas[] = "PRENDA REFLECTIVO: " . strtoupper($this->nombre_producto);
        }

        // Descripción
        if (!empty($this->descripcion)) {
            $lineas[] = $this->descripcion;
        }

        // Géneros
        if ($this->generos && is_array($this->generos)) {
            $generosStr = implode(', ', array_map('ucfirst', $this->generos));
            $lineas[] = "GENEROS: " . $generosStr;
        }

        // Tallas por género
        if ($this->cantidad_talla && is_array($this->cantidad_talla)) {
            foreach ($this->cantidad_talla as $genero => $tallas) {
                if (is_array($tallas)) {
                    $tallasTexto = [];
                    foreach ($tallas as $talla => $cantidad) {
                        if ($cantidad > 0) {
                            $tallasTexto[] = "$talla: $cantidad";
                        }
                    }
                    if (!empty($tallasTexto)) {
                        $lineas[] = "TALLAS " . strtoupper($genero) . ": " . implode(', ', $tallasTexto);
                    }
                }
            }
        }

        // Ubicaciones
        if ($this->ubicaciones && is_array($this->ubicaciones)) {
            foreach ($this->ubicaciones as $ubicacion) {
                $ubicDesc = "UBICACION: " . ($ubicacion['nombre'] ?? '');
                if (!empty($ubicacion['observaciones'])) {
                    $ubicDesc .= " - " . $ubicacion['observaciones'];
                }
                $lineas[] = $ubicDesc;
            }
        }

        // Observaciones generales
        if (!empty($this->observaciones_generales)) {
            $lineas[] = "OBSERVACIONES: " . $this->observaciones_generales;
        }

        return implode("\n", $lineas);
    }
}
