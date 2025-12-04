# Plan de Implementación: Estados Cotizaciones y Pedidos con Colas

## 1. ESTRUCTURA DE ESTADOS

### 1.1 Estados de Cotización
```
BORRADOR
├─ Asesor crea cotización
├─ Sin número_cotizacion asignado
├─ Solo se asigna ID de tabla
└─ Solo visible para la Asesor

ENVIADA_CONTADOR
├─ Asesor hace click "Enviar"
├─ Se asigna número_cotizacion (AUTOINCREMENT) - VÍA COLA
├─ Llega a Contador
└─ Estado: ENVIADA_CONTADOR

APROBADA_CONTADOR
├─ Contador verifica y aprueba
├─ Se envía a Aprobador_Cotizaciones
└─ Estado: APROBADA_CONTADOR

APROBADA_COTIZACIONES
├─ Aprobador_Cotizaciones verifica y aprueba
├─ Asesor YA PUEDE crear Pedido
├─ Buscable por cliente o número_cotizacion
└─ Estado: APROBADA_COTIZACIONES

CONVERTIDA_PEDIDO
├─ Asesor crea Pedido desde cotización
├─ Pedido va a PENDIENTE_SUPERVISOR
└─ Estado: CONVERTIDA_PEDIDO

FINALIZADA
├─ Pedido fue aprobado en producción
└─ Estado: FINALIZADA
```

### 1.2 Estados de Pedido (pedidos_produccion)
```
PENDIENTE_SUPERVISOR
├─ Se crea desde Cotización APROBADA_COTIZACIONES
├─ Sin número_pedido asignado aún (solo ID)
├─ "Número de pedido: Por asignar" en front
├─ Llega a Supervisor_Pedidos
└─ Estado: PENDIENTE_SUPERVISOR

APROBADO_SUPERVISOR
├─ Supervisor_Pedidos aprueba
├─ Se asigna número_pedido (AUTOINCREMENT) - VÍA COLA
├─ Ahora va a Producción
└─ Estado: APROBADO_SUPERVISOR

EN_PRODUCCION
├─ Ya asignó número_pedido
├─ Va a los diferentes procesos
└─ Estado: EN_PRODUCCION

FINALIZADO
├─ Todos los procesos completados
└─ Estado: FINALIZADO
```

## 2. TABLAS BASE DE DATOS

### 2.1 Tabla: cotizaciones (CAMBIOS)
```sql
ALTER TABLE cotizaciones ADD COLUMN estado ENUM(
    'BORRADOR',
    'ENVIADA_CONTADOR',
    'APROBADA_CONTADOR',
    'APROBADA_COTIZACIONES',
    'CONVERTIDA_PEDIDO',
    'FINALIZADA'
) DEFAULT 'BORRADOR' AFTER es_borrador;

ALTER TABLE cotizaciones ADD COLUMN numero_cotizacion INT UNSIGNED UNIQUE NULLABLE AFTER id;
ALTER TABLE cotizaciones ADD COLUMN aprobada_por_contador_en TIMESTAMP NULL AFTER estado;
ALTER TABLE cotizaciones ADD COLUMN aprobada_por_aprobador_en TIMESTAMP NULL AFTER aprobada_por_contador_en;
```

### 2.2 Tabla: pedidos_produccion (CAMBIOS)
```sql
ALTER TABLE pedidos_produccion ADD COLUMN estado ENUM(
    'PENDIENTE_SUPERVISOR',
    'APROBADO_SUPERVISOR',
    'EN_PRODUCCION',
    'FINALIZADO'
) DEFAULT 'PENDIENTE_SUPERVISOR' AFTER area;

ALTER TABLE pedidos_produccion ADD COLUMN numero_pedido INT UNSIGNED UNIQUE NULLABLE AFTER numero_cotizacion;
ALTER TABLE pedidos_produccion ADD COLUMN aprobado_por_supervisor_en TIMESTAMP NULL AFTER estado;
```

### 2.3 Tabla: historial_cambios_cotizaciones (NUEVA)
```sql
CREATE TABLE historial_cambios_cotizaciones (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    cotizacion_id BIGINT UNSIGNED NOT NULL,
    estado_anterior VARCHAR(50) NULLABLE,
    estado_nuevo VARCHAR(50) NOT NULL,
    usuario_id BIGINT UNSIGNED NULLABLE,
    usuario_nombre VARCHAR(255),
    rol_usuario VARCHAR(100),
    razon_cambio TEXT NULLABLE,
    ip_address VARCHAR(45) NULLABLE,
    user_agent TEXT NULLABLE,
    datos_adicionales JSON NULLABLE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_cotizacion_id (cotizacion_id),
    INDEX idx_estado_nuevo (estado_nuevo),
    INDEX idx_created_at (created_at)
);
```

### 2.4 Tabla: historial_cambios_pedidos (NUEVA)
```sql
CREATE TABLE historial_cambios_pedidos (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    pedido_id BIGINT UNSIGNED NOT NULL,
    estado_anterior VARCHAR(50) NULLABLE,
    estado_nuevo VARCHAR(50) NOT NULL,
    usuario_id BIGINT UNSIGNED NULLABLE,
    usuario_nombre VARCHAR(255),
    rol_usuario VARCHAR(100),
    razon_cambio TEXT NULLABLE,
    ip_address VARCHAR(45) NULLABLE,
    user_agent TEXT NULLABLE,
    datos_adicionales JSON NULLABLE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos_produccion(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_pedido_id (pedido_id),
    INDEX idx_estado_nuevo (estado_nuevo),
    INDEX idx_created_at (created_at)
);
```

## 3. FLUJO DE COLAS

### 3.1 Job: AsignarNumeroCotizacionJob
```
Trigger: Cuando Contador aprueba cotización (ENVIADA_CONTADOR → APROBADA_CONTADOR)
Acción: 
  - Obtiene MAX(numero_cotizacion) + 1
  - Asigna número a la cotización
  - Registra en historial
Delay: Queue normal (no retrasado)
```

### 3.2 Job: EnviarCotizacionAContadorJob
```
Trigger: Asesor hace click "Enviar"
Acción:
  - Cambia estado BORRADOR → ENVIADA_CONTADOR
  - Registra en historial
  - Notifica a Contador
Delay: Queue normal
```

### 3.3 Job: EnviarCotizacionAAprobadorJob
```
Trigger: Contador aprueba
Acción:
  - Cambia estado APROBADA_CONTADOR → APROBADA_COTIZACIONES
  - Asigna número_cotizacion
  - Registra en historial
  - Notifica a Aprobador_Cotizaciones
Delay: Queue normal
```

### 3.4 Job: AsignarNumeroPedidoJob
```
Trigger: Supervisor_Pedidos aprueba pedido
Acción:
  - Obtiene MAX(numero_pedido) + 1
  - Asigna número a pedido
  - Cambia estado APROBADO_SUPERVISOR → EN_PRODUCCION
  - Registra en historial
Delay: Queue normal
```

## 4. MODELOS ELOQUENT

### 4.1 EstadoCotizacion.php (Enum)
```php
enum EstadoCotizacion: string {
    case BORRADOR = 'BORRADOR';
    case ENVIADA_CONTADOR = 'ENVIADA_CONTADOR';
    case APROBADA_CONTADOR = 'APROBADA_CONTADOR';
    case APROBADA_COTIZACIONES = 'APROBADA_COTIZACIONES';
    case CONVERTIDA_PEDIDO = 'CONVERTIDA_PEDIDO';
    case FINALIZADA = 'FINALIZADA';
}
```

### 4.2 EstadoPedido.php (Enum)
```php
enum EstadoPedido: string {
    case PENDIENTE_SUPERVISOR = 'PENDIENTE_SUPERVISOR';
    case APROBADO_SUPERVISOR = 'APROBADO_SUPERVISOR';
    case EN_PRODUCCION = 'EN_PRODUCCION';
    case FINALIZADO = 'FINALIZADO';
}
```

### 4.3 HistorialCambiosCotizacion.php (Model)
```php
- cotizacion_id
- estado_anterior
- estado_nuevo
- usuario_id
- usuario_nombre
- rol_usuario
- razon_cambio
- ip_address
- user_agent
- datos_adicionales (JSON)
```

### 4.4 HistorialCambiosPedido.php (Model)
```php
- pedido_id
- estado_anterior
- estado_nuevo
- usuario_id
- usuario_nombre
- rol_usuario
- razon_cambio
- ip_address
- user_agent
- datos_adicionales (JSON)
```

## 5. SERVICIOS

### 5.1 CotizacionEstadoService
```
Métodos:
- enviarACOuntador(Cotizacion): Dispatch job
- aprobarComoCotador(Cotizacion): Dispatch job + asignar número
- aprobarComoAprobador(Cotizacion): Dispatch job
- convertirAPedido(Cotizacion): Dispatch job
- obtenerEstadoActual(Cotizacion): Estado
- obtenerHistorial(Cotizacion): Colección HistorialCambiosCotizacion
- validarTransicion(estado_actual, estado_nuevo): boolean
```

### 5.2 PedidoEstadoService
```
Métodos:
- crearDesdeC cotización(Cotizacion): PedidoProduccion
- aprobarComSupervisor(PedidoProduccion): Dispatch job + asignar número
- obtenerEstadoActual(PedidoProduccion): Estado
- obtenerHistorial(PedidoProduccion): Colección HistorialCambiosPedido
- validarTransición(estado_actual, estado_nuevo): boolean
```

## 6. CONTROLADORES

### 6.1 CotizacionEstadoController
```
- POST /cotizaciones/{id}/enviar (Asesor)
- POST /cotizaciones/{id}/aprobar-contador (Contador)
- POST /cotizaciones/{id}/aprobar-aprobador (Aprobador_Cotizaciones)
- GET /cotizaciones/{id}/historial (Todos)
- GET /cotizaciones/{id}/seguimiento (Asesor - para ver estado)
```

### 6.2 PedidoEstadoController
```
- POST /pedidos/{id}/aprobar-supervisor (Supervisor_Pedidos)
- GET /pedidos/{id}/historial (Todos)
- GET /pedidos/{id}/seguimiento (Asesor - para ver en qué proceso está)
```

## 7. VISTAS/COMPONENTES

### 7.1 Componentes necesarios
```
- button-enviar-cotizacion.blade.php (Asesor - Borrador)
- button-aprobar-contador.blade.php (Contador - Enviada_contador)
- button-aprobar-aprobador.blade.php (Aprobador - Aprobada_contador)
- button-crear-pedido.blade.php (Asesor - Aprobada_cotizaciones)
- button-aprobar-supervisor.blade.php (Supervisor - Pendiente_supervisor)
- modal-historial-cotizacion.blade.php (Todos)
- modal-historial-pedido.blade.php (Todos)
- table-seguimiento-cotizaciones.blade.php (Asesor)
- table-seguimiento-pedidos.blade.php (Asesor)
```

## 8. MIGRACIONES NECESARIAS

1. Crear: `2025_12_04_000001_add_estado_to_cotizaciones.php`
2. Crear: `2025_12_04_000002_add_estado_to_pedidos_produccion.php`
3. Crear: `2025_12_04_000003_create_historial_cambios_cotizaciones_table.php`
4. Crear: `2025_12_04_000004_create_historial_cambios_pedidos_table.php`

## 9. ORDEN DE IMPLEMENTACIÓN

1. Crear Migraciones (1-4)
2. Crear Enums (EstadoCotizacion, EstadoPedido)
3. Crear Modelos (HistorialCambiosCotizacion, HistorialCambiosPedido)
4. Crear Servicios (CotizacionEstadoService, PedidoEstadoService)
5. Crear Jobs (AsignarNumeroCotizacionJob, etc.)
6. Crear Controllers
7. Crear Rutas
8. Crear Vistas/Componentes
9. Crear Seeders de prueba
10. Testing

## 10. FLUJO COMPLETO DEL CASO FELIZ

```
1. ASESOR: Crea cotización (BORRADOR)
2. ASESOR: Click "Enviar" 
   → Job: EnviarCotizacionAContadorJob
   → Estado: ENVIADA_CONTADOR
   
3. CONTADOR: Recibe notificación
4. CONTADOR: Verifica datos
5. CONTADOR: Click "Aprobar"
   → Job: AsignarNumeroCotizacionJob (asigna número_cotizacion)
   → Job: EnviarCotizacionAAprobadorJob
   → Estado: APROBADA_COTIZACIONES
   
6. APROBADOR: Recibe notificación
7. APROBADOR: Verifica datos
8. APROBADOR: Click "Aprobar"
   → Estado: APROBADA_COTIZACIONES
   
9. ASESOR: Busca cotización por cliente o número_cotizacion
10. ASESOR: Click "Crear Pedido"
    → Job: CrearPedidoDesdeC otizacionJob
    → PedidoProduccion creado con estado PENDIENTE_SUPERVISOR
    → Cotización estado: CONVERTIDA_PEDIDO
    
11. SUPERVISOR_PEDIDOS: Recibe notificación
12. SUPERVISOR_PEDIDOS: Verifica datos
13. SUPERVISOR_PEDIDOS: Click "Aprobar"
    → Job: AsignarNumeroPedidoJob (asigna número_pedido)
    → Estado: EN_PRODUCCION
    → El pedido va a Producción
```

## 11. CONSIDERACIONES TÉCNICAS

- **Autoincrement de números**: Se hace VÍA COLA para evitar race conditions
- **Auditoría completa**: Cada cambio de estado se registra con usuario, rol, IP, etc.
- **Validaciones**: No permitir transiciones de estado inválidas
- **Notificaciones**: Cada cambio importante notifica a rol correspondiente
- **Performance**: Usar índices en historial_cambios_* para búsquedas rápidas
- **Búsqueda**: Asesor busca cotizaciones aprobadas por cliente o número_cotizacion
- **Seguimiento**: Asesor puede ver en qué estado está cada cotización y pedido
