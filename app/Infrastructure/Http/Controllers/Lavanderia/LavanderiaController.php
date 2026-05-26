<?php

namespace App\Infrastructure\Http\Controllers\Lavanderia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LavanderiaController extends Controller
{
    /**
     * Mostrar el dashboard principal de lavandería
     */
    public function index(): View
    {
        return view('lavanderia.index');
    }
}
