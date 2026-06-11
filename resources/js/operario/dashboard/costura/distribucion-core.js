export function procesarTallasParaDistribucion(tallas) {
    const tallasArray = [];

    if (Array.isArray(tallas) && tallas.length > 0 && typeof tallas[0] === 'object' && tallas[0] !== null && 'talla' in tallas[0] && 'cantidad' in tallas[0]) {
        tallas.forEach((v) => {
            const nombreTalla = String(v.talla || '').trim();
            if (!nombreTalla) return;
            const genero = String(v.genero || '').trim();

            if (v.colores_detalle && Array.isArray(v.colores_detalle) && v.colores_detalle.length > 0) {
                v.colores_detalle.forEach((colorDetalle) => {
                    const cantidad = parseInt(colorDetalle.cantidad) || 0;
                    if (cantidad <= 0) return;

                    const color = colorDetalle.color || null;
                    const nombreDisplay = genero ? `${nombreTalla} (${genero})` : nombreTalla;

                    tallasArray.push({
                        talla: nombreDisplay,
                        cantidad,
                        color,
                        tallaOriginal: nombreTalla,
                        genero,
                        colorDetalle,
                    });
                });
            } else {
                const cantidad = parseInt(v.cantidad) || 0;
                if (cantidad <= 0) return;
                const color = v.color_nombre || v.color || null;

                tallasArray.push({
                    talla: genero ? `${nombreTalla} (${genero})` : nombreTalla,
                    cantidad,
                    color,
                    tallaOriginal: nombreTalla,
                    genero,
                });
            }
        });

        return tallasArray;
    }

    if (Array.isArray(tallas)) {
        tallas.forEach((talla, index) => {
            if (typeof talla === 'object' && talla !== null) {
                tallasArray.push({
                    talla: talla.talla || talla.nombre || `Talla ${index + 1}`,
                    cantidad: parseInt(talla.cantidad) || 0,
                    color: talla.color_nombre || talla.color || null,
                });
            } else if (typeof talla === 'string') {
                tallasArray.push({
                    talla,
                    cantidad: 0,
                    color: null,
                });
            }
        });
    } else if (typeof tallas === 'object' && tallas !== null) {
        Object.entries(tallas).forEach(([genero, tallasGenero]) => {
            if (typeof tallasGenero === 'object') {
                Object.entries(tallasGenero).forEach(([nombreTalla, datos]) => {
                    let cantidad = 0;
                    let color = null;

                    if (typeof datos === 'object' && datos !== null) {
                        cantidad = parseInt(datos.cantidad) || 0;
                        color = datos.color_nombre || datos.color || null;
                    } else {
                        cantidad = parseInt(datos) || 0;
                    }

                    if (cantidad > 0) {
                        tallasArray.push({
                            talla: `${nombreTalla} (${genero})`,
                            cantidad,
                            color,
                        });
                    }
                });
            }
        });
    }

    return tallasArray;
}

export function agruparTallasPorGeneroYColor(tallas) {
    const grupos = {};

    (tallas || []).forEach((talla) => {
        const genero = talla.genero || 'Sin genero';
        const color = talla.color || 'Sin color';

        if (!grupos[genero]) {
            grupos[genero] = {};
        }

        if (!grupos[genero][color]) {
            grupos[genero][color] = [];
        }

        grupos[genero][color].push(talla);
    });

    return grupos;
}

export function procesarUsuariosParaDistribucion(usuarios) {
    return (usuarios || []).map((usuario, index) => ({
        id: index + 1,
        nombre: `Modulo ${index + 1}`,
        encargado: usuario.name || usuario.nombre || 'Sin nombre',
        usuarioId: usuario.id,
    }));
}

export function mostrarErrorDistribucion() {
    const interfazDiv = document.getElementById('interfazDistribucion');
    if (interfazDiv) {
        interfazDiv.innerHTML = `
            <div style="text-align: center; padding: 2rem; color: #dc2626;">
                <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.75rem;">error</span>
                <p style="font-size: 0.875rem; margin: 0;">No se pudo cargar la informacion. Por favor, intente nuevamente.</p>
            </div>
        `;
    }
}

export function cargarInterfazDistribucionConDatos(tallas, modulos) {
    const interfazDiv = document.getElementById('interfazDistribucion');
    if (!interfazDiv) {
        console.warn('[DISTRIBUCION] No existe #interfazDistribucion al cargar datos');
        return;
    }

    if (!tallas || tallas.length === 0) {
        interfazDiv.innerHTML = `
            <div style="text-align: center; padding: 2rem; color: #6b7280;">
                <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.75rem;">info</span>
                <p style="font-size: 0.875rem; margin: 0;">No hay tallas disponibles para esta prenda</p>
            </div>
        `;
        return;
    }

    if (!modulos || modulos.length === 0) {
        interfazDiv.innerHTML = `
            <div style="text-align: center; padding: 2rem; color: #6b7280;">
                <span class="material-symbols-rounded" style="font-size: 2rem; display: block; margin-bottom: 0.75rem;">person_off</span>
                <p style="font-size: 0.875rem; margin: 0;">No hay Modulos disponibles para asignar</p>
            </div>
        `;
        return;
    }

    if (window.datosModalCostura?.esEdicion) {
        window.mostrarCardsEncargados?.(tallas, modulos);
    } else {
        window.mostrarInterfazDistribucionNormal?.(tallas, modulos);
    }

    window.datosDistribucion = { tallas, modulos };
    window.dispatchEvent(new CustomEvent('costura:datos-distribucion-listos', {
        detail: window.datosDistribucion,
    }));
}
