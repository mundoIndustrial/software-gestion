/**
 * FormStateManager
 * 
 * Responsabilidad: Gestionar el estado del formulario de procesos
 * 
 * SRP: Una sola razón para cambiar — gestión de estado del formulario
 * 
 * Estado manejado:
 * - ¿Está abierto el formulario?
 * - ¿En modo agregar o editar?
 * - ¿Cuál es el proceso en edición?
 * - ¿Cuáles son los valores actuales del formulario?
 */

export class FormStateManager {
  constructor() {
    this.isOpen = false;
    this.mode = 'add'; // 'add' | 'edit'
    this.editingProcessId = null;
    this.currentValues = {
      area: '',
      estado: 'Pendiente',
      fechaInicio: '',
      encargado: '',
      observaciones: ''
    };
  }

  /**
   * Abrir formulario en modo agregar
   */
  openForAdd() {
    this.mode = 'add';
    this.editingProcessId = null;
    this.isOpen = true;
    this.clearValues();
  }

  /**
   * Abrir formulario en modo editar
   * @param {string} procesoId
   * @param {object} initialValues - Valores iniciales
   */
  openForEdit(procesoId, initialValues = {}) {
    this.mode = 'edit';
    this.editingProcessId = procesoId;
    this.isOpen = true;
    this.setValues(initialValues);
  }

  /**
   * Cerrar formulario
   */
  close() {
    this.isOpen = false;
    this.mode = 'add';
    this.editingProcessId = null;
    this.clearValues();
  }

  /**
   * Establecer valores del formulario
   * @param {object} values - { area, estado, fechaInicio, encargado, observaciones }
   */
  setValues(values = {}) {
    this.currentValues = {
      area: values.area ?? '',
      estado: values.estado ?? 'Pendiente',
      fechaInicio: values.fechaInicio ?? '',
      encargado: values.encargado ?? '',
      observaciones: values.observaciones ?? ''
    };
  }

  /**
   * Limpiar valores del formulario
   */
  clearValues() {
    this.currentValues = {
      area: '',
      estado: 'Pendiente',
      fechaInicio: '',
      encargado: '',
      observaciones: ''
    };
  }

  /**
   * Obtener estado actual del formulario
   */
  getState() {
    return {
      isOpen: this.isOpen,
      mode: this.mode,
      editingProcessId: this.editingProcessId,
      currentValues: { ...this.currentValues }
    };
  }

  /**
   * Obtener el estado del botón (texto + onclick)
   */
  getButtonState() {
    return {
      text: this.mode === 'edit' ? 'Actualizar Proceso' : 'Agregar Proceso',
      isEditMode: this.mode === 'edit'
    };
  }

  /**
   * ¿Está en modo edición?
   */
  isEditing() {
    return this.mode === 'edit';
  }
}
