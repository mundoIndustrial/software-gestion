/**
 * DIContainer - Dependency Injection Container (Phase 11)
 * 
 * Responsabilidad: Gestionar instanciación y inyección de dependencias centralizadamente
 * DIP (Dependency Inversion Principle): Services dependen de abstracciones, no de implementaciones
 * 
 * Características:
 * - Lazy loading (instancia solo cuando se solicita)
 * - Singleton pattern (una instancia por servicio)
 * - Fácil de mockear para testing
 * - Registro de servicios con factories
 * 
 * @class DIContainer
 */
export class DIContainer {
  constructor() {
    // Almacenar factories (funciones que crean servicios)
    this._factories = new Map();
    
    // Almacenar instancias (singleton)
    this._instances = new Map();
    
    // Servicios que requieren setup posterior (como renderers)
    this._deferredSetup = [];
  }

  /**
   * Registrar un servicio en el container
   * 
   * @param {string} serviceName - Nombre único del servicio
   * @param {Function} factory - Función que retorna la instancia del servicio
   * @example
   * container.register('orderApiService', () => new OrderApiService());
   * container.register('processService', (get) => new ProcessService(
   *   get('processDeleteService'),
   *   get('formValidationService'),
   *   ...
   * ));
   */
  register(serviceName, factory) {
    if (typeof factory !== 'function') {
      throw new Error(`Factory for '${serviceName}' must be a function`);
    }
    this._factories.set(serviceName, factory);
  }

  /**
   * Obtener instancia de un servicio (lazy loading singleton)
   * 
   * @param {string} serviceName - Nombre del servicio
   * @returns {*} Instancia del servicio
   */
  get(serviceName) {
    // Si ya existe instancia, retornarla
    if (this._instances.has(serviceName)) {
      return this._instances.get(serviceName);
    }

    // Si no existe factory, error
    if (!this._factories.has(serviceName)) {
      throw new Error(`Service '${serviceName}' not registered in DIContainer`);
    }

    // Crear instancia usando factory
    const factory = this._factories.get(serviceName);
    const instance = factory((serviceName) => this.get(serviceName));

    // Cachear instancia
    this._instances.set(serviceName, instance);

    return instance;
  }

  /**
   * Verificar si un servicio está registrado
   * 
   * @param {string} serviceName - Nombre del servicio
   * @returns {boolean}
   */
  has(serviceName) {
    return this._factories.has(serviceName);
  }

  /**
   * Registrar setup deferred para servicios que necesitan configuración post-instanciación
   * (ej: renderers que necesitan inyección de callbacks)
   * 
   * @param {Function} setupFn - Función que ejecuta setup
   */
  registerDeferredSetup(setupFn) {
    this._deferredSetup.push(setupFn);
  }

  /**
   * Ejecutar todos los setups deferred
   * Debe llamarse después de que todas las dependencias estén disponibles
   */
  executeDeferredSetup() {
    this._deferredSetup.forEach(setupFn => setupFn(this.get.bind(this)));
    this._deferredSetup = [];
  }

  /**
   * Resetear container (para testing)
   * Limpia instancias pero mantiene factories
   */
  reset() {
    this._instances.clear();
    this._deferredSetup = [];
  }

  /**
   * Resetear completamente (para testing extremo)
   * Limpia factories e instancias
   */
  resetAll() {
    this._factories.clear();
    this._instances.clear();
    this._deferredSetup = [];
  }

  /**
   * Obtener estado del container (para debugging)
   * 
   * @returns {Object} Estado del container
   */
  getState() {
    return {
      registeredServices: Array.from(this._factories.keys()),
      instantiatedServices: Array.from(this._instances.keys()),
      deferredSetups: this._deferredSetup.length
    };
  }

  /**
   * Log estado del container
   */
  debug() {
    const state = this.getState();
    console.log('[DIContainer] Estado:', state);
    console.log('[DIContainer] Servicios registrados:', state.registeredServices);
    console.log('[DIContainer] Instancias creadas:', state.instantiatedServices);
  }
}
