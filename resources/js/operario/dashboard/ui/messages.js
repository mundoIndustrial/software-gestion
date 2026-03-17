export function mostrarExito(titulo, texto = '') {
    mostrarMensaje(titulo, texto, 'exito', '✅');
}

export function mostrarError(titulo, texto = '') {
    mostrarMensaje(titulo, texto, 'error', '❌');
}

export function mostrarMensaje(titulo, texto, tipo = 'exito', icono = '✅') {
    const modal = document.getElementById('modalMensaje');
    const contenido = document.getElementById('modalMensajeContenido');
    const iconoEl = document.getElementById('modalMensajeIcono');
    const tituloEl = document.getElementById('modalMensajeTitulo');
    const textoEl = document.getElementById('modalMensajeTexto');

    if (!modal || !contenido) {
        console.error('Modal de mensaje no encontrado');
        return;
    }

    if (iconoEl) iconoEl.textContent = icono;
    if (tituloEl) tituloEl.textContent = titulo;
    if (textoEl) textoEl.textContent = texto;

    const colores = {
        exito: { bg: '#10b981', border: '#059669' },
        error: { bg: '#ef4444', border: '#dc2626' },
        info: { bg: '#3b82f6', border: '#2563eb' },
    };

    const color = colores[tipo] || colores.info;
    contenido.style.borderColor = color.border;

    const boton = document.createElement('button');
    boton.textContent = 'CERRAR';
    boton.style.cssText = `
            background: ${color.bg};
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
            transition: background 0.2s;
        `;
    boton.onmouseover = function () {
        this.style.background = color.border;
    };
    boton.onmouseout = function () {
        this.style.background = color.bg;
    };
    boton.onclick = cerrarModalMensaje;

    const botonAnterior = contenido.querySelector('button');
    if (botonAnterior) botonAnterior.remove();

    contenido.appendChild(boton);

    modal.style.display = 'flex';
}

export function cerrarModalMensaje() {
    const modal = document.getElementById('modalMensaje');
    if (modal) {
        modal.style.display = 'none';
    }
}
