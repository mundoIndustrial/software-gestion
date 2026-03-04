<?php

namespace App\Http\Controllers;

use App\Models\LogoObservacionPrendaCot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoObservacionPrendaCotController extends Controller
{
    public function upsert(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado',
            ], 401);
        }

        $allowedRoles = ['visualizador_cotizaciones_logo', 'admin'];
        $hasAccess = false;
        foreach ($allowedRoles as $role) {
            if ($user->hasRole($role)) {
                $hasAccess = true;
                break;
            }
        }
        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para editar la observación del logo',
            ], 403);
        }

        $validated = $request->validate([
            'cotizacion_id' => 'required|integer|exists:cotizaciones,id',
            'prenda_cot_id' => 'required|integer|exists:prendas_cot,id',
            'observacion' => 'nullable|string',
        ]);

        $observacion = LogoObservacionPrendaCot::updateOrCreate(
            [
                'cotizacion_id' => $validated['cotizacion_id'],
                'prenda_cot_id' => $validated['prenda_cot_id'],
            ],
            [
                'observacion' => $validated['observacion'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Observación guardada',
            'data' => [
                'id' => $observacion->id,
                'cotizacion_id' => $observacion->cotizacion_id,
                'prenda_cot_id' => $observacion->prenda_cot_id,
                'observacion' => $observacion->observacion,
                'updated_at' => $observacion->updated_at,
            ],
        ]);
    }
}
