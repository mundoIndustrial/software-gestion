<?php

namespace App\Infrastructure\Http\Controllers\Operario;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OperarioRecibosPrestamoController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'insumos');
        $tab = in_array($tab, ['insumos', 'contramuestra'], true) ? $tab : 'insumos';
        $searchInsumos = trim((string) $request->query('search_insumos', ''));
        $searchContramuestra = trim((string) $request->query('search_contramuestra', ''));
        $perPage = 10;

        $queryInsumos = DB::table('recibos_prestamo_insumos')
            ->select('id', 'numero_orden', 'fecha', 'nombre_costurero', 'firma_mensajero', 'firma_costurero', 'anulado', 'anulado_en', 'confirmado_entrada', 'confirmado_entrada_en', 'novedades', 'created_at')
            ->orderBy('numero_orden')
            ->orderBy('id');

        if ($searchInsumos !== '') {
            $queryInsumos->where(function ($q) use ($searchInsumos) {
                $q->where('numero_orden', 'like', '%' . $searchInsumos . '%')
                    ->orWhere('nombre_costurero', 'like', '%' . $searchInsumos . '%');
            });
        }

        $queryContramuestra = DB::table('recibos_prestamo_contramuestra')
            ->select('id', 'numero_orden', 'fecha', 'nombre_costurero', 'descripcion', 'firma_mensajero', 'firma_costurero', 'anulado', 'anulado_en', 'confirmado_entrada', 'confirmado_entrada_en', 'novedades', 'created_at')
            ->orderBy('numero_orden')
            ->orderBy('id');

        if ($searchContramuestra !== '') {
            $queryContramuestra->where(function ($q) use ($searchContramuestra) {
                $q->where('numero_orden', 'like', '%' . $searchContramuestra . '%')
                    ->orWhere('nombre_costurero', 'like', '%' . $searchContramuestra . '%');
            });
        }

        $recibosInsumos = $queryInsumos
            ->paginate($perPage, ['*'], 'page_insumos')
            ->appends([
                'tab' => $tab,
                'search_insumos' => $searchInsumos,
                'search_contramuestra' => $searchContramuestra,
            ]);

        $recibosContramuestra = $queryContramuestra
            ->paginate($perPage, ['*'], 'page_contramuestra')
            ->appends([
                'tab' => $tab,
                'search_insumos' => $searchInsumos,
                'search_contramuestra' => $searchContramuestra,
            ]);

        return view('operario.recibos-prestamo', [
            'tabActiva' => $tab,
            'searchInsumos' => $searchInsumos,
            'searchContramuestra' => $searchContramuestra,
            'recibosInsumos' => $recibosInsumos,
            'recibosContramuestra' => $recibosContramuestra,
        ]);
    }

    public function createInsumos()
    {
        $numeroOrden = ((int) DB::table('recibos_prestamo_insumos')->max('numero_orden')) + 1;
        $rolTallerId = (int) DB::table('roles')->whereRaw('LOWER(name) = ?', ['taller'])->value('id');

        $talleres = DB::table('users')
            ->select('name')
            ->where(function ($query) use ($rolTallerId) {
                if ($rolTallerId > 0) {
                    $query->whereJsonContains('roles_ids', $rolTallerId)
                        ->orWhere('role_id', $rolTallerId);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name')
            ->filter(fn ($name) => trim((string) $name) !== '')
            ->unique()
            ->values();

        return view('operario.recibos-prestamo-insumos-crear', [
            'numeroOrden' => $numeroOrden,
            'talleres' => $talleres,
        ]);
    }

    public function showInsumos(int $id)
    {
        $recibo = DB::table('recibos_prestamo_insumos')
            ->leftJoin('users', 'users.id', '=', 'recibos_prestamo_insumos.creado_por')
            ->select(
                'recibos_prestamo_insumos.id',
                'recibos_prestamo_insumos.numero_orden',
                'recibos_prestamo_insumos.fecha',
                'recibos_prestamo_insumos.nombre_costurero',
                'recibos_prestamo_insumos.firma_mensajero',
                'recibos_prestamo_insumos.firma_mensajero_fecha',
                'recibos_prestamo_insumos.firma_costurero',
                'recibos_prestamo_insumos.firma_costurero_fecha',
                'recibos_prestamo_insumos.anulado',
                'recibos_prestamo_insumos.anulado_en',
                'recibos_prestamo_insumos.created_at',
                'users.name as encargado_nombre'
            )
            ->where('recibos_prestamo_insumos.id', $id)
            ->first();

        if (!$recibo) {
            throw new NotFoundHttpException('Recibo de insumos no encontrado.');
        }

        $items = DB::table('recibos_prestamo_insumos_items')
            ->select('cantidad', 'descripcion', 'orden_fila')
            ->where('recibo_prestamo_insumo_id', $id)
            ->orderBy('orden_fila')
            ->get()
            ->map(function ($item) {
                $cantidadRaw = (float) $item->cantidad;
                $esEntero = fmod($cantidadRaw, 1.0) == 0.0;
                $item->cantidad = $esEntero ? (int) $cantidadRaw : $cantidadRaw;
                return $item;
            });

        return view('operario.recibos-prestamo-insumos-ver', [
            'recibo' => $recibo,
            'items' => $items,
        ]);
    }

    public function storeInsumos(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre_costurero' => ['required', 'string', 'max:150'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.cantidad' => ['nullable', 'numeric', 'min:0'],
            'items.*.descripcion' => ['nullable', 'string'],
        ]);

        $items = collect($validated['items'])
            ->map(function (array $item): array {
                return [
                    'cantidad' => isset($item['cantidad']) ? (float) $item['cantidad'] : 0,
                    'descripcion' => isset($item['descripcion']) ? trim((string) $item['descripcion']) : '',
                ];
            })
            ->filter(fn (array $item): bool => $item['descripcion'] !== '' || $item['cantidad'] > 0)
            ->values();

        if ($items->isEmpty()) {
            return back()
                ->withErrors(['items' => 'Debes registrar al menos una fila con cantidad o descripción.'])
                ->withInput();
        }

        DB::transaction(function () use ($validated, $items): void {
            $numeroOrden = ((int) DB::table('recibos_prestamo_insumos')->max('numero_orden')) + 1;

            $reciboId = DB::table('recibos_prestamo_insumos')->insertGetId([
                'numero_orden' => $numeroOrden,
                'fecha' => now()->toDateString(),
                'nombre_costurero' => $validated['nombre_costurero'],
                'anulado' => false,
                'creado_por' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rows = $items->map(function (array $item, int $index) use ($reciboId): array {
                return [
                    'recibo_prestamo_insumo_id' => $reciboId,
                    'cantidad' => $item['cantidad'],
                    'descripcion' => $item['descripcion'],
                    'orden_fila' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            DB::table('recibos_prestamo_insumos_items')->insert($rows);
        });

        return redirect()
            ->route('operario.recibos-prestamo.index', ['tab' => 'insumos'])
            ->with('success', 'Recibo de préstamo de insumos creado correctamente.');
    }

    public function createContramuestra()
    {
        $numeroOrden = ((int) DB::table('recibos_prestamo_contramuestra')->max('numero_orden')) + 1;
        $rolTallerId = (int) DB::table('roles')->whereRaw('LOWER(name) = ?', ['taller'])->value('id');

        $talleres = DB::table('users')
            ->select('name')
            ->where(function ($query) use ($rolTallerId) {
                if ($rolTallerId > 0) {
                    $query->whereJsonContains('roles_ids', $rolTallerId)
                        ->orWhere('role_id', $rolTallerId);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name')
            ->filter(fn ($name) => trim((string) $name) !== '')
            ->unique()
            ->values();

        return view('operario.recibos-prestamo-contramuestra-crear', [
            'numeroOrden' => $numeroOrden,
            'talleres' => $talleres,
        ]);
    }

    public function showContramuestra(int $id)
    {
        $recibo = DB::table('recibos_prestamo_contramuestra')
            ->leftJoin('users', 'users.id', '=', 'recibos_prestamo_contramuestra.creado_por')
            ->select(
                'recibos_prestamo_contramuestra.id',
                'recibos_prestamo_contramuestra.numero_orden',
                'recibos_prestamo_contramuestra.fecha',
                'recibos_prestamo_contramuestra.nombre_costurero',
                'recibos_prestamo_contramuestra.descripcion',
                'recibos_prestamo_contramuestra.firma_mensajero',
                'recibos_prestamo_contramuestra.firma_mensajero_fecha',
                'recibos_prestamo_contramuestra.firma_costurero',
                'recibos_prestamo_contramuestra.firma_costurero_fecha',
                'recibos_prestamo_contramuestra.anulado',
                'recibos_prestamo_contramuestra.anulado_en',
                'recibos_prestamo_contramuestra.created_at',
                'users.name as encargado_nombre'
            )
            ->where('recibos_prestamo_contramuestra.id', $id)
            ->first();

        if (!$recibo) {
            throw new NotFoundHttpException('Recibo de contramuestra no encontrado.');
        }

        return view('operario.recibos-prestamo-contramuestra-ver', [
            'recibo' => $recibo,
        ]);
    }

    public function storeContramuestra(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre_costurero' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($validated): void {
            $numeroOrden = ((int) DB::table('recibos_prestamo_contramuestra')->max('numero_orden')) + 1;

            DB::table('recibos_prestamo_contramuestra')->insert([
                'numero_orden' => $numeroOrden,
                'fecha' => now()->toDateString(),
                'nombre_costurero' => $validated['nombre_costurero'],
                'descripcion' => trim($validated['descripcion']),
                'anulado' => false,
                'creado_por' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('operario.recibos-prestamo.index', ['tab' => 'contramuestra'])
            ->with('success', 'Recibo de préstamo de contramuestra creado correctamente.');
    }
    public function guardarFirmaInsumos(Request $request, int $id, string $firmante): JsonResponse
    {
        if (!in_array($firmante, ['costurero', 'mensajero'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Firmante invalido.',
            ], 422);
        }

        $validated = $request->validate([
            'firma' => ['required', 'string'],
        ]);

        $firma = (string) $validated['firma'];

        if (!preg_match('/^data:image\/webp;base64,[A-Za-z0-9+\/=]+$/', $firma)) {
            return response()->json([
                'success' => false,
                'message' => 'La firma debe estar en formato WEBP.',
            ], 422);
        }

        $base64 = substr($firma, strpos($firma, ',') + 1);
        $binary = base64_decode($base64, true);
        if ($binary === false) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo decodificar la firma.',
            ], 422);
        }

        $directory = "firmas/recibos-prestamo/insumos/{$id}";
        $filename = "{$firmante}_" . now()->format('Ymd_His') . '.webp';
        $relativePath = "{$directory}/{$filename}";
        Storage::disk('public')->put($relativePath, $binary);

        $columnFirma = "firma_{$firmante}";
        $columnFecha = "firma_{$firmante}_fecha";

        $updated = DB::table('recibos_prestamo_insumos')
            ->where('id', $id)
            ->update([
                $columnFirma => 'storage/' . $relativePath,
                $columnFecha => now(),
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Recibo no encontrado o sin cambios.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Firma guardada correctamente.',
            'firma' => asset('storage/' . $relativePath),
        ]);
    }

    public function guardarFirmaContramuestra(Request $request, int $id, string $firmante): JsonResponse
    {
        if (!in_array($firmante, ['costurero', 'mensajero'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Firmante invalido.',
            ], 422);
        }

        $validated = $request->validate([
            'firma' => ['required', 'string'],
        ]);

        $firma = (string) $validated['firma'];
        if (!preg_match('/^data:image\/webp;base64,[A-Za-z0-9+\/=]+$/', $firma)) {
            return response()->json([
                'success' => false,
                'message' => 'La firma debe estar en formato WEBP.',
            ], 422);
        }

        $base64 = substr($firma, strpos($firma, ',') + 1);
        $binary = base64_decode($base64, true);
        if ($binary === false) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo decodificar la firma.',
            ], 422);
        }

        $directory = "firmas/recibos-prestamo/contramuestra/{$id}";
        $filename = "{$firmante}_" . now()->format('Ymd_His') . '.webp';
        $relativePath = "{$directory}/{$filename}";
        Storage::disk('public')->put($relativePath, $binary);

        $columnFirma = "firma_{$firmante}";
        $columnFecha = "firma_{$firmante}_fecha";

        $updated = DB::table('recibos_prestamo_contramuestra')
            ->where('id', $id)
            ->update([
                $columnFirma => 'storage/' . $relativePath,
                $columnFecha => now(),
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Recibo no encontrado o sin cambios.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Firma guardada correctamente.',
            'firma' => asset('storage/' . $relativePath),
        ]);
    }

    public function anularInsumos(int $id): JsonResponse
    {
        $updated = DB::table('recibos_prestamo_insumos')
            ->where('id', $id)
            ->update([
                'anulado' => true,
                'anulado_en' => now(),
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return response()->json(['success' => false, 'message' => 'Recibo no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Recibo anulado correctamente.']);
    }

    public function anularContramuestra(int $id): JsonResponse
    {
        $updated = DB::table('recibos_prestamo_contramuestra')
            ->where('id', $id)
            ->update([
                'anulado' => true,
                'anulado_en' => now(),
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return response()->json(['success' => false, 'message' => 'Recibo no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Recibo anulado correctamente.']);
    }

    public function confirmarEntradaInsumos(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'corresponde' => ['required', 'boolean'],
            'novedades' => ['nullable', 'string'],
        ]);

        if ($validated['corresponde'] === false && trim((string) ($validated['novedades'] ?? '')) === '') {
            return response()->json(['success' => false, 'message' => 'Debes registrar una novedad cuando no corresponde.'], 422);
        }

        $updated = DB::table('recibos_prestamo_insumos')
            ->where('id', $id)
            ->update([
                'confirmado_entrada' => true,
                'confirmado_entrada_en' => now(),
                'novedades' => trim((string) ($validated['novedades'] ?? '')) ?: null,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return response()->json(['success' => false, 'message' => 'Recibo no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Entrada confirmada correctamente.']);
    }

    public function confirmarEntradaContramuestra(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'corresponde' => ['required', 'boolean'],
            'novedades' => ['nullable', 'string'],
        ]);

        if ($validated['corresponde'] === false && trim((string) ($validated['novedades'] ?? '')) === '') {
            return response()->json(['success' => false, 'message' => 'Debes registrar una novedad cuando no corresponde.'], 422);
        }

        $updated = DB::table('recibos_prestamo_contramuestra')
            ->where('id', $id)
            ->update([
                'confirmado_entrada' => true,
                'confirmado_entrada_en' => now(),
                'novedades' => trim((string) ($validated['novedades'] ?? '')) ?: null,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return response()->json(['success' => false, 'message' => 'Recibo no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Entrada confirmada correctamente.']);
    }
}
