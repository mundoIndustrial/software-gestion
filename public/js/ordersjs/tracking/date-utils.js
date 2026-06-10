'use strict';

// Proteccion contra redeclaraciones si el script se carga multiples veces
if (typeof DateUtils !== 'undefined') {
  console.warn('[date-utils.js] DateUtils ya fue declarado, omitiendo redeclaracion');
} else {
  // Utilidades para manejo de fechas y calculo de di­as habiles
  class DateUtils {
  constructor() {
    this.festivosCache = new Map();
    this.festivosInFlight = new Map();
    this.precargaFestivosPromise = null;
  }

  // Formatear fecha
  formatDate(dateString) {
    if (!dateString) return '---';
    
    try {
      // YYYY-MM-DD: tratar como fecha local para evitar corrimiento por zona horaria
      if (typeof dateString === 'string') {
        const ymdMatch = dateString.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (ymdMatch) {
          const [, year, month, day] = ymdMatch;
          const date = new Date(
            parseInt(year, 10),
            parseInt(month, 10) - 1,
            parseInt(day, 10),
            12,
            0,
            0
          );
          return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          });
        }
      }

      // Si el formato es d/m/Y, convertirlo a Y-m-d para el constructor Date
      if (typeof dateString === 'string' && dateString.includes('/')) {
        const parts = dateString.split('/');
        if (parts.length === 3) {
          const [day, month, year] = parts;
          // Crear fecha en formato Y-m-d
          const isoDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
          const date = new Date(isoDate);
          return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          });
        }
      }
      
      // Para formatos estandar (ISO, etc.)
      const date = new Date(dateString);
      return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    } catch (error) {
      console.warn('[formatDate] Error formateando fecha:', dateString, error);
      return '---';
    }
  }

  // Formatear fecha con hora (mostrar fecha + hora completa)
  formatDateTime(dateString) {
    if (!dateString) return '---';

    try {
      const raw = (dateString && typeof dateString === 'object' && dateString.date)
        ? dateString.date
        : dateString;

      const date = raw instanceof Date ? raw : new Date(raw);
      if (isNaN(date.getTime())) return '---';

      return date.toLocaleString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
      });
    } catch (error) {
      console.warn('[formatDateTime] Error formateando fecha:', dateString, error);
      return '---';
    }
  }

  // Formatear fecha con hora en formato AM/PM americano
  formatDateWithAmPm(dateString) {
    if (!dateString) return '---';

    try {
      const raw = (dateString && typeof dateString === 'object' && dateString.date)
        ? dateString.date
        : dateString;

      const date = raw instanceof Date ? raw : new Date(raw);
      if (isNaN(date.getTime())) return '---';

      // Formatear fecha en formato es-ES (dd/mm/yyyy)
      const fechaFormato = date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });

      // Formatear hora en formato 12 horas con AM/PM
      const horaFormato = date.toLocaleString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
      });

      return `${fechaFormato} ${horaFormato}`;
    } catch (error) {
      console.warn('[formatDateWithAmPm] Error formateando fecha:', dateString, error);
      return '---';
    }
  }

  // Convertir a objeto Date
  toDateObject(value) {
    if (!value) return null;
    try {
      const raw = (value && typeof value === 'object' && value.date)
        ? value.date
        : value;
      const date = raw instanceof Date ? raw : new Date(raw);
      if (isNaN(date.getTime())) return null;
      return date;
    } catch (e) {
      return null;
    }
  }

  // Normalizar consecutivos (puede venir como array o como objeto indexado)
  normalizeConsecutivos(consecutivos) {
    if (!consecutivos) return [];
    if (Array.isArray(consecutivos)) return consecutivos;

    if (typeof consecutivos === 'object') {
      try {
        return Object.values(consecutivos).filter(Boolean);
      } catch (e) {
        return [];
      }
    }

    return [];
  }

  // Formatear duracion humana
  formatDurationHuman(diffMs) {
    const totalSeconds = Math.floor((diffMs || 0) / 1000);
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    const parts = [];
    if (days > 0) parts.push(`${days} ${days === 1 ? 'dí­a' : 'dí­as'}`);
    if (hours > 0) parts.push(`${hours}h`);
    if (minutes > 0) parts.push(`${minutes}m`);
    if (seconds > 0 || parts.length === 0) parts.push(`${seconds}s`);
    return parts.join(' ');
  }

  // Precargar festivos del ano actual y siguiente
  async precargarFestivos() {
    const anioActual = new Date().getFullYear();
    const anioSiguiente = anioActual + 1;

    if (this.festivosCache.has(anioActual) && this.festivosCache.has(anioSiguiente)) {
      console.log('[precargarFestivos] Cache hit: festivos ya precargados');
      return Promise.resolve({
        source: 'cache',
        years: [anioActual, anioSiguiente]
      });
    }

    if (this.precargaFestivosPromise) {
      console.log('[precargarFestivos] Reutilizando promise en curso (in-flight)');
      return this.precargaFestivosPromise;
    }

    console.log('[precargarFestivos] Iniciando primer preload real');
    this.precargaFestivosPromise = (async () => {
      try {
        await Promise.all([
          this.obtenerFestivos(anioActual),
          this.obtenerFestivos(anioSiguiente)
        ]);
        console.log('[precargarFestivos] Festivos precargados correctamente');
        return {
          source: 'network-or-cache',
          years: [anioActual, anioSiguiente]
        };
      } catch (error) {
        console.warn('[precargarFestivos] Error precargando festivos:', error);
        throw error;
      } finally {
        this.precargaFestivosPromise = null;
      }
    })();

    return this.precargaFestivosPromise;
  }

  // Obtener festivos desde la API (con cache)
  async obtenerFestivos(anio) {
    if (this.festivosCache.has(anio)) {
      return this.festivosCache.get(anio);
    }

    if (this.festivosInFlight.has(anio)) {
      return this.festivosInFlight.get(anio);
    }

    // En entorno de prueba (file://), usar directamente los festivos fijos
    if (window.location.protocol === 'file:') {
      console.log('[obtenerFestivos] Entorno de prueba detectado, usando festivos fijos');
      const festivosFijos = [
        `${anio}-01-01`, // Año Nuevo
        `${anio}-05-01`, // Día del Trabajo
        `${anio}-07-01`, // Día de la Independencia
        `${anio}-07-20`, // Grito de Independencia
        `${anio}-08-07`, // Batalla de Boyacá
        `${anio}-12-08`, // Inmaculada Concepción
        `${anio}-12-25`, // Navidad
      ];

      this.festivosCache.set(anio, festivosFijos);
      return festivosFijos;
    }

    const requestPromise = (async () => {
      try {
        const response = await fetch(`/api/festivos?year=${anio}`);
        if (!response.ok) throw new Error('Error al obtener festivos');

        const data = await response.json();
        if (data.success && data.data) {
          this.festivosCache.set(anio, data.data);
          return data.data;
        }

        // Fallback: festivos fijos colombianos si la API falla
        const festivosFijos = [
          `${anio}-01-01`, // Año Nuevo
          `${anio}-05-01`, // Día del Trabajo
          `${anio}-07-01`, // Día de la Independencia
          `${anio}-07-20`, // Grito de Independencia
          `${anio}-08-07`, // Batalla de Boyacá
          `${anio}-12-08`, // Inmaculada Concepción
          `${anio}-12-25`, // Navidad
        ];

        this.festivosCache.set(anio, festivosFijos);
        return festivosFijos;
      } catch (error) {
        console.warn('[obtenerFestivos] Error obteniendo festivos, usando fallback:', error);

        // Fallback: festivos fijos colombianos
        const festivosFijos = [
          `${anio}-01-01`, // Año Nuevo
          `${anio}-05-01`, // Día del Trabajo
          `${anio}-07-01`, // Día de la Independencia
          `${anio}-07-20`, // Grito de Independencia
          `${anio}-08-07`, // Batalla de Boyacá
          `${anio}-12-08`, // Inmaculada Concepción
          `${anio}-12-25`, // Navidad
        ];

        this.festivosCache.set(anio, festivosFijos);
        return festivosFijos;
      } finally {
        this.festivosInFlight.delete(anio);
      }
    })();

    this.festivosInFlight.set(anio, requestPromise);
    return requestPromise;
  }

  // Calcular di­as habiles entre dos fechas (replicando logica exacta del backend)
  async calcularDiasHabiles(fechaInicio, fechaFin) {
    if (!fechaInicio || !fechaFin) return 0;

    const inicio = fechaInicio instanceof Date ? fechaInicio : new Date(fechaInicio);
    const fin = fechaFin instanceof Date ? fechaFin : new Date(fechaFin);

    // Validar fechas
    if (isNaN(inicio.getTime()) || isNaN(fin.getTime())) return 0;
    if (fin < inicio) return 0;
    
    // Si las fechas son iguales, retornar 0 (no cuenta el mismo di­a)
    if (inicio.toDateString() === fin.toDateString()) return 0;

    try {
      // Obtener festivos del año de inicio
      let festivos = await this.obtenerFestivos(inicio.getFullYear());
      
      // Agregar festivos del siguiente año si es necesario
      if (fin.getFullYear() > inicio.getFullYear()) {
        const festivosSiguiente = await this.obtenerFestivos(fin.getFullYear());
        festivos = [...festivos, ...festivosSiguiente];
      }

      let diasHabiles = 0;
      const actual = new Date(inicio);
      
      // Iterar desde la fecha de inicio hasta la fecha fin (inclusive)
      while (actual <= fin) {
        // Verificar si no es sabado (6) ni domingo (0)
        if (actual.getDay() !== 0 && actual.getDay() !== 6) {
          // Verificar si no es festivo
          const fechaStr = actual.toISOString().slice(0, 10);
          if (!festivos.includes(fechaStr)) {
            diasHabiles++;
          }
        }
        
        actual.setDate(actual.getDate() + 1);
      }

      // Excluir el di­a de inicio solo si es un di­a habil (si cae en fin de semana/festivo ya no fue contado)
      const inicioStr = inicio.toISOString().slice(0, 10);
      const inicioEsDiaHabil = inicio.getDay() !== 0 && inicio.getDay() !== 6 && !festivos.includes(inicioStr);
      return Math.max(0, diasHabiles - (inicioEsDiaHabil ? 1 : 0));
    } catch (error) {
      console.error('[calcularDiasHabiles] Error:', error);
      // Fallback a calculo simple sin festivos
      return this.calcularDiasHabilesSimple(fechaInicio, fechaFin);
    }
  }

  // Version si­ncrona para compatibilidad (usa cache o fallback)
  calcularDiasHabilesSync(fechaInicio, fechaFin) {
    if (!fechaInicio || !fechaFin) return 0;

    const inicio = fechaInicio instanceof Date ? fechaInicio : new Date(fechaInicio);
    const fin = fechaFin instanceof Date ? fechaFin : new Date(fechaFin);

    // Validar fechas
    if (isNaN(inicio.getTime()) || isNaN(fin.getTime())) return 0;
    if (fin < inicio) return 0;
    
    // Si las fechas son iguales, retornar 0 (no cuenta el mismo di­a)
    if (inicio.toDateString() === fin.toDateString()) return 0;

    // Usar festivos del cache si estan disponibles, sino usar fallback
    const anio = inicio.getFullYear();
    let festivos = this.festivosCache.get(anio);
    
    if (!festivos) {
      // Fallback: festivos fijos colombianos
      festivos = [
        `${anio}-01-01`, // Ano Nuevo
        `${anio}-05-01`, // Di­a del Trabajo
        `${anio}-07-01`, // Dia de la Independencia
        `${anio}-07-20`, // Grito de Independencia
        `${anio}-08-07`, // Batalla de Boyaca
        `${anio}-12-08`, // Inmaculada Concepcion
        `${anio}-12-25`, // Navidad
      ];
    }

    // Agregar festivos del siguiente ano si es necesario
    if (fin.getFullYear() > inicio.getFullYear()) {
      const festivosSiguiente = this.festivosCache.get(fin.getFullYear()) || [
        `${fin.getFullYear()}-01-01`,
        `${fin.getFullYear()}-05-01`,
        `${fin.getFullYear()}-07-01`,
        `${fin.getFullYear()}-07-20`,
        `${fin.getFullYear()}-08-07`,
        `${fin.getFullYear()}-12-08`,
        `${fin.getFullYear()}-12-25`,
      ];
      festivos = [...festivos, ...festivosSiguiente];
    }

    let diasHabiles = 0;
    const actual = new Date(inicio);
    
    // Iterar desde la fecha de inicio hasta la fecha fin (inclusive)
    while (actual <= fin) {
      // Verificar si no es sabado (6) ni domingo (0)
      if (actual.getDay() !== 0 && actual.getDay() !== 6) {
        // Verificar si no es festivo
        const fechaStr = actual.toISOString().slice(0, 10);
        if (!festivos.includes(fechaStr)) {
          diasHabiles++;
        }
      }
      
      actual.setDate(actual.getDate() + 1);
    }

    // Excluir el di­a de inicio solo si es un di­a hbil (si cae en fin de semana/festivo ya no fue contado)
    const inicioStr = inicio.toISOString().slice(0, 10);
    const inicioEsDiaHabil = inicio.getDay() !== 0 && inicio.getDay() !== 6 && !festivos.includes(inicioStr);
    return Math.max(0, diasHabiles - (inicioEsDiaHabil ? 1 : 0));
  }

  // Calculo simple sin festivos (fallback)
  calcularDiasHabilesSimple(fechaInicio, fechaFin) {
    if (!fechaInicio || !fechaFin) return 0;

    const inicio = fechaInicio instanceof Date ? fechaInicio : new Date(fechaInicio);
    const fin = fechaFin instanceof Date ? fechaFin : new Date(fechaFin);

    if (isNaN(inicio.getTime()) || isNaN(fin.getTime())) return 0;
    if (fin < inicio) return 0;
    if (inicio.toDateString() === fin.toDateString()) return 0;

    let diasHabiles = 0;
    const actual = new Date(inicio);
    
    while (actual <= fin) {
      if (actual.getDay() !== 0 && actual.getDay() !== 6) {
        diasHabiles++;
      }
      actual.setDate(actual.getDate() + 1);
    }

    const inicioEsDiaHabil = inicio.getDay() !== 0 && inicio.getDay() !== 6;
    return Math.max(0, diasHabiles - (inicioEsDiaHabil ? 1 : 0));
  }
}

// Exportar para uso global
window.DateUtils = DateUtils;
window.dateUtils = new DateUtils();

// Funciones globales para compatibilidad (sin recursion)
window.formatDate = function(dateString) {
  return window.dateUtils.formatDate(dateString);
};
window.formatDateTime = function(dateString) {
  return window.dateUtils.formatDateTime(dateString);
};
window.formatDateWithAmPm = function(dateString) {
  return window.dateUtils.formatDateWithAmPm(dateString);
};
window.toDateObject = function(value) {
  return window.dateUtils.toDateObject(value);
};
window.normalizeConsecutivos = function(consecutivos) {
  return window.dateUtils.normalizeConsecutivos(consecutivos);
};
window.formatDurationHuman = function(diffMs) {
  return window.dateUtils.formatDurationHuman(diffMs);
};
window.precargarFestivos = function() {
  return window.dateUtils.precargarFestivos();
};
window.obtenerFestivos = function(anio) {
  return window.dateUtils.obtenerFestivos(anio);
};
window.calcularDiasHabiles = function(fechaInicio, fechaFin) {
  return window.dateUtils.calcularDiasHabiles(fechaInicio, fechaFin);
};
window.calcularDiasHabilesSync = function(fechaInicio, fechaFin) {
  return window.dateUtils.calcularDiasHabilesSync(fechaInicio, fechaFin);
};

} // Cierre del else - proteccion contra redeclaraciones
