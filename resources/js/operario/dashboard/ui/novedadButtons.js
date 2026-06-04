function escaparAtributoHtml(valor) {
    return String(valor ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

export function crearBotonAgregarNovedad({
    numeroPedido = '',
    prendaId = '',
    nombrePrenda = '',
    numeroRecibo = '',
    className = 'btn-agregar-novedad',
} = {}) {
    return `
        <button
            type="button"
            class="${className}"
            data-numero-pedido="${escaparAtributoHtml(numeroPedido)}"
            data-prenda-id="${escaparAtributoHtml(prendaId)}"
            data-nombre-prenda="${escaparAtributoHtml(nombrePrenda)}"
            data-numero-recibo="${escaparAtributoHtml(numeroRecibo)}"
            onclick="abrirModalNovedadDesdeElemento(this)"
        >
            <span class="material-symbols-rounded">comment</span>
            AGREGAR NOVEDAD
        </button>
    `;
}

export function asegurarBotonAgregarNovedad(card, datos = {}) {
    if (!card) return null;

    const contenedorBotones = card.querySelector('.orden-buttons');
    if (!contenedorBotones) return null;

    let boton = contenedorBotones.querySelector('.btn-agregar-novedad');
    if (boton) {
        boton.style.display = '';
        return boton;
    }

    contenedorBotones.insertAdjacentHTML('beforeend', crearBotonAgregarNovedad(datos));
    boton = contenedorBotones.querySelector('.btn-agregar-novedad:last-child');

    if (boton) {
        boton.style.display = '';
    }

    return boton;
}
