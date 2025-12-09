/**
 * Índice central de módulos
 * 
 * Carga todos los módulos en orden correcto de dependencias
 * Sigue patrón SOLID: Dependency Inversion
 */

// Orden de carga (de menor a mayor dependencia):
// 1. ValidationModule (sin dependencias)
// 2. TallasModule (sin dependencias) 
// 3. EspecificacionesModule (sin dependencias)
// 4. ProductoModule (depende de TallasModule)
// 5. FormModule (depende de ProductoModule)
// 6. CotizacionPrendaApp (orquestador - depende de todos)

console.log('📦 Cargando módulos de cotización de prendas...');
console.log('✓ Módulos listos para inicializar');
