@extends('layouts.base')

@section('title', 'Seguimiento de Lavanderia')
@section('page-title', 'Seguimiento de Lavanderia')
@section('module', 'produccion')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/top-nav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/talleres/talleres-admin.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/talleres/talleres-spa.css') }}?v={{ time() }}">
@endpush

@section('body')
    @include('components.top-nav')

    <aside class="talleres-sidebar">
        <div class="sidebar-header">
            <a href="{{ route('dashboard') }}" class="logo-wrapper" aria-label="Ir al Dashboard">
                <img src="{{ asset('images/logo2.png') }}"
                     alt="Logo Mundo Industrial"
                     class="header-logo"
                     data-logo-light="{{ asset('images/logo2.png') }}"
                     data-logo-dark="{{ asset('logo.png') }}">
            </a>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Colapsar menu">
                <span class="material-symbols-rounded">chevron_left</span>
            </button>
        </div>

        <nav class="sidebar-nav">
            <button class="sidebar-item active" data-view="viewOrdenes" id="navOrdenes">
                <span class="material-symbols-rounded">assignment</span>
                <span class="nav-label">Ordenes</span>
            </button>
            <button class="sidebar-item" data-view="viewHistorialMovimientos" id="navHistorialMovimientos">
                <span class="material-symbols-rounded">history</span>
                <span class="nav-label">Historial de Movimientos</span>
            </button>
        </nav>

        <div class="sidebar-footer">
            <a href="{{ route('dashboard') }}" class="btn-volver">
                <span class="material-symbols-rounded">arrow_back</span>
                <span class="nav-label">Volver</span>
            </a>
        </div>
    </aside>

    <main class="main-container seguimiento-lavanderia-main">
        <div id="viewOrdenes" class="view-container">
            

            <div class="table-container" style="padding: 24px;">
                <div class="recibos-card" style="margin: 0; box-shadow: none; border: 1px solid #e2e8f0;">
                    <div class="card-header">
                        <div class="icon">
                            <span class="material-symbols-rounded" style="font-size: 18px;">assignment</span>
                        </div>
                        <h2>Ordenes</h2>
                    </div>
                    <div style="padding: 20px; color: #64748b;">
                        Aqui se listaran las ordenes pendientes, en proceso y listas para seguimiento.
                    </div>
                </div>
            </div>
        </div>

        <div id="viewHistorialMovimientos" class="view-container" style="display: none;">
            <div class="page-header">
                <div>
                    <h1 class="section-title" style="margin: 0;">Historial de Movimientos</h1>
                    <p class="section-subtitle" style="margin: 6px 0 0 0;">Espacio preparado para las salidas y entradas registradas en lavanderia.</p>
                </div>
            </div>

            <div class="table-container" style="padding: 24px;">
                <div class="recibos-card" style="margin: 0; box-shadow: none; border: 1px solid #e2e8f0;">
                    <div class="card-header">
                        <div class="icon">
                            <span class="material-symbols-rounded" style="font-size: 18px;">history</span>
                        </div>
                        <h2>Movimientos</h2>
                    </div>
                    <div style="padding: 20px; color: #64748b;">
                        Aqui aparecera el historial de movimientos de lavanderia cuando lo conectemos.
                    </div>
                </div>
            </div>
        </div>
    </main>

    <style>
        .seguimiento-lavanderia-main {
            min-height: 100vh;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const body = document.body;
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.talleres-sidebar');
            const viewButtons = document.querySelectorAll('.sidebar-item[data-view]');
            const views = document.querySelectorAll('.view-container');

            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function () {
                    body.classList.toggle('talleres-sidebar-collapsed');
                });
            }

            function activateView(viewId) {
                views.forEach(view => {
                    view.style.display = view.id === viewId ? 'block' : 'none';
                });

                viewButtons.forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.view === viewId);
                });
            }

            viewButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    activateView(this.dataset.view);
                });
            });

            activateView('viewOrdenes');
        });
    </script>
@endsection
