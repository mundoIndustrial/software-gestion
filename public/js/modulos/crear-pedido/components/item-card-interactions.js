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
    console.log('🔄 [ITEM-CARD-INTERACTIONS] Ya inicializado, saltando...');
    return;
  }
  itemCardListenerInitialized = true;
  
  console.log('✅ [ITEM-CARD-INTERACTIONS] Inicializando event listeners para item cards');
  
  // Delegar eventos para botones de editar/eliminar y menú
  document.addEventListener('click', function(e) {
    // Botón Menú - Toggle dropdown
    if (e.target.closest('.btn-menu-expandible')) {
      console.log('🎯 [ITEM-CARD-INTERACTIONS] Click en btn-menu-expandible detectado');
      e.preventDefault();
      e.stopPropagation();
      const button = e.target.closest('.btn-menu-expandible');
      console.log('🔘 [ITEM-CARD-INTERACTIONS] Button:', button);
      const wrapper = button.closest('.btn-menu-wrapper');
      console.log('📦 [ITEM-CARD-INTERACTIONS] Wrapper encontrado?', !!wrapper);
      
      // Validar que wrapper existe
      if (!wrapper) {
        console.warn('❌ [ITEM-CARD-INTERACTIONS] btn-menu-wrapper no encontrado');
        console.warn('📍 [ITEM-CARD-INTERACTIONS] Button parents:', button.parentElement?.className);
        return;
      }
      
      const dropdown = wrapper.querySelector('.menu-dropdown');
      console.log('📋 [ITEM-CARD-INTERACTIONS] Dropdown encontrado?', !!dropdown);
      
      // Validar que dropdown existe
      if (!dropdown) {
        console.warn('❌ [ITEM-CARD-INTERACTIONS] menu-dropdown no encontrado en wrapper');
        console.log('📍 [ITEM-CARD-INTERACTIONS] Wrapper HTML:', wrapper.innerHTML.substring(0, 200));
        return;
      }
      
      // Toggle visibility
      const isOpen = dropdown.style.display !== 'none';
      console.log('🔄 [ITEM-CARD-INTERACTIONS] Dropdown abierto actualmente?', isOpen);
      dropdown.style.display = isOpen ? 'none' : 'block';
      console.log('✅ [ITEM-CARD-INTERACTIONS] Dropdown display ahora:', dropdown.style.display);
      
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
    console.error('No hay manejador de eliminación disponible');
  }
}

/**
 * Manejar edición de item
 * @param {number} itemIndex - Índice del item a editar
 */
function handleEditarItem(itemIndex) {
  console.log('✏️ [ITEM-CARD-INTERACTIONS] Editando item:', itemIndex);
  
  // Obtener el item del array global
  if (!window.itemsPedido || !window.itemsPedido[itemIndex]) {
    console.error('❌ [EDITAR] Item no encontrado en itemsPedido para índice:', itemIndex);
    return;
  }

  const item = window.itemsPedido[itemIndex];
  console.log('📦 [EDITAR] Item a editar:', item);

  // Cargar datos en el modal
  if (window.cargarItemEnModal && typeof window.cargarItemEnModal === 'function') {
    console.log('✅ [EDITAR] Usando cargarItemEnModal');
    window.cargarItemEnModal(item, itemIndex);
  } else if (window.abrirModalPrendaNueva && typeof window.abrirModalPrendaNueva === 'function') {
    // Fallback: solo abrir el modal
    console.log('ℹ️ [EDITAR] cargarItemEnModal no disponible, abriendo modal vacío');
    window.abrirModalPrendaNueva();
  } else {
    console.error('❌ [EDITAR] No hay función para abrir modal');
  }
}

/**
 * Actualizar interactividad después de renderizar nuevos items
 * Llamar a esta función después de agregar nuevos items al DOM
 */
function updateItemCardInteractions() {
  console.log('🔄 [UPDATE-ITEM-CARD] updateItemCardInteractions() llamado');
  
  // Verificar que los elementos existan en el DOM
  const menuButtons = document.querySelectorAll('.btn-menu-expandible');
  console.log('🔍 [UPDATE-ITEM-CARD] Menu buttons encontrados:', menuButtons.length);
  
  const menuWrappers = document.querySelectorAll('.btn-menu-wrapper');
  console.log('🔍 [UPDATE-ITEM-CARD] Menu wrappers encontrados:', menuWrappers.length);
  
  const menuDropdowns = document.querySelectorAll('.menu-dropdown');
  console.log('🔍 [UPDATE-ITEM-CARD] Menu dropdowns encontrados:', menuDropdowns.length);
  
  // Verificar estructura de cada wrapper
  menuWrappers.forEach((wrapper, idx) => {
    console.log(`🔎 [UPDATE-ITEM-CARD] Wrapper ${idx}:`, {
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
