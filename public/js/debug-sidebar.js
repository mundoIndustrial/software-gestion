// Debug script para analizar el comportamiento del sidebar y container
function debugSidebarWidths() {
  const sidebar = document.querySelector('.sidebar');
  const container = document.querySelector('.container');
  const mainContent = document.querySelector('.main-content');
  const pageContent = document.querySelector('.page-content');
  const table = document.querySelector('table');
  const tableWrapper = document.querySelector('.table-responsive, [class*="table"]');

  console.log('=== SIDEBAR DEBUG INFO ===');
  console.log('Timestamp:', new Date().toLocaleTimeString());
  
  if (sidebar) {
    console.log('\nSIDEBAR:');
    const sidebarStyles = window.getComputedStyle(sidebar);
    console.log('  Width:', sidebarStyles.width);
    console.log('  Margin-left:', sidebarStyles.marginLeft);
    console.log('  Classes:', sidebar.className);
    console.log('  Is collapsed:', sidebar.classList.contains('collapsed'));
    console.log('  --sidebar-width-desktop:', getComputedStyle(document.documentElement).getPropertyValue('--sidebar-width-desktop').trim());
    console.log('  --sidebar-width-collapsed:', getComputedStyle(document.documentElement).getPropertyValue('--sidebar-width-collapsed').trim());
  }

  if (container) {
    console.log('\nCONTAINER:');
    const containerStyles = window.getComputedStyle(container);
    console.log('  Width:', containerStyles.width);
    console.log('  Margin-left:', containerStyles.marginLeft);
    console.log('  Display:', containerStyles.display);
    console.log('  Classes:', container.className);
    console.log('  Actual offsetWidth:', container.offsetWidth);
    console.log('  Actual clientWidth:', container.clientWidth);
  }

  if (mainContent) {
    console.log('\nMAIN-CONTENT:');
    const mainStyles = window.getComputedStyle(mainContent);
    console.log('  Width:', mainStyles.width);
    console.log('  Actual offsetWidth:', mainContent.offsetWidth);
    console.log('  Actual clientWidth:', mainContent.clientWidth);
  }

  if (pageContent) {
    console.log('\nPAGE-CONTENT:');
    const pageStyles = window.getComputedStyle(pageContent);
    console.log('  Width:', pageStyles.width);
    console.log('  Padding:', pageStyles.padding);
    console.log('  Actual offsetWidth:', pageContent.offsetWidth);
    console.log('  Actual clientWidth:', pageContent.clientWidth);
    console.log('  Overflow-x:', pageStyles.overflowX);
  }

  if (table) {
    console.log('\nTABLE:');
    const tableStyles = window.getComputedStyle(table);
    console.log('  Width:', tableStyles.width);
    console.log('  Display:', tableStyles.display);
    console.log('  Actual offsetWidth:', table.offsetWidth);
    console.log('  Actual clientWidth:', table.clientWidth);
    console.log('  Parent width:', table.parentElement.offsetWidth);
    console.log('  Parent classes:', table.parentElement.className);
  }

  if (tableWrapper) {
    console.log('\nTABLE WRAPPER:');
    const wrapperStyles = window.getComputedStyle(tableWrapper);
    console.log('  Width:', wrapperStyles.width);
    console.log('  Display:', wrapperStyles.display);
    console.log('  Overflow-x:', wrapperStyles.overflowX);
    console.log('  Actual offsetWidth:', tableWrapper.offsetWidth);
    console.log('  Actual clientWidth:', tableWrapper.clientWidth);
    console.log('  ScrollWidth:', tableWrapper.scrollWidth);
  }

  // Viewport info
  console.log('\nVIEWPORT:');
  console.log('  Window innerWidth:', window.innerWidth);
  console.log('  Document documentElement.clientWidth:', document.documentElement.clientWidth);

  // Check body
  const body = document.body;
  console.log('\nBODY:');
  const bodyStyles = window.getComputedStyle(body);
  console.log('  Width:', bodyStyles.width);
  console.log('  Overflow-x:', bodyStyles.overflowX);
  console.log('  Actual offsetWidth:', body.offsetWidth);
  console.log('  Actual scrollWidth:', body.scrollWidth);

  console.log('\n=== END DEBUG INFO ===\n');
}

// Ejecutar cuando el DOM esté listo
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    setTimeout(debugSidebarWidths, 500);
  });
} else {
  setTimeout(debugSidebarWidths, 500);
}

// También ejecutar cuando se toggle el sidebar
const observeSidebarToggle = () => {
  const sidebar = document.querySelector('.sidebar');
  if (sidebar) {
    const observer = new MutationObserver(() => {
      console.log('\n*** SIDEBAR TOGGLE DETECTED ***');
      setTimeout(debugSidebarWidths, 100);
    });
    observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
  }
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', observeSidebarToggle);
} else {
  observeSidebarToggle();
}
