# ⚡ GUÍA RÁPIDA - Módulos SOLID

## Estructura
```
modules/
├── formatting.js ..................... Fechas, tipos
├── storageModule.js .................. localStorage cross-tab
├── notificationModule.js ............. Notificaciones visuales
├── updates.js ........................ Peticiones PATCH
├── dropdownManager.js ................ Dropdowns estado/área
├── diaEntregaModule.js ............... Día de entrega especializado
├── rowManager.js ..................... Row CRUD
├── tableManager.js ................... Orquestador
└── index.js .......................... Índice
```

---

## Acceso Rápido

### Notificaciones
```javascript
NotificationModule.showSuccess('Guardado');
NotificationModule.showError('Error');
NotificationModule.showAutoReload('Recargando...', 3000);
```

### Actualizar orden
```javascript
UpdatesModule.updateOrderStatus(id, estado);
UpdatesModule.updateOrderArea(id, area);
UpdatesModule.updateOrderDiaEntrega(id, dias);
```

### Formatear fechas
```javascript
FormattingModule.formatearFecha('2024-01-15');
// → '15/01/2024'
```

### Sincronizar tabs
```javascript
StorageModule.broadcastUpdate({ numeroOrden: 123, area: 'X' });
```

### Actualizar fila
```javascript
RowManager.updateRowColor(orden);
RowManager.actualizarOrdenEnTabla(orden);
```

### Gestionar día entrega
```javascript
const dias = DiaEntregaModule.getAvailableDays();
const sugerencia = DiaEntregaModule.getSuggestedDays('Cortando');
const color = DiaEntregaModule.getIndicatorColor(5);
```

---

## Debugging
```javascript
// Ver módulos cargados
TableManager.listModules();

// Acceder a módulo
TableManager.getModule('updates');

// Recargar tabla
TableManager.reloadTable();

// Verificar dependencias
TableManager.verifyDependencies();
```

---

## Agregar nueva funcionalidad

### ✅ Extensible (Open/Closed Principle)
Ejemplo: agregar update de nuevo campo

```javascript
// En updates.js, agregar método:
updateOrderNuevoCampo(numeroOrden, valor) {
    this.updateWithDebounce(numeroOrden, () => {
        UpdatesModule._sendUpdate(`/api/orders/${numeroOrden}/nuevo-campo`, { valor });
    });
}

// Usar:
UpdatesModule.updateOrderNuevoCampo(123, 'valor');
```

### ✅ Crear módulo nuevo
Si necesitas módulo completamente nuevo:

```javascript
const MiModulo = {
    init() {
        console.log('Inicializando...');
    },
    
    metodoPublico() {
        // lógica
    },
    
    _metodoPrivado() {
        // helpers
    }
};

// Agregar a tableManager.js en fase correspondiente
// Incluir en template
```

---

## Orden de carga (IMPORTANTE)

**NUNCA modificar este orden:**

```html
<!-- FASE 1 (sin dependencias) -->
<script src="modules/formatting.js"></script>
<script src="modules/storageModule.js"></script>
<script src="modules/notificationModule.js"></script>

<!-- FASE 2 (dependen de Fase 1) -->
<script src="modules/updates.js"></script>
<script src="modules/rowManager.js"></script>
<script src="modules/dropdownManager.js"></script>
<script src="modules/diaEntregaModule.js"></script>

<!-- FASE 3 (orquestador) -->
<script src="modules/tableManager.js"></script>

<!-- Originales -->
<script src="orders-table.js"></script>
```

---

## Errores comunes

### ❌ "X is not defined"
```javascript
// MALO:
UpdatesModule.updateOrderArea(id, area);
// Si módulos no están cargados en orden correcto

// BUENO:
if (TableManager.initialized) {
    UpdatesModule.updateOrderArea(id, area);
}
```

### ❌ "Cannot read property 'init' of undefined"
```javascript
// Verificar que todos los módulos están en template
// Verificar orden correcto
// F12 → Console → ver logs de inicialización
```

### ❌ Cambios no se sincronizan entre tabs
```javascript
// MALO:
// No enviar update a localStorage

// BUENO:
StorageModule.broadcastUpdate(data);
// Se sincroniza automáticamente
```

---

## Performance tips

1. **Debounce**: Dropdowns usan 300ms debounce automático
2. **Eventos delegados**: DropdownManager usa event delegation (sin listeners por fila)
3. **localStorage**: StorageModule solo sync cuando hay cambios
4. **Async**: UpdatesModule no bloquea UI

---

## Testing

Cada módulo es fácil de testear:

```javascript
// Test FormattingModule
test('formatearFecha', () => {
    expect(FormattingModule.formatearFecha('2024-01-15'))
        .toBe('15/01/2024');
});

// Test UpdatesModule
test('updateOrderArea', async () => {
    const response = await UpdatesModule.updateOrderArea(123, 'Area');
    expect(response.ok).toBe(true);
});

// Test RowManager
test('updateRowColor', () => {
    const orden = { estado: 'Confeccionando', dias: 5 };
    RowManager.updateRowColor(orden);
    // verificar clases CSS aplicadas
});
```

---

## 📋 Checklist de integración

- [ ] Módulos en `public/js/orders js/modules/`
- [ ] Template incluye en orden correcto
- [ ] Verificar en DevTools que modules cargan
- [ ] Testear cambio de área
- [ ] Testear cambio de estado
- [ ] Testear cambio de día entrega
- [ ] Testear entre tabs (localStorage)
- [ ] Verificar notificaciones muestran
- [ ] No hay errores en console

---

## ¿Preguntas?

Ver `ARQUITECTURA-MODULAR-SOLID.md` para documentación completa.
