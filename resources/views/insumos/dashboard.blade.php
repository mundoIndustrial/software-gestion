@extends('insumos.layout')

@section('title', 'Dashboard - Insumos')
@section('page-title', 'Dashboard de Insumos')

@section('content')
<div class="p-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Bienvenido, {{ Auth::user()->name }}</h2>
        <p class="text-gray-600 mb-6">Panel de control de insumos y materiales</p>
        
        <div class="mt-8">
            <a href="{{ route('insumos.materiales.index') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                <span class="material-symbols-rounded">inventory_2</span>
                Ir a Control de Materiales
            </a>
        </div>
    </div>
</div>
@endsection
