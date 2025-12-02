# 🔧 AGREGAR ROL supervisor_planta

## Pasos para agregar el rol

### 1. Crear el rol en la base de datos

```sql
INSERT INTO roles (name, description, created_at, updated_at) 
VALUES ('supervisor_planta', 'Supervisor de Planta - Gestión de órdenes, entregas, tableros, balanceo, vistas e insumos', NOW(), NOW());
```

### 2. Actualizar middlewares

**InsumosAccess.php:**
- Permitir `supervisor_planta` acceder a insumos

**CheckRole.php:**
- Permitir `supervisor_planta` en rutas que lo requieran

### 3. Actualizar controladores

**InsumosController.php:**
- Permitir `supervisor_planta` en `verificarRolInsumos()`

### 4. Crear sidebar para supervisor_planta

- `resources/views/supervisor_planta/sidebar.blade.php`
- Mostrar solo: Órdenes, Entregas, Tableros, Balanceo, Vistas, Insumos

### 5. Actualizar rutas

Las siguientes rutas ya están disponibles y solo necesitan acceso:
- `/registros` - Gestionar órdenes
- `/entrega/{tipo}` - Entregas
- `/tableros` - Tableros
- `/balanceo` - Balanceo
- `/vistas` - Vistas
- `/insumos` - Insumos

### 6. Asignar rol a usuario

```sql
UPDATE users SET roles_ids = '[ID_DEL_ROL]' WHERE id = USER_ID;
```

## Acceso por ruta

| Ruta | Rol | Acceso |
|------|-----|--------|
| /registros | supervisor_planta | ✅ |
| /entrega | supervisor_planta | ✅ |
| /tableros | supervisor_planta | ✅ |
| /balanceo | supervisor_planta | ✅ |
| /vistas | supervisor_planta | ✅ |
| /insumos | supervisor_planta | ✅ |
| /asesores | supervisor_planta | ❌ |
| /contador | supervisor_planta | ❌ |
| /users | supervisor_planta | ❌ |
| /configuracion | supervisor_planta | ❌ |
