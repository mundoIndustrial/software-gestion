#  Protección de Acceso - Rol Bodeguero

## Resumen

El bodeguero está **completamente protegido** y NUNCA puede acceder a vistas/recibos que no le corresponden.

## Capas de Protección

### 1️⃣ **Redirección post-Login** 
**Archivo**: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

Después de autenticarse, bodeguero es redirigido **DIRECTAMENTE** a:
```
GET /vistas?tipo=bodega
```

**Código**:
```php
if ($roleName === 'bodeguero') {
    return redirect()->route('vistas.index', ['tipo' => 'bodega'], absolute: false));
}
```

### 2️⃣ **Validación en VistasController**
**Archivo**: `app/Http/Controllers/VistasController.php`

Si bodeguero intenta acceder a otras vistas:
```
GET /vistas?tipo=corte         BLOQUEADO
GET /vistas?tipo=costura       BLOQUEADO
GET /vistas?origen=bodega      BLOQUEADO (sin tipo=bodega)
```

**Comportamiento**:
- Detecta si es bodeguero
- Verifica `tipo` solicitado
- Si no es `bodega`, **redirige de vuelta** a `/vistas?tipo=bodega`
- Registra intento en logs

**Logs**:
```
 [VistasController] Intento de acceso bloqueado para bodeguero
  - user_id: 5
  - tipo_solicitado: corte
  - mensaje: Bodeguero intentó acceder a: corte
```

### 3️⃣ **Filtrado de Procesos en API**
**Archivo**: `app/Http/Controllers/Api_temp/PedidoController.php`

Cuando bodeguero solicita `/pedidos-public/{id}/recibos-datos`:
- Backend detecta rol bodeguero
- Filtra procesos → solo `costura-bodega`
- Otros roles ven todos los procesos

**Ejemplo**:
```
Bodeguero solicita recibos de prenda con 7 procesos
Respuesta: SOLO 1 proceso (costura-bodega)

Asesor solicita recibos de misma prenda
Respuesta: TODOS 7 procesos
```

### 4️⃣ **Middleware OperarioAccess**
**Archivo**: `app/Http/Middleware/OperarioAccess.php`

Todas las rutas `/operario/*` están protegidas:
```php
if (!$usuario->hasAnyRole(['cortador', 'costurero', 'bodeguero'])) {
    // Bloquear acceso
}
```

### 5️⃣ **Sidebar Dinámico**
**Archivo**: `resources/views/layouts/sidebar.blade.php`

Bodeguero ve **SOLO** estas opciones en menú:
-  Corte Bodega
-  Costura Bodega

**No ve**:
-  Corte (pedidos)
-  Costura (pedidos)
-  Control de Calidad

## Flujo de Acceso Bodeguero

```
┌─────────────────────────────────────────┐
│  Usuario abre login                     │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│  Ingresa credenciales (bodeguero)       │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│  AuthenticatedSessionController::store()│
│  Detecta rol = bodeguero                │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│  redirect → /vistas?tipo=bodega         │
│  NUNCA a dashboard o ruta random        │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│  VistasController::index()              │
│  Valida: tipo === 'bodega'              │
│   PERMITIDO                           │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│  Renderiza Vista de Bodega              │
│  Muestra prendas COSTURA-BODEGA         │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│  Bodeguero abre recibos                 │
│  Llamada: /pedidos-public/{id}/recibos  │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│  PedidoController::obtenerDetalleCompleto│
│  Detecta: usuario.hasRole('bodeguero')  │
│  Filtra procesos → solo costura-bodega  │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│  Response: procesos filtrados           │
│  Frontend renderiza SOLO COSTURA-BODEGA │
└─────────────────────────────────────────┘
```

## Escenarios de Bloqueo

###  Intento 1: Acceder directamente a `/vistas?tipo=corte`
```
GET /vistas?tipo=corte
↓
VistasController detecta bodeguero
↓
Valida: tipo !== 'bodega' → BLOQUEAR
↓
Redirige a: /vistas?tipo=bodega
↓
Log: "Intento de acceso bloqueado para bodeguero"
```

###  Intento 2: Manipular parámetro origen
```
GET /vistas?tipo=bodega&origen=otras
↓
VistasController detecta bodeguero
↓
Valida: origen !== 'pedido' → BLOQUEAR
↓
Redirige a: /vistas?tipo=bodega
```

###  Intento 3: Acceder sin tipo
```
GET /vistas
↓
VistasController::determinarTipo()
↓
Detecta bodeguero → fuerza tipo=bodega
```

## Testing

### Escenario 1: Login Normal
```bash
# Bodeguero inicia sesión
POST /login
  email: bodeguero@ejemplo.com
  password: password

# Resultado esperado:
Location: /vistas?tipo=bodega 
```

### Escenario 2: Intento de Manipulación
```bash
# Usuario intenta ir a corte
GET /vistas?tipo=corte
  (como bodeguero)

# Resultado esperado:
Location: /vistas?tipo=bodega
alert: "Solo puedes acceder a la vista de Bodega" 
```

### Escenario 3: Recibos Filtrados
```bash
# Bodeguero solicita recibos
GET /pedidos-public/1/recibos-datos

# Respuesta:
[
  {
    prendas: [
      {
        procesos: [
          { tipo_proceso: "costura-bodega" }  ← ÚNICO PROCESO
        ]
      }
    ]
  }
] 
```

## Logs de Auditoría

```log
[2026-02-04] INFO: Login usuario
  - user_id: 5
  - role_name: bodeguero

[2026-02-04] INFO: Redirección post-login
  - user_id: 5
  - destino: /vistas?tipo=bodega

[2026-02-04] INFO:  Bodeguero accediendo a recibos-datos
  - user_id: 5
  - path: /pedidos-public/1/recibos-datos

[2026-02-04] INFO:  FILTRO BODEGUERO: Filtrando procesos
  - procesos_antes: 7
  - procesos_despues: 1
```

## Archivos Modificados

1.  `app/Http/Controllers/Auth/AuthenticatedSessionController.php` - Redirección
2.  `app/Http/Controllers/VistasController.php` - Validación de vistas
3.  `app/Http/Controllers/Api_temp/PedidoController.php` - Filtrado de procesos
4.  `app/Http/Middleware/OperarioAccess.php` - Incluye bodeguero
5.  `app/Http/Middleware/BodegueroRecibosProtection.php` - Protección adicional
6.  `resources/views/layouts/sidebar.blade.php` - Filtrado de menú

---

**Implementación**: 4 de Febrero de 2026
**Estado**:  Protección Multinivel Activada
