# Validación de Cambio de Tipo de Pedido

## 📋 Descripción

Este módulo implementa una validación inteligente que advierte al usuario cuando intenta cambiar entre "Desde Cotización" y "Nuevo Pedido" si ya tiene datos armados en el formulario.

## 🎯 Funcionalidad

Cuando el usuario intenta cambiar el tipo de pedido y ya hay datos en cualquiera de estos campos:
- **Cliente** - si tiene un cliente ingresado
- **Forma de Pago** - si tiene forma de pago seleccionada
- **Prendas** - si tiene prendas agregadas
- **Cotización** - si tiene una cotización seleccionada

Se mostrará un **modal de advertencia** que:
1. Lista todos los datos que serán eliminados
2. Advierte al usuario con un botón rojo "Sí, cambiar"
3. Permite cancelar la acción con "Cancelar"

## ✅ Si el usuario confirma:
- Se limpian todos los campos del formulario
- Se permite el cambio de tipo
- Se muestra un mensaje de confirmación en la consola

## ❌ Si el usuario cancela:
- Se revierte el radio button a su estado anterior
- Los datos se mantienen intactos
- Se muestra un mensaje en la consola

## 📝 Datos que se limpian:
- Cliente
- Forma de pago
- Prendas cargadas
- Cotización seleccionada
- Números de identificación (cotización, pedido)

## 🔧 Implementación Técnica

### Archivo creado:
```
/public/js/modulos/crear-pedido/validar-cambio-tipo-pedido.js
```

### Carga en la vista:
```blade
<!-- Validación de cambio de tipo de pedido (DEBE CARGARSE ANTES DE crear-pedido-editable.js) -->
<script src="{{ asset('js/modulos/crear-pedido/validar-cambio-tipo-pedido.js') }}?v={{ time() }}"></script>
```

### Cómo funciona:
1. El módulo se carga como IIFE (Immediately Invoked Function Expression)
2. Espera a que el DOM esté listo
3. Agrega listeners a ambos radio buttons
4. Cuando se cambia el tipo, detecta si hay datos
5. Si hay datos, muestra el modal con SweetAlert2
6. Según la respuesta, limpia o revierte

## 🎨 Apariencia del Modal

```
⚠️ ¿Cambiar tipo de pedido?

Ya tienes información armada en el formulario que será eliminada:
• Cliente: "Acme Corporation"
• Forma de pago: "Contado"
• 2 prenda(s) agregada(s)
• Cotización: Cot-2024-001

¿Estás seguro de que deseas continuar? Esta acción no se puede deshacer.

[Cancelar]  [Sí, cambiar]
```

## 🔍 Debugging

Abre la consola de desarrollador (F12) para ver los logs:
- ✅ "Validación inicializada"
- ✅ "Usuario confirmó cambio de tipo - datos limpiados"
- ❌ "Usuario canceló cambio - radio revertido"
- 🧹 "Formulario limpiado"

## 📦 Dependencias

- **SweetAlert2**: Modal de confirmación (ya incluido en la vista)
- **DOM**: Acceso a elementos del formulario

## 🚀 Ruta donde está implementado

```
http://servermi:8000/asesores/pedidos-produccion/crear
```

Vista: `resources/views/asesores/pedidos/crear-desde-cotizacion-editable.blade.php`
