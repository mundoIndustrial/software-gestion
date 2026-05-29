<?php

namespace App\Infrastructure\Http\Controllers\Operario;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OperarioRecibosPrestamoController extends Controller
{
    public function index()
    {
        $recibosInsumos = DB::table('recibos_prestamo_insumos')
            ->select('id', 'numero_orden', 'fecha', 'nombre_costurero', 'anulado', 'created_at')
            ->orderBy('numero_orden')
            ->orderBy('id')
            ->limit(30)
            ->get();

        $recibosContramuestra = DB::table('recibos_prestamo_contramuestra')
            ->select('id', 'numero_orden', 'fecha', 'nombre_costurero', 'descripcion', 'anulado', 'created_at')
            ->orderBy('numero_orden')
            ->orderBy('id')
            ->limit(30)
            ->get();

        return view('operario.recibos-prestamo', [
            'recibosInsumos' => $recibosInsumos,
            'recibosContramuestra' => $recibosContramuestra,
        ]);
    }

    public function createInsumos()
    {
        $numeroOrden = ((int) DB::table('recibos_prestamo_insumos')->max('numero_orden')) + 1;
        return view('operario.recibos-prestamo-insumos-crear', ['numeroOrden' => $numeroOrden]);
    }

    public function showInsumos(int $id)
    {
        $recibo = DB::table('recibos_prestamo_insumos')
            ->select('id', 'numero_orden', 'fecha', 'nombre_costurero', 'anulado', 'anulado_en', 'created_at')
            ->where('id', $id)
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
        return view('operario.recibos-prestamo-contramuestra-crear', ['numeroOrden' => $numeroOrden]);
    }

    public function showContramuestra(int $id)
    {
        $recibo = DB::table('recibos_prestamo_contramuestra')
            ->select('id', 'numero_orden', 'fecha', 'nombre_costurero', 'descripcion', 'anulado', 'anulado_en', 'created_at')
            ->where('id', $id)
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
}
