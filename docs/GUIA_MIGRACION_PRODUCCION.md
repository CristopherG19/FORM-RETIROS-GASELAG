# 🚀 Guía de Migración a Producción
## Sistema GASELAG - Retiros de Medidores

Esta guía te ayudará a migrar el sistema desde tu ambiente de desarrollo local (XAMPP) a un servidor de producción (cPanel/Hosting).

---

## 📋 Requisitos Previos

### En el Servidor de Producción:
- ✅ PHP 7.4 o superior
- ✅ MySQL 5.7 o superior
- ✅ Acceso a cPanel o similar
- ✅ Dominio configurado (opcional pero recomendado)
- ✅ Certificado SSL configurado (recomendado para HTTPS)

### En tu Computadora Local:
- ✅ Git instalado
- ✅ Acceso al repositorio
- ✅ Cliente FTP/SFTP (FileZilla, WinSCP, etc.) o acceso SSH

---

## 🔄 Proceso de Migración (Paso a Paso)

### **FASE 1: Preparar el Repositorio** ✅ (Ya Completado)

El repositorio ya está configurado correctamente con:
- ✅ Archivo `environment.php.example` como plantilla
- ✅ `.gitignore` configurado para excluir credenciales
- ✅ Estructura de carpetas lista
- ✅ Código listo para producción

---

### **FASE 2: Configurar Base de Datos en Producción**

#### 1. Acceder a cPanel → MySQL Databases

#### 2. Crear Base de Datos:
```
Nombre sugerido: usuario_gaselag
(cPanel añade automáticamente tu usuario como prefijo)
```

#### 3. Crear Usuario MySQL:
```
Usuario: usuario_gaselag_admin
Contraseña: [Genera una contraseña SEGURA]
```

**💡 TIP:** Usa el generador de contraseñas de cPanel (mínimo 16 caracteres)

#### 4. Asignar Privilegios:
```
✅ Seleccionar usuario creado
✅ Seleccionar base de datos creada
✅ Marcar "ALL PRIVILEGES"
✅ Hacer Click en "Make Changes"
```

#### 5. Anotar Credenciales:
```
Host: localhost (generalmente)
Puerto: 3306
Usuario: usuario_gaselag_admin
Password: [la contraseña generada]
Base de Datos: usuario_gaselag
```

---

### **FASE 3: Subir Archivos al Servidor**

#### **Opción A: Usando Git (RECOMENDADO)**

Si tu hosting tiene acceso SSH y Git:

```bash
# 1. Conectar por SSH al servidor
ssh usuario@tuservidor.com

# 2. Navegar a la carpeta web
cd public_html
# o
cd www

# 3. Clonar el repositorio
git clone https://github.com/TU_USUARIO/FORM-RETIROS-GASELAG.git retiros

# 4. Entrar a la carpeta
cd retiros

# 5. Verificar que todo se clonó correctamente
ls -la
```

#### **Opción B: Usando FTP/SFTP**

1. Conectar con FileZilla o WinSCP
2. Navegar a `public_html` o `www`
3. Crear carpeta `retiros`
4. Subir TODOS los archivos EXCEPTO:
   - ❌ carpeta `.git`
   - ❌ `config/environment.php` (subiremos el .example)
   - ❌ archivos de backups locales
   - ❌ carpeta `uploads` con contenido local

---

### **FASE 4: Configurar Archivo de Ambiente**

#### 1. En el servidor, copiar la plantilla:
```bash
cd config
cp environment.php.example environment.php
```

O si usas cPanel File Manager:
- Buscar `config/environment.php.example`
- Click derecho → Copy
- Nombrar como `environment.php`

#### 2. Editar `config/environment.php`:
```bash
nano environment.php
```

O usar el editor de cPanel File Manager.

#### 3. Configurar el ambiente:
```php
// Cambiar de 'development' a 'production'
define('ENVIRONMENT', 'production');
```

#### 4. Configurar credenciales de producción:
```php
'production' => [
    'db_host' => 'localhost',
    'db_port' => '3306',
    'db_user' => 'usuario_gaselag_admin',    // Tu usuario real
    'db_pass' => 'tu_password_seguro',       // Tu contraseña real
    'db_name' => 'usuario_gaselag',          // Tu base de datos real
    'base_url' => 'https://tudominio.com/retiros/',  // Tu URL real
    'debug' => false,
    'display_errors' => false,
    'session_timeout' => 3600,
    'upload_path' => '../uploads/',
    'timezone' => 'America/Lima'
]
```

**⚠️ IMPORTANTE:** Guarda y verifica que el archivo se llamó exactamente `environment.php`

---

### **FASE 5: Configurar Permisos de Carpetas**

Configurar permisos correctos para que PHP pueda escribir:

```bash
# Carpeta uploads (escritura)
chmod 755 uploads
chmod 755 uploads/perfiles

# Carpeta backups (escritura)
chmod 755 backups
chmod 755 backups/database
chmod 755 backups/database/daily
chmod 755 backups/database/weekly
chmod 755 backups/database/monthly
chmod 755 backups/uploads_backup
chmod 755 backups/uploads_backup/daily
chmod 755 backups/uploads_backup/weekly
chmod 755 backups/uploads_backup/monthly
chmod 755 backups/logs

# Carpeta logs (si existe)
chmod 755 logs
```

O desde cPanel File Manager:
- Click derecho en carpeta → Change Permissions
- Marcar: `755` (rwxr-xr-x)

---

### **FASE 6: Importar Base de Datos**

#### 1. Acceder a phpMyAdmin en cPanel

#### 2. Seleccionar la base de datos creada

#### 3. Click en pestaña "Import"

#### 4. Importar en este orden:

**A. Schema principal:**
```
Subir: database/schema.sql
```

**B. Migraciones (si existen):**
```
Subir: database/migration_seguridad.sql
Subir: database/migration_usuarios_perfil.sql
Subir: database/migration_asignaciones_oc.sql
```

#### 5. Verificar que las tablas se crearon:
- `ordenes_servicio`
- `retiros`
- `tipos_imposibilidad`
- `usuarios`
- Otras tablas del sistema

---

### **FASE 7: Verificar Instalación**

#### 1. Acceder al sistema:
```
https://tudominio.com/retiros/
```

#### 2. Debería redirigir a:
```
https://tudominio.com/retiros/login.php
```

#### 3. Intentar iniciar sesión con usuario admin:
```
Usuario: admin
Password: password
```

**⚠️ CAMBIAR ESTA CONTRASEÑA INMEDIATAMENTE**

#### 4. Si hay errores, verificar:
```
https://tudominio.com/retiros/tools/verificar_instalacion.php
```

---

### **FASE 8: Seguridad Post-Instalación**

#### ✅ 1. Cambiar contraseña del usuario admin:
- Ir a perfil de usuario
- Cambiar contraseña inmediatamente

#### ✅ 2. Crear usuarios reales del sistema:
- Eliminar usuarios de prueba (tecnico1, tecnico2)
- Crear usuarios con credenciales seguras

#### ✅ 3. Configurar HTTPS:
- Forzar redirección a HTTPS
- Verificar certificado SSL activo

#### ✅ 4. Configurar backups automáticos:
- Revisar `backups/scripts/auto_backup.php`
- Configurar cron job en cPanel

#### ✅ 5. Revisar permisos:
- Verificar que `environment.php` NO sea accesible públicamente
- Verificar que carpetas de backups estén protegidas

#### ✅ 6. Configurar archivo .htaccess:
```apache
# Proteger archivos sensibles
<Files "environment.php">
    Order Allow,Deny
    Deny from all
</Files>

<Files "database.php">
    Order Allow,Deny
    Deny from all
</Files>

# Forzar HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

### **FASE 9: Configurar Backups Automáticos en Producción**

#### 1. Acceder a Cron Jobs en cPanel

#### 2. Crear Cron Job para backups diarios:
```bash
# Ejecutar a las 2:00 AM todos los días
0 2 * * * php /home/usuario/public_html/retiros/backups/scripts/auto_backup.php
```

#### 3. Verificar que los backups funcionan:
- Esperar a que se ejecute el cron
- Verificar carpeta `backups/database/daily/`
- Revisar logs en `backups/logs/auto_backup.log`

---

## 🔄 Actualizar el Sistema en Producción

### Si usaste Git:
```bash
ssh usuario@tuservidor.com
cd public_html/retiros
git pull origin main
```

### Si usaste FTP:
1. Hacer backup del `environment.php` actual
2. Subir archivos nuevos/modificados
3. NO sobrescribir `environment.php`
4. Restaurar `environment.php` si fue sobrescrito

---

## 🆘 Solución de Problemas Comunes

### ❌ Error: "No se puede conectar a la base de datos"
**Solución:**
- Verificar credenciales en `config/environment.php`
- Verificar que `ENVIRONMENT` esté en `'production'`
- Verificar que el usuario MySQL tenga privilegios

### ❌ Error: "500 Internal Server Error"
**Solución:**
- Revisar logs de PHP en cPanel
- Verificar permisos de carpetas (755)
- Verificar que `display_errors` esté en `false` en producción

### ❌ Error: "Call to undefined function..."
**Solución:**
- Verificar versión de PHP en cPanel (debe ser 7.4+)
- Verificar extensiones PHP requeridas (mysqli, etc.)

### ❌ Las imágenes no se suben
**Solución:**
- Verificar permisos carpeta `uploads` (755)
- Verificar límites de subida en php.ini:
  ```
  upload_max_filesize = 20M
  post_max_size = 25M
  ```

---

## 📞 Checklist Final

Antes de dar por completada la migración:

- [ ] Base de datos creada y configurada
- [ ] Archivos subidos al servidor
- [ ] `environment.php` configurado correctamente
- [ ] Permisos de carpetas configurados
- [ ] Base de datos importada exitosamente
- [ ] Login funciona correctamente
- [ ] Contraseña de admin cambiada
- [ ] HTTPS configurado y funcionando
- [ ] Backups automáticos configurados
- [ ] Sistema probado completamente
- [ ] URLs de producción actualizadas

---

## 📚 Recursos Adicionales

- **Guía de Git:** `docs/GUIA_GIT.md`
- **Verificar instalación:** `/tools/verificar_instalacion.php`
- **Documentación backups:** `backups/INSTRUCCIONES_FINALES.md`
- **README principal:** `README.md`

---

## 🎉 ¡Sistema en Producción!

Una vez completados todos los pasos, tu sistema estará funcionando en producción de manera segura y profesional.

**Última actualización:** Noviembre 2025

