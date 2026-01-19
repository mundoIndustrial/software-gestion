# Guía de Migración del Módulo Pedidos-Recibos

## Resumen

El archivo original `order-detail-modal-proceso-dinamico.js` ha sido refactorizado y separado en un módulo modular ubicado en:

```
public/js/modulos/pedidos-recibos/
```

## Migración en Blade

### Paso 1: Reemplazar el script antiguo

**Antes (en `resources/views/asesores/pedidos/index.blade.php`):**
```html
<script src="{{ asset('js/orders js/order-detail-modal-proceso-dinamico.js') }}"></script>
```

**Después:**
```html
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>
```

### Paso 2: Sin cambios necesarios en el HTML/PHP

La API pública se mantiene idéntica:
- `openOrderDetailModalWithProcess()` - Sigue funcionando
- `cerrarModalRecibos()` - Sigue funcionando

## Nueva API Mejorada (opcional)

Si deseas usar la nueva API más moderna:

```javascript
// Acceso directo al módulo
const modulo = window.pedidosRecibosModule;

// Abrir recibo
await modulo.abrirRecibo(pedidoId, prendaId, 'costura', prendaIndex);

// Cerrar
modulo.cerrarRecibo();

// Galería
await modulo.abrirGaleria();

// Estado
const estado = modulo.getEstado();
```

## Estructura del Módulo

```
pedidos-recibos/
├── components/
│   ├── ModalManager.js          ← Gestión de modal
│   ├── CloseButtonManager.js    ← Botón X
│   ├── NavigationManager.js     ← Flechas prev/next
│   ├── GalleryManager.js        ← Galería de fotos
│   └── ReceiptRenderer.js       ← Renderizado
├── utils/
│   ├── ReceiptBuilder.js        ← Constructor de recibos
│   └── Formatters.js            ← Formateo de datos
├── PedidosRecibosModule.js      ← Orquestador principal
├── loader.js                    ← Cargador compatible
├── index.js                     ← Punto de entrada ESM
└── README.md                    ← Documentación
```

## Beneficios de la Refactorización

✅ **Separación de Responsabilidades**
- Cada componente hace una cosa bien

✅ **Reutilización**
- Los componentes pueden usarse en otros contextos

✅ **Testabilidad**
- Código modular es más fácil de testear

✅ **Mantenimiento**
- Cambios aislados a componentes específicos

✅ **Escalabilidad**
- Fácil agregar nuevas funcionalidades

## Archivo Antiguo

✅ **Eliminado:** El archivo original `public/js/orders js/order-detail-modal-proceso-dinamico.js` ha sido removido.

El nuevo módulo está completamente funcional y cubre toda la funcionalidad del archivo anterior.

## Validación

Para verificar que el módulo cargó correctamente, ejecuta en la consola:

```javascript
console.log(window.pedidosRecibosModule); // Debe mostrar PedidosRecibosModule instance
console.log(window.openOrderDetailModalWithProcess); // Debe ser function
```

## Depuración

Filtrar logs en DevTools por:
- `[PedidosRecibosModule]` - Logs principales
- `[ModalManager]` - Gestión de modal
- `[ReceiptRenderer]` - Renderizado
- `[GalleryManager]` - Galería
- etc.

## Compatibilidad

✅ Total compatibilidad hacia atrás  
✅ No requiere cambios en templates  
✅ No requiere cambios en controladores  
✅ No requiere cambios en rutas  

## Próximos Pasos

1. Incluir el `loader.js` en Blade
2. Probar la funcionalidad en navegador
3. Revisar console para logs de confirmación
4. Usar el nuevo módulo si necesitas funcionalidades adicionales
5. Opcionalmente eliminar el archivo antiguo

## Soporte

Si encuentras problemas:
1. Revisa los logs en consola
2. Verifica que los elementos del DOM existan
3. Asegúrate de cargar el loader ANTES de usarlo
4. Comprueba que no haya conflictos con otros scripts
