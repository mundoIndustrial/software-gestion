(function () {
  // Helpers compartidos por el modal de seguimiento.
  // Se definen en window.* para mantener compatibilidad con el código existente.

  window.toDateObject = window.toDateObject || function toDateObject(value) {
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
  };

  window.formatDate = window.formatDate || function formatDate(dateString) {
    if (!dateString) return null;

    try {
      const date = window.toDateObject(dateString);
      if (!date) return null;
      return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    } catch (e) {
      return null;
    }
  };

  window.formatDateTime = window.formatDateTime || function formatDateTime(dateString) {
    if (!dateString) return null;

    try {
      const date = window.toDateObject(dateString);
      if (!date) return null;

      const datePart = date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
      const timePart = date.toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });
      return `${datePart} ${timePart}`;
    } catch (e) {
      return null;
    }
  };

  window.normalizeConsecutivos = window.normalizeConsecutivos || function normalizeConsecutivos(consecutivos) {
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
  };

  // Conteo de días hábiles simple (misma idea que la tabla: empieza al día siguiente y no cuenta fines de semana)
  window.calcularDiasHabilesSimple = window.calcularDiasHabilesSimple || function calcularDiasHabilesSimple(inicio, fin) {
    if (!inicio || !fin) return 0;
    const start = inicio instanceof Date ? inicio : new Date(inicio);
    const end = fin instanceof Date ? fin : new Date(fin);
    if (isNaN(start.getTime()) || isNaN(end.getTime())) return 0;
    if (end.getTime() <= start.getTime()) return 0;

    const current = new Date(start.getTime());
    current.setDate(current.getDate() + 1);

    let totalDays = 0;
    let iterations = 0;
    const maxIterations = 3660;

    while (current.getTime() <= end.getTime() && iterations < maxIterations) {
      const day = current.getDay();
      const isWeekend = day === 0 || day === 6;
      if (!isWeekend) {
        totalDays++;
      }

      current.setDate(current.getDate() + 1);
      iterations++;
    }

    return Math.max(0, totalDays);
  };

  window.formatDurationHuman = window.formatDurationHuman || function formatDurationHuman(diffMs) {
    const totalSeconds = Math.floor((diffMs || 0) / 1000);
    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    const parts = [];
    if (days > 0) parts.push(`${days} ${days === 1 ? 'día' : 'días'}`);
    if (hours > 0) parts.push(`${hours}h`);
    if (minutes > 0) parts.push(`${minutes}m`);
    if (seconds > 0 || parts.length === 0) parts.push(`${seconds}s`);
    return parts.join(' ');
  };
})();
