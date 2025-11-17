# 📱 Guía para Acceso desde Celular

## 🎯 Opción 1: Red Local (Recomendado para Pruebas)

### Ventajas:
- ✅ Gratis
- ✅ Rápido de configurar
- ✅ Seguro (solo en tu red WiFi)
- ✅ Ideal para pruebas y desarrollo

### Requisitos:
- PC y celular en la **misma red WiFi**
- XAMPP corriendo en tu PC

---

## 📝 Paso 1: Obtener tu IP Local

### En Windows (PowerShell):
```powershell
ipconfig
```

Busca la sección **"Adaptador de LAN inalámbrica Wi-Fi"** o **"Ethernet"**:
- Busca: `Dirección IPv4`
- Ejemplo: `192.168.1.10`

### Método rápido:
1. Presiona `Windows + R`
2. Escribe: `cmd`
3. Escribe: `ipconfig`
4. Anota tu IP (ejemplo: `192.168.1.10`)

---

## 📝 Paso 2: Configurar Apache (XAMPP)

### 2.1 Editar httpd.conf

1. Abre XAMPP Control Panel
2. Click en **"Config"** al lado de Apache
3. Selecciona **"httpd.conf"**
4. Busca la línea (cerca de la línea 60):
   ```apache
   Listen 80
   ```

5. Agrega debajo:
   ```apache
   Listen 0.0.0.0:80
   ```

### 2.2 Permitir acceso desde cualquier IP

Busca esta sección (cerca de la línea 240):
```apache
<Directory "C:/xampp/htdocs">
    Options Indexes FollowSymLinks Includes ExecCGI
    AllowOverride All
    Require all granted
</Directory>
```

Asegúrate de que diga: `Require all granted`

### 2.3 Reiniciar Apache
1. En XAMPP Control Panel
2. Click **"Stop"** en Apache
3. Click **"Start"** en Apache

---

## 📝 Paso 3: Configurar Firewall de Windows

### Permitir Apache en el Firewall:

1. Presiona `Windows + R`
2. Escribe: `firewall.cpl`
3. Click en **"Permitir una aplicación o característica..."**
4. Click en **"Cambiar configuración"**
5. Busca **"Apache HTTP Server"** o **"httpd.exe"**
6. Marca las casillas: **"Privada"** y **"Pública"**
7. Click **"Aceptar"**

### O crear regla manualmente:
1. Panel de Control → Sistema y Seguridad → Firewall de Windows
2. **"Configuración avanzada"**
3. **"Reglas de entrada"** → **"Nueva regla"**
4. Selecciona **"Puerto"** → Siguiente
5. **TCP** → Puerto **80** → Siguiente
6. **"Permitir la conexión"** → Siguiente
7. Marca **todas las redes** → Siguiente
8. Nombre: **"XAMPP Apache"** → Finalizar

---

## 📝 Paso 4: Acceder desde tu Celular

### URL para acceder:
```
http://TU_IP_LOCAL/form%20gaselag%20retiros/
```

### Ejemplo real:
```
http://192.168.1.10/form%20gaselag%20retiros/
```

### Para el login directo:
```
http://192.168.1.10/form%20gaselag%20retiros/login.php
```

---

## 🎯 Opción 2: Usar ngrok (Acceso desde Internet)

### Ventajas:
- ✅ Acceso desde cualquier lugar (no solo tu WiFi)
- ✅ URL pública temporal
- ✅ Útil para mostrar a clientes/testers remotos

### Desventajas:
- ⚠️ Requiere instalar software adicional
- ⚠️ URL cambia cada vez que reinicias (en versión gratis)
- ⚠️ Puede ser más lento

### Pasos:

**1. Descargar ngrok:**
```
https://ngrok.com/download
```

**2. Instalar y autenticar:**
- Crea cuenta gratis en ngrok.com
- Copia tu authtoken
- En CMD:
```bash
ngrok authtoken TU_TOKEN_AQUI
```

**3. Exponer tu servidor local:**
```bash
ngrok http 80
```

**4. Usar la URL generada:**
```
https://xxxx-xx-xx-xxx-xxx.ngrok-free.app/form%20gaselag%20retiros/
```

---

## 🎯 Opción 3: Hosting/VPS (Producción)

### Para llevarlo a producción necesitarás:

**Hosting compartido (económico):**
- Hostinger, GoDaddy, Hostgator, etc.
- Requiere: PHP 7.4+, MySQL
- Costo: ~$3-10 USD/mes

**VPS (más control):**
- DigitalOcean, Vultr, Linode
- Requiere conocimientos de servidor
- Costo: ~$5-20 USD/mes

### Pasos básicos para producción:

1. **Contratar hosting con:**
   - PHP 7.4 o superior
   - MySQL 5.7 o superior
   - Acceso a phpMyAdmin
   - Soporte SSL (HTTPS)

2. **Subir archivos:**
   - Via FTP (FileZilla)
   - O panel cPanel

3. **Crear base de datos:**
   - Desde phpMyAdmin
   - Importar `database/schema.sql`

4. **Configurar database.php:**
   ```php
   define('DB_HOST', 'localhost'); // o IP del servidor MySQL
   define('DB_PORT', '3306');
   define('DB_USER', 'usuario_hosting');
   define('DB_PASS', 'password_seguro');
   define('DB_NAME', 'nombre_bd');
   ```

5. **Configurar permisos:**
   - Carpeta `uploads/` → 755 o 775
   - Verificar que PHP pueda escribir

6. **Activar HTTPS:**
   - SSL gratis con Let's Encrypt
   - La mayoría de hostings lo incluyen

---

## ⚡ Recomendación para tu Caso

### Para pruebas y desarrollo:
**Usa Opción 1 (Red Local)** → Es lo más simple y seguro

### Para mostrar a clientes remotos:
**Usa Opción 2 (ngrok)** → Acceso temporal desde internet

### Para producción real:
**Usa Opción 3 (Hosting)** → Cuando el sistema esté listo y probado

---

## 🔧 Troubleshooting

### No puedo acceder desde el celular:

1. **Verifica que estén en la misma red WiFi**
   - PC y celular deben ver el mismo router

2. **Verifica que Apache esté escuchando en 0.0.0.0**
   ```
   netstat -an | findstr :80
   ```
   Debe aparecer: `0.0.0.0:80`

3. **Desactiva temporalmente el firewall para probar**
   - Si funciona, entonces es el firewall
   - Agrega la regla correcta (ver Paso 3)

4. **Verifica la IP**
   - La IP puede cambiar si reinicias el router
   - Vuelve a verificar con `ipconfig`

5. **Prueba desde el navegador del celular:**
   - No uses "https://" → usa "http://"
   - Ejemplo: `http://192.168.1.10/form%20gaselag%20retiros/`

### El sistema funciona pero muy lento desde celular:

- Normal si tu router WiFi es antiguo
- Considera usar ngrok o llevarlo a hosting

---

## 📞 Soporte

Si tienes problemas, verifica:
1. ✅ XAMPP Apache corriendo
2. ✅ MySQL corriendo
3. ✅ Firewall permite puerto 80
4. ✅ PC y celular en misma red WiFi
5. ✅ IP correcta (no cambió)


