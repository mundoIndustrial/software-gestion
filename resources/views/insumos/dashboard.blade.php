@extends('insumos.layout')

@section('title', 'Dashboard - Insumos')
@section('page-title', 'Dashboard de Insumos')

@section('content')
<link rel="stylesheet" href="{{ asset('css/insumos/materiales.css') }}?v={{ time() }}">
<style>
    .dashboard-card {
        background-color: var(--bg-card);
        color: var(--text-primary);
        transition: all 0.3s ease;
    }
    
    .dashboard-card h2 {
        color: var(--text-primary);
    }
    
    .dashboard-card p {
        color: var(--text-secondary);
    }
    
    .dashboard-btn {
        background-color: var(--primary-color);
        color: white;
        transition: all 0.3s ease;
    }
    
    .dashboard-btn:hover {
        background-color: var(--primary-dark);
    }
</style>
<div class="p-8">
    <div class="dashboard-card rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-2">Bienvenido, {{ Auth::user()->name }}</h2>
        <p class="mb-6">Panel de control de insumos y materiales</p>
        
        <div class="mt-8">
            <a href="{{ route('insumos.materiales.index') }}" class="dashboard-btn inline-flex items-center gap-2 px-6 py-3 rounded-lg">
                <span class="material-symbols-rounded">inventory_2</span>
                Ir a Control de Materiales
            </a>
        </div>
    </div>
</div>
@endsection
