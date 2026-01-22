# 🔄 GUÍA DE REFACTORIZACIÓN: Variables Legacy → Estructura Relacional

## Contexto
Este documento sirve como referencia para refactorizar archivos JavaScript que aún usen variables legacy de tallas.

---

## ❌ PATRÓN LEGACY (A EVITAR)

### Patrón 1: Variables Globales de Trabajo
```javascript
// ❌ MALO - Variables auxiliares desorganizadas
window.cantidadesTallas = window.cantidadesTallas || {};
window.tallasSeleccionadas = {
    dama: { tallas: [], tipo: null },
    caballero: { tallas: [], tipo: null }
};
window._TALLAS_BACKUP_PERMANENTE = {};

// Guardar cantidad
window.cantidadesTallas['dama-s'] = 10;
window.cantidadesTallas['dama-m'] = 20;
window.cantidadesTallas['caballero-32'] = 15;

// Leer cantidad
const cantidad = window.cantidadesTallas['dama-s'];
```

### Patrón 2: Lectura de JSON Legacy
```javascript
// ❌ MALO - Parsing de cantidad_talla como JSON string
if (typeof prenda.cantidad_talla === 'string') {
    const tallasObj = JSON.parse(prenda.cantidad_talla);
    // Trabaja con objeto parseado
}
```

### Patrón 3: Envío con Estructura Antigua
```javascript
// ❌ MALO - Construir formData con campos separados
formData.append(`prendas[${i}][tallas_dama]`, JSON.stringify(tallasD));
formData.append(`prendas[${i}][tallas_caballero]`, JSON.stringify(tallasC));
```

---

## ✅ PATRÓN CORRECTO (A SEGUIR)

### Patrón 1: Estado Relacional Centralizado
```javascript
// ✅ BUENO - Estructura de datos centralizada y clara
class TallasManager {
    constructor() {
        // Estructura relacional única: {GENERO: {TALLA: CANTIDAD}}
        this.tallas = {};
    }
    
    // Establecer tallas
    setTallas(genero, tallasObj) {
        this.tallas[genero] = tallasObj; // {S: 10, M: 20, ...}
    }
    
    // Obtener tallas
    getTallas() {
        return this.tallas; // {DAMA: {S: 10, M: 20}, CABALLERO: {32: 15}}
    }
    
    // Obtener cantidad específica
    getCantidad(genero, talla) {
        return this.tallas[genero]?.[talla] ?? 0;
    }
    
    // Calcular total
    getTotal() {
        return Object.values(this.tallas).reduce((sum, generoTallas) => {
            return sum + Object.values(generoTallas).reduce((s, c) => s + c, 0);
        }, 0);
    }
}

// Uso
const tallasManager = new TallasManager();
tallasManager.setTallas('DAMA', {S: 10, M: 20});
tallasManager.setTallas('CABALLERO', {32: 15, 34: 10});

const cantidad = tallasManager.getCantidad('DAMA', 'S');  // 10
const total = tallasManager.getTotal();                    // 55
```

### Patrón 2: Lectura Segura de Tallas
```javascript
// ✅ BUENO - Lectura segura con conversión automática
function extraerTallas(prenda) {
    // Si viene como JSON string, parsear
    if (typeof prenda.cantidad_talla === 'string') {
        try {
            return JSON.parse(prenda.cantidad_talla);
        } catch (e) {
            console.warn('Error parseando tallas:', e);
            return {};
        }
    }
    
    // Si ya es objeto, devolverlo
    if (typeof prenda.cantidad_talla === 'object') {
        return prenda.cantidad_talla;
    }
    
    // Fallback: objeto vacío
    return {};
}

// Uso
const tallas = extraerTallas(prenda);
// Resultado: {DAMA: {S: 10, M: 20}, CABALLERO: {32: 15}}
```

### Patrón 3: Envío Relacional Correcto
```javascript
// ✅ BUENO - Envío en estructura relacional única
function enviarPrendas(prendas) {
    const formData = new FormData();
    
    prendas.forEach((prenda, index) => {
        // Datos básicos
        formData.append(`prendas[${index}][nombre]`, prenda.nombre);
        formData.append(`prendas[${index}][ref]`, prenda.ref);
        
        // TALLAS EN ESTRUCTURA RELACIONAL ÚNICA
        // {DAMA: {S: 10, M: 20}, CABALLERO: {32: 15}}
        formData.append(
            `prendas[${index}][cantidad_talla]`,
            JSON.stringify(prenda.tallas)  // Estructura relacional
        );
        
        // Procesos (cada uno con tallas relacionales)
        prenda.procesos.forEach((proc, pIdx) => {
            formData.append(
                `prendas[${index}][procesos][${pIdx}][tallas]`,
                JSON.stringify(proc.tallas)  // También relacional
            );
        });
    });
    
    return fetch('/api/pedidos', { method: 'POST', body: formData });
}
```

---

## 📝 Ejemplos de Migración

### Ejemplo 1: Limpieza de Modal
```javascript
// ❌ ANTES
window.cantidadesTallas = {};
window.tallasSeleccionadas = { dama: { tallas: [], tipo: null }, caballero: {...} };

// ✅ DESPUÉS
class GestorTallasModal {
    constructor() {
        this.tallas = {};
        this.selectedByGenero = {};
    }
    
    clear() {
        this.tallas = {};
        this.selectedByGenero = {};
    }
}

const gestor = new GestorTallasModal();
gestor.clear(); // Limpio y organizado
```

### Ejemplo 2: Captura de Cantidad
```javascript
// ❌ ANTES
window.guardarCantidadTalla = function(input) {
    const key = input.dataset.key; // "genero-talla"
    window.cantidadesTallas[key] = parseInt(input.value);
};

// ✅ DESPUÉS
class TallasHandler {
    guardarCantidad(genero, talla, cantidad) {
        if (!this.tallas[genero]) this.tallas[genero] = {};
        this.tallas[genero][talla] = cantidad;
    }
    
    onInputChange(event) {
        const {genero, talla} = event.target.dataset;
        this.guardarCantidad(genero, talla, event.target.value);
    }
}
```

### Ejemplo 3: Renderizado de Tallas
```javascript
// ❌ ANTES
const generosTallasHTML = Object.entries(window.cantidadesTallas)
    .map(([key, cantidad]) => {
        const [genero, talla] = key.split('-');
        return `<div>${genero} ${talla}: ${cantidad}</div>`;
    }).join('');

// ✅ DESPUÉS
function renderTallas(tallasObj) {
    // tallasObj: {DAMA: {S: 10, M: 20}, CABALLERO: {32: 15}}
    return Object.entries(tallasObj)
        .map(([genero, tallasGenero]) => `
            <div class="genero-section">
                <h4>${genero}</h4>
                ${Object.entries(tallasGenero)
                    .map(([talla, cantidad]) => 
                        `<div>${talla}: ${cantidad}</div>`
                    ).join('')}
            </div>
        `).join('');
}

// Uso
const html = renderTallas({DAMA: {S: 10, M: 20}, CABALLERO: {32: 15}});
```

---

## 🔄 Checklist de Refactorización

Cuando refactorices un archivo legacy, verifica:

### Paso 1: Identificar Variables Legacy
- [ ] `window.cantidadesTallas`
- [ ] `window._TALLAS_BACKUP_PERMANENTE`
- [ ] `window.tallasSeleccionadas`
- [ ] Parsing de `cantidad_talla` como JSON

### Paso 2: Reemplazar por Estructura Relacional
- [ ] Crear clase/manager para tallas
- [ ] Usar estructura `{GENERO: {TALLA: CANTIDAD}}`
- [ ] Implementar métodos de lectura/escritura

### Paso 3: Actualizar Envíos a API
- [ ] Campo único: `cantidad_talla` (JSON)
- [ ] Estructura: `{GENERO: {TALLA: CANTIDAD}}`
- [ ] Validar que API acepta el formato

### Paso 4: Testing
- [ ] Captura de tallas funciona
- [ ] Cálculo de totales es correcto
- [ ] Envío a API es exitoso
- [ ] Datos se guardan en BD correctamente
- [ ] Lectura desde BD muestra datos correctos

### Paso 5: Limpieza
- [ ] Remover variables globales legacy
- [ ] Actualizar comentarios/documentación
- [ ] Verificar no hay referencias rotas

---

## 📌 Notas de Implementación

### Compatibilidad Hacia Atrás
Si necesitas mantener compatibilidad temporalmente:

```javascript
// Envolver variables legacy en método de compatibilidad
function legacyGetTallas() {
    // Convierte variables globales a estructura relacional
    const resultado = {};
    
    for (let key in window.cantidadesTallas) {
        const [genero, talla] = key.split('-');
        if (!resultado[genero]) resultado[genero] = {};
        resultado[genero][talla] = window.cantidadesTallas[key];
    }
    
    return resultado;
}
```

### Performance
- ✅ Estructura relacional es más eficiente para cálculos
- ✅ JSON.stringify() es seguro y estándar
- ✅ Menos iteraciones en rendering

### Seguridad
- ✅ Validar estructura antes de usar
- ✅ Usar optional chaining: `genero?.talla`
- ✅ Nunca confiar en estructura sin validar

---

## ✅ Validación Post-Refactor

Después de refactorizar, ejecutar esta validación:

```javascript
function validarEstructuraTallas(tallas) {
    // Validar que sea objeto
    if (typeof tallas !== 'object') return false;
    
    // Validar géneros
    for (let genero in tallas) {
        // Cada género debe ser objeto
        if (typeof tallas[genero] !== 'object') return false;
        
        // Cada talla debe ser número
        for (let talla in tallas[genero]) {
            const cantidad = tallas[genero][talla];
            if (typeof cantidad !== 'number' || cantidad < 0) return false;
        }
    }
    
    return true;
}

// Uso
const tallas = {DAMA: {S: 10, M: 20}, CABALLERO: {32: 15}};
console.assert(validarEstructuraTallas(tallas), 'Estructura inválida');
```

---

## 📚 Referencias

- Estructura Relacional: [MODELO_DATOS_FIJO_REFERENCIA_RAPIDA.md]
- Validación: [VALIDACION_STRICTA_MODELO_DATOS.md]
- API: [Endpoints de Tallas]
- BD: Tabla `prenda_pedido_tallas`

