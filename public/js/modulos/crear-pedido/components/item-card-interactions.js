/**
 * Item Card Interactivity
 * 
 * Maneja:
 * - Expandir/contraer secciones
 * - Editar items
 * - Eliminar items
 */

/**
 * Alternar visibilidad de una sección expandible
 * @param {HTMLElement} headerElement - El elemento del header clickeado
 */
function toggleSection(headerElement) {
  // Evitar que se propague el evento
  event?.stopPropagation?.();

  const content = headerElement.nextElementSibling;
  if (!content || !content.classList.contains('section-content')) {
    return;
  }

  const isOpen = content.style.display !== 'none';

  if (isOpen) {
    // Cerrar
    content.style.display = 'none';
    headerElement.classList.remove('active');
  } else {
    // Abrir
    content.style.display = 'block';
    headerElement.classList.add('active');
  }
}

/**
 * Inicializar interactividad de cards
 */
let itemCardListenerInitialized = false;

document.addEventListener('DOMContentLoaded', function() {
  initializeItemCardInteractions();
});

function initializeItemCardInteractions() {
  // Solo registrar el listener una sola vez
  if (itemCardListenerInitialized) {

    return;
  }
  itemCardListenerInitialized = true;
  

  
  // Delegar eventos para botones de editar/eliminar y menú
  document.addEventListener('click', function(e) {
    // Botón Menú - Toggle dropdown
    if (e.target.closest('.btn-menu-expandible')) {

      e.preventDefault();
      e.stopPropagation();
      const button = e.target.closest('.btn-menu-expandible');

      const wrapper = button.closest('.btn-menu-wrapper');

      
      // Validar que wrapper existe
      if (!wrapper) {


        return;
      }
      
      const dropdown = wrapper.querySelector('.menu-dropdown');

      
      // Validar que dropdown existe
      if (!dropdown) {


        return;
      }
      
      // Toggle visibility
      const isOpen = dropdown.style.display !== 'none';

      dropdown.style.display = isOpen ? 'none' : 'block';

      
      // Cerrar otros dropdowns abiertos
      document.querySelectorAll('.menu-dropdown').forEach(menu => {
        if (menu !== dropdown) {
          menu.style.display = 'none';
        }
      });
      return;
    }

    // Botón Eliminar (ahora dentro del menú)
    if (e.target.closest('.btn-eliminar-item')) {
      e.stopPropagation();
      const button = e.target.closest('.btn-eliminar-item');
      const itemIndex = button.dataset.itemIndex;
      
      // Cerrar el menú
      const menu = button.closest('.menu-dropdown');
      if (menu) menu.style.display = 'none';
      
      handleEliminarItem(itemIndex);
    }

    // Botón Editar (ahora dentro del menú)
    if (e.target.closest('.btn-editar-item')) {
      e.stopPropagation();
      const button = e.target.closest('.btn-editar-item');
      const itemIndex = button.dataset.itemIndex;
      
      // Cerrar el menú
      const menu = button.closest('.menu-dropdown');
      if (menu) menu.style.display = 'none';
      
      handleEditarItem(itemIndex);
    }
  });

  // Cerrar dropdown cuando se clickea fuera
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.btn-menu-wrapper')) {
      document.querySelectorAll('.menu-dropdown').forEach(menu => {
        menu.style.display = 'none';
      });
    }
  });
}

/**
 * Manejar eliminación de item
 * @param {number} itemIndex - Índice del item a eliminar
 */
function handleEliminarItem(itemIndex) {
  // Obtener la UI de gestión de items si existe
  if (window.gestionItemsUI && typeof window.gestionItemsUI.eliminarItem === 'function') {
    window.gestionItemsUI.eliminarItem(itemIndex);
  } else if (window.gestorPrendaSinCotizacion && typeof window.gestorPrendaSinCotizacion.eliminarActiva === 'function') {
    // Fallback para gestor de prendas
    window.gestorPrendaSinCotizacion.eliminarActiva(itemIndex);
  } else {

  }
}

/**
 * Manejar edición de item
 * @param {number} itemIndex - Índice del item a editar
 */
function handleEditarItem(itemIndex) {
  const indiceVisual = Number.parseInt(itemIndex, 10);
  const itemDesdeGestion = window.gestionItemsUI &&
    typeof window.gestionItemsUI.obtenerItemPorIndiceVisual === 'function'
      ? window.gestionItemsUI.obtenerItemPorIndiceVisual(indiceVisual)
      : null;

  const item = itemDesdeGestion;
  if (!item) {
    return;
  }

  const esEpp = item.tipo === 'epp' || item.epp_id || item.pedido_epp_id || item.pedidoEppId;
  if (esEpp && typeof window.abrirModalEditarEPP === 'function') {
    window.abrirModalEditarEPP({
      ...item,
      id: item.tarjetaId || item.id || item.epp_id,
      tarjetaId: item.tarjetaId || `epp-${item.pedido_epp_id || item.pedidoEppId || item.epp_id || item.id || indiceVisual}`,
      epp_id: item.epp_id || item.id || null,
      pedido_epp_id: item.pedido_epp_id || item.pedidoEppId || null,
      nombre: item.nombre || item.nombre_epp || item.nombre_completo || '',
      nombre_epp: item.nombre_epp || item.nombre || item.nombre_completo || '',
      nombre_completo: item.nombre_completo || item.nombre_epp || item.nombre || '',
    });
    return;
  }

  if (window.cargarItemEnModal && typeof window.cargarItemEnModal === 'function') {
    window.cargarItemEnModal(item, indiceVisual);
  } else if (window.abrirModalPrendaNueva && typeof window.abrirModalPrendaNueva === 'function') {
    window.abrirModalPrendaNueva();
  }
}

/**
 * Actualizar interactividad después de renderizar nuevos items
 * Llamar a esta función después de agregar nuevos items al DOM
 */
function updateItemCardInteractions() {

  
  // Verificar que los elementos existan en el DOM
  const menuButtons = document.querySelectorAll('.btn-menu-expandible');

  
  const menuWrappers = document.querySelectorAll('.btn-menu-wrapper');

  
  const menuDropdowns = document.querySelectorAll('.menu-dropdown');

  
  // Verificar estructura de cada wrapper
  menuWrappers.forEach((wrapper, idx) => {
    console.log(` [UPDATE-ITEM-CARD] Wrapper ${idx}:`, {
      hasButton: !!wrapper.querySelector('.btn-menu-expandible'),
      hasDropdown: !!wrapper.querySelector('.menu-dropdown'),
      innerHTML: wrapper.innerHTML.substring(0, 150)
    });
  });
  
  initializeItemCardInteractions();
}

// Exportar para uso global
window.toggleSection = toggleSection;
window.updateItemCardInteractions = updateItemCardInteractions;
window.handleEliminarItem = handleEliminarItem;
window.handleEditarItem = handleEditarItem;
