/**
 * DateFormatterFacade - Semantic wrapper for dateUtils
 * 
 * Responsibility: Provide domain-friendly names for date formatting operations.
 * Acts as an adapter between the infrastructure layer (dateUtils) and the 
 * application/presentation layers.
 * 
 * Pattern: Facade Pattern
 * - Simplifies the interface of dateUtils
 * - Provides semantic naming tied to business domain
 * - Single point of change for date/time logic
 * 
 * Usage:
 *   const dateFacade = new DateFormatterFacade(dateUtils);
 *   const formatted = dateFacade.formatOrderDate('2026-03-24');
 *   const duration = dateFacade.formatDuration(milliseconds);
 */

class DateFormatterFacade {
  constructor(dateUtils) {
    if (!dateUtils) {
      throw new Error('[DateFormatterFacade] dateUtils es requerido');
    }
    this.dateUtils = dateUtils;
  }

  /**
   * Format date for order display (es: "24 de marzo de 2026")
   * @param {string} dateString - Date to format
   * @returns {string} Formatted date
   */
  formatOrderDate(dateString) {
    return this.dateUtils.formatDate(dateString);
  }

  /**
   * Format date and time together (es: "24 de marzo de 2026, 14:30")
   * @param {string} dateString - Date/time to format
   * @returns {string} Formatted date and time
   */
  formatDeliveryDateTime(dateString) {
    return this.dateUtils.formatDateTime(dateString);
  }

  /**
   * Normalize process consecutive numbers (es: ["001", "#002"] → ["001", "002"])
   * @param {array} consecutivos - Array of consecutive numbers
   * @returns {array} Normalized consecutivos
   */
  normalizeProcessConsecutivos(consecutivos) {
    return this.dateUtils.normalizeConsecutivos(consecutivos);
  }

  /**
   * Convert value to Date object
   * @param {string|number|Date} value - Value to convert
   * @returns {Date} JavaScript Date object
   */
  toDate(value) {
    return this.dateUtils.toDateObject(value);
  }

  /**
   * Calculate business days between two dates
   * Excludes weekends and holidays (handled by backend)
   * @param {string|Date} startDate - Start date
   * @param {string|Date} endDate - End date
   * @returns {number} Number of business days
   */
  calculateBusinessDays(startDate, endDate) {
    return this.dateUtils.calcularDiasHabilesSync(startDate, endDate);
  }

  /**
   * Format duration in human-readable format (es: "2 días, 3 horas")
   * @param {number} milliseconds - Duration in milliseconds
   * @returns {string} Human-readable duration
   */
  formatDuration(milliseconds) {
    return this.dateUtils.formatDurationHuman(milliseconds);
  }

  /**
   * Get current date formatted for display
   * @returns {string} Today's date formatted
   */
  formatToday() {
    return this.formatOrderDate(new Date().toISOString());
  }

  /**
   * Get delivery date formatted for display
   * Alias for formatDeliveryDateTime for semantic clarity
   * @param {string} dateString - Delivery date
   * @returns {string} Formatted delivery date
   */
  formatEstimatedDeliveryDate(dateString) {
    return this.formatDeliveryDateTime(dateString);
  }

  /**
   * Check if date is valid
   * @param {string|Date} value - Date to validate
   * @returns {boolean}
   */
  isValidDate(value) {
    try {
      const date = this.toDate(value);
      return date instanceof Date && !isNaN(date);
    } catch (e) {
      return false;
    }
  }
}
