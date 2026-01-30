@extends('layouts.asesores')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard de Pedidos')

@section('content')
<div class="dashboard-container">
    <!-- Tarjetas de Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card stat-day">
            <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-content">
                <h3>Pedidos Hoy</h3>
                <p class="stat-value">{{ $stats['pedidos_dia'] }}</p>
                <span class="stat-label">Hoy</span>
            </div>
        </div>

        <div class="stat-card stat-month">
            <div class="stat-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <h3>Pedidos Este Mes</h3>
                <p class="stat-value">{{ $stats['pedidos_mes'] }}</p>
                <span class="stat-label">Este mes</span>
            </div>
        </div>

        <div class="stat-card stat-year">
            <div class="stat-icon">
                <i class="fas fa-calendar"></i>
            </div>
            <div class="stat-content">
                <h3>Pedidos Este Año</h3>
                <p class="stat-value">{{ $stats['pedidos_anio'] }}</p>
                <span class="stat-label">Este año</span>
            </div>
        </div>

    </div>


    <!-- Acciones Rápidas -->
    <div class="quick-actions">
        <h2>Acciones Rápidas</h2>
        <div class="actions-grid">
            <a href="/asesores/pedidos-editable/crear-nuevo" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="action-content">
                    <h4>Nuevo Pedido</h4>
                    <p>Crear un nuevo pedido de dotación</p>
                </div>
            </a>

            <a href="{{ route('asesores.pedidos.index') }}" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="action-content">
                    <h4>Mis Pedidos</h4>
                    <p>Ver todos mis pedidos</p>
                </div>
            </a>

            <a href="{{ route('inventario-telas.index') }}" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="action-content">
                    <h4>Inventario de Telas</h4>
                    <p>Gestionar telas disponibles</p>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection

