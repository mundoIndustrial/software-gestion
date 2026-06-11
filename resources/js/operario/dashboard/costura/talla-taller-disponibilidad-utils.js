import { normalizarColor, normalizarGenero, parseTallaIdUnico } from './talla-utils';

export function getTotalOriginalTallaIdTaller(tallaId, fallbackCantidad = 0) {
    const { base, colorNorm, generoNorm } = parseTallaIdUnico(tallaId);
    const tallas = window?.datosDistribucion?.tallas || [];
    const colorObjetivo = colorNorm || 'sin_color';

    const item = tallas.find((t) => {
        const baseT = (t.tallaOriginal || (String(t.talla || '').split(' ')[0])) || '';
        if (String(baseT) !== String(base)) return false;
        if (normalizarColor(t.color) !== colorObjetivo) return false;
        return normalizarGenero(t.genero) === generoNorm;
    });

    const total = parseInt(item?.cantidad) || 0;
    return total > 0 ? total : (parseInt(fallbackCantidad) || 0);
}

export function getTotalAsignadoTallaTaller(tallaId) {
    let total = 0;
    Object.values(window.asignacionesPorTaller || {}).forEach((asignaciones) => {
        const valor = asignaciones?.[tallaId];
        if (typeof valor === 'object' && valor !== null) {
            total += parseInt(valor.cantidad) || 0;
        } else {
            total += parseInt(valor) || 0;
        }
    });
    return total;
}

export function getDisponibleRestanteGlobalTaller(tallaId) {
    return Math.max(0, getTotalOriginalTallaIdTaller(tallaId) - getTotalAsignadoTallaTaller(tallaId));
}
