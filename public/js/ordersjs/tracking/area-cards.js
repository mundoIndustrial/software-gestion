'use strict';

// Creación de tarjetas de área y componentes visuales
class AreaCards {
  constructor() {
    this.init();
  }

  init() {
    // Inicialización si es necesaria
  }

  // Crear tarjeta de área/proceso
  createAreaCard(area, data, readonly = false) {
    const card = document.createElement('div');
    
    // Si es readonly, agregar clase visual
    if (readonly) {
      card.classList.add('tracking-readonly-mode');
    }
    
    const iconSvg = this.getIconSvg(area) || this.getIconSvg('description');
    
    const fechaCompletadoDisplay = data.fecha_completado || null;
    const fechaFinParaDuracion = fechaCompletadoDisplay || data.fecha_fin || null;

    const totalDiasArea = (function() {
      const ini = typeof toDateObject === 'function' ? toDateObject(data.fecha_inicio) : null;
      
      // Si no hay fecha de fin o completado, contar hasta hoy (dinámico)
      if (!fechaFinParaDuracion) {
        if (!ini) return null;
        return typeof calcularDiasHabilesSync === 'function' 
          ? calcularDiasHabilesSync(ini, new Date())
          : 0;
      }
      
      // Si hay fecha fin/completado, contar hasta esa fecha (estático)
      const fin = typeof toDateObject === 'function' ? toDateObject(fechaFinParaDuracion) : null;
      if (!ini || !fin) return null;
      
      // Usar cálculo de días hábiles con festivos (igual que recibos-costura)
      return typeof calcularDiasHabilesSync === 'function' 
        ? calcularDiasHabilesSync(ini, fin)
        : 0;
    })();

    const isInsumos = String(area || '').toLowerCase() === 'insumos';
    const isCorte = String(area || '').toLowerCase().includes('corte');
    const isCostura = String(area || '').toLowerCase().includes('costura');
    const isControlCalidad = String(area || '').toLowerCase().includes('control') && String(area || '').toLowerCase().includes('calidad');
    
    // Procesos que requieren encargado y usan fecha_completado como fecha_fin
    const needsEncargado = isCorte || isCostura || isControlCalidad;
    const shouldShowAssignmentDuration = needsEncargado;
    
    // Determinar si se debe ocultar el campo de encargado
    const shouldHideEncargado = isInsumos || !needsEncargado;

    const hasFechaCompletado = !isInsumos && Boolean(typeof toDateObject === 'function' ? toDateObject(data.fecha_completado) : null);
    //  Usar estado_display del backend si disponible, si no calcular
    const estadoDisplay = (data.duraciones?.estado_display) || 
                          (isInsumos ? (data.estado || 'Pendiente') : (hasFechaCompletado ? 'Completado' : 'Pendiente'));
    const estaActivoDisplay = (data.duraciones?.esta_activo_display !== undefined) ? 
                             data.duraciones.esta_activo_display : 
                             (isInsumos ? Boolean(data.esta_activo) : !hasFechaCompletado);

    card.className = `tracking-area-card tracking-area-card-v2 ${estaActivoDisplay ? 'pending' : 'completed'}`;

    const formatBadgeDuration = function(diffMs) {
      const ms = Math.max(0, Number(diffMs) || 0);
      const minutes = Math.floor(ms / 60000);
      const hours = Math.floor(ms / 3600000);
      const days = Math.floor(ms / 86400000);
      
      if (days >= 1) {
        return `${days} ${days === 1 ? 'Día' : 'Días'}`;
      } else if (hours >= 1) {
        return `${hours}h`;
      } else if (minutes >= 1) {
        return `${minutes}min`;
      } else {
        return '< 1min';
      }
    };

    const fechaLlegada = typeof formatDate === 'function' ? formatDate(data.fecha_inicio) : '---';
    
    // Lógica dinámica para fecha_fin según el tipo de proceso
    let fechaFinRaw = null;
    if (isInsumos) {
      fechaFinRaw = data.fecha_fin || null;
    } else if (needsEncargado) {
      // Para Corte, Costura, Control Calidad: usar fecha_completado
      fechaFinRaw = data.fecha_completado || null;
    } else {
      // Para otros procesos (Entrega, Despacho, etc.): usar fecha_fin o determinar dinámicamente
      fechaFinRaw = data.fecha_fin || null;
      
      // Si no hay fecha_fin, podríamos intentar determinarla por el siguiente proceso
      // Esto requeriría datos adicionales de los otros procesos
    }
    
    const fechaFin = typeof formatDate === 'function' ? formatDate(fechaFinRaw) : (data.esta_activo ? '---' : '---');

    const fechaAsignacion = typeof formatDate === 'function' ? formatDate(data.fecha_de_asignacion_encargado) : '---';
    const duracionAsignacion = (function() {
      if (!shouldShowAssignmentDuration) return '---';
      const ini = typeof toDateObject === 'function' ? toDateObject(data.fecha_inicio) : null;
      const asg = typeof toDateObject === 'function' ? toDateObject(data.fecha_de_asignacion_encargado) : null;
      if (!ini || !asg) return '---';
      
      // Usar cálculo de días hábiles en lugar de diferencia simple en milisegundos
      const diasHabiles = typeof calcularDiasHabilesSync === 'function' 
        ? calcularDiasHabilesSync(ini, asg)
        : 0;
      
      return diasHabiles === 0 ? '0 días' : `${diasHabiles} día${diasHabiles !== 1 ? 's' : ''}`;
    })();

    const duracionEnArea = (function() {
      if (needsEncargado) {
        // Para procesos con encargado: calcular SOLO desde asignación
        const asg = typeof toDateObject === 'function' ? toDateObject(data.fecha_de_asignacion_encargado) : null;
        
        // Si no hay asignación, no mostrar duración en área
        if (!asg) return '---';
        
        let fin;
        // Si no hay fecha fin (fecha_completado), calcular hasta hoy (dinámico)
        if (!fechaFinRaw) {
          fin = new Date();
        } else {
          fin = typeof toDateObject === 'function' ? toDateObject(fechaFinRaw) : null;
          if (!fin) return '---';
        }

        const diasHabiles = typeof calcularDiasHabilesSync === 'function' 
          ? calcularDiasHabilesSync(asg, fin)
          : 0;
        return diasHabiles === 0 ? '0 días' : `${diasHabiles} día${diasHabiles !== 1 ? 's' : ''}`;
      } else {
        // Para procesos sin encargado: calcular desde inicio hasta fin
        const ini = typeof toDateObject === 'function' ? toDateObject(data.fecha_inicio) : null;
        
        // Si no hay fecha fin, contar hasta hoy (dinámico)
        if (!fechaFinRaw) {
          if (!ini) return '---';
          const diasHabiles = typeof calcularDiasHabilesSync === 'function' 
            ? calcularDiasHabilesSync(ini, new Date())
            : 0;
          return diasHabiles === 0 ? '0 días' : `${diasHabiles} día${diasHabiles !== 1 ? 's' : ''}`;
        }
        
        // Si hay fecha fin, contar hasta esa fecha (estático)
        const fin = typeof toDateObject === 'function' ? toDateObject(fechaFinRaw) : null;
        if (!ini || !fin) return '---';
        const diasHabiles = typeof calcularDiasHabilesSync === 'function' 
          ? calcularDiasHabilesSync(ini, fin)
          : 0;
        return diasHabiles === 0 ? '0 días' : `${diasHabiles} día${diasHabiles !== 1 ? 's' : ''}`;
      }
    })();

    const totalDiasAreaDisplay = (function() {
      console.log('[totalDiasAreaDisplay] Iniciando cálculo - area:', area, 'needsEncargado:', needsEncargado);
      console.log('[totalDiasAreaDisplay] Datos - fecha_inicio:', data.fecha_inicio, 'fecha_asignacion_encargado:', data.fecha_de_asignacion_encargado, 'fechaFinRaw:', fechaFinRaw);
      
      //  SIMPLIFICACIÓN: Si el backend ya calculó duraciones, usarlas directamente
      if (data.duraciones && typeof data.duraciones.total_dias_numero !== 'undefined') {
        const totalDias = data.duraciones.total_dias_numero;
        console.log('[totalDiasAreaDisplay] USANDO BACKEND: duraciones.total_dias_numero =', totalDias);
        return totalDias === 0 ? '0 días' : `${totalDias} día${totalDias !== 1 ? 's' : ''}`;
      }
      
      // Fallback: recalcular si no tiene duraciones (legacy)
      if (!needsEncargado) {
        console.log('[totalDiasAreaDisplay] Ejecutando caso SIN encargado');
        // Para procesos sin encargado: calcular duración total
        const ini = typeof toDateObject === 'function' ? toDateObject(data.fecha_inicio) : null;
        
        // Si no hay fecha fin, contar hasta hoy (dinámico)
        if (!fechaFinRaw) {
          if (!ini) return '---';
          const diasHabiles = typeof calcularDiasHabilesSync === 'function' 
            ? calcularDiasHabilesSync(ini, new Date())
            : 0;
          return diasHabiles === 0 ? '0 días' : `${diasHabiles} día${diasHabiles !== 1 ? 's' : ''}`;
        }
        
        // Si hay fecha fin, contar hasta esa fecha (estático)
        const fin = typeof toDateObject === 'function' ? toDateObject(fechaFinRaw) : null;
        if (!ini || !fin) return '---';
        const diasHabiles = typeof calcularDiasHabilesSync === 'function' 
          ? calcularDiasHabilesSync(ini, fin)
          : 0;
        return diasHabiles === 0 ? '0 días' : `${diasHabiles} día${diasHabiles !== 1 ? 's' : ''}`;
      }

      console.log('[totalDiasAreaDisplay] Ejecutando caso CON encargado');
      // Para procesos con encargado
      const ini = typeof toDateObject === 'function' ? toDateObject(data.fecha_inicio) : null;
      const asg = typeof toDateObject === 'function' ? toDateObject(data.fecha_de_asignacion_encargado) : null;
      
      // Si no hay fecha fin, verificar si hay asignación para decidir el cálculo
      if (!fechaFinRaw) {
        // Si hay asignación, sumar duración asignación + duración en área
        if (asg) {
          console.log('[totalDiasAreaDisplay] Caso 3 - Sin fin pero con asignación - sumando duraciones');
          // Extraer valor numérico de duracionAsignacion
          const asignacionNum = (() => {
            if (duracionAsignacion === '---') return 0;
            const match = String(duracionAsignacion).match(/(\d+)/);
            return match ? parseInt(match[1], 10) : 0;
          })();
          
          // Extraer valor numérico de duracionEnArea
          const areaNum = (() => {
            if (duracionEnArea === '---') return 0;
            const match = String(duracionEnArea).match(/(\d+)/);
            return match ? parseInt(match[1], 10) : 0;
          })();
          
          const suma = asignacionNum + areaNum;
          console.log('[totalDiasAreaDisplay] Caso 3 - asignacionNum:', asignacionNum, 'areaNum:', areaNum, 'suma:', suma);
          return suma === 0 ? '0 días' : `${suma} día${suma !== 1 ? 's' : ''}`;
        } else {
          // Si no hay asignación, calcular desde inicio hasta hoy (Caso 5)
          console.log('[totalDiasAreaDisplay] Caso 5 - Sin fin y sin asignación - calculando desde inicio hasta hoy');
          const diasTotales = typeof calcularDiasHabilesSync === 'function' 
            ? calcularDiasHabilesSync(ini, new Date())
            : 0;
          console.log('[totalDiasAreaDisplay] Caso 5 - diasTotales:', diasTotales);
          return diasTotales === 0 ? '0 días' : `${diasTotales} día${diasTotales !== 1 ? 's' : ''}`;
        }
      }

      // Si hay fecha fin, contar desde el punto de inicio hasta fin (estático)
      const fin = typeof toDateObject === 'function' ? toDateObject(fechaFinRaw) : null;
      if (!fin) {
        return totalDiasArea === null ? '---' : (totalDiasArea === 0 ? '0 días' : `${totalDiasArea} día${totalDiasArea !== 1 ? 's' : ''}`);
      }

      // Si hay fecha de asignación, sumar duración asignación + duración en área
      if (asg) {
        // Extraer valor numérico de duracionAsignacion
        const asignacionNum = (() => {
          if (duracionAsignacion === '---') return 0;
          const match = String(duracionAsignacion).match(/(\d+)/);
          return match ? parseInt(match[1], 10) : 0;
        })();
        
        // Extraer valor numérico de duracionEnArea
        const areaNum = (() => {
          if (duracionEnArea === '---') return 0;
          const match = String(duracionEnArea).match(/(\d+)/);
          return match ? parseInt(match[1], 10) : 0;
        })();
        
        const suma = asignacionNum + areaNum;
        return suma === 0 ? '0 días' : `${suma} día${suma !== 1 ? 's' : ''}`;
      }

      // Si no hay asignación, calcular desde inicio hasta fin/hoy
      const inicioCalculo = ini;
      if (!inicioCalculo) {
        console.log('[totalDiasAreaDisplay] Caso 5 - No hay fecha de inicio');
        return totalDiasArea === null ? '---' : (totalDiasArea === 0 ? '0 días' : `${totalDiasArea} día${totalDiasArea !== 1 ? 's' : ''}`);
      }

      console.log('[totalDiasAreaDisplay] Caso 5 - inicioCalculo:', inicioCalculo, 'fin:', fin);

      // Si hay fecha fin, calcular hasta fin
      if (fin) {
        const diasTotales = typeof calcularDiasHabilesSync === 'function' 
          ? calcularDiasHabilesSync(inicioCalculo, fin)
          : 0;
        console.log('[totalDiasAreaDisplay] Caso 5 - Con fin - diasTotales:', diasTotales);
        return diasTotales === 0 ? '0 días' : `${diasTotales} día${diasTotales !== 1 ? 's' : ''}`;
      } else {
        // Si no hay fecha fin, calcular desde inicio hasta hoy
        const diasTotales = typeof calcularDiasHabilesSync === 'function' 
          ? calcularDiasHabilesSync(inicioCalculo, new Date())
          : 0;
        console.log('[totalDiasAreaDisplay] Caso 5 - Sin fin - diasTotales:', diasTotales);
        return diasTotales === 0 ? '0 días' : `${diasTotales} día${diasTotales !== 1 ? 's' : ''}`;
      }
    })();

    const tiempoCompletadoDisplay = (function() {
      if (data.tiempo_transcurrido) return data.tiempo_transcurrido;
      
      const ini = typeof toDateObject === 'function' ? toDateObject(data.fecha_inicio) : null;
      if (!ini) return null;
      
      let fin;
      // Si no hay fecha completado, usar hoy (dinámico)
      if (!fechaCompletadoDisplay) {
        fin = new Date();
      } else {
        fin = typeof toDateObject === 'function' ? toDateObject(fechaCompletadoDisplay) : null;
        if (!fin) return null;
      }
      
      const diasHabiles = typeof calcularDiasHabilesSync === 'function' 
        ? calcularDiasHabilesSync(ini, fin)
        : 0;
      return diasHabiles === 0 ? '0 días' : `${diasHabiles} día${diasHabiles !== 1 ? 's' : ''}`;
    })();

    // SOLO generar botones si NO es readonly
    const accionesHtml = readonly ? '' : `${(data.id || data.can_edit) ? `
            <button class="tracking-edit-btn" onclick="${data.id ? `handleEditarProceso(${data.id}, '${area}', ${JSON.stringify(data).replace(/"/g, '&quot;')}, event)` : `handleCrearProcesoDesdeArea('${area}', event, '${String(data.encargado || '').replace(/'/g, "\\'")}')`}" title="Editar proceso">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
              </svg>
            </button>
            ${data.id ? `
            <button class="tracking-delete-btn" onclick="handleEliminarProceso(${data.id}, '${area}', event)" title="Eliminar proceso">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14zM10 11v6M14 11v6"/>
              </svg>
            </button>
            ` : ''}
            ` : ''}`;

    if (isInsumos) {
      card.innerHTML = `
        <div class="tracking-area-v2-left">
          <div class="tracking-area-v2-icon">${iconSvg}</div>
          <div class="tracking-area-v2-name">${area}</div>
        </div>

        <div class="tracking-area-v2-body">
          <div class="tracking-area-v2-row">
            <div class="tracking-area-v2-field">
              <div class="tracking-area-v2-label">Fecha de llegada:</div>
              <div class="tracking-area-v2-pill">${fechaLlegada}</div>
            </div>
            <div class="tracking-area-v2-field">
              <div class="tracking-area-v2-label">Fecha de envío a producción</div>
              <div class="tracking-area-v2-pill">${fechaFin}</div>
            </div>
            <div class="tracking-area-v2-field tracking-area-v2-field-right"></div>
          </div>

          <div class="tracking-area-v2-footer">
            <div class="tracking-area-v2-status">
              <span class="tracking-days-badge ${estaActivoDisplay ? '' : 'tracking-days-badge-zero'}">${estadoDisplay}</span>
            </div>
            <div class="tracking-area-v2-actions">${accionesHtml}</div>
            <div class="tracking-area-v2-total-days">
              <span class="tracking-area-v2-total-label">Total Días:</span>
              <span class="tracking-area-v2-total-value">${totalDiasAreaDisplay}</span>
            </div>
          </div>
        </div>
      `;
    } else {
      card.innerHTML = `
        <div class="tracking-area-v2-left">
          <div class="tracking-area-v2-icon">${iconSvg}</div>
          <div class="tracking-area-v2-name">${area}</div>
        </div>

        <div class="tracking-area-v2-body">
          <div class="tracking-area-v2-row">
            <div class="tracking-area-v2-field">
              <div class="tracking-area-v2-label">Fecha de llegada:</div>
              <div class="tracking-area-v2-pill">${fechaLlegada}</div>
            </div>
            ${!data.encargado || data.encargado.trim() === '' ? `
            <div class="tracking-area-v2-field tracking-area-v2-field-right">
              <div class="tracking-area-v2-label">Fecha fin</div>
              <div class="tracking-area-v2-pill">${fechaFin}</div>
            </div>
            ` : `
            <div class="tracking-area-v2-field">
              <div class="tracking-area-v2-label">Fecha de asignación de ${String(area).toLowerCase()}:</div>
              <div class="tracking-area-v2-pill">${fechaAsignacion}</div>
            </div>
            <div class="tracking-area-v2-field tracking-area-v2-field-right">
              <div class="tracking-area-v2-label">Duración asignación de ${String(area).toLowerCase()}:</div>
              <div class="tracking-area-v2-badge">${duracionAsignacion}</div>
            </div>
            `}
          </div>

          <div class="tracking-area-v2-row">
            ${!data.encargado || data.encargado.trim() === '' ? '' : `
            ${shouldHideEncargado || data.hide_encargado ? '' : `
            <div class="tracking-area-v2-field">
              <div class="tracking-area-v2-label">Encargado:</div>
              <div class="tracking-area-v2-pill">${data.encargado || '---'}</div>
            </div>
            `}
            <div class="tracking-area-v2-field">
              <div class="tracking-area-v2-label">Fecha fin</div>
              <div class="tracking-area-v2-pill">${fechaFin}</div>
            </div>
            <div class="tracking-area-v2-field tracking-area-v2-field-right">
              <div class="tracking-area-v2-label">Duración en ${area}</div>
              <div class="tracking-area-v2-badge">${duracionEnArea}</div>
            </div>
            `}
          </div>

          <div class="tracking-area-v2-footer">
            <div class="tracking-area-v2-status">
              <span class="tracking-days-badge ${estaActivoDisplay ? '' : 'tracking-days-badge-zero'}">${estadoDisplay}</span>
            </div>
            <div class="tracking-area-v2-actions">${accionesHtml}</div>
            <div class="tracking-area-v2-total-days">
              <span class="tracking-area-v2-total-label">Total Días:</span>
              <span class="tracking-area-v2-total-value">${totalDiasAreaDisplay}</span>
            </div>
          </div>
        </div>
      `;
    }
    
    return card;
  }

  // Obtener SVG del icono
  getIconSvg(iconName) {
    const icons = {
      // Iconos genéricos existentes
      'description': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>',
      'inventory_2': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path></svg>',
      'content_cut': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="3"></circle><circle cx="18" cy="18" r="3"></circle><path d="M20.41 3.59l-7.06 7.06a2 2 0 01-2.83 0l-2.12-2.12a2 2 0 010-2.83l7.06-7.06a2 2 0 012.83 0l2.12 2.12a2 2 0 010 2.83z"></path></svg>',
      'brush': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.71 4.63l-1.34-1.34a1 1 0 00-1.41 0L9 12.59 10.41 14l8.3-8.3a1 1 0 000-1.41z"></path><path d="M18 13l3 3"></path><path d="M3 21l9-9"></path></svg>',
      'print': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>',
      'dry_cleaning': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v8"></path><path d="M8 12h8"></path></svg>',
      'checkroom': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7l-10-5z"></path><path d="M12 22V12"></path></svg>',
      'construction': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 21l6-6m0 0V9m0 6h6m-6-6l6-6m6 0l6 6m0 0v6m0-6h-6m6 6l-6 6"></path></svg>',
      'local_laundry_service': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7l-10-5z"></path><circle cx="12" cy="13" r="4"></circle></svg>',
      
      // Iconos específicos para áreas
      'Corte': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M3 17a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M8.6 8.6l10.4 10.4" /><path d="M8.6 15.4l10.4 -10.4" /></svg>',
      'Bordado': '<svg viewBox="0 0 24 24" fill="currentColor"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 2c5.498 0 10 4.002 10 9c0 1.351 -.6 2.64 -1.654 3.576c-1.03 .914 -2.412 1.424 -3.846 1.424h-2.516a1 1 0 0 0 -.5 1.875a1 1 0 0 1 .194 .14a2.3 2.3 0 0 1 -1.597 3.99l-.156 -.009l.068 .004l-.273 -.004c-5.3 -.146 -9.57 -4.416 -9.716 -9.716l-.004 -.28c0 -5.523 4.477 -10 10 -10m-3.5 6.5a2 2 0 0 0 -1.995 1.85l-.005 .15a2 2 0 1 0 2 -2m8 0a2 2 0 0 0 -1.995 1.85l-.005 .15a2 2 0 1 0 2 -2m-4 -3a2 2 0 0 0 -1.995 1.85l-.005 .15a2 2 0 1 0 2 -2" /></svg>',
      'Estampado': '<svg viewBox="0 0 24 24" fill="currentColor"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 2c5.498 0 10 4.002 10 9c0 1.351 -.6 2.64 -1.654 3.576c-1.03 .914 -2.412 1.424 -3.846 1.424h-2.516a1 1 0 0 0 -.5 1.875a1 1 0 0 1 .194 .14a2.3 2.3 0 0 1 -1.597 3.99l-.156 -.009l.068 .004l-.273 -.004c-5.3 -.146 -9.57 -4.416 -9.716 -9.716l-.004 -.28c0 -5.523 4.477 -10 10 -10m-3.5 6.5a2 2 0 0 0 -1.995 1.85l-.005 .15a2 2 0 1 0 2 -2m8 0a2 2 0 0 0 -1.995 1.85l-.005 .15a2 2 0 1 0 2 -2m-4 -3a2 2 0 0 0 -1.995 1.85l-.005 .15a2 2 0 1 0 2 -2" /></svg>',
      'Costura': '<svg viewBox="0 0 24 24" fill="currentColor"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14.883 3.007l.095 -.007l.112 .004l.113 .017l.113 .03l6 2a1 1 0 0 1 .677 .833l.007 .116v5a1 1 0 0 1 -.883 .993l-.117 .007h-2v7a2 2 0 0 1 -1.85 1.995l-.15 .005h-10a2 2 0 0 1 -1.995 -1.85l-.005 -.15v-7h-2a1 1 0 0 1 -.993 -.883l-.007 -.117v-5a1 1 0 0 1 .576 -.906l.108 -.043l6 -2a1 1 0 0 1 1.316 .949a2 2 0 0 0 3.995 .15l.009 -.24l.017 -.113l.037 -.134l.044 -.103l.05 -.092l.068 -.093l.069 -.08c.056 -.054 .113 -.1 .175 -.14l.096 -.053l.103 -.044l.108 -.032l.112 -.02z" /></svg>',
      'Taller': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21h18"/><path d="M5.5 21l-1.5 -6l6 -1"/><path d="M18.5 21l1.5 -6l-6 -1"/><path d="M8 12l4 -4l4 4"/><path d="M12 8v13"/></svg>',
      'Lavandería': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M12 3v6"/></svg>',
      'Control de Calidad': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 12l5 5l10 -10"/></svg>',
      'Despacho': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 2l8 4.5v9l-8 4.5l-8 -4.5v-9z"/><path d="M12 12l8 -4.5"/><path d="M12 12v9"/><path d="M12 12l-8 -4.5"/></svg>',
      'Entrega': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 21l-8 -4.5v-9l8 -4.5l8 4.5v4.5" /><path d="M12 12l8 -4.5" /><path d="M12 12v9" /><path d="M12 12l-8 -4.5" /><path d="M15 18h7" /><path d="M19 15l3 3l-3 3" /></svg>',
      'Insumos': '<svg viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"><line x1="20" y1="140" x2="180" y2="140" /><line x1="20" y1="40" x2="20" y2="140" /><line x1="20" y1="40" x2="60" y2="40" /><circle cx="60" cy="170" r="15" /><circle cx="140" cy="170" r="15" /><rect x="40" y="90" width="50" height="40" /><rect x="55" y="90" width="20" height="10" /><rect x="100" y="90" width="50" height="40" /><rect x="115" y="90" width="20" height="10" /><rect x="75" y="50" width="50" height="40" /><rect x="90" y="50" width="20" height="10" /></svg>'
    };
    
    return icons[iconName] || icons['description'];
  }

  // Renderizar badges de seguimientos por tipo de recibo
  renderSeguimientosBadges(seguimientos) {
    if (Object.keys(seguimientos).length === 0) {
      return '';
    }
    
    let badgesHtml = '<div class="tracking-prenda-seguimientos">';
    
    Object.entries(seguimientos).forEach(([tipo, data]) => {
      const statusClass = data.tiene_disponibles ? 'pendiente' : 'completado';
      
      badgesHtml += `
        <span class="tracking-seguimiento-badge ${statusClass}">
          ${tipo}: ${data.consecutivo_actual}/${data.consecutivo_inicial}
        </span>
      `;
    });
    
    badgesHtml += '</div>';
    return badgesHtml;
  }

  // Renderizar badges de áreas/procesos
  renderAreasBadges(areas) {
    if (Object.keys(areas).length === 0) {
      return '';
    }
    
    let badgesHtml = '<div class="tracking-prenda-areas">';
    
    Object.entries(areas).forEach(([area, data]) => {
      const statusClass = data.esta_activo ? 'pendiente' : 'completado';
      
      badgesHtml += `
        <span class="tracking-seguimiento-badge ${statusClass}">
          ${area}: ${data.estado}
        </span>
      `;
    });
    
    badgesHtml += '</div>';
    return badgesHtml;
  }

  // Crear tabla simple de prenda (estilo TNS)
  createPrendaCard(prenda, index) {
    const card = document.createElement('div');
    card.className = 'tracking-prenda-table';
    
    // Añadir event listener con debug
    card.addEventListener('click', (e) => {
      console.log('[createPrendaCard] Click en tabla de prenda:', prenda);
      e.preventDefault();
      e.stopPropagation();
      if (typeof showPrendaTracking === 'function') {
        showPrendaTracking(prenda);
      }
    });
    
    const seguimientosHtml = this.renderSeguimientosBadges(prenda.seguimientos || {});
    const areasHtml = this.renderAreasBadges(prenda.seguimientos_por_area || {});
    
    // Construir HTML de procesos en formato de fila
    let procesosHtml = '';
    if (prenda.procesos && prenda.procesos.length > 0) {
      procesosHtml = '<tr><td colspan="2"><div class="tracking-procesos-lista">';
      prenda.procesos.forEach(proceso => {
        // Acceder correctamente a los datos del tipo_proceso
        const tipoProceso = proceso.tipo_proceso;
        const procesoNombre = tipoProceso?.nombre || 'Proceso';
        const procesoEstado = proceso.estado || 'PENDIENTE';
        
        console.log('[createPrendaCard] Proceso:', proceso);
        console.log('[createPrendaCard] TipoProceso:', tipoProceso);
        
        procesosHtml += `
          <div class="tracking-proceso-item">
            <span class="proceso-nombre">${procesoNombre}</span>
            <span class="proceso-estado">${procesoEstado}</span>
          </div>
        `;
      });
      procesosHtml += '</div></td></tr>';
    }

    // Badge de bodega si aplica
    let bodegaBadge = '';
    if (prenda.de_bodega) {
      bodegaBadge = '<tr><td colspan="2"><div class="tracking-bodega-indicador">Se saca de bodega</div></td></tr>';
    }

    card.innerHTML = `
      <table class="tracking-table">
        <thead>
          <tr>
            <th colspan="2" class="tracking-table-header">
              ${prenda.nombre_prenda || `Prenda ${index + 1}`}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr class="tracking-table-row">
            <td class="tracking-table-label">Cantidad:</td>
            <td class="tracking-table-value">${prenda.cantidad || 0}</td>
          </tr>
          <tr class="tracking-table-row">
            <td class="tracking-table-label">Procesos:</td>
            <td class="tracking-table-value">${prenda.total_procesos || 0}</td>
          </tr>
          ${procesosHtml}
          ${bodegaBadge}
          ${seguimientosHtml ? `<tr><td colspan="2">${seguimientosHtml}</td></tr>` : ''}
          ${areasHtml ? `<tr><td colspan="2">${areasHtml}</td></tr>` : ''}
        </tbody>
      </table>
    `;
    
    return card;
  }
}

// Exportar para uso global
window.AreaCards = AreaCards;
window.areaCards = new AreaCards();

// Funciones globales para compatibilidad
window.createAreaCard = (area, data, readonly) => window.areaCards.createAreaCard(area, data, readonly);
window.renderSeguimientosBadges = (seguimientos) => window.areaCards.renderSeguimientosBadges(seguimientos);
window.renderAreasBadges = (areas) => window.areaCards.renderAreasBadges(areas);
window.createPrendaCard = (prenda, index) => window.areaCards.createPrendaCard(prenda, index);
