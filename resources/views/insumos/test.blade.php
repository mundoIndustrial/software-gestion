@extends('insumos.layout')

@section('title', 'Test - Insumos')
@section('page-title', 'Test de Insumos')

@section('content')
<div class="p-8 bg-white rounded-lg m-4">
    <h1 class="text-3xl font-bold mb-4">Test - Módulo de Insumos</h1>
    
    <div class="bg-blue-50 p-4 rounded-lg mb-4">
        <p class="text-blue-900"><strong>Usuario:</strong> {{ Auth::user()->name }}</p>
        <p class="text-blue-900"><strong>Email:</strong> {{ Auth::user()->email }}</p>
        <p class="text-blue-900"><strong>Rol:</strong> {{ Auth::user()->role ?? 'Sin rol' }}</p>
        @if(is_object(Auth::user()->role))
            <p class="text-blue-900"><strong>Rol (objeto):</strong> {{ Auth::user()->role->name ?? 'Sin nombre' }}</p>
        @endif
    </div>
    
    <div class="bg-green-50 p-4 rounded-lg mb-4">
        <h2 class="text-xl font-bold text-green-900 mb-2">Rutas Disponibles:</h2>
        <ul class="list-disc list-inside text-green-900">
            <li><a href="{{ route('insumos.dashboard') }}" class="text-blue-600 underline">Dashboard de Insumos</a></li>
            <li><a href="{{ route('insumos.materiales.index') }}" class="text-blue-600 underline">Control de Materiales</a></li>
        </ul>
    </div>
    
    <div class="bg-yellow-50 p-4 rounded-lg">
        <h2 class="text-xl font-bold text-yellow-900 mb-2">Información de Sesión:</h2>
        <p class="text-yellow-900"><strong>Authenticated:</strong> {{ Auth::check() ? 'Sí' : 'No' }}</p>
        <p class="text-yellow-900"><strong>Ruta actual:</strong> {{ Route::currentRouteName() ?? 'Sin nombre' }}</p>
        <p class="text-yellow-900"><strong>URL actual:</strong> {{ url()->current() }}</p>
    </div>
</div>
@endsection
