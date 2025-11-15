# 📚 GUIA COMPLETA DE BACKUPS CON GOOGLE DRIVE
## Sistema GASELAG - Gestion de Retiros

---

## 📋 INDICE

1. [Instalacion y Configuracion](#instalacion-y-configuracion)
2. [Configuracion de Google Drive Desktop](#configuracion-de-google-drive-desktop)
3. [Configuracion de la Tarea Programada](#configuracion-de-la-tarea-programada)
4. [Uso del Panel de Administracion](#uso-del-panel-de-administracion)
5. [Ejecucion Manual de Backups](#ejecucion-manual-de-backups)
6. [Restauracion de Backups](#restauracion-de-backups)
7. [Solucion de Problemas](#solucion-de-problemas)

---

## 1. INSTALACION Y CONFIGURACION

### Requisitos Previos

- ✅ XAMPP con PHP 7.4+ y MySQL instalado
- ✅ Cuenta de Google (Gmail)
- ✅ Espacio suficiente en Google Drive (recomendado: 5+ GB)
- ✅ Permisos de administrador en Windows

### Estructura de Backups Creada

```
backups/
├── database/               # Backups de base de datos
│   ├── daily/             # Ultimos 7 dias
│   ├── weekly/            # Ultimas 4 semanas
│   └── monthly/           # Ultimos 12 meses
│
├── uploads_backup/        # Backups de archivos subidos
│   ├── daily/
│   ├── weekly/
│   └── monthly/
│
├── system/                # Backups completos del sistema
├── logs/                  # Logs de ejecucion
│
└── scripts/               # Scripts de backup
    ├── backup_database.php
    ├── backup_uploads.php
    ├── auto_backup.php
    ├── cleanup_old_backups.php
    ├── crear_tarea_programada.ps1
    └── crear_estructura.ps1
```

---

## 2. CONFIGURACION DE GOOGLE DRIVE DESKTOP

### Paso 1: Descargar e Instalar Google Drive Desktop

1. Ve a https://www.google.com/drive/download/
2. Descarga **Google Drive para Desktop**
3. Ejecuta el instalador
4. Inicia sesion con tu cuenta de Google

### Paso 2: Configurar la Sincronizacion

#### Opcion A: Mover la carpeta backups a Google Drive (RECOMENDADO)

1. Abre el explorador de archivos
2. Ve a tu carpeta de Google Drive (generalmente `G:\Mi unidad\`)
3. Crea una carpeta llamada `GASELAG_BACKUPS`
4. Copia la carpeta `C:\xampp\htdocs\form gaselag retiros\backups\` completa dentro de `GASELAG_BACKUPS`
5. Actualiza la ruta en los scripts PHP:
   ```php
   // En backup_database.php, backup_uploads.php, etc.
   'backup_path' => 'G:\\Mi unidad\\GASELAG_BACKUPS\\backups'
   ```

#### Opcion B: Sincronizar la carpeta actual

1. Abre Google Drive Desktop (icono en la bandeja del sistema)
2. Click en el icono de configuracion (engranaje)
3. Selecciona "Preferencias"
4. Ve a "Mi PC"
5. Click en "Agregar carpeta"
6. Selecciona: `C:\xampp\htdocs\form gaselag retiros\backups`
7. Elige "Sincronizar con Google Drive"
8. Click en "Listo"

### Paso 3: Verificar Sincronizacion

1. Abre Google Drive en el navegador (drive.google.com)
2. Busca la carpeta `backups` o `GASELAG_BACKUPS`
3. Verifica que puedes ver las carpetas `database`, `uploads_backup`, etc.

**NOTA:** La sincronizacion es automatica. Cualquier backup nuevo se subira a Google Drive automaticamente.

---

## 3. CONFIGURACION DE LA TAREA PROGRAMADA

### Metodo 1: Usar el Script Automatico (RECOMENDADO)

1. Abre PowerShell como **Administrador**
   - Click derecho en el menu Inicio
   - Selecciona "Windows PowerShell (Administrador)"

2. Navega a la carpeta de scripts:
   ```powershell
   cd "C:\xampp\htdocs\form gaselag retiros\backups\scripts"
   ```

3. Ejecuta el script:
   ```powershell
   .\crear_tarea_programada.ps1
   ```

4. El script creara automaticamente la tarea programada

### Metodo 2: Crear Manualmente

1. Abre el "Programador de tareas" de Windows
2. Click en "Crear tarea basica"
3. Configura:
   - **Nombre:** Backup GASELAG Automatico
   - **Descripcion:** Backup automatico del sistema GASELAG
   - **Desencadenador:** Diariamente a las 2:00 AM
   - **Accion:** Iniciar un programa
   - **Programa:** `C:\xampp\php\php.exe`
   - **Argumentos:** `"C:\xampp\htdocs\form gaselag retiros\backups\scripts\auto_backup.php"`
   - **Iniciar en:** `C:\xampp\htdocs\form gaselag retiros\backups\scripts`

### Verificar que la Tarea esta Activa

1. Abre "Programador de tareas"
2. Busca "Backup GASELAG Automatico"
3. Deberia aparecer "Preparado" en la columna Estado
4. Puedes hacer click derecho > "Ejecutar" para probar

---

## 4. USO DEL PANEL DE ADMINISTRACION

### Acceder al Panel

1. Abre tu navegador
2. Ve a: `http://localhost/form%20gaselag%20retiros/pages/admin_backups.php`
3. Inicia sesion con una cuenta de administrador

### Funciones Disponibles

#### Dashboard Principal
- **Backups Totales:** Cantidad de archivos de backup
- **Espacio Usado:** Tamano total de backups
- **Ultimo Backup:** Fecha del ultimo backup exitoso
- **Estado:** Estado del sistema de backups

#### Botones de Accion
- **Ejecutar Backup Completo:** BD + Archivos
- **Solo Base de Datos:** Backup rapido de BD
- **Solo Archivos:** Backup de uploads/
- **Limpiar Antiguos:** Elimina backups segun politica de retencion
- **Ver Logs:** Abre los logs de ejecucion

#### Pestanas

1. **Base de Datos**
   - Lista de backups de BD por periodo
   - Opcion de descargar cada backup

2. **Archivos Subidos**
   - Lista de backups de uploads/ por periodo
   - Opcion de descargar cada backup

3. **Configuracion**
   - Politica de retencion
   - Informacion de sincronizacion
   - Detalles de tarea programada

---

## 5. EJECUCION MANUAL DE BACKUPS

### Desde el Panel Web

1. Ve a `admin_backups.php`
2. Click en el boton correspondiente:
   - "Ejecutar Backup Completo"
   - "Solo Base de Datos"
   - "Solo Archivos"

### Desde Linea de Comandos

```bash
# Backup de base de datos
php "C:\xampp\htdocs\form gaselag retiros\backups\scripts\backup_database.php"

# Backup de archivos
php "C:\xampp\htdocs\form gaselag retiros\backups\scripts\backup_uploads.php"

# Backup completo con notificacion
php "C:\xampp\htdocs\form gaselag retiros\backups\scripts\auto_backup.php"

# Limpiar backups antiguos
php "C:\xampp\htdocs\form gaselag retiros\backups\scripts\cleanup_old_backups.php"
```

### Verificar Resultados

Los logs se guardan en:
```
C:\xampp\htdocs\form gaselag retiros\backups\logs\
```

Archivos de log:
- `backup_database.log` - Log de backups de BD
- `backup_uploads.log` - Log de backups de archivos
- `auto_backup.log` - Log del sistema automatico
- `cleanup.log` - Log de limpiezas

---

## 6. RESTAURACION DE BACKUPS

### Restaurar Base de Datos

#### Desde Google Drive

1. Ve a drive.google.com
2. Navega a la carpeta de backups
3. Descarga el archivo .zip deseado
4. Extrae el archivo .sql

#### Restaurar con phpMyAdmin

1. Abre http://localhost/phpmyadmin
2. Selecciona la base de datos `gaselag_retiros`
3. Click en "Importar"
4. Selecciona el archivo .sql
5. Click en "Continuar"

#### Restaurar con Linea de Comandos

```bash
# Descomprimir backup
unzip gaselag_db_backup_daily_2025-11-14_02-00-00.sql.zip

# Restaurar
mysql -u root -p --port=3307 gaselag_retiros < gaselag_db_backup_daily_2025-11-14_02-00-00.sql
```

### Restaurar Archivos (uploads/)

1. Descarga el backup de uploads desde Google Drive
2. Extrae el archivo .zip
3. Copia el contenido a: `C:\xampp\htdocs\form gaselag retiros\uploads\`
4. Verifica permisos de escritura en la carpeta

---

## 7. SOLUCION DE PROBLEMAS

### Problema: Los backups no se ejecutan automaticamente

**Solucion:**
1. Verifica que la tarea programada este activa en Windows
2. Ejecuta manualmente para ver errores
3. Revisa los logs en `backups/logs/`
4. Verifica que XAMPP este iniciado

### Problema: No se sincronizan con Google Drive

**Solucion:**
1. Verifica que Google Drive Desktop este ejecutandose
2. Click en el icono de Google Drive en la bandeja
3. Verifica el estado de sincronizacion
4. Asegurate de que hay espacio suficiente en Google Drive
5. Verifica que la carpeta este configurada para sincronizar

### Problema: Error "mysqldump no encontrado"

**Solucion:**
1. Abre `backups/scripts/backup_database.php`
2. Ajusta la ruta de mysqldump:
   ```php
   'mysqldump_path' => 'C:\\xampp\\mysql\\bin\\mysqldump.exe'
   ```
3. Verifica que el archivo existe en esa ubicacion

### Problema: Error de permisos al crear backups

**Solucion:**
1. Click derecho en la carpeta `backups/`
2. Propiedades > Seguridad
3. Editar permisos
4. Dar "Control total" a tu usuario

### Problema: Backups muy grandes

**Solucion:**
1. Los backups se comprimen automaticamente
2. Ejecuta manualmente el script de limpieza:
   ```bash
   php backups/scripts/cleanup_old_backups.php
   ```
3. Ajusta la politica de retencion si es necesario

### Problema: No llegan notificaciones por email

**Solucion:**
1. Verifica la configuracion SMTP de Windows
2. Alternativa: Usa PHPMailer (ver seccion siguiente)
3. Verifica que el email este configurado en `auto_backup.php`:
   ```php
   'email_to' => 'TU_EMAIL_AQUI@ejemplo.com'
   ```

---

## 📧 CONFIGURACION AVANZADA: Notificaciones con PHPMailer

Para notificaciones mas confiables, puedes usar PHPMailer con Gmail:

### 1. Instalar PHPMailer

```bash
cd "C:\xampp\htdocs\form gaselag retiros"
composer require phpmailer/phpmailer
```

### 2. Configurar Gmail

1. Ve a myaccount.google.com/security
2. Activa "Verificacion en dos pasos"
3. Genera una "Contrasena de aplicacion"
4. Guarda la contrasena generada

### 3. Actualizar auto_backup.php

Agrega al inicio del archivo:

```php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
```

Modifica la funcion `sendEmailNotification`:

```php
function sendEmailNotification($results, $overallSuccess) {
    global $config;
    
    $mail = new PHPMailer(true);
    
    try {
        // Configuracion del servidor
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tu_email@gmail.com';
        $mail->Password = 'tu_contrasena_de_aplicacion';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Destinatarios
        $mail->setFrom('backup@gaselag.com', 'Sistema de Backups GASELAG');
        $mail->addAddress($config['email_to']);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $config['email_subject'];
        $mail->Body = $emailBody; // (el mismo HTML de antes)
        
        $mail->send();
        writeLog("Email enviado exitosamente", "SUCCESS");
    } catch (Exception $e) {
        writeLog("Error al enviar email: {$mail->ErrorInfo}", "ERROR");
    }
}
```

---

## 📊 POLITICA DE RETENCION

| Periodo | Frecuencia | Cantidad Mantenida | Almacenamiento Estimado |
|---------|------------|-------------------|-------------------------|
| **Diarios** | Cada dia a las 2:00 AM | 7 backups | ~500 MB |
| **Semanales** | Domingos a las 2:00 AM | 4 backups | ~300 MB |
| **Mensuales** | Dia 1 a las 2:00 AM | 12 backups | ~1 GB |
| **TOTAL** | - | **23 backups** | **~1.8 GB** |

*Estimaciones basadas en una BD de 50 MB y uploads de 100 MB*

---

## ✅ CHECKLIST DE VERIFICACION

### Instalacion Inicial
- [ ] Estructura de carpetas creada
- [ ] Google Drive Desktop instalado y configurado
- [ ] Carpeta backups sincronizando con Google Drive
- [ ] Tarea programada creada y activa
- [ ] Email de notificaciones configurado

### Verificacion Semanal
- [ ] Backups aparecen en Google Drive
- [ ] Logs sin errores
- [ ] Espacio suficiente en Google Drive
- [ ] Tarea programada ejecutandose correctamente

### Verificacion Mensual
- [ ] Probar restauracion de un backup
- [ ] Verificar integridad de backups
- [ ] Limpiar backups antiguos manualmente si es necesario
- [ ] Revisar espacio usado

---

## 📞 SOPORTE

### Logs para Diagnostico

Al reportar un problema, incluye:
1. Contenido de `backups/logs/auto_backup.log`
2. Captura de pantalla del error
3. Version de PHP: `php -v`
4. Estado de la tarea programada

### Contacto

- **Sistema:** GASELAG - Gestion de Retiros
- **Version:** 2.0
- **Fecha:** Noviembre 2025

---

## 🎉 CONCLUSION

Con esta configuracion, tu sistema GASELAG tiene:

✅ **Backups automaticos diarios** a las 2:00 AM  
✅ **Sincronizacion con Google Drive** en tiempo real  
✅ **Politica de retencion** (7 dias, 4 semanas, 12 meses)  
✅ **Panel web** para administracion  
✅ **Notificaciones por email** de estado  
✅ **Limpieza automatica** de backups antiguos  
✅ **Logs detallados** de todas las operaciones  

**¡Tu informacion esta protegida! 🛡️**

---

**Ultima actualizacion:** Noviembre 2025  
**Sistema:** GASELAG v2.0  
**Autor:** Sistema de Backups Automaticos

