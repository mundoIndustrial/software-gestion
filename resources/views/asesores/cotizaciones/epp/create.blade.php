@extends('layouts.asesores')

@section('title', 'Cotización EPP')
@section('page-title', 'Cotizaciones')

@section('extra_styles')
<link rel="stylesheet" href="{{ asset('css/modulos/epp-modal.css') }}">
<style>
    .top-nav {
        display: none !important;
    }

    .form-section {
        background: #ffffff;
        border-radius: 10px;
        padding: 16px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        margin-bottom: 1rem;
    }

    .form-section h2 {
        margin: 0 0 1rem 0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1rem;
        font-weight: 700;
        color: #111827;
    }

    .form-section h2 > span {
        width: 26px;
        height: 26px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        font-weight: 800;
        color: white;
        background: #1d4ed8;
    }

    .items-pedido-box {
        border-left: 4px solid #1d4ed8;
        border-radius: 10px;
        padding: 12px;
        background: #ffffff;
    }

    .items-pedido-empty {
        border: 2px dashed #e5e7eb;
        border-radius: 10px;
        padding: 22px 16px;
        text-align: center;
        color: #6b7280;
        font-weight: 500;
        background: #fafafa;
    }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <script>
        window.__EPP_COTIZACION_MODE__ = true;
    </script>
    <div style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); border-radius: 12px; padding: 1.25rem 1.75rem; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 1.5rem; align-items: start;">
            <div style="display: flex; align-items: center; gap: 0.75rem; grid-column: 1 / -1;">
                <span class="material-symbols-rounded" style="font-size: 1.75rem; color: white;">engineering</span>
                <div>
                    <h2 style="margin: 0; color: white; font-size: 1.25rem; font-weight: 700;">Cotización EPP</h2>
                    <p style="margin: 0.2rem 0 0 0; color: rgba(255,255,255,0.85); font-size: 0.8rem;">Completa los datos de la cotización</p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; grid-column: 1 / -1;">
                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Cliente</label>
                    <input type="text" id="header-cliente" placeholder="Nombre del cliente" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>

                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Asesor</label>
                    <input type="text" id="header-asesor" value="{{ auth()->user()->name }}" readonly style="width: 100%; background: rgba(255,255,255,0.9); border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; cursor: not-allowed;">
                </div>

                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Fecha</label>
                    <input type="date" id="header-fecha" value="{{ date('Y-m-d') }}" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>

                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Tipo para Cotizar</label>
                    <select id="header-tipo-venta" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s; cursor: pointer;">
                        <option value="">-- SELECCIONA --</option>
                        <option value="M">M</option>
                        <option value="D">D</option>
                        <option value="X">X</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="form-container">
        <form id="cotizacionEppForm">
            @csrf

            <div class="form-section">
                <div class="items-pedido-box">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 1rem;">
                        <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                            <span>1</span>
                            Ítems del Pedido
                        </h2>

                        <button type="button" onclick="abrirModalAgregarEPP()" style="padding: 0.55rem 0.9rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); border: 2px solid #003d7a; border-radius: 8px; cursor: pointer; font-weight: 700; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; white-space: nowrap;" onmouseover="this.style.background='linear-gradient(135deg, #0052a3 0%, #003d7a 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 12px rgba(0, 102, 204, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0066cc 0%, #0052a3 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                            <span class="material-symbols-rounded" style="font-size: 18px;">add_circle</span>
                            Agregar
                        </button>
                    </div>

                    <div id="lista-items-pedido" style="display: flex; flex-direction: column; gap: 0.75rem;"></div>

                    <div id="prendas-container-editable" style="margin-top: 1.25rem;">
                        <div class="items-pedido-empty empty-state">
                            Agrega ítems al pedido
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2 style="margin: 0 0 1rem 0;">
                    <span>2</span>
                    Observaciones generales
                </h2>

                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
                    <div style="grid-column: span 1;">
                        <label for="valor-iva-epp" style="display: block; color: #111827; font-size: 0.75rem; font-weight: 800; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.4px;">IVA</label>
                        <input
                            type="number"
                            id="valor-iva-epp"
                            min="0"
                            step="1"
                            placeholder="Esperando para digitar el valor"
                            style="width: 100%; background: white; border: 2px solid #e5e7eb; padding: 0.6rem 0.75rem; border-radius: 8px; font-weight: 700; color: #111827; font-size: 0.9rem; transition: all 0.2s;"
                        >
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <div style="display: flex; gap: 0.5rem; flex: 1; justify-content: flex-end;">
                    <button id="btnGuardarBorradorEpp" type="button" class="btn btn-primary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); border: 2px solid #3d8b40; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='linear-gradient(135deg, #45a049 0%, #3d8b40 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(76, 175, 80, 0.2)';" onmouseout="this.style.background='linear-gradient(135deg, #4CAF50 0%, #45a049 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <i class="fas fa-save" style="font-size: 0.9rem;"></i> Guardar Borrador
                    </button>
                    <button id="btnEnviarCotizacionEpp" type="button" class="btn btn-primary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); border: 2px solid #003d7a; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='linear-gradient(135deg, #0052a3 0%, #003d7a 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 12px rgba(0, 102, 204, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0066cc 0%, #0052a3 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <i class="fas fa-paper-plane" style="font-size: 0.9rem;"></i> Enviar
                    </button>
                </div>
            </div>
        </form>
    </div>

    @include('asesores.cotizaciones.epp.components.modal-agregar-epp')
</div>
@endsection

@push('scripts')
<script defer src="{{ asset('js/utilidades/dom-utils.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/services/epp/EppHttpService.js') }}"></script>

<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-api-service.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-state-manager.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-modal-manager.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-item-manager.js') }}?v={{ time() }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-imagen-manager.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-service.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-notification-service.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-creation-service.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-form-manager.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/services/epp-menu-handlers.js') }}"></script>

<script defer src="{{ asset('js/modulos/crear-pedido/epp/templates/epp-modal-template.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/interfaces/epp-modal-interface.js') }}"></script>
<script defer src="{{ asset('js/modulos/crear-pedido/epp/epp-init.js') }}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const emptySelector = '#prendas-container-editable .empty-state';
        const listSelector = '#lista-items-pedido';

        async function enviarCotizacionEpp(accion) {
            const btnEnviar = document.getElementById('btnEnviarCotizacionEpp');
            const btnBorrador = document.getElementById('btnGuardarBorradorEpp');
            const cliente = document.getElementById('header-cliente')?.value?.trim();
            const tipoVenta = document.getElementById('header-tipo-venta')?.value?.trim();
            const ivaRaw = document.getElementById('valor-iva-epp')?.value;

            async function convertirImagenAFile(img, fallbackName = 'epp_imagen.webp') {
                try {
                    if (!img) return null;

                    if (img instanceof File) {
                        return img;
                    }

                    const src = (typeof img === 'string')
                        ? img
                        : (img?.previewUrl || img?.base64 || img?.url || img?.ruta_web || img?.ruta_webp || img?.ruta_original || null);

                    if (!src || typeof src !== 'string') return null;

                    // DataURL
                    if (src.startsWith('data:')) {
                        const res = await fetch(src);
                        const blob = await res.blob();
                        return new File([blob], fallbackName, { type: blob.type || 'image/webp' });
                    }

                    // Blob URL o URL normal (misma origen o accesible)
                    if (src.startsWith('blob:') || src.startsWith('http') || src.startsWith('/')) {
                        const res = await fetch(src);
                        const blob = await res.blob();
                        const name = (img?.nombre_archivo || img?.name || fallbackName);
                        return new File([blob], name, { type: blob.type || 'image/webp' });
                    }

                    return null;
                } catch (e) {
                    return null;
                }
            }

            const itemsPedido = Array.isArray(window.itemsPedido) ? window.itemsPedido : [];
            const epps = itemsPedido.filter(i => (i?.tipo || '').toLowerCase() === 'epp');

            if (!cliente) {
                if (window.Swal) {
                    Swal.fire({ icon: 'warning', title: 'Cliente requerido', text: 'Por favor ingresa el nombre del cliente' });
                }
                return;
            }

            if (!tipoVenta) {
                if (window.Swal) {
                    Swal.fire({ icon: 'warning', title: 'Tipo requerido', text: 'Por favor selecciona el tipo para cotizar (M/D/X)' });
                }
                return;
            }

            if (epps.length === 0) {
                if (window.Swal) {
                    Swal.fire({ icon: 'warning', title: 'Sin ítems', text: 'Agrega al menos un EPP a la cotización' });
                }
                return;
            }

            const formData = new FormData();
            formData.append('_token', document.querySelector('input[name="_token"]')?.value || '');
            formData.append('accion', accion);
            formData.append('cliente', cliente);
            formData.append('tipo_venta', tipoVenta);

            const ivaValor = (ivaRaw !== undefined && ivaRaw !== null && String(ivaRaw).trim() !== '' && !isNaN(Number(ivaRaw)))
                ? Number(ivaRaw)
                : null;
            const observacionesGenerales = {};
            if (ivaValor !== null) {
                observacionesGenerales.valor_iva = ivaValor;
            }
            formData.append('observaciones_generales', JSON.stringify(observacionesGenerales));

            // Items (sin archivos, esos van aparte)
            const itemsPayload = epps.map((epp) => ({
                nombre: epp.nombre_epp || epp.nombre_completo || epp.nombre || 'Sin nombre',
                cantidad: epp.cantidad || 1,
                valor_unitario: (epp.valor_unitario !== undefined && epp.valor_unitario !== null && String(epp.valor_unitario).trim() !== '')
                    ? Number(epp.valor_unitario)
                    : null,
                total: (epp.total !== undefined && epp.total !== null && String(epp.total).trim() !== '')
                    ? Number(epp.total)
                    : null,
                observaciones: epp.observaciones || null,
            }));
            formData.append('items', JSON.stringify(itemsPayload));

            // Archivos: items[i][imagenes][]
            for (let idx = 0; idx < epps.length; idx++) {
                const epp = epps[idx];
                const imagenes = Array.isArray(epp.imagenes) ? epp.imagenes : [];
                for (let j = 0; j < imagenes.length; j++) {
                    const file = await convertirImagenAFile(imagenes[j], `epp_${idx + 1}_${j + 1}.webp`);
                    if (file) {
                        formData.append(`items[${idx}][imagenes][]`, file, file.name);
                    }
                }
            }

            const url = `{{ url('/asesores/cotizaciones-epp') }}`;
            try {
                if (btnEnviar) btnEnviar.disabled = true;
                if (btnBorrador) btnBorrador.disabled = true;

                const res = await fetch(url, {
                    method: 'POST',
                    body: formData,
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    const msg = data.message || 'Error guardando cotización EPP';
                    if (window.Swal) {
                        Swal.fire({ icon: 'error', title: 'Error', text: msg });
                    }
                    return;
                }

                const cotizacionId = data.cotizacionId;
                const redirectUrl = data.redirect || `{{ url('/asesores/cotizaciones') }}?tab=${accion === 'borrador' ? 'borradores' : 'cotizaciones'}&highlight=${cotizacionId}`;

                if (window.Swal) {
                    const title = accion === 'borrador' ? 'Borrador guardado' : 'Cotización enviada';
                    const text = data.message || (accion === 'borrador'
                        ? 'La cotización EPP fue guardada como borrador'
                        : 'La cotización EPP fue enviada correctamente');

                    const result = await Swal.fire({
                        icon: 'success',
                        title,
                        text,
                        confirmButtonText: 'OK',
                    });

                    if (result.isConfirmed) {
                        window.location.href = redirectUrl;
                    }
                } else {
                    if (confirm(data.message || 'Proceso exitoso')) {
                        window.location.href = redirectUrl;
                    }
                }
            } finally {
                if (btnEnviar) btnEnviar.disabled = false;
                if (btnBorrador) btnBorrador.disabled = false;
            }
        }

        function syncEmptyState() {
            const list = document.querySelector(listSelector);
            const empty = document.querySelector(emptySelector);
            if (!list || !empty) return;

            const hasItems = list.querySelectorAll('.item-epp, .item-epp-card').length > 0;
            empty.style.display = hasItems ? 'none' : 'flex';
        }

        if (typeof window.finalizarAgregarEPP === 'function' && !window.__eppCotizacionFinalizarWrapped) {
            const original = window.finalizarAgregarEPP;
            window.finalizarAgregarEPP = async function (...args) {
                const result = await original.apply(this, args);
                syncEmptyState();
                return result;
            };
            window.__eppCotizacionFinalizarWrapped = true;
        }

        const list = document.querySelector(listSelector);
        if (list && typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver(syncEmptyState);
            observer.observe(list, { childList: true, subtree: true });
        }

        const btnEnviar = document.getElementById('btnEnviarCotizacionEpp');
        if (btnEnviar) {
            btnEnviar.addEventListener('click', function () {
                enviarCotizacionEpp('enviar');
            });
        }

        const btnBorrador = document.getElementById('btnGuardarBorradorEpp');
        if (btnBorrador) {
            btnBorrador.addEventListener('click', function () {
                enviarCotizacionEpp('borrador');
            });
        }

        syncEmptyState();
    });
</script>
@endpush
