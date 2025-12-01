# 🔧 Cambiar Base de Datos a Local

## Opción 1: Script Automático (Recomendado)

### Paso 1: Ejecutar el script
```powershell
.\cambiar-db-local.ps1
```

### Resultado
El script cambiará automáticamente:
- `DB_HOST=127.0.0.1` (localhost)
- `DB_PORT=3306` (puerto MySQL por defecto)
- `DB_DATABASE=mundo_bd` (nombre de BD)
- `DB_USERNAME=root` (usuario por defecto)
- `DB_PASSWORD=` (vacío - sin contraseña)

---

## Opción 2: Manual

### Paso 1: Abrir archivo `.env`
```
c:\Users\Usuario\Documents\proyecto\v10\mundoindustrial\.env
```

### Paso 2: Encontrar sección de BD
Busca estas líneas:
```
DB_CONNECTION=mysql
DB_HOST=192.168.0.54
DB_PORT=3306
DB_DATABASE=mundo_bd
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

### Paso 3: Cambiar valores
Reemplaza con:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mundo_bd
DB_USERNAME=root
DB_PASSWORD=
```

### Paso 4: Guardar archivo

---

## Paso 3: Verificar Conexión

### Opción A: Desde terminal
```bash
php artisan tinker
>>> DB::connection()->getPdo()
```

Si funciona, verás: `PDOConnection`

### Opción B: Ver logs
```bash
tail -f storage/logs/laravel.log
```

### Opción C: Acceder a la aplicación
```
http://192.168.0.168:8000
```

Si ves la página de login, la BD está conectada ✅

---

## ⚠️ Notas Importantes

### Si MySQL no está corriendo
```bash
# Windows - Iniciar MySQL
net start MySQL80

# O desde Services.msc
# Busca "MySQL80" y haz clic en "Start"
```

### Si tienes contraseña en MySQL
Cambia `DB_PASSWORD=` a tu contraseña:
```
DB_PASSWORD=tu_contraseña_mysql
```

### Si la BD no existe
```bash
# Crear BD
php artisan migrate:fresh --seed

# O solo crear tablas
php artisan migrate
```

### Si tienes problemas de conexión
```bash
# Limpiar cache
php artisan config:cache
php artisan cache:clear

# Reiniciar servidor
php artisan serve
```

---

## 🔍 Verificar Configuración Actual

```bash
# Ver valores de BD
php artisan tinker
>>> config('database.connections.mysql')
```

Debería mostrar:
```
[
  "driver" => "mysql",
  "host" => "127.0.0.1",
  "port" => 3306,
  "database" => "mundo_bd",
  "username" => "root",
  "password" => "",
]
```

---

## ✅ Checklist

- [ ] Archivo `.env` actualizado
- [ ] MySQL está corriendo en tu PC
- [ ] BD `mundo_bd` existe
- [ ] Usuario `root` tiene acceso
- [ ] Ejecutaste `php artisan migrate` (si es primera vez)
- [ ] Aplicación conecta correctamente

---

## 🚀 Próximos Pasos

1. Ejecuta el script o cambia manualmente
2. Verifica que MySQL esté corriendo
3. Prueba la conexión
4. Si hay errores, revisa `storage/logs/laravel.log`
