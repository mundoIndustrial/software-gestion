<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemError;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemErrorController extends Controller
{
    /**
     * Mostrar vista de errores del sistema
     */
    public function index(Request $request): View
    {
        $query = SystemError::query();

        // Filtro por tipo
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->get('tipo'));
        }

        // Filtro por origen
        if ($request->filled('origen')) {
            $query->where('origen', $request->get('origen'));
        }

        // Filtro por período
        $horas = $request->get('horas', 24);
        $query->where('ocurrido_en', '>=', now()->subHours($horas));

        // Búsqueda
        if ($request->filled('buscar')) {
            $buscar = $request->get('buscar');
            $query->where(function($q) use ($buscar) {
                $q->where('mensaje', 'like', "%{$buscar}%")
                  ->orWhere('tipo', 'like', "%{$buscar}%");
            });
        }

        // Ordenar por recientes primero
        $query->orderBy('ocurrido_en', 'desc');

        // Paginar
        $errores = $query->paginate(25);

        // Estadísticas
        $totalReciente = SystemError::where('ocurrido_en', '>=', now()->subHours($horas))->count();
        $porTipo = SystemError::where('ocurrido_en', '>=', now()->subHours($horas))
            ->groupBy('tipo')
            ->selectRaw('tipo, count(*) as total')
            ->get()
            ->pluck('total', 'tipo');

        $porOrigen = SystemError::where('ocurrido_en', '>=', now()->subHours($horas))
            ->groupBy('origen')
            ->selectRaw('origen, count(*) as total')
            ->get()
            ->pluck('total', 'origen');

        return view('admin.configuracion.errores-sistema', [
            'errores' => $errores,
            'totalReciente' => $totalReciente,
            'porTipo' => $porTipo,
            'porOrigen' => $porOrigen,
            'filtroTipo' => $request->get('tipo'),
            'filtroOrigen' => $request->get('origen'),
            'filtroHoras' => $horas,
            'buscar' => $request->get('buscar')
        ]);
    }

    /**
     * Ver detalles de un error
     */
    public function ver($id): View
    {
        $error = SystemError::findOrFail($id);

        return view('admin.configuracion.error-detalle', [
            'error' => $error
        ]);
    }

    /**
     * Limpiar errores antiguos
     */
    public function limpiar(Request $request)
    {
        $horas = $request->get('horas', 72);

        $deleted = SystemError::where('ocurrido_en', '<', now()->subHours($horas))->delete();

        return back()->with('success', "Se eliminaron {$deleted} errores más antiguos de {$horas} horas.");
    }

    /**
     * Exportar errores a CSV
     */
    public function exportar(Request $request)
    {
        $query = SystemError::query();

        // Aplicar los mismos filtros
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->get('tipo'));
        }

        if ($request->filled('origen')) {
            $query->where('origen', $request->get('origen'));
        }

        $horas = $request->get('horas', 24);
        $query->where('ocurrido_en', '>=', now()->subHours($horas));

        $errores = $query->orderBy('ocurrido_en', 'desc')->get();

        $csv = "Tipo,Mensaje,Origen,Usuario,Pedido,Hora\n";

        foreach ($errores as $error) {
            $usuario = $error->usuario?->name ?? 'Sistema';
            $pedido = $error->pedido_id ?? '-';
            $hora = $error->ocurrido_en->format('Y-m-d H:i:s');

            // Escapar comillas en CSV
            $mensaje = str_replace('"', '""', $error->mensaje);

            $csv .= "\"{$error->tipo}\",\"{$mensaje}\",\"{$error->origen}\",\"{$usuario}\",{$pedido},\"{$hora}\"\n";
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="errores-sistema.csv"');
    }
}
