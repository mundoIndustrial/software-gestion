<?php

namespace App\Infrastructure\Http\Controllers\Contador;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class CotizacionNotificacionesController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'error' => 'Usuario no autenticado',
                ], 401);
            }

            $viewedCotizationIds = session('viewed_cotizations_' . $user->id, []);

            $cotizacionesParaRevisar = Cotizacion::with('cliente', 'tipoCotizacion')
                ->where('estado', 'ENVIADA_CONTADOR')
                ->whereNotIn('id', $viewedCotizationIds)
                ->where(function ($q) {
                    $q->whereNull('tipo_cotizacion_id')
                        ->orWhereHas('tipoCotizacion', function ($tq) {
                            $tq->where('codigo', '!=', 'EPP');
                        });
                })
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $nuevasCotizaciones = Cotizacion::with('cliente', 'tipoCotizacion')
                ->where('created_at', '>=', now()->subHours(24))
                ->whereNotIn('estado', ['ENVIADA_CONTADOR'])
                ->whereNotIn('id', $viewedCotizationIds)
                ->where(function ($q) {
                    $q->whereNull('tipo_cotizacion_id')
                        ->orWhereHas('tipoCotizacion', function ($tq) {
                            $tq->where('codigo', '!=', 'EPP');
                        });
                })
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $totalNotificaciones = $cotizacionesParaRevisar->count() + $nuevasCotizaciones->count();

            $paraRevisarTransformada = $cotizacionesParaRevisar->map(function ($cot) {
                return [
                    'id' => $cot->id,
                    'cliente' => $cot->cliente ? $cot->cliente->nombre : 'Sin cliente',
                    'created_at' => $cot->created_at,
                ];
            });

            $nuevasTransformada = $nuevasCotizaciones->map(function ($cot) {
                return [
                    'id' => $cot->id,
                    'cliente' => $cot->cliente ? $cot->cliente->nombre : 'Sin cliente',
                    'created_at' => $cot->created_at,
                ];
            });

            return response()->json([
                'cotizaciones_para_revisar' => $paraRevisarTransformada,
                'nuevas_cotizaciones' => $nuevasTransformada,
                'total_notificaciones' => $totalNotificaciones,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error al obtener notificaciones',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function markAllAsRead(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'error' => 'Usuario no autenticado',
                ], 401);
            }

            $cotizacionesParaRevisar = Cotizacion::where('estado', 'ENVIADA_CONTADOR')
                ->pluck('id')
                ->toArray();

            $nuevasCotizaciones = Cotizacion::where('created_at', '>=', now()->subHours(24))
                ->whereNotIn('estado', ['ENVIADA_CONTADOR'])
                ->pluck('id')
                ->toArray();

            $allCotizationIds = array_merge($cotizacionesParaRevisar, $nuevasCotizaciones);
            session(['viewed_cotizations_' . $user->id => $allCotizationIds]);

            return response()->json([
                'success' => true,
                'message' => 'Notificaciones marcadas como leidas',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error al marcar notificaciones como leidas',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

