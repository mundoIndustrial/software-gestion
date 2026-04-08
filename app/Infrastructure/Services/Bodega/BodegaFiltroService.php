<?php

namespace App\Infrastructure\Services\Bodega;

use App\Domain\Bodega\Services\BodegaFiltroServiceContract;

use App\Models\ReciboPrenda;
use App\Models\BodegaDetalleTalla;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class BodegaFiltroService implements BodegaFiltroServiceContract
{
    /**
     * Obtener clientes únicos de ReciboPrenda
     */
    public function obtenerClientesUnicos(string|null $search, int $page, int $perPage): Collection
    {
        $search = $search ?? '';
        
        $query = ReciboPrenda::select('cliente')
            ->whereNotNull('cliente')
            ->where('cliente', '!=', '')
            ->distinct()
            ->orderBy('cliente', 'asc');

        if ($search) {
            $query->where('cliente', 'LIKE', '%' . $search . '%');
        }

        return $query->get()->pluck('cliente')->map(function($cliente) {
            return ['valor' => $cliente, 'cantidad' => ReciboPrenda::where('cliente', $cliente)->count()];
        });
    }

    /**
     * Obtener asesores únicos de ReciboPrenda
     */
    public function obtenerAsesoresUnicos(string|null $search, int $page, int $perPage): Collection
    {
        $search = $search ?? '';
        
        $query = ReciboPrenda::with(['asesor'])
            ->whereHas('asesor')
            ->select('asesor_id')
            ->distinct()
            ->get()
            ->map(function($recibo) {
                $asesor = $recibo->asesor;
                return $asesor ? ($asesor->nombre ?? $asesor->name ?? 'Sin nombre') : null;
            })
            ->filter()
            ->unique()
            ->sort();

        if ($search) {
            $query = $query->filter(function($nombre) use ($search) {
                return stripos($nombre, $search) !== false;
            });
        }

        return $query->values()->map(function($nombre) {
            return ['valor' => $nombre, 'cantidad' => ReciboPrenda::whereHas('asesor', function($q) use ($nombre) {
                $q->where('name', $nombre)->orWhere('nombre', $nombre);
            })->count()];
        });
    }

    /**
     * Obtener estados únicos de ReciboPrenda
     */
    public function obtenerEstadosUnicos(string|null $search, int $page, int $perPage): Collection
    {
        $search = $search ?? '';
        
        $estados = [
            ['valor' => 'ENTREGADO', 'texto' => 'Entregado', 'cantidad' => 0],
            ['valor' => 'EN EJECUCIÓN', 'texto' => 'En Ejecución', 'cantidad' => 0],
            ['valor' => 'PENDIENTE_SUPERVISOR', 'texto' => 'Pendiente Supervisor', 'cantidad' => 0],
            ['valor' => 'PENDIENTE_INSUMOS', 'texto' => 'Pendiente Insumos', 'cantidad' => 0],
            ['valor' => 'NO INICIADO', 'texto' => 'No Iniciado', 'cantidad' => 0],
            ['valor' => 'ANULADA', 'texto' => 'Anulada', 'cantidad' => 0],
            ['valor' => 'DEVUELTO_A_ASESORA', 'texto' => 'Devuelto a Asesora', 'cantidad' => 0],
        ];

        foreach ($estados as &$estado) {
            $estado['cantidad'] = ReciboPrenda::where('estado', $estado['valor'])->count();
        }

        if ($search) {
            $estados = collect($estados)->filter(function($estado) use ($search) {
                return stripos($estado['texto'], $search) !== false || stripos($estado['valor'], $search) !== false;
            });
        }

        return collect($estados);
    }

    /**
     * Obtener fechas únicas de ReciboPrenda
     */
    public function obtenerFechasUnicas(string|null $search, int $page, int $perPage): Collection
    {
        $search = $search ?? '';
        
        $query = ReciboPrenda::selectRaw('DATE(created_at) as fecha')
            ->distinct()
            ->orderBy('fecha', 'desc');

        if ($search && strlen($search) >= 4) {
            $query->where('created_at', 'LIKE', '%' . $search . '%');
        }

        return $query->get()->pluck('fecha')->map(function($fecha) {
            return ['valor' => Carbon::parse($fecha)->format('d/m/Y'), 'cantidad' => ReciboPrenda::whereDate('created_at', $fecha)->count()];
        });
    }

    // ==================== FILTROS COSTURA ====================

    /**
     * Obtener números de pedido para Costura Pendiente
     */
    public function obtenerNumerosPedidoCostura(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerNumerosPedidoCostura iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::select('numero_pedido')
                ->where('area', 'Costura')
                ->where('estado_bodega', 'Pendiente')
                ->distinct()
                ->orderBy('numero_pedido', 'asc');

            if ($search) {
                $query->where('numero_pedido', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('numero_pedido')->map(function($numero) {
                return ['valor' => $numero, 'cantidad' => BodegaDetalleTalla::where('numero_pedido', $numero)->where('area', 'Costura')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerNumerosPedidoCostura completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerNumerosPedidoCostura: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    /**
     * Obtener clientes para Costura Pendiente
     */
    public function obtenerClientesCostura(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerClientesCostura iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::select('empresa')
                ->where('area', 'Costura')
                ->where('estado_bodega', 'Pendiente')
                ->whereNotNull('empresa')
                ->where('empresa', '!=', '')
                ->distinct()
                ->orderBy('empresa', 'asc');

            if ($search) {
                $query->where('empresa', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('empresa')->map(function($cliente) {
                return ['valor' => $cliente, 'cantidad' => BodegaDetalleTalla::where('empresa', $cliente)->where('area', 'Costura')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerClientesCostura completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerClientesCostura: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    /**
     * Obtener asesores para Costura Pendiente
     */
    public function obtenerAsesoresCostura(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerAsesoresCostura iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::select('asesor')
                ->where('area', 'Costura')
                ->where('estado_bodega', 'Pendiente')
                ->whereNotNull('asesor')
                ->where('asesor', '!=', '')
                ->distinct()
                ->orderBy('asesor', 'asc');

            if ($search) {
                $query->where('asesor', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('asesor')->map(function($asesor) {
                return ['valor' => $asesor, 'cantidad' => BodegaDetalleTalla::where('asesor', $asesor)->where('area', 'Costura')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerAsesoresCostura completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerAsesoresCostura: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    /**
     * Obtener estados para Costura Pendiente
     */
    public function obtenerEstadosCostura(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerEstadosCostura iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $estados = [
                ['valor' => 'Pendiente', 'cantidad' => BodegaDetalleTalla::where('area', 'Costura')->where('estado_bodega', 'Pendiente')->count()],
                ['valor' => 'Entregado', 'cantidad' => BodegaDetalleTalla::where('area', 'Costura')->where('estado_bodega', 'Entregado')->count()],
            ];

            $result = collect($estados)->filter(function($estado) use ($search) {
                return empty($search) || stripos($estado['valor'], $search) !== false;
            });
            
            \Log::info('obtenerEstadosCostura completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerEstadosCostura: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    /**
     * Obtener fechas para Costura Pendiente
     */
    public function obtenerFechasCostura(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerFechasCostura iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::selectRaw('DATE(fecha_entrega) as fecha')
                ->where('area', 'Costura')
                ->where('estado_bodega', 'Pendiente')
                ->whereNotNull('fecha_entrega')
                ->distinct()
                ->orderBy('fecha', 'desc');

            if ($search && strlen($search) >= 4) {
                $query->where('fecha_entrega', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('fecha')->map(function($fecha) {
                return ['valor' => Carbon::parse($fecha)->format('d/m/Y'), 'cantidad' => BodegaDetalleTalla::whereDate('fecha_entrega', $fecha)->where('area', 'Costura')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerFechasCostura completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerFechasCostura: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    // ==================== FILTROS EPP ====================

    /**
     * Obtener números de pedido para EPP Pendiente
     */
    public function obtenerNumerosPedidoEpp(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerNumerosPedidoEpp iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::select('numero_pedido')
                ->where('area', 'EPP')
                ->where('estado_bodega', 'Pendiente')
                ->distinct()
                ->orderBy('numero_pedido', 'asc');

            if ($search) {
                $query->where('numero_pedido', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('numero_pedido')->map(function($numero) {
                return ['valor' => $numero, 'cantidad' => BodegaDetalleTalla::where('numero_pedido', $numero)->where('area', 'EPP')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerNumerosPedidoEpp completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerNumerosPedidoEpp: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    /**
     * Obtener clientes para EPP Pendiente
     */
    public function obtenerClientesEpp(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerClientesEpp iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::select('empresa')
                ->where('area', 'EPP')
                ->where('estado_bodega', 'Pendiente')
                ->whereNotNull('empresa')
                ->where('empresa', '!=', '')
                ->distinct()
                ->orderBy('empresa', 'asc');

            if ($search) {
                $query->where('empresa', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('empresa')->map(function($cliente) {
                return ['valor' => $cliente, 'cantidad' => BodegaDetalleTalla::where('empresa', $cliente)->where('area', 'EPP')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerClientesEpp completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerClientesEpp: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    /**
     * Obtener asesores para EPP Pendiente
     */
    public function obtenerAsesoresEpp(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerAsesoresEpp iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::select('asesor')
                ->where('area', 'EPP')
                ->where('estado_bodega', 'Pendiente')
                ->whereNotNull('asesor')
                ->where('asesor', '!=', '')
                ->distinct()
                ->orderBy('asesor', 'asc');

            if ($search) {
                $query->where('asesor', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('asesor')->map(function($asesor) {
                return ['valor' => $asesor, 'cantidad' => BodegaDetalleTalla::where('asesor', $asesor)->where('area', 'EPP')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerAsesoresEpp completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerAsesoresEpp: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    /**
     * Obtener estados para EPP Pendiente
     */
    public function obtenerEstadosEpp(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerEstadosEpp iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $estados = [
                ['valor' => 'Pendiente', 'cantidad' => BodegaDetalleTalla::where('area', 'EPP')->where('estado_bodega', 'Pendiente')->count()],
                ['valor' => 'Entregado', 'cantidad' => BodegaDetalleTalla::where('area', 'EPP')->where('estado_bodega', 'Entregado')->count()],
            ];

            $result = collect($estados)->filter(function($estado) use ($search) {
                return empty($search) || stripos($estado['valor'], $search) !== false;
            });
            
            \Log::info('obtenerEstadosEpp completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerEstadosEpp: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    /**
     * Obtener fechas para EPP Pendiente
     */
    public function obtenerFechasEpp(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerFechasEpp iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::selectRaw('DATE(fecha_entrega) as fecha')
                ->where('area', 'EPP')
                ->where('estado_bodega', 'Pendiente')
                ->whereNotNull('fecha_entrega')
                ->distinct()
                ->orderBy('fecha', 'desc');

            if ($search && strlen($search) >= 4) {
                $query->where('fecha_entrega', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('fecha')->map(function($fecha) {
                return ['valor' => Carbon::parse($fecha)->format('d/m/Y'), 'cantidad' => BodegaDetalleTalla::whereDate('fecha_entrega', $fecha)->where('area', 'EPP')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerFechasEpp completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerFechasEpp: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    public function obtenerFechasCreacionCostura(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerFechasCreacionCostura iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::selectRaw('DATE(created_at) as fecha')
                ->where('area', 'Costura')
                ->where('estado_bodega', 'Pendiente')
                ->whereNotNull('created_at')
                ->distinct()
                ->orderBy('fecha', 'desc');

            if ($search && strlen($search) >= 4) {
                $query->where('created_at', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('fecha')->map(function($fecha) {
                return ['valor' => Carbon::parse($fecha)->format('d/m/Y'), 'cantidad' => BodegaDetalleTalla::whereDate('created_at', $fecha)->where('area', 'Costura')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerFechasCreacionCostura completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerFechasCreacionCostura: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    public function obtenerFechasCreacionEpp(string|null $search, int $page, int $perPage): Collection
    {
        try {
            \Log::info('obtenerFechasCreacionEpp iniciado', ['search' => $search]);
            
            $search = $search ?? '';
            
            $query = BodegaDetalleTalla::selectRaw('DATE(created_at) as fecha')
                ->where('area', 'EPP')
                ->where('estado_bodega', 'Pendiente')
                ->whereNotNull('created_at')
                ->distinct()
                ->orderBy('fecha', 'desc');

            if ($search && strlen($search) >= 4) {
                $query->where('created_at', 'LIKE', '%' . $search . '%');
            }

            $result = $query->get()->pluck('fecha')->map(function($fecha) {
                return ['valor' => Carbon::parse($fecha)->format('d/m/Y'), 'cantidad' => BodegaDetalleTalla::whereDate('created_at', $fecha)->where('area', 'EPP')->where('estado_bodega', 'Pendiente')->count()];
            });
            
            \Log::info('obtenerFechasCreacionEpp completado', ['count' => $result->count()]);
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error en obtenerFechasCreacionEpp: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return collect();
        }
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {BodegaFiltroService}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}
