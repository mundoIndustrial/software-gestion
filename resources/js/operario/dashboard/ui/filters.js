export function initReciboFilters() {
    window.filtrarPrendasPorRecibo = function (filtro) {
        console.log(' [FILTRO] Iniciando filtro:', filtro);

        document.querySelectorAll('.badge-filtro').forEach((btn) => {
            btn.classList.remove('badge-filtro-active');
        });
        const btnFiltro = document.querySelector(`[data-filtro="${filtro}"]`);
        if (btnFiltro) {
            btnFiltro.classList.add('badge-filtro-active');
        }

        const ordenesList = document.getElementById('ordenesList');
        if (!ordenesList) {
            console.error(' ordenesList no encontrado');
            return;
        }

        const ordenCards = ordenesList.querySelectorAll('.orden-card-simple');
        console.log(` [FILTRO] Tarjetas encontradas: ${ordenCards.length}`);

        let mostradas = 0;
        let ocultadas = 0;

        ordenCards.forEach((card, index) => {
            const tipoRecibo = card.dataset.tipoRecibo;
            const numeroPedido = card.dataset.numero;
            const nombrePrenda = card.dataset.prenda;

            console.log(
                `Tarjeta ${index + 1}: Pedido=${numeroPedido}, Prenda=${nombrePrenda}, data-tipo-recibo="${tipoRecibo}"`
            );

            if (filtro === 'todos') {
                card.style.display = '';
                const elementosFiltrables = card.querySelectorAll('[data-visible-filtro]');
                elementosFiltrables.forEach((elemento) => {
                    elemento.style.display = '';
                });
                mostradas++;
                return;
            }

            const tipos = tipoRecibo ? tipoRecibo.split(',').map((t) => t.trim()) : [];

            if (tipos.includes(filtro)) {
                console.log(`  Mostrando (contiene "${filtro}" en [${tipos.join(', ')}])`);
                card.style.display = '';
                const elementosFiltrables = card.querySelectorAll('[data-visible-filtro]');
                elementosFiltrables.forEach((elemento) => {
                    const filtrosElemento = (elemento.dataset.visibleFiltro || '')
                        .split(',')
                        .map((valor) => valor.trim())
                        .filter(Boolean);

                    elemento.style.display = filtrosElemento.includes(filtro) ? '' : 'none';
                });
                mostradas++;
            } else {
                console.log(`  Ocultando (no contiene "${filtro}" en [${tipos.join(', ')}])`);
                card.style.display = 'none';
                ocultadas++;
            }
        });

        console.log(` [FILTRO] Filtro completado: ${mostradas} mostradas, ${ocultadas} ocultadas`);
    };
}
