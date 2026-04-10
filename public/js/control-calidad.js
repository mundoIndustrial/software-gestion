/**
 * Control de Calidad - Search and Filter Script
 * Búsqueda dinámica sin recarga de página
 * Respeta el filtro de tags activos (COSTURA/REFLECTIVO)
 */

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const clearFilterBtn = document.getElementById('clearFilterBtn');
    const ordenesList = document.getElementById('ordenesList');
    const reciboTags = document.getElementById('reciboTags');

    // Obtener el filtro activo actual
    function getActiveFilter() {
        if (!reciboTags) return 'COSTURA'; // Default
        const activeBtn = reciboTags.querySelector('.recibo-tag.active');
        if (activeBtn) {
            return (activeBtn.dataset.filter || 'COSTURA').toUpperCase();
        }
        return 'COSTURA';
    }

    // Función para filtrar tarjetas en tiempo real
    function filtrarTarjetas(query) {
        const searchLower = query.toLowerCase().trim();
        const activeFilter = getActiveFilter(); // Obtener filtro activo
        const cards = ordenesList.querySelectorAll('.orden-card-simple');
        let totalVisible = 0;

        cards.forEach(card => {
            let mostrar = false;
            
            // Primero verificar que el tipo de recibo coincida con el filtro activo
            const tipoRecibo = (card.dataset.tipoRecibo || 'COSTURA').toUpperCase();
            const matchTipo = tipoRecibo === activeFilter;
            
            if (!matchTipo) {
                // Si no coincide el tipo de recibo, no mostrar
                card.style.display = 'none';
                return;
            }

            if (!searchLower) {
                // Si no hay búsqueda, mostrar todas las del tipo activo
                mostrar = true;
            } else {
                // Obtener atributos de la tarjeta
                const numero = (card.dataset.numero || '').toLowerCase().trim();
                const cliente = (card.dataset.cliente || '').toLowerCase().trim();
                const prenda = (card.dataset.prenda || '').toLowerCase().trim();
                
                // Obtener el número visible del recibo/parcial (del h4)
                const h4 = card.querySelector('.orden-numero');
                const numeroVisible = h4 ? h4.textContent.toLowerCase().replace('#', '').trim() : '';

                // Si la búsqueda es un número (con o sin decimales), extraer el entero
                let numeroBuscadoEntero = null;
                if (/^\d+(\.\d+)?$/.test(searchLower)) {
                    numeroBuscadoEntero = Math.floor(parseFloat(searchLower)).toString();
                }

                // Búsqueda 1: Búsqueda substring normal en todos los campos
                if (numero.includes(searchLower) || 
                    cliente.includes(searchLower) || 
                    prenda.includes(searchLower) ||
                    numeroVisible.includes(searchLower)) {
                    mostrar = true;
                }

                // Búsqueda 2: Si es número (30.1 -> busca 30), buscar el número entero
                if (!mostrar && numeroBuscadoEntero) {
                    if (numero.includes(numeroBuscadoEntero) || 
                        numeroVisible === numeroBuscadoEntero ||
                        numeroVisible.startsWith(numeroBuscadoEntero)) {
                        mostrar = true;
                    }
                }
            }

            if (mostrar) {
                card.style.display = '';
                totalVisible++;
            } else {
                card.style.display = 'none';
            }
        });

        // Mostrar/ocultar botón de limpiar
        if (clearFilterBtn) {
            clearFilterBtn.style.display = searchLower ? 'block' : 'none';
        }
    }

    // Búsqueda en tiempo real sin recargar
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filtrarTarjetas(this.value);
        });
    }

    // Limpiar búsqueda
    if (clearFilterBtn) {
        clearFilterBtn.addEventListener('click', function() {
            if (searchInput) {
                searchInput.value = '';
            }
            filtrarTarjetas('');
        });
    }

    // Escuchar cambios en los tags para actualizar búsqueda cuando se cambia el filtro
    if (reciboTags) {
        reciboTags.addEventListener('click', function() {
            // Cuando cambia el filtro de tags, reaplicar la búsqueda actual
            setTimeout(() => {
                filtrarTarjetas(searchInput ? searchInput.value : '');
            }, 100);
        });
    }
});

