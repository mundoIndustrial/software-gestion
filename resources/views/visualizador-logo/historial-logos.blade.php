@extends('layouts.visualizador-logo')

@section('title', 'Historial de Logos')

@section('page-title', 'Historial de Logos')

@section('content')
<div style="padding: 1rem 1rem 2rem 1rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); min-height: calc(100vh - 60px); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <div style="display: flex; justify-content: center;">
        <div style="width: 100%; max-width: 1180px;">
            <!-- Grid de Clientes -->
            <div style="
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            " id="clientesGrid">
                @forelse($clientesConLogos as $clienteData)
                    <div style="
                        background: white;
                        border-radius: 16px;
                        border: 1px solid #e2e8f0;
                        padding: 1.5rem;
                        display: flex;
                        flex-direction: column;
                        gap: 1rem;
                        box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
                        transition: all 0.2s ease;
                    " 
                    data-cliente-id="{{ $clienteData['cliente_id'] }}"
                    data-cliente-nombre="{{ strtolower($clienteData['cliente_nombre']) }}"
                    data-cantidad-logos="{{ $clienteData['cantidad_logos'] }}"
                    onmouseover="this.style.borderColor='#cbd5e1'; this.style.boxShadow='0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)'; this.style.transform='translateY(-2px)';"
                    onmouseout="this.style.borderColor='#e2e8f0'; this.style.boxShadow='0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)'; this.style.transform='translateY(0)';">
                        
                        <!-- Card Header -->
                        <div>
                            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; font-weight: 800; color: #1e293b; word-wrap: break-word;">
                                {{ $clienteData['cliente_nombre'] }}
                            </h3>
                            <p style="margin: 0; font-size: 0.85rem; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; color: #64748b;">
                                Cliente
                            </p>
                        </div>

                        <!-- Stats -->
                        <div style="
                            background: #f8fafc;
                            border: 1px solid #e2e8f0;
                            border-radius: 12px;
                            padding: 1rem;
                            display: flex;
                            flex-direction: column;
                            gap: 0.75rem;
                        ">
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                                <span style="font-size: 0.9rem; color: #475569;">Logos Confirmados</span>
                                <span style="
                                    font-size: 1.25rem;
                                    font-weight: 800;
                                    color: #0ea5e9;
                                    background: linear-gradient(135deg, #dbeafe 0%, #e0f2fe 100%);
                                    padding: 0.25rem 0.75rem;
                                    border-radius: 8px;
                                ">{{ $clienteData['cantidad_logos'] }}</span>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <button 
                            onclick="abrirGaleriaDisenios({{ $clienteData['cliente_id'] }}, '{{ $clienteData['cliente_nombre'] }}')"
                            style="
                                display: inline-flex;
                                align-items: center;
                                justify-content: center;
                                gap: 0.5rem;
                                width: 100%;
                                padding: 0.75rem;
                                border-radius: 12px;
                                background: linear-gradient(135deg, #2450ef 0%, #1e40af 100%);
                                color: white;
                                font-size: 0.9rem;
                                font-weight: 700;
                                letter-spacing: 0.3px;
                                text-transform: uppercase;
                                border: none;
                                cursor: pointer;
                                box-shadow: 0 10px 20px rgba(36, 80, 239, 0.18);
                                transition: all 0.2s ease;
                                margin-top: auto;
                            "
                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 15px 30px rgba(36, 80, 239, 0.25)';"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 20px rgba(36, 80, 239, 0.18)';">
                            <span class="material-symbols-rounded" style="font-size: 1.1rem;">image</span>
                            Ver Diseños
                        </button>
                    </div>
                @empty
                    <div style="
                        width: 100%;
                        padding: 3rem 2rem;
                        text-align: center;
                        color: #64748b;
                        background: white;
                        border-radius: 12px;
                        border: 1px dashed #cbd5e1;
                        grid-column: 1 / -1;
                    ">
                        <div style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;">
                            <span class="material-symbols-rounded" style="font-size: 3.5rem;">inbox</span>
                        </div>
                        <p style="margin: 0; font-size: 1rem; font-weight: 500;">
                            No se encontraron clientes con logos confirmados.
                        </p>
                        <p style="margin: 0.5rem 0 0; font-size: 0.9rem; color: #94a3b8;">
                            Los logos aparecerán aquí cuando sean confirmados.
                        </p>
                    </div>
                @endforelse
            </div>

            <!-- Paginación de Clientes -->
            @if($clientesConLogos->hasPages())
                <div style="display: flex; justify-content: center; margin-top: 2rem;">
                    <nav style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: center;">
                        @if($clientesConLogos->onFirstPage())
                            <span style="
                                padding: 0.6rem 1rem;
                                border-radius: 8px;
                                background: #f1f5f9;
                                color: #94a3b8;
                                font-weight: 600;
                                cursor: not-allowed;
                            ">← Anterior</span>
                        @else
                            <a href="{{ $clientesConLogos->previousPageUrl() }}" style="
                                padding: 0.6rem 1rem;
                                border-radius: 8px;
                                background: white;
                                border: 2px solid #e2e8f0;
                                color: #2450ef;
                                text-decoration: none;
                                font-weight: 600;
                                transition: all 0.2s;
                                cursor: pointer;
                            " onmouseover="this.style.background='#f0f4ff'; this.style.borderColor='#2450ef';" onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0';">← Anterior</a>
                        @endif

                        @for($i = 1; $i <= $clientesConLogos->lastPage(); $i++)
                            @if($i == $clientesConLogos->currentPage())
                                <span style="
                                    padding: 0.6rem 1rem;
                                    border-radius: 8px;
                                    background: linear-gradient(135deg, #2450ef 0%, #1e40af 100%);
                                    color: white;
                                    font-weight: 600;
                                    min-width: 2.5rem;
                                    text-align: center;
                                    cursor: default;
                                ">{{ $i }}</span>
                            @else
                                <a href="{{ $clientesConLogos->url($i) }}" style="
                                    padding: 0.6rem 1rem;
                                    border-radius: 8px;
                                    background: white;
                                    border: 2px solid #e2e8f0;
                                    color: #475569;
                                    text-decoration: none;
                                    font-weight: 600;
                                    min-width: 2.5rem;
                                    text-align: center;
                                    transition: all 0.2s;
                                    cursor: pointer;
                                " onmouseover="this.style.background='#f0f4ff'; this.style.borderColor='#2450ef'; this.style.color='#2450ef';" onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0'; this.style.color='#475569';">{{ $i }}</a>
                            @endif
                        @endfor

                        @if($clientesConLogos->hasMorePages())
                            <a href="{{ $clientesConLogos->nextPageUrl() }}" style="
                                padding: 0.6rem 1rem;
                                border-radius: 8px;
                                background: white;
                                border: 2px solid #e2e8f0;
                                color: #2450ef;
                                text-decoration: none;
                                font-weight: 600;
                                transition: all 0.2s;
                                cursor: pointer;
                            " onmouseover="this.style.background='#f0f4ff'; this.style.borderColor='#2450ef';" onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0';">Siguiente →</a>
                        @else
                            <span style="
                                padding: 0.6rem 1rem;
                                border-radius: 8px;
                                background: #f1f5f9;
                                color: #94a3b8;
                                font-weight: 600;
                                cursor: not-allowed;
                            ">Siguiente →</span>
                        @endif
                    </nav>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Galería de Diseños -->
<div id="galeriaModal" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
" onclick="if(event.target === this) cerrarModal();">
    <div style="
        background: white;
        border-radius: 16px;
        width: 100%;
        max-width: 1200px;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
    ">
        <!-- Header del Modal -->
        <div style="
            background: linear-gradient(135deg, #2450ef 0%, #1e40af 100%);
            color: white;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 16px 16px 0 0;
            position: sticky;
            top: 0;
            z-index: 100;
        ">
            <div>
                <h2 style="margin: 0; font-size: 1.5rem; font-weight: 800;">
                    <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 0.5rem; font-size: 1.8rem;">image</span>
                    Galería de Diseños
                </h2>
            </div>
            <button onclick="cerrarModal()" style="
                background: rgba(255, 255, 255, 0.2);
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                padding: 0.5rem 1rem;
                border-radius: 4px;
                transition: all 0.2s;
            " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                ✕
            </button>
        </div>
        
        <!-- Contenido de Galería -->
        <div class="galeria-modal-content" style="
            padding: 2rem;
        "></div>
    </div>
</div>

<!-- Modal Visor de Imagen -->
<div id="visorImagenModal" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.95);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 1rem;
" onclick="if(event.target === this) cerrarVisor();">
    <div style="
        position: relative;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    ">
        <img id="imagenVisor" src="" alt="Imagen ampliada" style="
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        ">
        <button onclick="cerrarVisor()" style="
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: white;
            border: none;
            color: #1e293b;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.2s;
            font-weight: bold;
            z-index: 10001;
        " onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'">
            ✕
        </button>
    </div>
</div>

<!-- Modal Novedades -->
<div id="novedadesModal" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10001;
    padding: 1rem;
" onclick="if(event.target === this) cerrarNovedades();">
    <div style="
        background: white;
        border-radius: 16px;
        width: 100%;
        max-width: 600px;
        max-height: 80vh;
        overflow-y: auto;
        position: relative;
    ">
        <!-- Header del Modal -->
        <div style="
            background: linear-gradient(135deg, #2450ef 0%, #1e40af 100%);
            color: white;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 16px 16px 0 0;
            position: sticky;
            top: 0;
            z-index: 100;
        ">
            <div>
                <h2 style="margin: 0; font-size: 1.3rem; font-weight: 800;">
                    <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 0.5rem; font-size: 1.5rem;">notifications_active</span>
                    Novedades
                </h2>
            </div>
            <button onclick="cerrarNovedades()" style="
                background: rgba(255, 255, 255, 0.2);
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                padding: 0.5rem 1rem;
                border-radius: 4px;
                transition: all 0.2s;
            " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                ✕
            </button>
        </div>
        
        <!-- Contenido de Novedades -->
        <div class="novedades-modal-content" style="
            padding: 2rem;
        "></div>
    </div>
</div>

<style>
.material-symbols-rounded {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Vista de historial de logos cargada');
});

function abrirGaleriaDisenios(clienteId, clienteNombre) {
    console.log('Abriendo galería para cliente:', clienteNombre, 'ID:', clienteId);
    
    // Mostrar modal loading
    mostrarModalCargando();
    
    // Obtener diseños del cliente
    fetch(`{{ route('visualizador-logo.historial-logos.cliente', ['clienteId' => ':clienteId']) }}`.replace(':clienteId', clienteId))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarGaleriaDisenios(data);
            } else {
                alert('Error al cargar los diseños');
                cerrarModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los diseños');
            cerrarModal();
        });
}

function mostrarModalCargando() {
    const modal = document.getElementById('galeriaModal');
    const modalContent = document.querySelector('.galeria-modal-content');
    
    modal.style.display = 'flex';
    modalContent.innerHTML = `
        <div style="text-align: center; padding: 2rem; color: #64748b;">
            <div style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;">
                <span class="material-symbols-rounded" style="font-size: 3.5rem; animation: spin 1s linear infinite;">
                    progress_activity
                </span>
            </div>
            <p style="margin: 0; font-size: 1rem; font-weight: 500;">Cargando diseños...</p>
        </div>
    `;
}

function mostrarGaleriaDisenios(data) {
    const modal = document.getElementById('galeriaModal');
    const modalContent = document.querySelector('.galeria-modal-content');
    
    const clienteNombre = data.cliente_nombre || 'Cliente desconocido';
    const diseños = data.diseños || [];
    const paginacion = data.paginacion || {};
    const clienteId = data.cliente_id;
    
    if (paginacion.total === 0) {
        modalContent.innerHTML = `
            <div style="width: 100%; padding: 2rem; text-align: center; color: #64748b;">
                <div style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;">
                    <span class="material-symbols-rounded" style="font-size: 3.5rem;">image_not_supported</span>
                </div>
                <p style="margin: 0; font-size: 1rem; font-weight: 500;">No hay diseños para este cliente</p>
            </div>
        `;
        return;
    }
    
    // Crear sección de búsqueda y contador
    let headerHTML = `
        <div style="margin-bottom: 2rem; display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 250px;">
                <div style="position: relative;">
                    <span class="material-symbols-rounded" style="
                        position: absolute;
                        left: 1rem;
                        top: 50%;
                        transform: translateY(-50%);
                        color: #94a3b8;
                        pointer-events: none;
                        font-size: 1.25rem;
                    ">search</span>
                    <input
                        type="text"
                        id="searchDiseños"
                        placeholder="Buscar por recibo..."
                        style="
                            width: 100%;
                            max-width: 400px;
                            padding: 0.75rem 1rem 0.75rem 3rem;
                            border: 2px solid #e2e8f0;
                            border-radius: 999px;
                            font-size: 0.95rem;
                            font-weight: 600;
                            color: #1e293b;
                            background: white;
                            box-sizing: border-box;
                            transition: all 0.25s ease;
                            outline: none;
                            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
                        "
                        onfocus="this.style.borderColor='#2450ef'; this.style.boxShadow='0 0 0 4px rgba(36, 80, 239, 0.08), 0 4px 6px -1px rgb(0 0 0 / 0.1)';"
                        onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='0 1px 3px 0 rgb(0 0 0 / 0.1)';"
                        oninput="filtrarDiseños()"
                    >
                </div>
            </div>
            
            <div style="
                background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%);
                border: 1px solid #dbeafe;
                border-radius: 8px;
                padding: 0.75rem 1.25rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                white-space: nowrap;
            ">
                <span class="material-symbols-rounded" style="color: #2450ef; font-size: 1.25rem;"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-palette"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M12 21a9 9 0 0 1 0 -18c4.97 0 9 3.582 9 8c0 1.06 -.474 2.078 -1.318 2.828c-.844 .75 -1.989 1.172 -3.182 1.172h-2.5a2 2 0 0 0 -1 3.75a1.3 1.3 0 0 1 -1 2.25" /><path d="M7.5 10.5a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M11.5 7.5a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M15.5 10.5a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /></svg></span>
                <span style="font-weight: 700; color: #1e40af;">
                    ${paginacion.total} diseños
                </span>
            </div>
        </div>
    `;
    
    let galeriaHTML = headerHTML + `
        <div style="
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            width: 100%;
            margin-bottom: 2rem;
        " id="galeriaGridDiseños">
    `;
    
    diseños.forEach((diseno, index) => {
        const fechaConfirmacion = new Date(diseno.created_at).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        const numeroReciboBadge = `#${diseno.numero_recibo}-${diseno.tipo_recibo}`;
        const tieneNovedades = diseno.novedades && diseno.novedades.length > 0;
        
        galeriaHTML += `
            <div style="
                background: white;
                border-radius: 12px;
                overflow: hidden;
                border: 1px solid #e2e8f0;
                box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
                transition: all 0.2s ease;
                cursor: pointer;
                display: flex;
                flex-direction: column;
            "
            class="diseno-card"
            data-numero-recibo="${diseno.numero_recibo.toString().toLowerCase()}"
            data-tipo-recibo="${diseno.tipo_recibo.toLowerCase()}"
            onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 10px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)';"
            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)';">
                
                <!-- Imagen -->
                <div style="
                    width: 100%;
                    height: 180px;
                    background: #f8fafc;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    overflow: hidden;
                    position: relative;
                ">
                    <img src="${diseno.url}" alt="Diseño ${index + 1}" style="
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                        cursor: pointer;
                    "
                    ondblclick="abrirVisorImagen('${diseno.url}')"
                    title="Doble clic para ampliar"
                    >
                    <button onclick="abrirVisorImagen('${diseno.url}')" style="
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        width: 44px;
                        height: 44px;
                        border-radius: 50%;
                        background: white;
                        border: none;
                        color: #2450ef;
                        font-size: 20px;
                        cursor: pointer;
                        display: none;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                        transition: all 0.2s ease;
                    "
                    id="btnVisor${index}"
                    onmouseover="document.getElementById('btnVisor${index}').style.transform='scale(1.1) translate(-50%, -50%)'; document.getElementById('btnVisor${index}').style.display='flex';"
                    onmouseout="document.getElementById('btnVisor${index}').style.transform='scale(1) translate(-50%, -50%)'; document.getElementById('btnVisor${index}').style.display='none';">
                        <span class="material-symbols-rounded">zoom_in</span>
                    </button>
                </div>
                
                <!-- Info -->
                <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem; flex: 1;">
                    <!-- Número de Recibo -->
                    <div>
                        <span style="
                            display: inline-block;
                            background: linear-gradient(135deg, #2450ef 0%, #1e40af 100%);
                            color: white;
                            padding: 0.4rem 0.75rem;
                            border-radius: 6px;
                            font-size: 0.85rem;
                            font-weight: 700;
                        ">${numeroReciboBadge}</span>
                    </div>
                    
                    <!-- Prenda -->
                    <p style="margin: 0; font-size: 0.9rem; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        ${diseno.nombre_prenda}
                    </p>
                    
                    <!-- Fecha -->
                    <p style="margin: 0; font-size: 0.8rem; color: #64748b;">
                        <span class="material-symbols-rounded" style="font-size: 0.9rem; vertical-align: middle; margin-right: 0.25rem;">schedule</span>
                        ${fechaConfirmacion}
                    </p>
                    
                    <!-- Botones de acción -->
                    <div style="display: flex; gap: 0.5rem; margin-top: auto;">
                        ${tieneNovedades ? `
                            <button onclick="abrirModalNovedades(${JSON.stringify(diseno.novedades).replace(/"/g, '&quot;')})" style="
                                flex: 1;
                                padding: 0.5rem;
                                border-radius: 8px;
                                background: #f0f4ff;
                                border: 1px solid #dbeafe;
                                color: #2450ef;
                                font-size: 0.75rem;
                                font-weight: 700;
                                cursor: pointer;
                                transition: all 0.2s ease;
                            "
                            onmouseover="this.style.background='#dbeafe';"
                            onmouseout="this.style.background='#f0f4ff';">
                                Ver Novedades
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    galeriaHTML += '</div>';
    
    // Agregar controles de paginación
    if (paginacion.last_page > 1) {
        galeriaHTML += `
            <div style="display: flex; justify-content: center; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
                <nav style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: center;">
                    ${paginacion.current_page === 1 
                        ? `<span style="
                            padding: 0.6rem 1rem;
                            border-radius: 8px;
                            background: #f1f5f9;
                            color: #94a3b8;
                            font-weight: 600;
                            cursor: not-allowed;
                        ">← Anterior</span>`
                        : `<button onclick="cargarPaginaDiseños(${clienteId}, ${paginacion.current_page - 1})" style="
                            padding: 0.6rem 1rem;
                            border-radius: 8px;
                            background: white;
                            border: 2px solid #e2e8f0;
                            color: #2450ef;
                            text-decoration: none;
                            font-weight: 600;
                            transition: all 0.2s;
                            cursor: pointer;
                        " onmouseover="this.style.background='#f0f4ff'; this.style.borderColor='#2450ef';" onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0';">← Anterior</button>`
                    }
                    
                    ${(() => {
                        let html = '';
                        for (let i = 1; i <= paginacion.last_page; i++) {
                            if (i === paginacion.current_page) {
                                html += `<span style="
                                    padding: 0.6rem 1rem;
                                    border-radius: 8px;
                                    background: linear-gradient(135deg, #2450ef 0%, #1e40af 100%);
                                    color: white;
                                    font-weight: 600;
                                    min-width: 2.5rem;
                                    text-align: center;
                                    cursor: default;
                                ">${i}</span>`;
                            } else {
                                html += `<button onclick="cargarPaginaDiseños(${clienteId}, ${i})" style="
                                    padding: 0.6rem 1rem;
                                    border-radius: 8px;
                                    background: white;
                                    border: 2px solid #e2e8f0;
                                    color: #475569;
                                    text-decoration: none;
                                    font-weight: 600;
                                    min-width: 2.5rem;
                                    text-align: center;
                                    transition: all 0.2s;
                                    cursor: pointer;
                                " onmouseover="this.style.background='#f0f4ff'; this.style.borderColor='#2450ef'; this.style.color='#2450ef';" onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0'; this.style.color='#475569';">${i}</button>`;
                            }
                        }
                        return html;
                    })()}
                    
                    ${paginacion.current_page < paginacion.last_page
                        ? `<button onclick="cargarPaginaDiseños(${clienteId}, ${paginacion.current_page + 1})" style="
                            padding: 0.6rem 1rem;
                            border-radius: 8px;
                            background: white;
                            border: 2px solid #e2e8f0;
                            color: #2450ef;
                            text-decoration: none;
                            font-weight: 600;
                            transition: all 0.2s;
                            cursor: pointer;
                        " onmouseover="this.style.background='#f0f4ff'; this.style.borderColor='#2450ef';" onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0';">Siguiente →</button>`
                        : `<span style="
                            padding: 0.6rem 1rem;
                            border-radius: 8px;
                            background: #f1f5f9;
                            color: #94a3b8;
                            font-weight: 600;
                            cursor: not-allowed;
                        ">Siguiente →</span>`
                    }
                </nav>
            </div>
        `;
    }
    
    modalContent.innerHTML = galeriaHTML;
    
    // Agregar event listener a la búsqueda
    const searchInput = document.getElementById('searchDiseños');
    if (searchInput) {
        searchInput.focus();
    }
}

function cargarPaginaDiseños(clienteId, pagina) {
    // Mostrar modal loading
    mostrarModalCargando();
    
    // Obtener diseños de la página especificada
    fetch(`{{ route('visualizador-logo.historial-logos.cliente', ['clienteId' => ':clienteId']) }}`.replace(':clienteId', clienteId) + `?page=${pagina}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarGaleriaDisenios(data);
            } else {
                alert('Error al cargar los diseños');
                cerrarModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los diseños');
            cerrarModal();
        });
}

function abrirVisorImagen(url) {
    const visor = document.getElementById('visorImagenModal');
    const imagenVisor = document.getElementById('imagenVisor');
    
    imagenVisor.src = url;
    visor.style.display = 'flex';
}

function filtrarDiseños() {
    const searchInput = document.getElementById('searchDiseños');
    const searchTerm = searchInput.value.toLowerCase();
    const cards = document.querySelectorAll('.diseno-card');
    let visiblesCount = 0;
    
    cards.forEach(card => {
        const numeroRecibo = card.dataset.numeroRecibo;
        const tipoRecibo = card.dataset.tipoRecibo;
        
        if (numeroRecibo.includes(searchTerm) || tipoRecibo.includes(searchTerm)) {
            card.style.display = '';
            visiblesCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Mostrar mensaje si no hay resultados
    const grid = document.getElementById('galeriaGridDiseños');
    if (visiblesCount === 0 && searchTerm !== '') {
        let mensaje = grid.querySelector('.sin-resultados');
        if (!mensaje) {
            mensaje = document.createElement('div');
            mensaje.className = 'sin-resultados';
            mensaje.style.cssText = `
                grid-column: 1 / -1;
                padding: 2rem;
                text-align: center;
                color: #64748b;
            `;
            mensaje.innerHTML = `
                <div style="font-size: 2rem; color: #cbd5e1; margin-bottom: 1rem;">
                    <span class="material-symbols-rounded" style="font-size: 2.5rem;">search_off</span>
                </div>
                <p style="margin: 0; font-size: 0.95rem;">No se encontraron diseños para "${searchTerm}"</p>
            `;
            grid.appendChild(mensaje);
        }
    } else {
        const mensaje = grid.querySelector('.sin-resultados');
        if (mensaje) {
            mensaje.remove();
        }
    }
}

function abrirModalNovedades(novedades) {
    const modal = document.getElementById('novedadesModal');
    const contenido = document.querySelector('.novedades-modal-content');
    
    let html = '<div style="width: 100%;">';
    
    novedades.forEach((novedad, index) => {
        html += `
            <div style="
                background: #f8fafc;
                border-left: 4px solid #2450ef;
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 1rem;
            ">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                    <span style="
                        display: inline-block;
                        background: #2450ef;
                        color: white;
                        padding: 0.25rem 0.75rem;
                        border-radius: 4px;
                        font-size: 0.75rem;
                        font-weight: 700;
                        text-transform: uppercase;
                    ">${novedad.tipo_novedad || 'General'}</span>
                    <span style="font-size: 0.8rem; color: #94a3b8;">
                        ${novedad.created_at ? new Date(novedad.created_at).toLocaleDateString('es-ES', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        }) : '-'}
                    </span>
                </div>
                <p style="margin: 0; font-size: 0.95rem; color: #475569; line-height: 1.5;">
                    ${novedad.novedad}
                </p>
                ${novedad.usuario ? `
                    <p style="margin: 0.5rem 0 0; font-size: 0.8rem; color: #94a3b8;">
                        Por: <strong>${novedad.usuario.name || 'Usuario'}</strong>
                    </p>
                ` : ''}
            </div>
        `;
    });
    
    html += '</div>';
    
    contenido.innerHTML = html;
    modal.style.display = 'flex';
}

function cerrarVisor() {
    document.getElementById('visorImagenModal').style.display = 'none';
}

function cerrarModal() {
    document.getElementById('galeriaModal').style.display = 'none';
}

function cerrarNovedades() {
    document.getElementById('novedadesModal').style.display = 'none';
}

// Cerrar modal al hacer clic en el fondo
window.addEventListener('click', function(event) {
    const modal = document.getElementById('galeriaModal');
    if (event.target === modal) {
        cerrarModal();
    }
    
    const visor = document.getElementById('visorImagenModal');
    if (event.target === visor) {
        cerrarVisor();
    }
    
    const novedades = document.getElementById('novedadesModal');
    if (event.target === novedades) {
        cerrarNovedades();
    }
});

// Cerrar con tecla Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarModal();
        cerrarVisor();
        cerrarNovedades();
    }
});
</script>

<script src="{{ asset('js/visualizador-logo/historial-logos-search.js') }}"></script>

@endsection
