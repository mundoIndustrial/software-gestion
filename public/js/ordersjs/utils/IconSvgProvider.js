/**
 * Icon SVG Provider - Proveedor centralizado de iconos SVG
 * Contiene todos los iconos reutilizables para el modal de seguimiento
 */

const IconSvgProvider = {
  icons: {
    'description': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>',
    'inventory_2': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path></svg>',
    'content_cut': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="3"></circle><circle cx="18" cy="18" r="3"></circle><path d="M20.41 3.59l-7.06 7.06a2 2 0 01-2.83 0l-2.12-2.12a2 2 0 010-2.83l7.06-7.06a2 2 0 012.83 0l2.12 2.12a2 2 0 010 2.83z"></path></svg>',
    'brush': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.71 4.63l-1.34-1.34a1 1 0 00-1.41 0L9 12.59 10.41 14l8.3-8.3a1 1 0 000-1.41z"></path><path d="M18 13l3 3"></path><path d="M3 21l9-9"></path></svg>',
    'print': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>',
    'dry_cleaning': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v8"></path><path d="M8 12h8"></path></svg>',
    'checkroom': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7l-10-5z"></path><path d="M12 22V12"></path></svg>',
    'construction': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 21l6-6m0 0V9m0 6h6m-6-6l6-6m6 0l6 6m0 0v6m0-6h-6m6 6l-6 6"></path></svg>',
    'local_laundry_service': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7l-10-5z"></path><circle cx="12" cy="13" r="4"></circle></svg>',
    'handyman': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v7m0 0l3-3m-3 3l-3-3"></path><path d="M12 22v-7m0 0l3 3m-3-3l-3 3"></path><path d="M2 12h7m0 0l-3-3m3 3l-3 3"></path><path d="M22 12h-7m0 0l3-3m-3 3l3 3"></path></svg>',
    'verified': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
    'local_shipping': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path><polyline points="14,2 14,8 20,8"></polyline><line x1="16" y1="13" x2="16" y2="21"></line><line x1="8" y1="13" x2="8" y2="21"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
    'directions_car': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17l2-2h8l2 2M5 7l2 2h8l2-2"></path><path d="M7 12h10"></path></svg>',
    'highlight': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11H3m6 0v6m0-6l-6 6m12 0h6m-6 0v6m0-6l6 6"></path></svg>',
    'search': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>'
  },

  get: function(iconName) {
    return this.icons[iconName] || this.icons.description;
  }
};
