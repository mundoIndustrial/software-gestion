import { construirTallaIdUnico, normalizarColor, normalizarGenero, parseTallaIdUnico } from './talla-utils';

export function getTotalOriginalTallaId(tallaId) {
    const { base, colorNorm, generoNorm } = parseTallaIdUnico(tallaId);
    const tallas = window?.datosDistribucion?.tallas || [];
    const colorObjetivo = colorNorm || 'sin_color';

    const item = tallas.find((t) => {
        const baseT = (t.tallaOriginal || (String(t.talla || '').split(' ')[0])) || '';
        if (String(baseT) !== String(base)) return false;

        const c = normalizarColor(t.color);
        if (c !== colorObjetivo) return false;

        const g = normalizarGenero(t.genero);
        return g === generoNorm;
    });

    return parseInt(item?.cantidad) || 0;
}

export function getColorParaTallaId(tallaId) {
    const { base, colorNorm, generoNorm } = parseTallaIdUnico(tallaId);
    const tallas = window?.datosDistribucion?.tallas || [];
    const colorObjetivo = colorNorm || 'sin_color';

    const item = tallas.find((t) => {
        const baseT = (t.tallaOriginal || (String(t.talla || '').split(' ')[0])) || '';
        if (String(baseT) !== String(base)) return false;

        const c = normalizarColor(t.color);
        if (c !== colorObjetivo) return false;

        const g = normalizarGenero(t.genero);
        return g === generoNorm;
    });

    return item?.color || null;
}

export function getGeneroParaTallaId(tallaId) {
    const { base, colorNorm, generoNorm } = parseTallaIdUnico(tallaId);
    const tallas = window?.datosDistribucion?.tallas || [];
    const colorObjetivo = colorNorm || 'sin_color';

    const item = tallas.find((t) => {
        const baseT = (t.tallaOriginal || (String(t.talla || '').split(' ')[0])) || '';
        if (String(baseT) !== String(base)) return false;

        const c = normalizarColor(t.color);
        if (c !== colorObjetivo) return false;

        const g = normalizarGenero(t.genero);
        return g === generoNorm;
    });

    return item?.genero || null;
}

export function getDisponibleRestanteGlobal(tallaId) {
    const totalOriginal = getTotalOriginalTallaId(tallaId);
    const asignadoTotal = getTotalAsignadoTalla(tallaId, null);
    return Math.max(0, totalOriginal - asignadoTotal);
}

export function getMaxDisponibleParaModulo(tallaId, moduloId) {
    const totalOriginal = getTotalOriginalTallaId(tallaId);
    const totalAsignadoOtros = getTotalAsignadoTalla(tallaId, moduloId);
    const max = Math.max(0, totalOriginal - totalAsignadoOtros);
    console.log(`[MAX DISPONIBLE] Talla ID: ${tallaId}, Total original: ${totalOriginal}, Asignado otros: ${totalAsignadoOtros}, Max: ${max}`);
    return max;
}

function getTotalAsignadoTalla(tallaId, moduloIdExcluir = null) {
    let total = 0;
    Object.entries(window.asignacionesPorModulo || {}).forEach(([moduloId, asignaciones]) => {
        if (moduloIdExcluir !== null && parseInt(moduloId) === parseInt(moduloIdExcluir)) return;

        const valor = asignaciones?.[tallaId];
        if (typeof valor === 'object' && valor !== null) {
            total += parseInt(valor.cantidad) || 0;
        } else {
            total += parseInt(valor) || 0;
        }
    });
    return total;
}

export function refrescarDistribucionUI() {
    const inputs = document.querySelectorAll('input.dist-talla-input[data-tallaid][data-moduloid]');
    inputs.forEach((input) => {
        const tallaId = input.dataset.tallaid;
        const moduloId = parseInt(input.dataset.moduloid);
        if (!tallaId || !Number.isFinite(moduloId)) return;

        const max = getMaxDisponibleParaModulo(tallaId, moduloId);
        input.max = String(max);

        const asignado = (() => {
            const v = window.asignacionesPorModulo?.[moduloId]?.[tallaId];
            if (typeof v === 'object' && v !== null) return parseInt(v.cantidad) || 0;
            if (typeof v === 'number') return parseInt(v) || 0;
            return 0;
        })();

        if (asignado > max) {
            if (window.asignacionesPorModulo?.[moduloId]?.[tallaId]) {
                if (typeof window.asignacionesPorModulo[moduloId][tallaId] === 'object' && window.asignacionesPorModulo[moduloId][tallaId] !== null) {
                    window.asignacionesPorModulo[moduloId][tallaId].cantidad = max;
                } else {
                    window.asignacionesPorModulo[moduloId][tallaId] = max;
                }
            }
            input.value = String(max);
        }

        if (!input.disabled) {
            const cur = parseInt(input.value) || 0;
            if (cur > max) {
                input.value = String(max);
                if (window.asignacionesPorModulo?.[moduloId]?.[tallaId]) {
                    if (typeof window.asignacionesPorModulo[moduloId][tallaId] === 'object' && window.asignacionesPorModulo[moduloId][tallaId] !== null) {
                        window.asignacionesPorModulo[moduloId][tallaId].cantidad = max;
                    } else {
                        window.asignacionesPorModulo[moduloId][tallaId] = max;
                    }
                }
            }
        }

        const check = input.closest('.dist-talla-row')?.querySelector('input.dist-talla-check[data-tallaid][data-moduloid]');
        const row = input.closest('.dist-talla-row');
        const selected = asignado > 0;
        if (check) check.checked = selected;
        if (row) row.classList.toggle('is-selected', selected);
        input.disabled = !selected;
        if (selected) input.value = String(asignado);
        if (!selected) input.value = '0';
    });

    const disps = document.querySelectorAll('.dist-disp[data-tallaid][data-moduloid]');
    disps.forEach((el) => {
        const tallaId = el.dataset.tallaid;
        if (!tallaId) return;
        el.textContent = `Disp: ${getDisponibleRestanteGlobal(tallaId)}`;
    });
}
