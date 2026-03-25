<?php

namespace App\Infrastructure\Repositories\InventarioTelas;

use App\Domain\InventarioTelas\Repositories\InventarioTelaRepositoryInterface;
use App\Models\InventarioTela;
use Illuminate\Support\Facades\DB;

class InventarioTelaRepository implements InventarioTelaRepositoryInterface
{
    public function obtenerTodas()
    {
        return InventarioTela::orderBy('categoria')->orderBy('nombre_tela')->get();
    }

    public function obtenerPorId(int $id)
    {
        return InventarioTela::findOrFail($id);
    }

    public function crear(array $datos)
    {
        return InventarioTela::create($datos);
    }

    public function actualizarStock(int $telaId, float $nuevoStock)
    {
        $tela = InventarioTela::findOrFail($telaId);
        $tela->stock = $nuevoStock;
        $tela->save();
        return $tela;
    }

    public function registrarMovimiento(int $telaId, int $usuarioId, string $tipoAccion, float $cantidad, float $stockAnterior, float $stockNuevo, ?string $observaciones = null)
    {
        DB::table('inventario_telas_historial')->insert([
            'inventario_tela_id' => $telaId,
            'user_id' => $usuarioId,
            'tipo_accion' => $tipoAccion,
            'cantidad' => $cantidad,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $stockNuevo,
            'observaciones' => $observaciones,
            'fecha_accion' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function obtenerHistorial(int $limite = 100)
    {
        return DB::table('inventario_telas_historial as h')
            ->join('inventario_telas as t', 'h.inventario_tela_id', '=', 't.id')
            ->join('users as u', 'h.user_id', '=', 'u.id')
            ->select(
                'h.*',
                't.nombre_tela as tela_nombre',
                't.categoria as tela_categoria',
                'u.name as usuario_nombre'
            )
            ->orderBy('h.fecha_accion', 'desc')
            ->limit($limite)
            ->get();
    }

    public function obtenerEstadisticas()
    {
        return [
            'total_entradas' => DB::table('inventario_telas_historial')
                ->where('tipo_accion', 'entrada')
                ->count(),
            'total_salidas' => DB::table('inventario_telas_historial')
                ->where('tipo_accion', 'salida')
                ->count(),
            'stock_total' => InventarioTela::sum('stock')
        ];
    }

    public function obtenerTelasMasMovidas(int $dias = 30, int $limite = 10)
    {
        return DB::table('inventario_telas_historial as h')
            ->join('inventario_telas as t', 'h.inventario_tela_id', '=', 't.id')
            ->where('h.fecha_accion', '>=', now()->subDays($dias))
            ->select(
                't.nombre_tela',
                DB::raw('SUM(h.cantidad) as total_movimientos')
            )
            ->groupBy('t.id', 't.nombre_tela')
            ->orderBy('total_movimientos', 'desc')
            ->limit($limite)
            ->get();
    }

    public function obtenerStockPorTela()
    {
        return InventarioTela::select('nombre_tela', 'stock')
            ->orderBy('stock', 'desc')
            ->get();
    }

    public function obtenerTelasParaFiltros()
    {
        return InventarioTela::select('id', 'nombre_tela', 'categoria')
            ->orderBy('nombre_tela')
            ->get();
    }

    public function eliminar(int $telaId)
    {
        DB::table('inventario_telas_historial')
            ->where('inventario_tela_id', $telaId)
            ->delete();
        
        InventarioTela::findOrFail($telaId)->delete();
    }
}
