# CONFIGURAR IP ESTÁTICA EN WINDOWS

## ¿Por qué necesitas esto?
Para que la URL de red `http://192.168.0.189:8000` no cambie cada vez que reinicies tu computadora.

## Pasos para configurar IP estática:

### 1. Abrir Configuración de Red
- Presiona `Windows + R`
- Escribe: `ncpa.cpl`
- Presiona Enter

### 2. Configurar el Adaptador de Red
1. Haz clic derecho en tu adaptador de red activo (Ethernet o Wi-Fi)
2. Selecciona **"Propiedades"**
3. Busca y selecciona **"Protocolo de Internet versión 4 (TCP/IPv4)"**
4. Haz clic en **"Propiedades"**

### 3. Configurar IP Estática
Selecciona **"Usar la siguiente dirección IP"** y completa:

```
Dirección IP:        192.168.0.189
Máscara de subred:   255.255.255.0
Puerta de enlace:    192.168.0.1
```

**DNS preferido:**   `8.8.8.8` (Google DNS)
**DNS alternativo:** `8.8.4.4` (Google DNS alternativo)

### 4. Guardar Cambios
1. Haz clic en **"Aceptar"**
2. Cierra todas las ventanas
3. Reinicia tu computadora (opcional pero recomendado)

## Verificar la Configuración

Abre PowerShell o CMD y ejecuta:
```powershell
ipconfig
```

Deberías ver tu IP fija: `192.168.0.189`

## IMPORTANTE: Antes de Configurar

### Verifica tu puerta de enlace actual:
```powershell
ipconfig | findstr "Puerta"
```

Usa ese valor como **Puerta de enlace** en la configuración.

### Verifica que la IP no esté en uso:
```powershell
ping 192.168.0.189
```

Si responde, elige otra IP (ejemplo: 192.168.0.190, 192.168.0.191, etc.)

## Notas Adicionales

- **Rango recomendado:** 192.168.0.100 - 192.168.0.254
- **Evita:** 192.168.0.1 (router) y 192.168.0.2-192.168.0.50 (reservados para DHCP)
- Si tienes problemas, vuelve a seleccionar **"Obtener dirección IP automáticamente"**

## Alternativa: Reserva DHCP en el Router

Si prefieres no tocar la configuración de Windows:
1. Accede a tu router (usualmente `http://192.168.0.1`)
2. Busca la sección **"DHCP Reservation"** o **"Reserva de IP"**
3. Asocia la MAC de tu PC con la IP 192.168.0.189
4. Reinicia tu PC

Esto mantiene DHCP activo pero siempre te asigna la misma IP.
