<?php

namespace App\Infrastructure\Http\Controllers\VisualizadorPedidos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class VisualizadorPedidosController extends Controller
{
    public function dashboard()
    {
        return view('visualizador-pedidos.dashboard');
    }
}
