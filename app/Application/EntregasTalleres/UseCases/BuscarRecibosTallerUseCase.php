<?php

namespace App\Application\EntregasTalleres\UseCases;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class BuscarRecibosTallerUseCase
{
    public function execute(?string $term = null, int $limit = 0, ?int $tallerId = null, ?string $estado = null)
    {
        $term = trim((string) $term);
        $estado = strtolower(trim((string) $estado));
        $tallerNombre = null;

        if ($tallerId) {
            $tallerNombre = User::findOrFail($tallerId)->name;
        }

        $recibos = collect();

        $recibos = $recibos
            ->concat($this->buscarRecibosNormales($term, $limit, $tallerNombre))
            ->concat($this->buscarRecibosParcialesNormales($term, $limit, $tallerNombre))
            ->concat($this->buscarRecibosBodega($term, $limit, $tallerNombre))
            ->concat($this->buscarRecibosParcialesBodega($term, $limit, $tallerNombre));

        $recibos = $recibos
            ->unique(function ($r) {
                return $r->id . '|' . $r->es_parcial . '|' . ($r->es_bodega ?? 0);
            })
            ->values()
            ->map(function ($r) {
                $r->numero_recibo = $r->numero_recibo + 0;
                $r->completado = $this->esReciboCompletado($r);
                return $r;
            });

        if ($estado === 'completados') {
            return $recibos->filter(fn ($r) => $r->completado)->values();
        }

        return $recibos->filter(fn ($r) => !$r->completado)->values();
    }

    private function esReciboCompletado(object $recibo): bool
    {
        $query = DB::table('prenda_recibo_completado')
            ->where('area', 'Costura');

        if ((int) ($recibo->es_parcial ?? 0) === 1) {
            $query->where('id_parcial', (int) $recibo->id);
        } else {
            $query->where('id_recibo', (int) $recibo->id);
        }

        return $query->exists();
    }

    private function buscarRecibosNormales(string $term, int $limit, ?string $tallerNombre)
    {
        $query = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
            ->join('pedidos_produccion as ppro', 'crp.pedido_produccion_id', '=', 'ppro.id')
            ->leftJoin('procesos_prenda as ppren', function ($join) {
                $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
                    ->on('crp.consecutivo_actual', '=', 'ppren.numero_recibo')
                    ->where('ppren.proceso', '=', 'Costura');
            })
            ->whereIn('crp.tipo_recibo', ['REFLECTIVO', 'COSTURA'])
            ->where('crp.area', '=', 'Costura')
            ->select(
                'crp.id',
                'crp.consecutivo_actual as numero_recibo',
                'pp.nombre_prenda',
                'ppren.encargado',
                'crp.tipo_recibo',
                DB::raw('0 as es_parcial'),
                DB::raw('0 as es_bodega')
            )
            ->distinct();

        if ($tallerNombre) {
            $query->whereRaw('LOWER(TRIM(ppren.encargado)) = LOWER(TRIM(?))', [$tallerNombre]);
        }

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('crp.consecutivo_actual', 'LIKE', "%{$term}%")
                    ->orWhere('pp.nombre_prenda', 'LIKE', "%{$term}%")
                    ->orWhere('ppren.encargado', 'LIKE', "%{$term}%");
            });
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    private function buscarRecibosParcialesNormales(string $term, int $limit, ?string $tallerNombre)
    {
        $query = DB::table('recibo_por_partes as rpp')
            ->join('prendas_pedido as pp', 'rpp.prenda_pedido_id', '=', 'pp.id')
            ->join('pedidos_produccion as ppro', 'rpp.pedido_produccion_id', '=', 'ppro.id')
            ->join('procesos_prenda as ppren', function ($join) {
                $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
                    ->on('rpp.prenda_pedido_id', '=', 'ppren.prenda_pedido_id')
                    ->on('rpp.consecutivo_parcial', '=', 'ppren.numero_recibo_parcial')
                    ->where('ppren.proceso', '=', 'Costura');
            })
            ->whereIn('rpp.tipo_recibo', ['REFLECTIVO', 'COSTURA'])
            ->select(
                'rpp.id',
                'rpp.consecutivo_parcial as numero_recibo',
                'pp.nombre_prenda',
                'ppren.encargado',
                'rpp.tipo_recibo',
                DB::raw('1 as es_parcial'),
                DB::raw('0 as es_bodega')
            )
            ->distinct();

        if ($tallerNombre) {
            $query->whereRaw('LOWER(TRIM(ppren.encargado)) = LOWER(TRIM(?))', [$tallerNombre]);
        }

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('rpp.consecutivo_parcial', 'LIKE', "%{$term}%")
                    ->orWhere('pp.nombre_prenda', 'LIKE', "%{$term}%")
                    ->orWhere('ppren.encargado', 'LIKE', "%{$term}%");
            });
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    private function buscarRecibosBodega(string $term, int $limit, ?string $tallerNombre)
    {
        $query = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('prenda_bodega as pb', 'crp.prenda_bodega_id', '=', 'pb.id')
            ->where('crp.tipo_recibo', 'CORTE-PARA-BODEGA')
            ->select(
                'crp.id',
                'crp.consecutivo_actual as numero_recibo',
                'pb.nombre as nombre_prenda',
                'crp.tipo_recibo',
                'crp.prenda_bodega_id',
                DB::raw('0 as es_parcial'),
                DB::raw('1 as es_bodega')
            )
            ->selectSub(function ($sub) {
                $sub->from('procesos_prenda as ppren')
                    ->selectRaw('TRIM(ppren.encargado)')
                    ->whereColumn('ppren.numero_recibo', 'crp.consecutivo_actual')
                    ->whereRaw("COALESCE(NULLIF(TRIM(ppren.encargado), ''), '') <> ''")
                    ->where(function ($q) {
                        $q->whereColumn('ppren.prenda_bodega_id', 'crp.prenda_bodega_id')
                          ->orWhereNull('ppren.prenda_bodega_id');
                    })
                    ->orderByRaw("CASE WHEN ppren.proceso = 'Costura' THEN 0 ELSE 1 END")
                    ->orderByDesc('ppren.fecha_de_asignacion_encargado')
                    ->orderByDesc('ppren.id')
                    ->limit(1);
            }, 'encargado')
            ->distinct();

        if ($tallerNombre) {
            $query->whereExists(function ($sub) use ($tallerNombre) {
                $sub->select(DB::raw(1))
                    ->from('procesos_prenda as ppren')
                    ->whereColumn('ppren.numero_recibo', 'crp.consecutivo_actual')
                    ->where(function ($q) {
                        $q->whereColumn('ppren.prenda_bodega_id', 'crp.prenda_bodega_id')
                          ->orWhereNull('ppren.prenda_bodega_id');
                    })
                    ->whereRaw('LOWER(TRIM(ppren.encargado)) = LOWER(TRIM(?))', [$tallerNombre]);
            });
        }

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('crp.consecutivo_actual', 'LIKE', "%{$term}%")
                    ->orWhere('pb.nombre', 'LIKE', "%{$term}%")
                    ->orWhereExists(function ($sub) use ($term) {
                        $sub->select(DB::raw(1))
                            ->from('procesos_prenda as ppren')
                            ->whereColumn('ppren.prenda_bodega_id', 'crp.prenda_bodega_id')
                            ->whereColumn('ppren.numero_recibo', 'crp.consecutivo_actual')
                            ->where('ppren.encargado', 'LIKE', "%{$term}%");
                    });
            });
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    private function buscarRecibosParcialesBodega(string $term, int $limit, ?string $tallerNombre)
    {
        $query = DB::table('recibo_por_partes as rpp')
            ->join('consecutivos_recibos_pedidos as crp', function ($join) {
                $join->on('crp.consecutivo_actual', '=', 'rpp.consecutivo_original')
                    ->where('crp.tipo_recibo', '=', 'CORTE-PARA-BODEGA');
            })
            ->join('prenda_bodega as pb', 'crp.prenda_bodega_id', '=', 'pb.id')
            ->where('rpp.tipo_recibo', 'CORTE-PARA-BODEGA')
            ->select(
                'rpp.id',
                'rpp.consecutivo_parcial as numero_recibo',
                'pb.nombre as nombre_prenda',
                'rpp.tipo_recibo',
                'crp.prenda_bodega_id',
                DB::raw('1 as es_parcial'),
                DB::raw('1 as es_bodega')
            )
            ->selectSub(function ($sub) {
                $sub->from('procesos_prenda as ppren')
                    ->selectRaw('TRIM(ppren.encargado)')
                    ->whereRaw("COALESCE(NULLIF(TRIM(ppren.encargado), ''), '') <> ''")
                    ->where(function ($q) {
                        $q->whereColumn('ppren.prenda_bodega_id', 'crp.prenda_bodega_id')
                          ->orWhereNull('ppren.prenda_bodega_id');
                    })
                    ->where(function ($q) {
                        $q->whereColumn('ppren.numero_recibo_parcial', 'rpp.consecutivo_parcial')
                          ->orWhereNull('ppren.numero_recibo_parcial');
                    })
                    ->where(function ($q) {
                        $q->whereColumn('ppren.numero_recibo', 'crp.consecutivo_actual')
                          ->orWhereNull('ppren.numero_recibo');
                    })
                    ->orderByRaw("CASE WHEN ppren.proceso = 'Costura' THEN 0 ELSE 1 END")
                    ->orderByDesc('ppren.fecha_de_asignacion_encargado')
                    ->orderByDesc('ppren.id')
                    ->limit(1);
            }, 'encargado')
            ->distinct();

        if ($tallerNombre) {
            $query->whereExists(function ($sub) use ($tallerNombre) {
                $sub->select(DB::raw(1))
                    ->from('procesos_prenda as ppren')
                    ->where(function ($q) {
                        $q->whereColumn('ppren.prenda_bodega_id', 'crp.prenda_bodega_id')
                          ->orWhereNull('ppren.prenda_bodega_id');
                    })
                    ->whereRaw('LOWER(TRIM(ppren.encargado)) = LOWER(TRIM(?))', [$tallerNombre]);
                $sub->where(function ($q) {
                    $q->whereColumn('ppren.numero_recibo_parcial', 'rpp.consecutivo_parcial')
                      ->orWhereNull('ppren.numero_recibo_parcial');
                });
                $sub->where(function ($q) {
                    $q->whereColumn('ppren.numero_recibo', 'crp.consecutivo_actual')
                      ->orWhereNull('ppren.numero_recibo');
                });
            });
        }

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('rpp.consecutivo_parcial', 'LIKE', "%{$term}%")
                    ->orWhere('pb.nombre', 'LIKE', "%{$term}%")
                    ->orWhereExists(function ($sub) use ($term) {
                        $sub->select(DB::raw(1))
                            ->from('procesos_prenda as ppren')
                            ->whereColumn('ppren.prenda_bodega_id', 'crp.prenda_bodega_id')
                            ->where(function ($q) {
                                $q->whereColumn('ppren.numero_recibo', 'crp.consecutivo_actual')
                                  ->orWhereNull('ppren.numero_recibo');
                            })
                            ->where(function ($q) {
                                $q->whereColumn('ppren.numero_recibo_parcial', 'rpp.consecutivo_parcial')
                                  ->orWhereNull('ppren.numero_recibo_parcial');
                            })
                            ->where('ppren.encargado', 'LIKE', "%{$term}%");
                    });
            });
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
