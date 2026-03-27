<?php

namespace App\Infrastructure\Http\Controllers\Contador;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\PrendaCotizacionFriendly;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

final class ContadorModuleController extends Controller
{
    public function profile()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login')->with('error', 'Por favor inicia sesion para ver tu perfil.');
            }

            return view('contador.profile', compact('user'));
        } catch (\Exception $e) {
            return redirect()->route('contador.index')->with('error', 'Error al cargar el perfil: ' . $e->getMessage());
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
                'telefono' => 'nullable|string|max:20',
                'ciudad' => 'nullable|string|max:255',
                'departamento' => 'nullable|string|max:255',
                'bio' => 'nullable|string|max:500',
                'password' => 'nullable|string|min:8|confirmed',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->telefono = $validated['telefono'] ?? $user->telefono;
            $user->ciudad = $validated['ciudad'] ?? $user->ciudad;
            $user->departamento = $validated['departamento'] ?? $user->departamento;
            $user->bio = $validated['bio'] ?? $user->bio;

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            if ($request->hasFile('avatar')) {
                if ($user->avatar && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
                    Storage::disk('public')->delete('avatars/' . $user->avatar);
                }

                $file = $request->file('avatar');
                $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('avatars', $filename, 'public');
                $user->avatar = $filename;
            }

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado correctamente',
                'avatar_url' => $user->avatar ? route('storage.serve', ['path' => 'avatars/' . $user->avatar]) : null,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function index()
    {
        $cotizaciones = Cotizacion::with('cliente', 'usuario', 'tipoCotizacion')
            ->where('estado', 'ENVIADA_CONTADOR')
            ->where('es_borrador', 0)
            ->where(function ($q) {
                $q->whereNull('tipo_cotizacion_id')
                    ->orWhereHas('tipoCotizacion', function ($tq) {
                        $tq->where('codigo', '!=', 'EPP');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $cotizacionesPorCorregir = Cotizacion::with('cliente', 'usuario', 'tipoCotizacion')
            ->where('estado', 'EN_CORRECCION')
            ->where('es_borrador', 0)
            ->where(function ($q) {
                $q->whereNull('tipo_cotizacion_id')
                    ->orWhereHas('tipoCotizacion', function ($tq) {
                        $tq->where('codigo', '!=', 'EPP');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $cotizacionesRechazadas = Cotizacion::with('cliente', 'usuario', 'tipoCotizacion')
            ->where('estado', 'EN_CORRECCION')
            ->where('es_borrador', 0)
            ->where(function ($q) {
                $q->whereNull('tipo_cotizacion_id')
                    ->orWhereHas('tipoCotizacion', function ($tq) {
                        $tq->where('codigo', '!=', 'EPP');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('contador.index', compact('cotizaciones', 'cotizacionesPorCorregir', 'cotizacionesRechazadas'));
    }

    public function todas()
    {
        $todasLasCotizaciones = Cotizacion::with('cliente', 'usuario', 'tipoCotizacion')
            ->where('estado', '!=', 'ENVIADA_CONTADOR')
            ->where('es_borrador', 0)
            ->where(function ($q) {
                $q->whereNull('tipo_cotizacion_id')
                    ->orWhereHas('tipoCotizacion', function ($tq) {
                        $tq->where('codigo', '!=', 'EPP');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('contador.todas', compact('todasLasCotizaciones'));
    }

    public function porRevisar()
    {
        $cotizacionesParaRevisar = Cotizacion::with('cliente', 'usuario', 'tipoCotizacion')
            ->where('estado', 'EN_CORRECCION')
            ->where(function ($q) {
                $q->whereNull('tipo_cotizacion_id')
                    ->orWhereHas('tipoCotizacion', function ($tq) {
                        $tq->where('codigo', '!=', 'EPP');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('contador.por-revisar', compact('cotizacionesParaRevisar'));
    }

    public function aprobadas()
    {
        $cotizacionesAprobadas = Cotizacion::with('cliente', 'usuario', 'tipoCotizacion')
            ->where('estado', 'APROBADA_POR_APROBADOR')
            ->where('es_borrador', 0)
            ->where(function ($q) {
                $q->whereNull('tipo_cotizacion_id')
                    ->orWhereHas('tipoCotizacion', function ($tq) {
                        $tq->where('codigo', '!=', 'EPP');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('contador.aprobadas', compact('cotizacionesAprobadas'));
    }

    public function guardarNotasTallas(int $prendaId, Request $request)
    {
        try {
            $prenda = PrendaCotizacionFriendly::findOrFail($prendaId);
            $request->validate(['notas' => 'required|string']);

            $prenda->notas_tallas = $request->input('notas');
            $prenda->save();

            return response()->json([
                'success' => true,
                'message' => 'Notas de tallas guardadas correctamente',
                'notas' => $prenda->notas_tallas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar las notas: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function guardarTextoPersonalizadoTallas(int $prendaId, Request $request)
    {
        try {
            $prenda = \App\Models\PrendaCot::findOrFail($prendaId);
            $request->validate(['texto_personalizado' => 'nullable|string']);

            $prenda->texto_personalizado_tallas = $request->input('texto_personalizado');
            $prenda->save();

            return response()->json([
                'success' => true,
                'message' => 'Texto personalizado de tallas guardado correctamente',
                'texto_personalizado' => $prenda->texto_personalizado_tallas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el texto personalizado: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function guardarTallasCostos(Request $request)
    {
        try {
            $validated = $request->validate([
                'cotizacion_id' => 'required|integer|exists:cotizaciones,id',
                'prenda_cot_id' => 'required|integer|exists:prendas_cot,id',
                'descripcion' => 'nullable|string',
            ]);

            $tallasCostos = \App\Models\TallasCostosCot::where('cotizacion_id', $validated['cotizacion_id'])
                ->where('prenda_cot_id', $validated['prenda_cot_id'])
                ->first();

            if ($tallasCostos) {
                $tallasCostos->update(['descripcion' => $validated['descripcion']]);
            } else {
                \App\Models\TallasCostosCot::create($validated);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tallas costos guardado correctamente',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en guardarTallasCostos: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage(),
            ], 500);
        }
    }
}

