# 🔧 Solución: No Puedo Acceder desde el Móvil

## 🎯 Problema Común

**"Hace unos días SÍ podía acceder desde mi celular, pero ahora NO"**

---

## ✅ Solución Rápida (5 pasos)

### Paso 1: Ejecutar Diagnóstico
```
Doble click en: diagnosticar_acceso_movil.bat
```
Este script te dirá exactamente qué está mal.

### Paso 2: Verificar la IP
```
Doble click en: obtener_ip_local.bat
```
**PROBLEMA MÁS COMÚN:** Tu IP cambió y seguís usando la IP antigua.

### Paso 3: Configurar Firewall
```
Click derecho en: configurar_firewall.bat
Selecciona: "Ejecutar como administrador"
```
Esto permite que Apache reciba conexiones externas.

### Paso 4: Reiniciar Apache
```
1. Abre XAMPP Control Panel
2. Click "Stop" en Apache
3. Espera 3 segundos
4. Click "Start" en Apache
```

### Paso 5: Probar de Nuevo
```
1. Abre en tu celular el navegador
2. Usa la IP del Paso 2
3. Ejemplo: http://192.168.1.10/form%20gaselag%20retiros/
```

---

## 🔍 Causas Comunes y Soluciones

### 🔴 Causa #1: La IP Cambió (MÁS COMÚN)

**Síntoma:** La URL que usabas antes ya no funciona

**Por qué pasa:** 
- Reiniciaste el router
- Reiniciaste la PC
- Tu router asigna IPs dinámicas

**Solución:**
```
1. Ejecuta: obtener_ip_local.bat
2. Anota la NUEVA IP
3. Usa esa IP en tu celular
```

**Ejemplo:**
```
Antes usabas: http://192.168.1.10/form%20gaselag%20retiros/
Ahora es:     http://192.168.1.15/form%20gaselag%20retiros/
              👆 IP CAMBIÓ
```

---

### 🔴 Causa #2: Firewall Bloqueó Apache

**Síntoma:** El celular dice "No se puede conectar" o "Timeout"

**Por qué pasa:**
- Windows Update cambió configuración
- Antivirus/Firewall se actualizó
- Cambió la red de "Privada" a "Pública"

**Solución:**
```
1. Click derecho en: configurar_firewall.bat
2. "Ejecutar como administrador"
3. Espera que termine
4. Reinicia Apache en XAMPP
5. Prueba de nuevo
```

**Alternativa rápida (solo para probar):**
```
1. Windows + R
2. Escribe: firewall.cpl
3. Click "Activar o desactivar..."
4. Desactiva temporalmente
5. Prueba si funciona
6. Si funciona, el problema era el firewall
7. Reactiva el firewall y usa el script configurar_firewall.bat
```

---

### 🔴 Causa #3: Apache Solo Escucha en Localhost

**Síntoma:** Funciona en la PC pero NO en el celular

**Por qué pasa:**
- Configuración por defecto de Apache
- No está escuchando en 0.0.0.0 (todas las interfaces)

**Solución:**
```
1. Ejecuta: diagnosticar_acceso_movil.bat
2. Si dice "Apache solo escucha en localhost"
3. Ejecuta: configurar_apache_red_local.bat
4. Reinicia Apache en XAMPP
5. Prueba de nuevo
```

**Solución manual:**
```
1. Abre XAMPP Control Panel
2. Click "Config" al lado de Apache
3. Selecciona "httpd.conf"
4. Busca: Listen 80
5. Agrega debajo: Listen 0.0.0.0:80
6. Guarda el archivo
7. Reinicia Apache
```

---

### 🔴 Causa #4: Redes WiFi Diferentes

**Síntoma:** El celular no encuentra la PC

**Por qué pasa:**
- El celular está usando datos móviles
- El celular está en una red WiFi diferente
- La PC está conectada por cable pero el celular por WiFi de otra red

**Solución:**
```
En el celular:
1. Abre Configuración
2. WiFi
3. Desactiva "Datos móviles" temporalmente
4. Verifica que estás en la MISMA red WiFi que la PC
5. Prueba de nuevo
```

**Cómo verificar que están en la misma red:**
```
En la PC:
1. Ejecuta: obtener_ip_local.bat
2. Anota la IP (ejemplo: 192.168.1.10)
                            👆👆👆 primeros 3 números

En el celular:
1. Configuración → WiFi → (i) Información de red
2. Verifica la IP del celular
3. Los primeros 3 números deben ser iguales
4. Ejemplo: 192.168.1.25 ✅ CORRECTO
            192.168.0.25 ❌ RED DIFERENTE
```

---

### 🔴 Causa #5: Apache No Está Corriendo

**Síntoma:** Tampoco funciona en la PC (localhost)

**Por qué pasa:**
- Apache se cerró
- Puerto 80 ocupado por otro programa
- Error en la configuración

**Solución:**
```
1. Abre XAMPP Control Panel
2. Verifica que Apache tenga fondo VERDE
3. Si no está verde:
   - Click "Start"
   - Si no inicia, mira el botón "Logs"
   - Busca el error

4. Si dice "Puerto 80 en uso":
   - Detén Skype, IIS u otro programa
   - O cambia Apache a puerto 8080
```

---

## 📋 Checklist Completo

Marca ✅ cada paso que verifiques:

```
□ PC y celular en la MISMA red WiFi
□ Apache corriendo en XAMPP (fondo verde)
□ MySQL corriendo en XAMPP (fondo verde)
□ IP local obtenida correctamente (ejecutar obtener_ip_local.bat)
□ Firewall permite puerto 80 (ejecutar configurar_firewall.bat como admin)
□ Apache escucha en 0.0.0.0 (ejecutar diagnosticar_acceso_movil.bat)
□ URL correcta con la IP actual (no la antigua)
□ Celular NO está usando datos móviles
□ Antivirus no está bloqueando (desactivar temporalmente para probar)
```

---

## 🎯 Scripts de Ayuda Creados

### 1. `diagnosticar_acceso_movil.bat`
Identifica automáticamente todos los problemas:
- ✅ IP local
- ✅ Apache corriendo
- ✅ Apache escuchando en todas las interfaces
- ✅ MySQL corriendo
- ✅ Firewall
- ✅ Acceso local funcionando

### 2. `obtener_ip_local.bat`
Muestra tu IP actual y las URLs para usar

### 3. `configurar_firewall.bat` (Administrador)
Configura automáticamente las reglas del firewall

### 4. `configurar_apache_red_local.bat`
Configura Apache para aceptar conexiones de red local

---

## 🚀 Proceso Recomendado

### Si hace días funcionaba y ahora no:

```
1. Ejecutar: diagnosticar_acceso_movil.bat
   → Te dirá qué está mal

2. Si la IP cambió:
   → Ejecutar: obtener_ip_local.bat
   → Usar la nueva IP

3. Si el firewall bloquea:
   → Ejecutar como admin: configurar_firewall.bat
   → Reiniciar Apache

4. Si Apache solo escucha en localhost:
   → Ejecutar: configurar_apache_red_local.bat
   → Reiniciar Apache

5. Probar desde el celular con la IP correcta
```

---

## 🆘 Si Nada Funciona

### Opción A: Descartar problemas uno por uno

```bash
# 1. Verifica que funciona en la PC
http://localhost/form%20gaselag%20retiros/

# 2. Si funciona en PC, prueba con la IP local desde la PC
http://TU_IP/form%20gaselag%20retiros/

# 3. Si funciona con IP en PC, el problema es el celular/red
# 4. Si no funciona con IP en PC, el problema es Apache/Firewall
```

### Opción B: Reinicio completo

```
1. Cierra XAMPP completamente
2. Reinicia tu PC
3. Reinicia tu router WiFi
4. Abre XAMPP
5. Inicia Apache y MySQL
6. Ejecuta: diagnosticar_acceso_movil.bat
7. Ejecuta: configurar_firewall.bat (como admin)
8. Obtén la IP: obtener_ip_local.bat
9. Prueba desde el celular
```

### Opción C: Usar ngrok (acceso desde internet)

Si la red local no funciona, usa ngrok:

```
1. Descarga ngrok: https://ngrok.com/download
2. En CMD: ngrok http 80
3. Usa la URL que te da (ejemplo: https://xxxx.ngrok-free.app)
4. Funciona desde cualquier red
```

---

## 📞 Soporte Adicional

Si revisaste todo y aún no funciona:

1. Ejecuta `diagnosticar_acceso_movil.bat` y copia el resultado
2. Verifica los logs de Apache: `C:\xampp\apache\logs\error.log`
3. Verifica la configuración de tu router (puede tener AP Isolation activado)

---

## 💡 Prevenir el Problema

### IP Estática (recomendado)

Para que tu IP no cambie:

```
1. Ejecuta: obtener_ip_local.bat (anota tu IP actual)
2. Windows + R → ncpa.cpl
3. Click derecho en tu adaptador WiFi → Propiedades
4. Selecciona "Protocolo de Internet versión 4"
5. Propiedades
6. Selecciona "Usar la siguiente dirección IP"
7. IP: [tu IP actual]
8. Máscara: 255.255.255.0
9. Puerta de enlace: [IP de tu router, ejemplo: 192.168.1.1]
10. DNS: 8.8.8.8 (Google DNS)
11. Aceptar

Ahora tu IP nunca cambiará
```

---

¡Con estos scripts y guía deberías poder identificar y solucionar el problema! 🎉

