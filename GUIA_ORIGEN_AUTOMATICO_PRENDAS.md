# Guía de Implementación: Origen Automático de Prendas desde Cotización

## 📋 Descripción General

Esta solución implementa una lógica de negocio que asigna automáticamente el `origen` de una prenda al agregarla desde una cotización, basándose en el tipo de cotización.

### Comportamiento

- **Si la cotización es tipo "Reflectivo" o "Logo"**: `prenda.origen = "bodega"`
- **Si la cotización es otro tipo**: `prenda.origen = "confeccion"` (comportamiento normal)
- **Solo aplica si viene de cotización**, no para prendas agregadas manualmente

---

## 🏗️ Arquitectura

### Archivos Generados

1. **`cotizacion-prenda-handler.js`** - Clase principal con toda la lógica
2. **`cotizacion-prenda-handler-ejemplos.js`** - Ejemplos de integración y testing

### Estructura de la Clase

```javascript
CotizacionPrendaHandler
├── TIPOS_COTIZACION_BODEGA (Configuración)
├── requiereBodega() (Verificación)
├── aplicarOrigenAutomatico() (Aplicación de lógica)
├── prepararPrendaParaEdicion() (Orquestación)
├── registrarTipoBodega() (Registro dinámico)
├── obtenerTiposBodega() (Consulta)
└── reiniciarTipos() (Reset para testing)
```

---

## 🚀 Instalación

### 1. Incluir los Scripts en HTML

```html
<!-- En el head o antes de cerrar body -->
<script src="/js/modulos/crear-pedido/procesos/services/cotizacion-prenda-handler.js"></script>
```

### 2. Agregar Scripts en Assets (si usas Vite/Mix)

```javascript
// resources/js/app.js
import CotizacionPrendaHandler from './modulos/crear-pedido/procesos/services/cotizacion-prenda-handler.js';
window.CotizacionPrendaHandler = CotizacionPrendaHandler;
```

---

## 📝 Uso Básico

### Opción 1: Uso Simple

```javascript
// Tienes una prenda y una cotización
const prenda = {
    nombre: 'Camiseta',
    talla: 'M',
    color: 'Azul'
};

const cotizacion = {
    id: 100,
    tipo_cotizacion_id: 'Reflectivo',
    numero_cotizacion: 'CZ-001'
};

// Aplicar origen automático
const prendaProcesada = CotizacionPrendaHandler.prepararPrendaParaEdicion(
    prenda, 
    cotizacion
);

// prendaProcesada.origen ahora será "bodega"
console.log(prendaProcesada.origen); // "bodega"
```

### Opción 2: Verificar Solo el Tipo

```javascript
// Si solo necesitas saber si un tipo requiere bodega
const esReflectivo = CotizacionPrendaHandler.requiereBodega('Reflectivo');
console.log(esReflectivo); // true

const esLogo = CotizacionPrendaHandler.requiereBodega('Logo');
console.log(esLogo); // true

const esEstandar = CotizacionPrendaHandler.requiereBodega('Estándar');
console.log(esEstandar); // false
```

---

## 🔧 Configuración

### Tipos de Cotización por Defecto

Definidos en `CotizacionPrendaHandler.TIPOS_COTIZACION_BODEGA`:

```javascript
{
    'Reflectivo': ['Reflectivo'],
    'Logo': ['Logo']
}
```

### Agregar Nuevos Tipos

```javascript
// Opción 1: Registro dinámico (recomendado)
CotizacionPrendaHandler.registrarTipoBodega('Bordado', 'Bordado Premium');

// Opción 2: Modificar la configuración directamente
CotizacionPrendaHandler.TIPOS_COTIZACION_BODEGA['4'] = ['Estampado Especial'];

// Opción 3: Al inicializar desde la API
fetch('/api/tipos-cotizacion')
    .then(r => r.json())
    .then(tipos => {
        tipos
            .filter(t => t.requiere_bodega)
            .forEach(t => {
                CotizacionPrendaHandler.registrarTipoBodega(t.id, t.nombre);
            });
    });
```

---

## 🔌 Integración con PrendaEditor

### Ubicación Recomendada

En el flujo donde se cargan prendas desde cotización:

```javascript
// En el módulo que carga cotizaciones
function cargarPrendasDesdeCtizacion(cotizacionId, cotizacionData) {
    fetch(`/api/cotizaciones/${cotizacionId}/prendas`)
        .then(response => response.json())
        .then(data => {
            const prendas = data.prendas || [];

            // ← AQUÍ: Procesar prendas con origen automático
            const prendasProcesadas = prendas.map(prenda => 
                CotizacionPrendaHandler.prepararPrendaParaEdicion(
                    prenda, 
                    cotizacionData
                )
            );

            // Agregar al pedido
            window.prendas = [...(window.prendas || []), ...prendasProcesadas];
            actualizarVistaPrendas();
        });
}
```

### Punto de Integración en PrendaEditor

En el método `abrirModal()`:

```javascript
abrirModal(esEdicion = false, prendaIndex = null, cotizacionSeleccionada = null) {
    if (esEdicion && prendaIndex !== null) {
        this.prendaEditIndex = prendaIndex;
    } else {
        this.prendaEditIndex = null;
    }

    // ← AQUÍ: Si viene de cotización, procesar
    if (cotizacionSeleccionada && window.prendas[prendaIndex]) {
        CotizacionPrendaHandler.prepararPrendaParaEdicion(
            window.prendas[prendaIndex],
            cotizacionSeleccionada
        );
    }

    // Resto del código...
    this.mostrarModal();
}
```

---

## ✅ Testing

### Ejecutar Tests Manuales

```javascript
// En la consola del navegador
testearOrigenAutomatico();

// O cargar el archivo de ejemplos y ejecutar
// Los tests mostrarán en consola todos los casos:
// ✓ Cotización Reflectivo → bodega
// ✓ Cotización Logo → bodega
// ✓ Cotización Normal → confeccion
// ✓ Sin cotización → sin cambios
```

### Casos de Prueba

| Escenario | Entrada | Origen Esperado | Status |
|-----------|---------|-----------------|--------|
| Cotización Reflectivo | tipo_id: 'Reflectivo' | 'bodega' | ✓ |
| Cotización Logo | tipo_id: 'Logo' | 'bodega' | ✓ |
| Cotización Estándar | tipo_id: 'Estándar' | 'confeccion' | ✓ |
| Sin cotización | null | (sin cambios) | ✓ |
| Prenda inválida | null | (log warning) | ✓ |

---

## 🐛 Debugging

### Niveles de Log

La clase usa `console` para logging automático:

```javascript
// Debug (información de flujo)
console.debug('[CotizacionPrendaHandler] Origen asignado a bodega...');

// Info (operaciones importantes)
console.info('Tipo de bodega registrado: "Bordado"');

// Warn (situaciones inusuales pero no críticas)
console.warn('Cotización inválida:', cotizacionSeleccionada);

// Error (fallos críticos)
console.error('Intento de preparar prenda nula');
```

### Habilitar Debug Detallado

```javascript
// En la consola
CotizacionPrendaHandler.TIPOS_COTIZACION_BODEGA; // Ver configuración actual
CotizacionPrendaHandler.obtenerTiposBodega(); // Listar tipos registrados
```

---

## 📊 Flujo de Datos

```
Cotización Seleccionada
        ↓
CotizacionPrendaHandler.prepararPrendaParaEdicion()
        ↓
    ├─ Valida prenda y cotización
    ├─ Extrae tipo_cotizacion_id
    ├─ Verifica en TIPOS_COTIZACION_BODEGA
    └─ Asigna origen ('bodega' o 'confeccion')
        ↓
Prenda Procesada (lista para modal)
        ↓
PrendaEditor.abrirModal()
        ↓
Modal renderizado con origen correcto
```

---

## 🎯 Casos de Uso Comunes

### Caso 1: Cargar Cotización Completa

```javascript
// Usuario selecciona una cotización del dropdown
document.getElementById('select-cotizacion').addEventListener('change', (e) => {
    const cotizacionId = e.target.value;
    
    fetch(`/api/cotizaciones/${cotizacionId}`)
        .then(r => r.json())
        .then(cotizacion => {
            // Procesar cada prenda de la cotización
            const prendas = cotizacion.prendas.map(p => 
                CotizacionPrendaHandler.prepararPrendaParaEdicion(p, cotizacion)
            );
            
            // Agregar al pedido
            agregarPrendasAlPedido(prendas);
        });
});
```

### Caso 2: Editar Prenda Existente

```javascript
// Usuario hace click en editar una prenda
document.getElementById('btn-editar').addEventListener('click', (e) => {
    const prendaIndex = parseInt(e.target.dataset.prendaIndex);
    const prenda = window.prendas[prendaIndex];
    
    // Si la prenda viene de una cotización, procesar
    if (prenda.cotizacion_id) {
        fetch(`/api/cotizaciones/${prenda.cotizacion_id}`)
            .then(r => r.json())
            .then(cotizacion => {
                CotizacionPrendaHandler.prepararPrendaParaEdicion(prenda, cotizacion);
                window.prendaEditor.abrirModal(true, prendaIndex);
            });
    } else {
        // Prenda manual
        window.prendaEditor.abrirModal(true, prendaIndex);
    }
});
```

### Caso 3: Sincronización Dinámica con API

```javascript
// Al iniciar, cargar tipos de cotización desde la API
document.addEventListener('DOMContentLoaded', async () => {
    const tipos = await fetch('/api/tipos-cotizacion').then(r => r.json());
    
    // Registrar tipos que requieren bodega
    tipos
        .filter(t => t.requiere_bodega)
        .forEach(t => {
            CotizacionPrendaHandler.registrarTipoBodega(t.id, t.nombre);
        });
});
```

---

## 🔐 Ventajas de este Diseño

✅ **Modular**: La lógica está centralizada y separada de otros módulos  
✅ **Escalable**: Fácil agregar nuevos tipos de cotización  
✅ **Testeable**: Métodos independientes y sin estado global  
✅ **Mantenible**: Código limpio con comentarios detallados  
✅ **Seguro**: Validación de entrada en cada método  
✅ **Observable**: Logging detallado para debugging  
✅ **Flexible**: Soporta búsqueda por ID o nombre  
✅ **Performante**: O(1) en búsquedas de tipos  

---

## 📚 API Completa

### `CotizacionPrendaHandler.requiereBodega(tipoCotizacionId, nombreTipo)`

Verifica si un tipo requiere bodega.

```javascript
CotizacionPrendaHandler.requiereBodega('Reflectivo') // → true
CotizacionPrendaHandler.requiereBodega('Estándar')   // → false
```

### `CotizacionPrendaHandler.aplicarOrigenAutomatico(prenda, cotizacion)`

Aplica el origen automático a una prenda.

```javascript
const prenda = CotizacionPrendaHandler.aplicarOrigenAutomatico(
    { nombre: 'Camiseta' },
    { tipo_cotizacion_id: 'Logo' }
);
// prenda.origen === 'bodega'
```

### `CotizacionPrendaHandler.prepararPrendaParaEdicion(prenda, cotizacion)`

Prepara una prenda para edición (método recomendado).

```javascript
const prendaLista = CotizacionPrendaHandler.prepararPrendaParaEdicion(
    prenda,
    cotizacion
);
```

### `CotizacionPrendaHandler.registrarTipoBodega(tipoId, nombreTipo)`

Registra un nuevo tipo que requiere bodega.

```javascript
CotizacionPrendaHandler.registrarTipoBodega('4', 'Bordado Premium');
```

### `CotizacionPrendaHandler.obtenerTiposBodega()`

Obtiene lista de tipos registrados.

```javascript
CotizacionPrendaHandler.obtenerTiposBodega()
// → ['Reflectivo', 'Logo']
```

---

## ❓ FAQ

**P: ¿Qué pasa si se agrega una prenda sin cotización?**  
R: Se ignora la lógica de origen automático. Si la prenda no tiene `origen` definido, quedará sin asignar.

**P: ¿Puedo cambiar el origen después de asignarlo?**  
R: Sí, la clase solo asigna el valor. El usuario puede editarlo después en el modal.

**P: ¿Cómo sincronizo nuevos tipos desde la base de datos?**  
R: Llama a `registrarTipoBodega()` cada vez que se cargue un tipo nuevo de la API.

**P: ¿Afecta el rendimiento?**  
R: No, las búsquedas son O(1) y no hay iteraciones costosas.

**P: ¿Funciona con prendas editadas después?**  
R: Sí, puedes llamar a `prepararPrendaParaEdicion()` en cualquier momento para re-aplicar la lógica.

---

## 🔄 Versionado

- **v1.0.0** - Implementación inicial
  - Soporte para tipos Reflectivo y Logo
  - Registro dinámico de tipos
  - Testing integrado
  - Documentación completa

---

## 📞 Soporte

Para preguntas o problemas, revisa:
1. El archivo `cotizacion-prenda-handler-ejemplos.js`
2. La consola del navegador para logs detallados
3. Ejecuta `testearOrigenAutomatico()` para verificar instalación
