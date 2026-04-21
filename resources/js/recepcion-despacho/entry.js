/**
 * Recepcion Despacho Frontend Entrypoint
 *
 * Punto de entrada único para el módulo de recepción de prendas.
 * Maneja montaje del componente React y carga de datos iniciales.
 */

import React from 'react';
import { createRoot } from 'react-dom/client';
import RecepcionPrendas from './RecepcionPrendas';

const entryState = {
  isReady: false,
  version: '1.0.0-2026-04-21',
};

async function fetchRecepcionData() {
  try {
    const response = await fetch('/api/recepcion-despacho/items', {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        Accept: 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`API error: ${response.status} ${response.statusText}`);
    }

    const contentType = response.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
      throw new Error(`Expected JSON, got ${contentType}`);
    }

    return await response.json();
  } catch (error) {
    console.error('[Recepcion Entry] Error fetching data:', error);
    return [];
  }
}

function isRecepcionPage() {
  const has = Boolean(document.getElementById('recepcion-despacho-app'));
  console.log('[Recepcion Entry] isRecepcionPage:', has);
  return has;
}

async function mountRecepcionApp() {
  console.log('[Recepcion Entry] mountRecepcionApp called');
  const appContainer = document.getElementById('recepcion-despacho-app');
  console.log('[Recepcion Entry] Container found:', !!appContainer);

  if (!appContainer) {
    console.error('[Recepcion Entry] Container #recepcion-despacho-app not found');
    return;
  }

  try {
    console.log('[Recepcion Entry] Fetching initial data...');
    const response = await fetchRecepcionData();
    console.log('[Recepcion Entry] Data fetched:', response);

    // Extraer data del objeto paginado si existe
    const initialData = response.data || response;
    const pagination = response.pagination || null;
    const counts = response.counts || null;

    const root = createRoot(appContainer);
    console.log('[Recepcion Entry] React root created');

    root.render(
      React.createElement(RecepcionPrendas, {
        initialData,
        pagination,
        counts,
      })
    );

    console.log('[Recepcion Entry] Component rendered successfully');
  } catch (error) {
    console.error('[Recepcion Entry] Error mounting component:', error);
    const appContainer = document.getElementById('recepcion-despacho-app');
    if (appContainer) {
      appContainer.innerHTML = '<div style="padding: 20px; color: red; font-family: monospace;">Error loading component: ' + error.message + '</div>';
    }
  }
}

function initRecepcionEntry() {
  if (entryState.isReady) {
    return;
  }

  if (!isRecepcionPage()) {
    console.log('[Recepcion Entry] Not on recepcion page, skipping init');
    return;
  }

  mountRecepcionApp()
    .then(() => {
      entryState.isReady = true;
      window.recepcionDespachoEntry = Object.freeze({
        ...entryState,
        initializedAt: new Date().toISOString(),
      });

      document.dispatchEvent(
        new CustomEvent('recepcion-despacho:entry-ready', {
          detail: window.recepcionDespachoEntry,
        })
      );

      console.log('[Recepcion Entry] Ready', window.recepcionDespachoEntry);
    })
    .catch((error) => {
      console.error('[Recepcion Entry] Initialization failed:', error);
    });
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initRecepcionEntry);
} else {
  initRecepcionEntry();
}
