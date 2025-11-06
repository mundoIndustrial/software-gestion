# 📡 Instrucciones para Actualizaciones en Tiempo Real

## Problema Identificado

Las vistas de seguimiento fullscreen (polos, corte, producción) no se actualizaban en tiempo real porque **el servidor Reverb no estaba corriendo**.

## Solución

### 1. Iniciar el Servidor Reverb

**IMPORTANTE:** El servidor Reverb debe estar corriendo **SIEMPRE** para que las actualizaciones en tiempo real funcionen.

```bash
# Iniciar Reverb en modo normal
php artisan reverb:start

# O en modo debug para ver los eventos
php artisan reverb:start --debug
```

### 2. Verificar que Reverb está corriendo

Deberías ver un mensaje como:
```
INFO  Starting server on 127.0.0.1:8080.
```

### 3. Mantener Reverb Corriendo

El servidor Reverb debe mantenerse corriendo en segundo plano. Opciones:

#### Opción A: Terminal Separada (Desarrollo)
- Abre una terminal dedicada
- Ejecuta `php artisan reverb:start`
- Deja esa terminal abierta mientras trabajas

#### Opción B: Script de Inicio Automático
Usa el archivo `start-dev.bat` que ya existe:
```batch
start-dev.bat
```

Este script inicia tanto el servidor de desarrollo como Reverb.

#### Opción C: Supervisor (Producción)
Para producción, configura Supervisor para mantener Reverb corriendo automáticamente.

## Cómo Funciona

### Eventos Emitidos

1. **ProduccionRecordCreated** → Canal: `produccion`
   - Se emite cuando se crea, actualiza o elimina un registro de producción

2. **PoloRecordCreated** → Canal: `polo`
   - Se emite cuando se crea, actualiza o elimina un registro de polos

3. **CorteRecordCreated** → Canal: `corte`
   - Se emite cuando se crea, actualiza o elimina un registro de corte

### Vistas que Escuchan

1. **tableros-fullscreen.blade.php**
   - Escucha los 3 canales
   - Recarga automáticamente según la sección activa

2. **tableros-corte-fullscreen.blade.php**
   - Escucha solo el canal `corte`
   - Recarga automáticamente cuando hay cambios

3. **components/seguimiento-modulos.blade.php**
   - Escucha los 3 canales
   - Actualiza la tabla de seguimiento sin recargar la página completa

## Probar el Broadcasting

Ejecuta el script de prueba:

```bash
php test-broadcast-realtime.php
```

Este script:
1. Verifica la configuración de Reverb
2. Emite eventos de prueba para los 3 canales
3. Te muestra si hay errores

### Verificar en el Navegador

1. Abre la vista fullscreen: `/tableros/fullscreen?section=produccion`
2. Abre la consola del navegador (F12)
3. Deberías ver mensajes como:
   ```
   ✅ Echo disponible, suscribiendo a canales...
   ✅ WebSocket conectado exitosamente a Reverb
   🎉 Evento ProduccionRecordCreated recibido en fullscreen
   ```

## Troubleshooting

### Error: "Failed to connect to 127.0.0.1 port 8080"

**Causa:** Reverb no está corriendo.

**Solución:** Inicia Reverb con `php artisan reverb:start`

### Los eventos no llegan al navegador

1. Verifica que Reverb esté corriendo
2. Abre la consola del navegador y busca errores
3. Verifica que la configuración en `.env` sea correcta:
   ```
   BROADCAST_CONNECTION=reverb
   REVERB_APP_ID=123456
   REVERB_APP_KEY=mundo-industrial-key
   REVERB_APP_SECRET=mundo-industrial-secret
   REVERB_HOST=127.0.0.1
   REVERB_PORT=8080
   REVERB_SCHEME=http
   ```

### Echo no está disponible

1. Verifica que `@vite(['resources/js/app.js'])` esté en la vista
2. Ejecuta `npm run dev` o `npm run build`
3. Limpia la caché del navegador

## Configuración de Producción

Para producción, considera:

1. **Usar un proceso manager** (Supervisor, PM2)
2. **Configurar HTTPS** si tu sitio usa SSL
3. **Ajustar el puerto** si 8080 está ocupado
4. **Monitorear logs** de Reverb regularmente

## Archivos Relacionados

- `config/broadcasting.php` - Configuración de broadcasting
- `resources/js/bootstrap.js` - Inicialización de Echo
- `app/Events/` - Eventos que se emiten
- `app/Http/Controllers/TablerosController.php` - Controlador que emite eventos
- `resources/views/tableros-fullscreen.blade.php` - Vista que escucha eventos
- `resources/views/tableros-corte-fullscreen.blade.php` - Vista de corte
- `resources/views/components/seguimiento-modulos.blade.php` - Componente de seguimiento

## Logs

Revisa los logs si hay problemas:

```bash
# Logs de Laravel
Get-Content storage\logs\laravel.log -Tail 50

# Logs de Reverb (si está en modo debug)
# Se muestran en la terminal donde corre Reverb
```

## Resumen

✅ **Reverb DEBE estar corriendo** para que funcione el tiempo real
✅ Los eventos se emiten correctamente desde el backend
✅ Las vistas están configuradas para escuchar eventos
✅ Usa `test-broadcast-realtime.php` para probar

**Comando más importante:**
```bash
php artisan reverb:start
```

¡Sin este comando corriendo, NO habrá actualizaciones en tiempo real!
