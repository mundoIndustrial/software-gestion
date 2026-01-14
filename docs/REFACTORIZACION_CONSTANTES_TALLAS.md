# 📦 Refactorización: Constantes de Tallas Extraídas

## ✅ Cambio Realizado

Las constantes de tallas han sido extraídas del archivo `crear-desde-cotizacion-editable.blade.php` a un archivo dedicado:

```
public/js/constantes-tallas.js
```

## 📋 Constantes Disponibles

Ahora puedes usar estas constantes en cualquier archivo JavaScript:

```javascript
// Tallas de letra (XS a XXXL)
TALLAS_LETRAS

// Tallas numéricas para DAMA (2 a 28)
TALLAS_NUMEROS_DAMA

// Tallas numéricas para CABALLERO (30 a 56)
TALLAS_NUMEROS_CABALLERO

// Objeto centralizado
CONSTANTES_TALLAS.LETRAS
CONSTANTES_TALLAS.NUMEROS_DAMA
CONSTANTES_TALLAS.NUMEROS_CABALLERO
```

## 🔄 Cómo se Carga

El archivo se carga automáticamente en [crear-desde-cotizacion-editable.blade.php](crear-desde-cotizacion-editable.blade.php#L234):

```blade
<script src="{{ asset('js/constantes-tallas.js') }}"></script>
```

**IMPORTANTE**: Se carga PRIMERO, antes de los otros módulos, para asegurar disponibilidad global.

## 🎯 Ventajas

- ✅ **Mantenibilidad**: Un solo lugar para modificar tallas
- ✅ **Reutilización**: Disponible en otros archivos sin duplicación
- ✅ **Limpieza**: Blade template más limpio
- ✅ **Escalabilidad**: Fácil agregar nuevas categorías de tallas

## 📝 Localización

| Archivo | Propósito |
|---------|-----------|
| [public/js/constantes-tallas.js](../../../public/js/constantes-tallas.js) | Definiciones centralizadas |
| [recursos/views/asesores/pedidos/crear-desde-cotizacion-editable.blade.php](crear-desde-cotizacion-editable.blade.php#L234) | Importación en script |

## 🔧 Para Modificar Tallas

Solo edita [public/js/constantes-tallas.js](../../../public/js/constantes-tallas.js) y los cambios se reflejarán automáticamente en todos lados.
