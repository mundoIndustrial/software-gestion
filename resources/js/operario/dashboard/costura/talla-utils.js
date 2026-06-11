export function normalizarColor(color) {
    const colorLimpio = String(color || '').trim().toLowerCase();
    if (!colorLimpio || colorLimpio === 'sin color') {
        return 'sin_color';
    }
    return colorLimpio.replace(/\s+/g, '_');
}

export function normalizarGenero(genero) {
    const genLimpio = String(genero || '').trim().toLowerCase();
    if (!genLimpio || genLimpio === 'sin genero' || genLimpio === 'sin género') {
        return 'sin_genero';
    }
    return genLimpio.replace(/\s+/g, '_');
}

export function construirTallaIdUnico(nombreTalla, color, genero = '') {
    const tallaBase = String(nombreTalla || '').trim();
    const colorNormalizado = normalizarColor(color);
    const generoNormalizado = normalizarGenero(genero);
    return `${tallaBase}_${colorNormalizado}_${generoNormalizado}`;
}

export function parseTallaIdUnico(tallaId) {
    const raw = String(tallaId || '');
    const parts = raw.split('_');

    if (parts.length < 3) {
        return {
            base: raw.trim(),
            colorNorm: '',
            generoNorm: '',
        };
    }

    const generoNorm = parts.pop() || '';
    const colorNorm = parts.pop() || '';
    const base = parts.join('_').trim();

    return {
        base,
        colorNorm,
        generoNorm,
    };
}
