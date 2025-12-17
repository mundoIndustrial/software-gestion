# 👤 USUARIO COSTURA-REFLECTIVO - EXPLICACIÓN COMPLETA

## ¿QUÉ ES COSTURA-REFLECTIVO?

**Costura-Reflectivo** es un **usuario especial del sistema** que actúa como un **filtro automático** para mostrar pedidos que cumplen con características muy específicas relacionadas con cotizaciones tipo REFLECTIVO.

```
┌──────────────────────────────────────────────────────────┐
│  USUARIO: Costura-Reflectivo                            │
│  ├─ Email: costura-reflectivo@mundoindustrial.com       │
│  ├─ Contraseña: password123                             │
│  ├─ Rol: Costurero                                      │
│  └─ Función: Filtrar pedidos especiales                 │
└──────────────────────────────────────────────────────────┘
```

---

## ¿POR QUÉ EXISTE ESTE USUARIO?

### Contexto del Negocio

En la fábrica **Mundo Industrial** existen diferentes **tipos de cotizaciones**:

1. **Cotización PRENDA** - Prendas normales (camisas, pantalones, etc.)
2. **Cotización LOGO** - Solo logos/bordados
3. **Cotización REFLECTIVO** - Prendas con material reflectivo (chalecos de seguridad, cintas, etc.)

### El Problema

Las cotizaciones **REFLECTIVO** tienen un flujo especial:

- ❌ **NO deben pasar por la fase INSUMOS** (compra de materiales)
- ✅ **DEBEN ir directo a COSTURA** con Ramiro (encargado especializado)

### La Solución

Se creó el usuario **Costura-Reflectivo** que:

1. **Detecta automáticamente** cuando se crea un pedido REFLECTIVO
2. **Crea automáticamente** los procesos necesarios (creacion_de_orden + costura con Ramiro)
3. **Muestra solo esos pedidos** en un dashboard especializado
4. **Facilita la gestión** de estos pedidos especiales

---

## ¿CÓMO FUNCIONA?

### Flujo Automático

```
┌────────────────────────────────────────────────────────────┐
│  ASESOR crea COTIZACIÓN tipo "REFLECTIVO"               │
└──────────────────┬─────────────────────────────────────────┘
                   │
                   ↓
        ┌──────────────────────────────────┐
        │  ASESOR aprueba la cotización    │
        └──────────┬───────────────────────┘
                   │
                   ↓
        ┌──────────────────────────────────┐
        │  ASESOR crea PEDIDO desde cot.  │
        └──────────┬───────────────────────┘
                   │
        ┌──────────▼──────────────────────────────────────┐
        │  SISTEMA AUTOMÁTICAMENTE:                       │
        │  ✅ Crea proceso "creacion_de_orden"          │
        │  ✅ Crea proceso "Costura" con Ramiro         │
        │  ✅ Salta la fase INSUMOS                     │
        │  ✅ Marca pedido como área "Costura"          │
        └──────────┬──────────────────────────────────────┘
                   │
                   ↓
        ┌──────────────────────────────────┐
        │  Pedido lista para Ramiro        │
        │  (Costurero especializado)       │
        └──────────────────────────────────┘
```

---

## ¿QUÉ DATOS VE COSTURA-REFLECTIVO?

### Cuando un usuario inicia sesión como Costura-Reflectivo

Se le muestra **únicamente** los pedidos que cumplan:

```
✅ CONDICIÓN 1: Pedido en área "Costura"
   (campo: pedidos_produccion.area = 'Costura')

✅ CONDICIÓN 2: Proceso Costura asignado a Ramiro
   (en procesos_prenda: proceso='Costura' Y encargado='Ramiro')
```

### Ejemplo Real

```
Base de datos actual:

Total pedidos en el sistema: 10,000+
├─ Pedidos en área Costura: 44
├─ Procesos Costura → Ramiro: 1,177
│
└─ RESULTADO PARA COSTURA-REFLECTIVO: 25 pedidos
   ├─ En proceso: 21
   └─ Completados: 0
```

---

## COMPARACIÓN: USUARIO NORMAL vs COSTURA-REFLECTIVO

```
╔════════════════════════╦═══════════════════════╦═══════════════════════╗
║ Aspecto                ║ Costurero Normal      ║ Costura-Reflectivo    ║
╠════════════════════════╬═══════════════════════╬═══════════════════════╣
║ Nombre usuario         ║ Juan, María, etc.     ║ Costura-Reflectivo    ║
║ Email                  ║ juan@empresa.com      ║ costura-reflectivo@   ║
║                        ║                       ║ mundoindustrial.com   ║
╠════════════════════════╬═══════════════════════╬═══════════════════════╣
║ ¿Qué ve?               ║ Pedidos normales      ║ Solo pedidos          ║
║                        ║ asignados a él        ║ REFLECTIVO            ║
║                        ║ (por nombre)          ║ (con Ramiro)          ║
╠════════════════════════╬═══════════════════════╬═══════════════════════╣
║ Filtrado por           ║ nombre_usuario =      ║ area = 'Costura'      ║
║                        ║ 'Juan'                ║ Y encargado = 'Ramiro'║
╠════════════════════════╬═══════════════════════╬═══════════════════════╣
║ Procesos creados       ║ Manualmente por       ║ AUTOMÁTICAMENTE       ║
║                        ║ administrador         ║ cuando se crea pedido │
╠════════════════════════╬═══════════════════════╬═══════════════════════╣
║ Automatización         ║ NO                    ║ SÍ (para REFLECTIVO)  ║
╠════════════════════════╬═══════════════════════╬═══════════════════════╣
║ Total pedidos          ║ Variable por persona  ║ 25 (fijos para        ║
║                        ║                       ║ REFLECTIVO)           ║
╚════════════════════════╩═══════════════════════╩═══════════════════════╝
```

---

## PROCESO PASO A PASO

### PASO 1: Asesor crea cotización

```
Pantalla: Crear Cotización
├─ Cliente: ABC Company
├─ Tipo de Cotización: [dropdown] → Selecciona "REFLECTIVO"
└─ Agrega prendas con reflectivo
```

### PASO 2: Asesor aprueba

```
Cotización #1001 creada
├─ Estado: ENVIADA
├─ Tipo: REFLECTIVO ✓
└─ Acciones:
   ├─ Ver detalles
   └─ APROBAR ← Asesor hace clic
```

### PASO 3: Asesor crea pedido

```
Pantalla: Crear Pedido de Producción
├─ Selecciona cotización #1001
├─ Ingresa cantidades por talla
└─ Haz clic en "Crear Pedido"
```

### PASO 4: Sistema crea pedido (en BD)

```
Inserción en pedidos_produccion:
├─ numero_pedido: 45121 (generado)
├─ cotizacion_id: 1001
├─ cliente: ABC Company
├─ area: 'Costura' ← AUTOMÁTICO
└─ estado: 'En Ejecución'
```

### PASO 5: Sistema crea procesos (AUTOMÁTICO - Listener)

```
Listener detecta: ¿cotizacion.tipo = 'REFLECTIVO'?
Respuesta: SÍ ✓

Crea automáticamente en procesos_prenda:

Proceso 1:
├─ numero_pedido: 45121
├─ proceso: 'creacion_de_orden'
├─ estado_proceso: 'Completado'
└─ fecha_inicio: 17/12/2025 10:30

Proceso 2:
├─ numero_pedido: 45121
├─ proceso: 'Costura'
├─ encargado: 'Ramiro' ← AUTOMÁTICO
├─ estado_proceso: 'En Ejecución'
└─ fecha_inicio: 17/12/2025 10:30
```

### PASO 6: Pedido aparece en dashboard

```
Usuario "Costura-Reflectivo" accede a /operario/dashboard

Sistema ejecuta:
1. SELECT * FROM pedidos_produccion WHERE area = 'Costura'
2. Para cada pedido, verifica:
   ├─ ¿Existe proceso Costura?
   └─ ¿Es con Ramiro?
3. Si ambas son SÍ → Incluir en lista

RESULTADO: 25 pedidos mostrados
```

---

## DIFERENCIA: CON vs SIN COSTURA-REFLECTIVO

### ANTES (Sin automatización)

```
Asesor crea pedido REFLECTIVO
    ↓
❌ Pedido pasa por fase INSUMOS (sin necesidad)
    ↓
❌ Administrador debe crear procesos manualmente
    ↓
❌ Riesgo de errores (olvidar asignar a Ramiro)
    ↓
❌ Procesos desorganizados
```

### AHORA (Con Costura-Reflectivo)

```
Asesor crea pedido REFLECTIVO
    ↓
✅ Procesos se crean AUTOMÁTICAMENTE
    ↓
✅ Salta fase INSUMOS correctamente
    ↓
✅ Se asigna automáticamente a Ramiro
    ↓
✅ Pedido aparece en dashboard especial
    ↓
✅ Todo organizado y sin errores
```

---

## ¿QUIÉN ES RAMIRO?

**Ramiro** es el **costurero especializado** en trabajar con materiales REFLECTIVO.

```
Usuario del Sistema: Ramiro
├─ Rol: Costurero
├─ Especialidad: Trabajo con reflectivo
├─ Asignación: Automática para cotizaciones REFLECTIVO
└─ Procesos que ve:
   ├─ Todos los procesos "Costura" asignados a él
   └─ Puede incluir reflectivo o no (depende tipo cot.)
```

---

## VENTAJAS DEL USUARIO COSTURA-REFLECTIVO

```
✅ AUTOMATIZACIÓN
   └─ Los procesos se crean solos (sin intervención)

✅ EFICIENCIA
   └─ Los pedidos REFLECTIVO no pierden tiempo en INSUMOS

✅ CLARIDAD
   └─ Dashboard especializado muestra solo lo relevante

✅ EVITA ERRORES
   └─ No hay riesgo de olvidar asignar a Ramiro

✅ ORGANIZACIÓN
   └─ Procesos siempre consistentes y ordenados

✅ TRAZABILIDAD
   └─ Fácil rastrear qué es REFLECTIVO vs. normal
```

---

## DATOS TÉCNICOS (Para Administrador)

### Ubicación en Base de Datos

```
Tabla: users
ID: 77
name: Costura-Reflectivo
email: costura-reflectivo@mundoindustrial.com
roles_ids: [5] (ID del rol costurero)
```

### Archivos de Código

| Archivo | Función |
|---------|---------|
| `app/Application/Operario/Services/ObtenerPedidosOperarioService.php` | Lógica de filtrado |
| `app/Listeners/CrearProcesosParaCotizacionReflectivo.php` | Automatización |
| `app/Providers/EventServiceProvider.php` | Registro del listener |
| `database/seeders/CrearUsuarioCosturaReflectivoSeeder.php` | Creación del usuario |

### URLs Disponibles

```
/login                          → Login del sistema
/operario/dashboard             → Dashboard Costura-Reflectivo
/operario/mis-pedidos           → Tabla de pedidos
/operario/pedido/{numero}       → Detalle de pedido
```

---

## CASOS DE USO REALES

### Caso 1: Cotización REFLECTIVO

```
Empresa: "Seguridad Total SA"
Producto: Chalecos reflectivos con franjas
Cotización tipo: REFLECTIVO ✓

Asesor crea pedido:
✅ Procesos creados automáticamente
✅ Va directo a Costura
✅ Asignado a Ramiro
✅ Aparece en dashboard Costura-Reflectivo
```

### Caso 2: Cotización PRENDA normal

```
Empresa: "Textil Andino"
Producto: Camisas polo
Cotización tipo: PRENDA ✓

Asesor crea pedido:
❌ Procesos NO se crean automáticamente
❌ Flujo normal (INSUMOS → CORTE → COSTURA)
❌ NO aparece en Costura-Reflectivo
```

### Caso 3: Revisión de pedidos REFLECTIVO

```
Supervisor accede a panel de administrador
├─ Filtra por encargado "Ramiro"
├─ Ve 1,177 procesos asignados a Ramiro
├─ De esos, 25 son pedidos completos (area='Costura')
└─ Puede revisar estado y hacer cambios
```

---

## RESUMEN EN UNA FRASE

> **Costura-Reflectivo es un usuario especial que automáticamente filtra y organiza los pedidos de cotizaciones reflectivo, evitando que pasen por la fase de INSUMOS y asignándolos directamente a Ramiro para que los procese en COSTURA.**

---

## CHECKLIST DE FUNCIONALIDADES

- ✅ Usuario crea autenticación
- ✅ Filtra pedidos por área "Costura"
- ✅ Filtra pedidos por encargado "Ramiro"
- ✅ Muestra 25 pedidos (en BD actual)
- ✅ Dashboard con estadísticas
- ✅ Tabla con filtros y búsqueda
- ✅ Detalle de cada pedido
- ✅ Listener crea procesos automáticamente
- ✅ Procesos creados son "creacion_de_orden" + "Costura"
- ✅ Encargado automático es "Ramiro"
- ✅ Salta fase INSUMOS

---

## PREGUNTAS FRECUENTES

### ¿Por qué se llama "Costura-Reflectivo"?

Porque filtra pedidos de **Costura** que provienen de cotizaciones **REFLECTIVO**.

### ¿Es un usuario real o un filtro?

Es un **usuario real** (existe en la BD) que actúa como un **filtro**.

### ¿Cuántos pedidos ve?

Depende de cuántos cumplan las condiciones:
- Área = Costura
- Encargado = Ramiro

En la BD actual: **25 pedidos**

### ¿Qué pasa si cambio el nombre de Ramiro?

Necesitarías actualizar:
- El Listener
- El Servicio de filtrado
- Actualizar todo en la BD

### ¿Puedo tener otro usuario similar?

Sí, podrías crear "Costura-Logo" o "Bordado-Especial", siguiendo el mismo patrón.

---

**Explicación del Usuario Costura-Reflectivo**
Documento Ejecutivo
Versión: 1.0
Fecha: 17 Diciembre 2025
