/**
 * Protección Global contra Double-Click
 *
 * Previene que el usuario envíe formularios dos veces haciendo doble-click
 * o click rápido en botones de envío/guardar
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('[DoubleClickProtection] Inicializando protección contra double-click');

    // Seleccionar todos los botones que pueden causar envíos duplicados
    const botonesProtegidos = document.querySelectorAll(
        'button[type="submit"], ' +           // Botones submit
        'button[onclick*="guardar"], ' +      // Botones que guardan
        'button[onclick*="agregar"], ' +      // Botones que agregan
        'button[onclick*="enviar"], ' +       // Botones que envían
        'button[onclick*="crear"], ' +        // Botones que crean
        'button[onclick*="actualizar"], ' +   // Botones que actualizan
        'button[onclick*="eliminar"]'         // Botones que eliminan
    );

    console.log(`[DoubleClickProtection] Encontrados ${botonesProtegidos.length} botones protegidos`);

    // Aplicar protección a cada botón
    botonesProtegidos.forEach((boton) => {
        const textoOriginal = boton.innerHTML;

        boton.addEventListener('click', function(event) {
            // Si el botón ya está deshabilitado, no hacer nada
            if (this.disabled) {
                console.log('[DoubleClickProtection] Botón ya está deshabilitado, ignorando click');
                event.preventDefault();
                return;
            }

            // Deshabilitarlo inmediatamente
            this.disabled = true;

            // Cambiar apariencia
            this.style.opacity = '0.6';
            this.style.cursor = 'not-allowed';
            this.classList.add('disabled');

            // Mostrar indicador de carga (si hay)
            const spinner = this.querySelector('.spinner-border, .spinner, .loading');
            if (spinner) {
                spinner.style.display = 'inline-block';
            }

            // Cambiar texto para indicar que está procesando
            if (!this.innerHTML.includes('Procesando') && !this.innerHTML.includes('Enviando')) {
                const iconoOriginal = this.querySelector('span.material-symbols-rounded, i');
                if (iconoOriginal) {
                    this.innerHTML = `<span class="material-symbols-rounded">hourglass_empty</span> Procesando...`;
                } else {
                    this.innerHTML = 'Procesando...';
                }
            }

            console.log(`[DoubleClickProtection] Botón deshabilitado: ${this.id || this.className}`);

            // Reabilitar después de 2 segundos (tiempo prudente para que se procese)
            const tiempoEspera = 2000;

            setTimeout(() => {
                this.disabled = false;
                this.style.opacity = '1';
                this.style.cursor = 'pointer';
                this.classList.remove('disabled');
                this.innerHTML = textoOriginal;

                console.log(`[DoubleClickProtection] Botón re-habilitado: ${this.id || this.className}`);
            }, tiempoEspera);
        });
    });

    // Protección adicional: Prevenir múltiples form submissions
    const formularios = document.querySelectorAll('form');
    console.log(`[DoubleClickProtection] Encontrados ${formularios.length} formularios`);

    formularios.forEach((form) => {
        form.addEventListener('submit', function(event) {
            // Si el formulario ya fue enviado, prevenir re-envío
            if (this.classList.contains('enviando')) {
                console.log('[DoubleClickProtection] Formulario ya está siendo enviado, previniendo re-envío');
                event.preventDefault();
                return;
            }

            // Marcar como enviando
            this.classList.add('enviando');

            // Deshabilitar TODOS los botones del formulario
            const botonesDelFormulario = this.querySelectorAll('button[type="submit"], button[type="button"]');
            botonesDelFormulario.forEach((btn) => {
                btn.disabled = true;
                btn.style.opacity = '0.6';
            });

            console.log(`[DoubleClickProtection] Formulario enviado, deshabilitados ${botonesDelFormulario.length} botones`);

            // Re-habilitar después de 3 segundos
            setTimeout(() => {
                this.classList.remove('enviando');
                botonesDelFormulario.forEach((btn) => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                });

                console.log('[DoubleClickProtection] Formulario disponible nuevamente');
            }, 3000);
        });
    });

    console.log('[DoubleClickProtection] ✓ Protección contra double-click activada');
});

/**
 * Función auxiliar para reabilitar manualmente un botón después de un error
 * Uso: habilitarBoton(documento.getElementById('mi-boton'))
 */
window.habilitarBoton = function(boton) {
    if (!boton) return;

    boton.disabled = false;
    boton.style.opacity = '1';
    boton.style.cursor = 'pointer';
    boton.classList.remove('disabled');

    console.log('[DoubleClickProtection] Botón habilitado manualmente');
};

/**
 * Función para deshabilitar un botón manualmente (para casos especiales)
 */
window.deshabilitarBoton = function(boton, tiempoEspera = 2000) {
    if (!boton) return;

    boton.disabled = true;
    boton.style.opacity = '0.6';
    boton.style.cursor = 'not-allowed';
    boton.classList.add('disabled');

    console.log('[DoubleClickProtection] Botón deshabilitado manualmente por ' + tiempoEspera + 'ms');

    if (tiempoEspera > 0) {
        setTimeout(() => window.habilitarBoton(boton), tiempoEspera);
    }
};
