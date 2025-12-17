# 📖 GUÍA DE USO - ROL COSTURA-REFLECTIVO

## 🚀 INICIO RÁPIDO

### 1. Acceso al Sistema

**URL de Login:**
```
http://localhost:8000/login
```

**Credenciales:**
```
Email:    costura-reflectivo@mundoindustrial.com
Password: password123
```

### 2. Después de Login

El sistema **automáticamente** te redirecciona a:
```
http://localhost:8000/operario/dashboard
```

---

## 📊 DASHBOARD

### ¿Qué veo en el Dashboard?

```
┌─────────────────────────────────────────────────────┐
│  MIS ÓRDENES - COSTURA-REFLECTIVO                  │
├─────────────────────────────────────────────────────┤
│                                                     │
│  📊 Estadísticas:                                  │
│  ├─ Total de órdenes: 25                          │
│  ├─ En proceso: 21                                │
│  ├─ Completadas: 0                                │
│  └─ Área: Costura-Reflectivo                      │
│                                                     │
│  📋 Últimas Órdenes:                              │
│  ├─ #45092 - CERAMICA ITALIA                      │
│  │  Estado: Completado                            │
│  │  Prendas: CAMISA POLO MANGA CORTA...           │
│  │  Cantidad: 7                                    │
│  │                                                 │
│  ├─ #45097 - CERAMICA ITALIA                      │
│  │  Estado: En Ejecución                          │
│  │  Prendas: CAMIBUSO POLO MANGA LARGA...         │
│  │  Cantidad: 2                                    │
│  │                                                 │
│  └─ ... más órdenes                               │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## 📋 MIS PEDIDOS

**Acceso:** `http://localhost:8000/operario/mis-pedidos`

### ¿Qué puedo hacer?

#### 1. **Filtrar por Estado**

```
Estado: [dropdown]
  ├─ Todos
  ├─ En Ejecución
  ├─ Completada
  └─ Pendiente
```

Selecciona un estado para ver solo los pedidos en esa fase.

#### 2. **Ordenar Pedidos**

```
Ordenar por: [dropdown]
  ├─ Más Reciente (default)
  ├─ Más Antiguo
  └─ Cliente (A-Z)
```

#### 3. **Búsqueda en Tiempo Real**

```
🔍 Buscar: [_______________]
```

Puedes buscar por:
- **Número de pedido** (ej: 45092)
- **Cliente** (ej: CERAMICA)
- **Descripción** (ej: CAMISA)

---

## 👁️ DETALLE DE PEDIDO

**Acceso:** Haz clic en cualquier pedido o ve a:
```
http://localhost:8000/operario/pedido/{numero_pedido}
```

### Información Mostrada

```
Pedido: #45092
├─ Cliente: CERAMICA ITALIA
├─ Fecha Creación: 15/12/2025
├─ Fecha Estimada: 20/12/2025
├─ Estado: Completado
├─ Forma de Pago: Transferencia
├─ Asesora: Maria Rodríguez
├─ Novedades: Ninguna
│
├─ Prendas:
│  ├─ CAMISA POLO MANGA CORTA CABALLERO
│  │  Color: Azul
│  │  Tela: Drill
│  │  Manga: Corta
│  │  Cantidad: 7 (S:2, M:3, L:2)
│  │
│  └─ CAMISA POLO MANGA CORTA DAMA
│     Color: Blanco
│     Tela: Drill
│     Manga: Corta
│     Cantidad: 5 (S:2, M:2, L:1)
│
└─ Procesos:
   ├─ Costura (En Ejecución)
   │  Encargado: Ramiro
   │  Inicio: 15/12/2025
   └─ ...
```

---

## 🔍 ¿CÓMO SE LLENA MI LISTA DE PEDIDOS?

### Para que un pedido aparezca en tu dashboard, debe cumplir:

```
✅ CONDICIÓN 1: Área del Pedido = "Costura"
   └─ Se define en la tabla pedidos_produccion

✅ CONDICIÓN 2: Proceso "Costura" asignado a "Ramiro"
   └─ Se define en la tabla procesos_prenda
   └─ Encargado = "Ramiro" (sin importar mayúsculas)
```

### Ejemplo de Pedidos que VES:

```
Pedido #45092:
├─ área: Costura ✓
├─ Procesos:
│  ├─ creacion_de_orden: Completado
│  └─ Costura: En Ejecución, Ramiro ✓ → ¡LO VES!
└─ RESULTADO: APARECE en tu lista

Pedido #45100:
├─ área: Insumos ✗
├─ Procesos:
│  └─ Costura: En Ejecución, Ramiro ✓
└─ RESULTADO: NO APARECE (área diferente)

Pedido #45200:
├─ área: Costura ✓
├─ Procesos:
│  ├─ Costura: En Ejecución, Juan ✗
│  └─ Bordado: Completado
└─ RESULTADO: NO APARECE (no es Ramiro)
```

---

## 🤖 AUTOMATIZACIÓN: CÓMO SE CREAN TUS PEDIDOS

### Cuando se crea una Cotización tipo "REFLECTIVO":

```
1️⃣ Asesor crea COTIZACIÓN
   └─ Tipo: REFLECTIVO

2️⃣ Asesor aprueba la cotización
   └─ Estado: APROBADA

3️⃣ Asesor crea PEDIDO desde cotización
   └─ Hace clic en "Crear Pedido"
   └─ Completa cantidad por talla
   └─ Envía formulario

4️⃣ Sistema crea PEDIDO automáticamente
   └─ numero_pedido: XXXX (generado)
   └─ cliente: (desde cotización)
   └─ area: Costura (automático para reflectivo)

5️⃣ Sistema crea PROCESOS automáticamente
   └─ Proceso 1: "creacion_de_orden" (Completado)
   └─ Proceso 2: "Costura" (En Ejecución, Ramiro)
   
6️⃣ ¡Pedido listo para ti!
   └─ Aparece en tu dashboard
   └─ Area: Costura-Reflectivo
   └─ Asignado a: Ramiro
```

---

## 📞 ACCIONES POSIBLES

### En el Dashboard

- ✅ Ver resumen de pedidos
- ✅ Ver últimos pedidos
- ✅ Hacer clic en un pedido para ver detalles
- ❌ NO puedes crear, editar, eliminar pedidos

### En Mi Pedidos

- ✅ Ver tabla completa de pedidos
- ✅ Filtrar por estado
- ✅ Ordenar por: Reciente, Antiguo, Cliente
- ✅ Buscar pedidos
- ✅ Hacer clic para ver detalles

### En Detalle de Pedido

- ✅ Ver información completa
- ✅ Ver prendas y detalles
- ✅ Ver procesos y estados
- ✅ Ver información de cliente y asesora
- ❌ NO puedes modificar datos

---

## ⚙️ CONFIGURACIÓN TÉCNICA

### URLs Disponibles

| URL | Función |
|-----|---------|
| `/login` | Iniciar sesión |
| `/operario/dashboard` | Panel principal |
| `/operario/mis-pedidos` | Tabla de pedidos |
| `/operario/pedido/{numero}` | Detalle de pedido |
| `/operario/api/pedidos` | API JSON (para integraciones) |

### Datos Técnicos

| Campo | Valor |
|-------|-------|
| User ID | 77 |
| Email | costura-reflectivo@mundoindustrial.com |
| Rol | Costurero |
| Tipo Operario | costurero-reflectivo |
| Área | Costura-Reflectivo |

---

## 🆘 TROUBLESHOOTING

### Problema: No veo ningún pedido

**Posibles causas:**
- No hay pedidos con área "Costura"
- No hay procesos asignados a "Ramiro"
- El seeder no se ejecutó correctamente

**Solución:**
1. Contacta al administrador
2. Verifica que existan cotizaciones tipo "REFLECTIVO"
3. Verifica que haya procesos asignados a Ramiro

### Problema: Veo menos pedidos de lo esperado

**Posibles causas:**
- Hay procesos "Costura" pero con otro encargado (no Ramiro)
- Hay pedidos pero con área diferente a "Costura"

**Solución:**
- Verifica que el nombre sea exactamente "Ramiro"
- Los nombres se normalizan automáticamente (mayúsculas, espacios)

### Problema: No puedo hacer login

**Posibles causas:**
- Contraseña incorrecta
- Usuario no existe
- Navegador con cache

**Solución:**
1. Limpia cache del navegador (Ctrl + Shift + Delete)
2. Intenta nuevamente
3. Contacta al administrador

---

## 📊 ESTADÍSTICAS

### Datos Actuales (17/12/2025)

```
Total de Pedidos en Sistema: ???
Pedidos con área 'Costura': 44
Procesos Costura → Ramiro: 1177
Pedidos visibles en tu dashboard: 25
  ├─ En Proceso: 21
  └─ Completados: 0
```

---

## 🔐 SEGURIDAD Y PRIVACIDAD

- ✅ Solo ves pedidos del área Costura + Ramiro
- ✅ No puedes modificar datos
- ✅ Tu sesión se cierra después de inactividad
- ✅ Todos los accesos se registran en logs

---

## 📝 NOTAS IMPORTANTES

1. **Automatización**: Los procesos se crean automáticamente para cotizaciones REFLECTIVO
2. **Normalización**: "Ramiro", "RAMIRO", "ramiro" son iguales (insensible a mayúsculas)
3. **Tiempo Real**: La lista se actualiza en tiempo real cuando el admin crea pedidos
4. **Sin Insumos**: Los pedidos reflectivo saltan la fase de INSUMOS
5. **Asignación Automática**: Todos los procesos de costura van a Ramiro automáticamente

---

## ✅ CHECKLIST PARA COMENZAR

- [ ] Abro navegador
- [ ] Voy a http://localhost:8000/login
- [ ] Ingreso: costura-reflectivo@mundoindustrial.com
- [ ] Ingreso contraseña: password123
- [ ] Se abre dashboard automáticamente
- [ ] Veo mis pedidos en el dashboard
- [ ] Hago clic en "Mis Pedidos" para ver lista completa
- [ ] Hago clic en un pedido para ver detalles
- [ ] ¡Listo para comenzar!

---

**Guía de Uso - ROL COSTURA-REFLECTIVO**
Actualizada: 17 Diciembre 2025
Versión: 1.0
