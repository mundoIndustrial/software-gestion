# Sistema de Auditoría Completo - Mundo Industrial

## 📋 Descripción

Se ha implementado un **sistema de auditoría completo** que registra TODAS las modificaciones (crear, actualizar, eliminar) en la base de datos y muestra notificaciones en el dashboard.

## 🎯 Características Implementadas

### 1. **Trait Auditable**
- Ubicación: `app/Traits/Auditable.php`
- Captura automáticamente eventos de modelos:
  - ✅ **Creación** de registros
  - ✅ **Actualización** de registros (con detalles de cambios)
  - ✅ **Eliminación** de registros
- Registra automáticamente:
  - Usuario que realizó la acción
  - Tabla y registro afectado
  - Cambios específicos (antes/después)
  - Pedido asociado (si aplica)

### 2. **Modelos con Auditoría Automática**

Se aplicó el trait `Auditable` a los siguientes modelos:

#### Modelos de Órdenes y Registros:
- ✅ `TablaOriginal` (órdenes de pedidos)
- ✅ `RegistrosPorOrden`
- ✅ `User` (usuarios del sistema)

#### Modelos de Tableros:
- ✅ `RegistroPisoCorte`
- ✅ `RegistroPisoProduccion`
- ✅ `RegistroPisoPolo`

#### Modelos de Entregas:
- ✅ `EntregaPedidoCorte`
- ✅ `EntregaPedidoCostura`
- ✅ `EntregaBodegaCorte`
- ✅ `EntregaBodegaCostura`

#### Otros Modelos:
- ✅ `Balanceo`

### 3. **Base de Datos Extendida**

Nueva migración: `2025_11_07_161800_extend_news_table.php`

Campos agregados a la tabla `news`:
- `table_name`: Nombre de la tabla afectada
- `record_id`: ID del registro afectado
- Índices para optimizar búsquedas

### 4. **API Mejorada**

#### Endpoint: `/dashboard/news`
Parámetros opcionales:
- `date`: Fecha de filtrado (default: hoy)
- `table`: Filtrar por tabla específica
- `event_type`: Filtrar por tipo de evento
- `limit`: Cantidad de registros (default: 50)

#### Nuevo Endpoint: `/dashboard/audit-stats`
Retorna estadísticas de auditoría:
- Total de eventos del día
- Eventos por tipo
- Eventos por tabla
- Eventos por usuario

### 5. **Dashboard Mejorado**

#### Notificaciones con Iconos y Colores:
- 🟢 **Verde**: Creaciones y entregas
- 🔵 **Azul**: Actualizaciones
- 🔴 **Rojo**: Eliminaciones
- 🟡 **Amarillo**: Cambios de estado
- 🔵 **Cyan**: Cambios de área
- 🟣 **Morado**: Órdenes creadas

#### Información Mostrada:
- Tipo de evento con icono
- Tabla afectada (badge)
- Descripción detallada
- Usuario que realizó la acción
- Fecha y hora exacta
- Pedido asociado (si aplica)

## 🚀 Instalación y Configuración

### Paso 1: Ejecutar Migraciones

```bash
php artisan migrate
```

Esto creará las nuevas columnas en la tabla `news`.

### Paso 2: Verificar que el Sistema Funciona

1. **Crear una orden nueva** en `/registros`
   - Verás una notificación en el dashboard

2. **Editar un registro** en los tableros
   - Se registrará automáticamente con detalles de cambios

3. **Eliminar un registro**
   - Se guardará el registro de eliminación

4. **Registrar una entrega**
   - Aparecerá en las notificaciones

### Paso 3: Ver Notificaciones

1. Ve al **Dashboard** (`/dashboard`)
2. En la sección "Notificaciones" verás todos los eventos
3. Usa el filtro de fecha para ver eventos de días anteriores

## 📊 Tipos de Eventos Registrados

| Tipo de Evento | Descripción | Icono |
|----------------|-------------|-------|
| `record_created` | Registro creado | ➕ |
| `record_updated` | Registro actualizado | ✏️ |
| `record_deleted` | Registro eliminado | 🗑️ |
| `order_created` | Orden creada | 📦 |
| `status_changed` | Estado cambiado | 🔄 |
| `area_changed` | Área cambiada | 📍 |
| `delivery_registered` | Entrega registrada | ✅ |
| `order_deleted` | Orden eliminada | ❌ |

## 🔍 Cómo Identificar Quién Hizo un Cambio

### En el Dashboard:
1. Cada notificación muestra el **usuario** que realizó la acción
2. El **badge de usuario** (👤) indica el nombre
3. La **fecha y hora exacta** del cambio

### Consulta Directa en Base de Datos:
```sql
SELECT 
    n.event_type,
    n.table_name,
    n.record_id,
    n.description,
    u.name as usuario,
    n.created_at,
    n.metadata
FROM news n
LEFT JOIN users u ON n.user_id = u.id
WHERE DATE(n.created_at) = CURDATE()
ORDER BY n.created_at DESC;
```

### Ver Cambios de un Registro Específico:
```sql
SELECT * FROM news 
WHERE table_name = 'registro_piso_corte' 
AND record_id = 123
ORDER BY created_at DESC;
```

### Ver Todas las Acciones de un Usuario:
```sql
SELECT 
    n.*,
    u.name
FROM news n
JOIN users u ON n.user_id = u.id
WHERE u.name = 'Nombre del Usuario'
ORDER BY n.created_at DESC;
```

## 🛡️ Seguridad y Privacidad

- ✅ Contraseñas y tokens NO se registran en auditoría
- ✅ Solo usuarios autenticados pueden ver notificaciones
- ✅ Los registros de auditoría NO se pueden modificar
- ✅ Se guarda el estado anterior y nuevo en cada cambio

## 📈 Estadísticas de Auditoría

Para ver estadísticas del día actual:
```javascript
// En la consola del navegador
fetch('/dashboard/audit-stats?date=2025-11-07')
  .then(r => r.json())
  .then(stats => console.log(stats));
```

## 🔧 Personalización

### Agregar Auditoría a Nuevos Modelos

1. Importar el trait:
```php
use App\Traits\Auditable;
```

2. Usar el trait en el modelo:
```php
class MiModelo extends Model
{
    use Auditable;
    // ...
}
```

¡Listo! El modelo ahora registrará automáticamente todos los cambios.

### Excluir Campos de la Auditoría

En el modelo, sobrescribe el método:
```php
protected function getAuditableAttributes(): array
{
    $excluded = ['password', 'remember_token', 'campo_sensible'];
    return array_diff_key($this->attributes, array_flip($excluded));
}
```

## 📝 Notas Importantes

1. **Rendimiento**: El sistema está optimizado con índices en la tabla `news`
2. **Almacenamiento**: Los registros de auditoría crecerán con el tiempo. Considera implementar limpieza automática de registros antiguos
3. **Timestamps**: Los modelos sin timestamps (como entregas) también se auditan correctamente
4. **Metadata**: Toda la información adicional se guarda en el campo JSON `metadata`

## 🐛 Solución de Problemas

### No aparecen notificaciones:
1. Verifica que ejecutaste las migraciones: `php artisan migrate`
2. Revisa que el usuario esté autenticado
3. Verifica en la tabla `news` si hay registros: `SELECT * FROM news ORDER BY created_at DESC LIMIT 10;`

### Error al crear registros:
1. Verifica que las columnas `table_name` y `record_id` existan en la tabla `news`
2. Ejecuta: `php artisan migrate:fresh` (⚠️ CUIDADO: Esto borrará todos los datos)

### Los cambios no se registran:
1. Verifica que el modelo tenga el trait `Auditable`
2. Asegúrate de que el usuario esté autenticado al hacer cambios

## 📞 Soporte

Si encuentras algún problema o necesitas agregar auditoría a más tablas, contacta al equipo de desarrollo.

---

**Fecha de Implementación**: 7 de Noviembre, 2025
**Versión**: 1.0
**Estado**: ✅ Completado y Funcional
