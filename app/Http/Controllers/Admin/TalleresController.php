<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TalleresController extends Controller
{
    public function index()
    {
        return view('admin.talleres.index');
    }
}
