@extends('layouts.base')

@section('title', 'Préstamos de Taller')
@section('page-title', 'Préstamos de Taller')

@section('body')
<div style="max-width:1100px;margin:0 auto;padding:16px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <div>
            <h2 style="margin:0;">{{ $taller->name }}</h2>
            <p style="margin:4px 0 0;color:#64748b;">Recibos enviados al taller</p>
        </div>
        <a href="{{ route('talleres.index') }}" style="border:1px solid #cbd5e1;border-radius:8px;padding:8px 12px;text-decoration:none;color:#0f172a;">Volver</a>
    </div>

    <div style="display:flex;gap:8px;margin-bottom:12px;">
        <a href="{{ route('talleres.prestamos', ['id' => $taller->id, 'tab' => 'insumos']) }}"
           style="padding:8px 12px;border-radius:8px;text-decoration:none;border:1px solid #cbd5e1;{{ $tab === 'insumos' ? 'background:#0f172a;color:#fff;border-color:#0f172a;' : 'background:#fff;color:#0f172a;' }}">
            Recibos Préstamo Insumos
        </a>
        <a href="{{ route('talleres.prestamos', ['id' => $taller->id, 'tab' => 'contramuestra']) }}"
           style="padding:8px 12px;border-radius:8px;text-decoration:none;border:1px solid #cbd5e1;{{ $tab === 'contramuestra' ? 'background:#0f172a;color:#fff;border-color:#0f172a;' : 'background:#fff;color:#0f172a;' }}">
            Préstamo Contramuestra
        </a>
    </div>

    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <table style="width:100%;border-collapse:collapse;">
            <thead style="background:#f8fafc;">
                <tr>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">N° Recibo</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Costurero</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Fecha salida</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Fecha entrada</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Estado</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Novedad</th>
                    <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse($registros as $r)
                <tr>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;">#{{ $r->numero_orden }}</td>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;">{{ $r->nombre_costurero }}</td>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;">{{ \Carbon\Carbon::parse($r->fecha_salida)->format('d/m/Y') }}</td>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;">{{ $r->fecha_entrada ? \Carbon\Carbon::parse($r->fecha_entrada)->format('d/m/Y H:i') : '-' }}</td>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;">
                        @if($r->anulado)
                            ANULADO
                        @elseif($r->confirmado_entrada)
                            ENTRADA CONFIRMADA
                        @else
                            PENDIENTE
                        @endif
                    </td>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;">{{ $r->novedades ?: '-' }}</td>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;">
                        <button type="button"
                                class="btn-ver-prestamo"
                                data-tipo="{{ $tab }}"
                                data-id="{{ $r->id }}"
                                style="display:inline-block;border:1px solid #cbd5e1;border-radius:8px;padding:6px 10px;background:#fff;color:#0f172a;cursor:pointer;">
                            Ver
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding:16px;text-align:center;color:#64748b;">Sin registros para este servicio.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:10px;">
        {{ $registros->links('vendor.pagination.simple-clean') }}
    </div>
</div>

<div id="modal-overlay"
     style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;"
     onclick="closeModalOverlay()"></div>
<div id="order-detail-modal-wrapper"
     style="width: 90%; max-width: 672px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>
<style>
    #order-detail-modal-wrapper #order-pedido {
        transform: translateY(20px) !important;
    }
    #order-detail-modal-wrapper #btn-galeria {
        display: none !important;
    }
</style>

<script src="{{ asset('js/ordersjs/order-detail-modal-manager.js') }}"></script>
<script>
if (typeof window.printReceiptModal !== 'function') {
    window.printReceiptModal = function () {
        const wrapper = document.getElementById('order-detail-modal-wrapper');
        const card = wrapper ? wrapper.querySelector('.order-detail-card') : null;
        if (!card) {
            window.print();
            return;
        }

        const printWindow = window.open('', '_blank', 'width=900,height=1200');
        if (!printWindow) {
            window.print();
            return;
        }

        const cardHtml = card.outerHTML;
        printWindow.document.write(`
            <!doctype html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Imprimir recibo</title>
                <link rel="stylesheet" href="{{ asset('css/order-detail-modal.css') }}">
                <link rel="stylesheet" href="{{ asset('css/print-order-detail-modal.css') }}">
                <style>
                    body { margin: 0; padding: 18px; background: #fff; }
                    .order-detail-card {
                        margin: 0 auto !important;
                        box-shadow: none !important;
                        transform: none !important;
                        zoom: 1 !important;
                        height: auto !important;
                        min-height: 0 !important;
                        position: relative !important;
                        padding-bottom: 120px !important;
                    }
                    #order-descripcion {
                        position: relative !important;
                        top: auto !important;
                        left: auto !important;
                        right: auto !important;
                        bottom: auto !important;
                        overflow: visible !important;
                        margin-top: 132px !important;
                        margin-bottom: 130px !important;
                        padding-right: 0 !important;
                    }
                    #prestamo-firmas-table {
                        position: absolute !important;
                        bottom: 0 !important;
                        left: 0 !important;
                        right: 0 !important;
                        margin-top: 0 !important;
                    }
                    #order-pedido {
                        transform: translateY(20px) !important;
                    }
                    #floating-buttons-container { display: none !important; }
                </style>
            </head>
            <body>
                ${cardHtml}
                <script>
                    window.addEventListener('load', function () {
                        setTimeout(function () { window.print(); }, 80);
                    });
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    };
}

function closeModalOverlay() {
    document.getElementById('modal-overlay').style.display = 'none';
    document.getElementById('order-detail-modal-wrapper').style.display = 'none';
    const btnCerrar = document.getElementById('btn-cerrar-modal-dinamico');
    if (btnCerrar) {
        btnCerrar.remove();
    }
}

document.querySelectorAll('.btn-ver-prestamo').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const tipo = btn.dataset.tipo;
        const id = btn.dataset.id;
        const overlay = document.getElementById('modal-overlay');
        const modal = document.getElementById('order-detail-modal-wrapper');
        overlay.style.display = 'block';
        modal.style.display = 'block';
        let btnCerrar = document.getElementById('btn-cerrar-modal-dinamico');
        if (!btnCerrar) {
            btnCerrar = document.createElement('button');
            btnCerrar.id = 'btn-cerrar-modal-dinamico';
            btnCerrar.type = 'button';
            btnCerrar.title = 'Cerrar';
            btnCerrar.style.position = 'fixed';
            btnCerrar.style.right = '10px';
            btnCerrar.style.top = '10px';
            btnCerrar.style.width = '40px';
            btnCerrar.style.height = '40px';
            btnCerrar.style.borderRadius = '50%';
            btnCerrar.style.background = 'rgba(255, 255, 255, 0.95)';
            btnCerrar.style.border = 'none';
            btnCerrar.style.color = 'rgb(51, 51, 51)';
            btnCerrar.style.cursor = 'pointer';
            btnCerrar.style.display = 'flex';
            btnCerrar.style.alignItems = 'center';
            btnCerrar.style.justifyContent = 'center';
            btnCerrar.style.fontSize = '24px';
            btnCerrar.style.transition = '0.3s';
            btnCerrar.style.boxShadow = '0px 2px 8px rgba(0, 0, 0, 0.2)';
            btnCerrar.style.zIndex = '10001';
            btnCerrar.style.fontWeight = 'bold';
            btnCerrar.innerHTML = '<i class="fas fa-times"></i>';
            btnCerrar.addEventListener('click', closeModalOverlay);
            document.body.appendChild(btnCerrar);
        }

        const descripcionEl = document.getElementById('descripcion-text');
        if (descripcionEl) {
            descripcionEl.innerHTML = 'Cargando...';
        }

        const res = await fetch(`/talleres/api/prestamos/${tipo}/${id}/detalle`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!res.ok || !data.success) {
            if (descripcionEl) {
                descripcionEl.innerHTML = `<p style="color:#b91c1c;">${data.message || 'No se pudo cargar el detalle.'}</p>`;
            }
            return;
        }

        const r = data.recibo;
        const receiptTitleEl = document.getElementById('receipt-title');
        const pedidoNumberEl = document.getElementById('order-pedido');
        const dayBox = modal.querySelector('.day-box');
        const monthBox = modal.querySelector('.month-box');
        const yearBox = modal.querySelector('.year-box');
        const asesoraEl = document.getElementById('order-asesora');
        const formaPagoEl = document.getElementById('order-forma-pago');
        const clienteEl = document.getElementById('order-cliente');

        if (asesoraEl) asesoraEl.style.display = 'none';
        if (formaPagoEl) formaPagoEl.style.display = 'none';
        if (clienteEl) clienteEl.style.display = 'none';

        if (receiptTitleEl) {
            receiptTitleEl.innerHTML = tipo === 'insumos'
                ? 'RECIBO PRESTAMO<br>DE INSUMOS'
                : 'RECIBO PRESTAMO<br>CONTRAMUESTRA';
        }
        if (pedidoNumberEl) {
            pedidoNumberEl.textContent = `#${r.numero_orden || ''}`;
        }

        const fecha = r.fecha ? String(r.fecha).split('-') : ['--','--','----'];
        const dia = fecha[2] || '--';
        const mes = fecha[1] || '--';
        const ano = fecha[0] || '----';
        if (dayBox) dayBox.textContent = dia;
        if (monthBox) monthBox.textContent = mes;
        if (yearBox) yearBox.textContent = ano;

        let descripcionHtml = '';
        if (tipo === 'insumos') {
            descripcionHtml += `<div style="margin-top: 4px; font-size: 12px; color: #374151;"><strong>ENCARGADO:</strong> ${r.encargado || '-'}</div>`;
            descripcionHtml += `<strong style="font-size:13.4px;">COSTURERO - <span style="font-weight:700;">${r.nombre_costurero || '-'}</span></strong>`;
            descripcionHtml += `<div style="margin-top:8px;">`;
            descripcionHtml += `<div style="display:flex;gap:1rem;margin-bottom:6px;font-weight:700;font-size:11px;color:#374151;"><div style="flex:1;">DESCRIPCIÓN</div><div style="width:80px;text-align:right;">CANTIDAD</div></div>`;
            (data.items || []).forEach(it => {
                descripcionHtml += `<div style="display:flex;gap:1rem;margin-bottom:4px;font-size:11px;border:1px solid #d1d5db;padding:6px 8px;border-radius:4px;"><div style="flex:1;">${it.descripcion || ''}</div><div style="width:80px;text-align:right;">${it.cantidad ?? ''}</div></div>`;
            });
            if (!data.items || data.items.length === 0) {
                descripcionHtml += `<span style="display:block;color:#64748b;">Sin items registrados.</span>`;
            }
            descripcionHtml += `</div>`;
        } else {
            descripcionHtml += `<div style="margin-top: 4px; font-size: 12px; color: #374151;"><strong>ENCARGADO:</strong> ${r.encargado || '-'}</div>`;
            descripcionHtml += `<strong style="font-size:13.4px;">COSTURERO - <span style="font-weight:700;">${r.nombre_costurero || '-'}</span></strong>`;
            descripcionHtml += `<div style="margin-top:8px;"><strong style="font-size:13.4px;">DESCRIPCIÓN</strong><br><span style="display:block;margin-top:8px;color:#212529;font-weight:600;white-space:pre-wrap;">${r.descripcion || '-'}</span></div>`;
        }

        let novedadHtml = '';
        if (r.novedades) {
            novedadHtml = `<div style="margin-top:10px;color:#dc2626;"><strong>NOVEDAD:</strong><br>${r.novedades}</div>`;
        }

        const firmaMensajeroRaw = r.firma_mensajero || '';
        const firmaCostureroRaw = r.firma_costurero || '';
        const normalizarFirma = (firma) => {
            if (!firma) return '';
            const firmaStr = String(firma);
            if (firmaStr.startsWith('http://') || firmaStr.startsWith('https://') || firmaStr.startsWith('data:image')) {
                return firmaStr;
            }
            if (firmaStr.startsWith('/storage/')) {
                return firmaStr;
            }
            if (firmaStr.startsWith('storage/')) {
                return `/${firmaStr}`;
            }
            if (firmaStr.startsWith('/')) {
                return firmaStr;
            }
            return `/storage/${firmaStr}`;
        };
        const firmaMensajero = normalizarFirma(firmaMensajeroRaw);
        const firmaCosturero = normalizarFirma(firmaCostureroRaw);
        const firmasHtml = `
            <table id="prestamo-firmas-table" style="width: 100%; border-collapse: collapse; border: 1px solid #d1d5db; position: absolute; bottom: 0; left: 0; right: 0;">
                <tbody>
                    <tr>
                        <td style="flex: 1; border: 1px solid #d1d5db; padding: 12px 8px; text-align: center; width: 50%;">
                            <div style="font-weight: 700; font-size: 10px; color: #374151; margin-bottom: 30px;">FIRMA MENSAJERO</div>
                            <div id="firma-mensajero-wrapper" style="min-height: 40px; display: flex; align-items: center; justify-content: center;">
                                <div id="firma-mensajero-placeholder" style="height:1px;"></div>
                                <img id="firma-mensajero-img" src="${firmaMensajero}" alt="Firma mensajero" style="${firmaMensajero ? 'display:block;' : 'display:none;'} max-width: 100%; max-height: 70px; object-fit: contain;">
                            </div>
                        </td>
                        <td style="flex: 1; border: 1px solid #d1d5db; padding: 12px 8px; text-align: center; width: 50%;">
                            <div style="font-weight: 700; font-size: 10px; color: #374151; margin-bottom: 30px;">FIRMA COSTURERO</div>
                            <div id="firma-costurero-wrapper" style="min-height: 40px; display: flex; align-items: center; justify-content: center;">
                                <div id="firma-costurero-placeholder" style="height:1px;"></div>
                                <img id="firma-costurero-img" src="${firmaCosturero}" alt="Firma costurero" style="${firmaCosturero ? 'display:block;' : 'display:none;'} max-width: 100%; max-height: 70px; object-fit: contain;">
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>`;

        if (descripcionEl) {
            descripcionEl.innerHTML = `${descripcionHtml}${novedadHtml}`;
        }

        const cardEl = modal.querySelector('.order-detail-card');
        if (cardEl) {
            cardEl.style.position = 'relative';
            cardEl.style.paddingBottom = '120px';
            const oldFirmas = cardEl.querySelector('#prestamo-firmas-table');
            if (oldFirmas) {
                oldFirmas.remove();
            }
            cardEl.insertAdjacentHTML('beforeend', firmasHtml);
        }
    });
});
</script>
@endsection
