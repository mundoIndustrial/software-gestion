/**
 * Manejador de modal para visualizar detalles de préstamos
 * Reutilizable en múltiples vistas (prestamos.blade.php, prestamos-global.blade.php)
 */

function closeModalOverlay() {
    document.getElementById('modal-overlay').style.display = 'none';
    document.getElementById('order-detail-modal-wrapper').style.display = 'none';
    const btnCerrar = document.getElementById('btn-cerrar-modal-dinamico');
    if (btnCerrar) {
        btnCerrar.remove();
    }
}

function attachPrestamoBtnListeners() {
    document.querySelectorAll('.btn-ver-prestamo').forEach((btn) => {
        btn.onclick = async function() {
            const tipo = this.dataset.tipo;
            const id = this.dataset.id;
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
            const formatCantidad = (value) => {
                if (value === null || value === undefined || value === '') return '';
                const num = Number(value);
                if (Number.isNaN(num)) return value;
                return Number.isInteger(num) ? String(num) : num.toString();
            };

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
                    descripcionHtml += `<div style="display:flex;gap:1rem;margin-bottom:4px;font-size:11px;border:1px solid #d1d5db;padding:6px 8px;border-radius:4px;"><div style="flex:1;">${it.descripcion || ''}</div><div style="width:80px;text-align:right;">${formatCantidad(it.cantidad)}</div></div>`;
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
            if (r.novedades && r.novedades.toLowerCase() !== 'no aplica') {
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
                descripcionEl.innerHTML = `${descripcionHtml}${novedadHtml}${firmasHtml}`;
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
        };
    });
}

document.addEventListener('DOMContentLoaded', attachPrestamoBtnListeners);

// Re-attach cuando haya cambios en la tabla (búsqueda, paginación)
const prestamosTableBody = document.getElementById('prestamosTableBody');
if (prestamosTableBody) {
    const observer = new MutationObserver(attachPrestamoBtnListeners);
    observer.observe(prestamosTableBody, { childList: true });
}
