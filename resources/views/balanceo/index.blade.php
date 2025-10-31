@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/tableros.css') }}">
<link rel="stylesheet" href="{{ asset('css/modern-table.css') }}">

<div class="tableros-container" x-data="{ searchQuery: '' }">
    <div class="page-header" style="margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h1 class="tableros-title" style="margin: 0;">
                    <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 10px;">schedule</span>
                    Balanceo de Líneas
                </h1>
                <p class="page-subtitle" style="color: #94a3b8; font-size: 16px; margin-top: 10px;">
                    Gestión de prendas y balanceo de operaciones
                </p>
            </div>
            <a href="{{ route('balanceo.prenda.create') }}" 
               style="background: #ff9d58; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 8px; text-decoration: none; font-weight: 500; box-shadow: 0 2px 4px rgba(255, 157, 88, 0.3); transition: background 0.2s;"
               onmouseover="this.style.background='#e88a47'" onmouseout="this.style.background='#ff9d58'">
                <span class="material-symbols-rounded">add</span>
                Nueva Prenda
            </a>
        </div>

        <!-- Buscador -->
        <div style="padding: 18px 0;">
            <div style="position: relative;">
                <span class="material-symbols-rounded" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 22px;">search</span>
                <input type="text" 
                       x-model="searchQuery"
                       placeholder="Buscar por nombre, referencia o tipo de prenda..."
                       style="width: 100%; padding: 12px 16px 12px 48px; border: 1px solid rgba(255, 157, 88, 0.2); border-radius: 8px; font-size: 15px; transition: all 0.3s ease; background: rgba(255, 255, 255, 0.05); color: white;"
                       onfocus="this.style.borderColor='rgba(255, 157, 88, 0.4)'; this.style.boxShadow='0 0 0 3px rgba(255, 157, 88, 0.1)'"
                       onblur="this.style.borderColor='rgba(255, 157, 88, 0.2)'; this.style.boxShadow='none'">
            </div>
        </div>
    </div>

    @if(session('success'))
    <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <span class="material-symbols-rounded">check_circle</span>
        {{ session('success') }}
    </div>
    @endif

    <!-- Grid de prendas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
        @forelse($prendas as $prenda)
        <div class="prenda-card" 
             x-show="searchQuery === '' || 
                     '{{ strtolower($prenda->nombre) }}'.includes(searchQuery.toLowerCase()) || 
                     '{{ strtolower($prenda->referencia ?? '') }}'.includes(searchQuery.toLowerCase()) || 
                     '{{ strtolower($prenda->tipo) }}'.includes(searchQuery.toLowerCase())"
             x-transition
             style="background: rgba(255, 255, 255, 0.03); border-radius: 12px; overflow: hidden; border: 1px solid rgba(255, 157, 88, 0.15); transition: transform 0.3s ease, border-color 0.3s ease; cursor: pointer;"
             onclick="window.location='{{ route('balanceo.show', $prenda->id) }}'">
            
            <!-- Imagen de la prenda -->
            <div style="height: 180px; background: white; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                @if($prenda->imagen)
                <img src="{{ asset($prenda->imagen) }}" 
                     alt="{{ $prenda->nombre }}"
                     style="width: 100%; height: 100%; object-fit: contain;">
                @else
                <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                    <span class="material-symbols-rounded" style="font-size: 80px; color: #ccc;">checkroom</span>
                </div>
                @endif
                
                <!-- Badge del tipo -->
                <div style="position: absolute; top: 12px; right: 12px; background: #ff9d58; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase;">
                    {{ $prenda->tipo }}
                </div>
            </div>

            <!-- Contenido de la tarjeta -->
            <div style="padding: 20px;">
                <h3 style="margin: 0 0 8px 0; font-size: 20px; color: white;">{{ $prenda->nombre }}</h3>
                
                @if($prenda->referencia)
                <p style="margin: 0 0 12px 0; color: #94a3b8; font-size: 14px;">
                    <strong>Ref:</strong> {{ $prenda->referencia }}
                </p>
                @endif

                @if($prenda->descripcion)
                <p style="margin: 0 0 16px 0; color: #94a3b8; font-size: 14px; line-height: 1.5;">
                    {{ Str::limit($prenda->descripcion, 100) }}
                </p>
                @endif

                <!-- Información del balanceo -->
                @if($prenda->balanceoActivo)
                <div style="border-top: 1px solid rgba(255, 157, 88, 0.15); padding-top: 16px; margin-top: 16px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
                        <div>
                            <p style="margin: 0; color: #94a3b8; font-size: 11px; text-transform: uppercase;">Operaciones</p>
                            <p style="margin: 4px 0 0 0; font-weight: 600; color: #ff9d58;">
                                {{ $prenda->balanceoActivo->operaciones->count() }}
                            </p>
                        </div>
                        <div>
                            <p style="margin: 0; color: #94a3b8; font-size: 11px; text-transform: uppercase;">SAM Total</p>
                            <p style="margin: 4px 0 0 0; font-weight: 600; color: #ff9d58;">
                                {{ number_format($prenda->balanceoActivo->sam_total, 2) }}s
                            </p>
                        </div>
                        <div>
                            <p style="margin: 0; color: #94a3b8; font-size: 11px; text-transform: uppercase;">Operarios</p>
                            <p style="margin: 4px 0 0 0; font-weight: 600; color: #ff9d58;">
                                {{ $prenda->balanceoActivo->total_operarios }}
                            </p>
                        </div>
                        <div>
                            <p style="margin: 0; color: #94a3b8; font-size: 11px; text-transform: uppercase;">Meta Real</p>
                            <p style="margin: 4px 0 0 0; font-weight: 600; color: #ff9d58;">
                                {{ $prenda->balanceoActivo->meta_real ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
                @else
                <div style="border-top: 1px solid rgba(255, 157, 88, 0.15); padding-top: 16px; margin-top: 16px; text-align: center;">
                    <p style="margin: 0; color: #94a3b8; font-size: 13px;">Sin balanceo configurado</p>
                </div>
                @endif

                <!-- Botón de acción -->
                <button style="width: 100%; margin-top: 16px; background: #ff9d58; color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 8px; transition: background 0.2s;" onmouseover="this.style.background='#e88a47'" onmouseout="this.style.background='#ff9d58'">
                    <span class="material-symbols-rounded" style="font-size: 18px;">visibility</span>
                    Ver Balanceo
                </button>
            </div>
        </div>
        @empty
        <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 157, 88, 0.15);">
            <span class="material-symbols-rounded" style="font-size: 64px; color: #94a3b8; display: block; margin-bottom: 16px;">checkroom</span>
            <h3 style="color: white; margin-bottom: 8px;">No hay prendas registradas</h3>
            <p style="color: #94a3b8; margin-bottom: 24px;">Comienza creando tu primera prenda para gestionar su balanceo</p>
            <a href="{{ route('balanceo.prenda.create') }}" 
               style="background: #ff9d58; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-weight: 500;">
                <span class="material-symbols-rounded">add</span>
                Nueva Prenda
            </a>
        </div>
        @endforelse
    </div>
</div>

<style>
.prenda-card:hover {
    transform: translateY(-5px);
    border-color: rgba(255, 157, 88, 0.4);
}

.page-subtitle {
    color: #666;
    font-size: 16px;
    margin-top: 10px;
}
</style>

@endsection
