import {
    cargarTalleresDisponibles as cargarTalleresDisponiblesHelper,
    cargarTalleresParaDistribucion as cargarTalleresParaDistribucionHelper,
} from './taller-loaders';

function generarEstadoCargaTaller(mensaje) {
    return `
        <div style="display: flex; align-items: center; justify-content: center; gap: 0.875rem; padding: 2rem; color: #6b7280; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px;">
            <span class="material-symbols-rounded" style="font-size: 2rem; color: #f59e0b;">hourglass_empty</span>
            <div style="text-align: left;">
                <p style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #374151;">${mensaje}</p>
                <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #6b7280;">Puede tardar unos segundos mientras preparamos la vista.</p>
            </div>
        </div>
    `;
}

export function mostrarEstadoCargaDistribucionTaller(mensaje = 'Cargando tallas disponibles...') {
    const interfazDiv = document.getElementById('interfazDistribucionTaller');
    if (!interfazDiv) return;

    interfazDiv.innerHTML = generarEstadoCargaTaller(mensaje);
}

export function ocultarEstadoCargaDistribucionTaller() {
    const interfazDiv = document.getElementById('interfazDistribucionTaller');
    if (!interfazDiv) return;

    interfazDiv.dataset.cargando = 'false';
}

export function mostrarContenidoTaller() {
    const contenidoDiv = document.getElementById('contenidoAsignacion');

    contenidoDiv.innerHTML = `
        <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
            <button type="button" id="btnTallerUnico" onclick="seleccionarTipoTaller('unico')" style="padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s; text-align: left;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; background: #8b5cf6; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="material-symbols-rounded" style="color: white; font-size: 1.25rem;">person</span>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <h5 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #1e293b; line-height: 1.3;">Un solo taller</h5>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #64748b; line-height: 1.3;">Todas las unidades a un unico taller externo</p>
                    </div>
                </div>
            </button>

            <button type="button" id="btnTallerMultiple" onclick="seleccionarTipoTaller('multiple')" style="padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white; cursor: pointer; transition: all 0.2s; text-align: left;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; background: #ec4899; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="material-symbols-rounded" style="color: white; font-size: 1.25rem;">groups</span>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <h5 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #1e293b; line-height: 1.3;">Multiples talleres</h5>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #64748b; line-height: 1.3;">Repartir entre diversos talleres externos</p>
                    </div>
                </div>
            </button>
        </div>
    `;
}

export function mostrarContenidoTallerUnico() {
    const contenidoDiv = document.getElementById('contenidoAsignacion');

    contenidoDiv.innerHTML = `
        <div>
            <h4 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 600; color: #1e293b;">Seleccionar Taller</h4>
            <div style="position: relative; margin-bottom: 1rem;">
                <div style="position: relative;">
                    <span class="material-symbols-rounded" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 1.25rem; pointer-events: none;">search</span>
                    <input type="text" id="tallerUnicoSelector" list="listaTalleresUnicos"
                        placeholder="Buscar y agregar..."
                        style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 2.5rem; border: 2px solid #d1d5db; border-radius: 8px; background: white; font-size: 0.875rem; transition: all 0.2s; outline: none; box-sizing: border-box;"
                        onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)';"
                        onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
                    >
                    <datalist id="listaTalleresUnicos"></datalist>
                </div>
            </div>
            <div style="padding: 0.75rem; background: #f0f9ff; border-radius: 8px; border: 1px solid #bfdbfe;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span class="material-symbols-rounded" style="color: #1e40af; font-size: 1rem;">info</span>
                    <div style="flex: 1; min-width: 0;">
                        <p style="margin: 0; font-size: 0.75rem; font-weight: 600; color: #1e40af;">Taller seleccionado:</p>
                        <p id="textoEstadoTallerUnico" style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #1e40af;">Ninguno</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    cargarTalleresDisponiblesHelper();

    setTimeout(() => {
        const input = document.getElementById('tallerUnicoSelector');
        const textoEstado = document.getElementById('textoEstadoTallerUnico');

        const actualizarEstado = () => {
            const val = input?.value.trim();
            if (val) {
                if (textoEstado) textoEstado.textContent = val;
            } else if (textoEstado) {
                textoEstado.textContent = 'Ninguno';
            }
        };

        if (input) {
            input.oninput = actualizarEstado;
            input.onchange = actualizarEstado;
        }
    }, 100);
}

export function mostrarContenidoTallerMultiple() {
    const contenidoDiv = document.getElementById('contenidoAsignacion');
    const esEdicion = Boolean(window.datosModalCostura?.esEdicion);
    const parcialesEdicion = window.__datosParcialesEdicion || [];

    const wizardHeader = `
        <div style="display: grid; gap: 0.75rem; margin-bottom: 1rem;">
        </div>
    `;

    if (esEdicion) {
        contenidoDiv.innerHTML = `
            <div>
                ${wizardHeader}
                <div style="margin-bottom: 1rem;">
                    <h4 style="margin: 0; font-size: 1rem; font-weight: 600; color: #1e293b;">Tallas Disponibles para Asignar</h4>
                    <p style="margin: 0.35rem 0 0; font-size: 0.85rem; color: #64748b;">Selecciona una talla y luego el taller activo.</p>
                </div>
                <div id="interfazDistribucionTaller" style="margin-top: 1rem;">
                    ${generarEstadoCargaTaller('Cargando tallas disponibles...')}
                </div>
            </div>
        `;

        cargarTalleresParaDistribucionHelper().then(() => {
            window.asignacionesPorTaller = window.construirMapaAsignacionesTallerDesdeParciales
                ? window.construirMapaAsignacionesTallerDesdeParciales(parcialesEdicion, window.talleresDisponibles || [])
                : {};

            if (typeof window.cargarInterfazDistribucionTallerMultiple === 'function') {
                window.cargarInterfazDistribucionTallerMultiple([]);
            }
        });

        return;
    }

    contenidoDiv.innerHTML = `
        <div>
            ${wizardHeader}
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1rem; margin-bottom: 1rem;">
                <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 700; color: #0f172a;">1. Elige un taller</h4>
                <div style="position: relative;">
                    <input type="text" id="tallerMultipleSelector" list="listaTalleresMultiplesDatalist"
                        placeholder="Escribe el nombre del taller..."
                        style="width: 100%; padding: 0.9rem 0.95rem; border: 1.5px solid #cbd5e1; border-radius: 10px; background: white; font-size: 0.95rem; transition: all 0.2s; outline: none; box-sizing: border-box;"
                        onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.12)';"
                        onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none';"
                        onkeypress="if(event.key === 'Enter') agregarTallerSeleccionado()"
                        onchange="setTimeout(() => agregarTallerSeleccionado(), 100)"
                    >
                    <datalist id="listaTalleresMultiplesDatalist"></datalist>
                </div>
                <button type="button" onclick="agregarTallerSeleccionado()" style="margin-top: 0.75rem; width: 100%; padding: 0.9rem 1rem; border: none; border-radius: 10px; background: #2563eb; color: white; font-size: 0.95rem; font-weight: 700; cursor: pointer;">Agregar taller</button>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1rem; margin-bottom: 1rem;">
                <h4 style="margin: 0 0 0.75rem 0; font-size: 0.95rem; font-weight: 700; color: #0f172a;">2. Talleres del proceso</h4>
                <div id="listaTalleresSeleccionados" style="display: grid; gap: 0.5rem;"></div>
            </div>
            <div id="interfazDistribucionTaller" style="margin-top: 1rem;">
                ${generarEstadoCargaTaller('Seleccione uno o mas talleres para ver las tallas disponibles')}
            </div>
        </div>
    `;

    cargarTalleresParaDistribucionHelper();
}
