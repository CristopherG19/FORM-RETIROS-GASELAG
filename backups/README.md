# 🛡️ SISTEMA DE BACKUPS AUTOMATICOS
## GASELAG - Gestion de Retiros

---

## 🚀 INICIO RAPIDO

### 1. Configurar Email para Notificaciones

Edita `scripts/auto_backup.php` y cambia:
```php
'email_to' => 'TU_EMAIL_AQUI@ejemplo.com'
```

### 2. Configurar Google Drive Desktop

1. Instala Google Drive Desktop
2. Sincroniza esta carpeta `backups/` con Google Drive
3. Sigue la guia completa en: `GUIA_BACKUPS_GOOGLE_DRIVE.md`

### 3. Crear Tarea Programada

Ejecuta como Administrador:
```powershell
.\scripts\crear_tarea_programada.ps1
```

### 4. Probar el Sistema

```bash
# Backup manual completo
php scripts\auto_backup.php

# Solo base de datos
php scripts\backup_database.php

# Solo archivos
php scripts\backup_uploads.php
```

---

## 📁 ESTRUCTURA

```
backups/
├── database/              # Backups de BD
│   ├── daily/            # Ultimos 7 dias
│   ├── weekly/           # Ultimas 4 semanas
│   └── monthly/          # Ultimos 12 meses
│
├── uploads_backup/       # Backups de archivos
│   ├── daily/
│   ├── weekly/
│   └── monthly/
│
├── system/               # Backups del sistema completo
├── logs/                 # Logs de ejecucion
│
└── scripts/              # Scripts de backup
    ├── backup_database.php          # Backup de BD
    ├── backup_uploads.php           # Backup de uploads
    ├── auto_backup.php              # Backup completo automatico
    ├── cleanup_old_backups.php      # Limpieza de antiguos
    ├── crear_tarea_programada.ps1   # Configurar Windows Task
    └── crear_estructura.ps1         # Crear carpetas
```

---

## ⚙️ CONFIGURACION

### Politica de Retencion

| Periodo | Mantener | Frecuencia |
|---------|----------|------------|
| Diarios | 7 backups | Todos los dias 2:00 AM |
| Semanales | 4 backups | Domingos 2:00 AM |
| Mensuales | 12 backups | Dia 1 del mes 2:00 AM |

### Rutas Importantes

- **PHP:** `C:\xampp\php\php.exe`
- **MySQLDump:** `C:\xampp\mysql\bin\mysqldump.exe`
- **Scripts:** `C:\xampp\htdocs\form gaselag retiros\backups\scripts\`
- **Logs:** `C:\xampp\htdocs\form gaselag retiros\backups\logs\`

---

## 🌐 PANEL WEB DE ADMINISTRACION

Accede a: `http://localhost/form%20gaselag%20retiros/pages/admin_backups.php`

**Funciones:**
- Ver estadisticas de backups
- Ejecutar backups manualmente
- Descargar backups
- Ver logs
- Limpiar backups antiguos

---

## 📊 SCRIPTS DISPONIBLES

### backup_database.php
Crea backup de la base de datos MySQL.
- Comprime automaticamente (.zip)
- Organiza por periodo (daily/weekly/monthly)
- Genera logs detallados

### backup_uploads.php
Crea backup de la carpeta uploads/ (fotos de evidencias).
- Comprime en formato ZIP
- Incluye toda la estructura de carpetas
- Excluye archivos index.php

### auto_backup.php (PRINCIPAL)
Script maestro que ejecuta todos los backups.
- Ejecuta backup_database.php
- Ejecuta backup_uploads.php
- Envia notificaciones por email
- Genera reporte HTML
- Se ejecuta automaticamente via tarea programada

### cleanup_old_backups.php
Elimina backups antiguos segun politica de retencion.
- Mantiene 7 backups diarios
- Mantiene 4 backups semanales
- Mantiene 12 backups mensuales
- Libera espacio en disco

---

## 🔔 NOTIFICACIONES

El sistema envia emails con:
- Estado de cada backup (exitoso/error)
- Tiempo de ejecucion
- Tamano de archivos generados
- Estadisticas generales
- Ubicacion de backups

**Formato:** HTML profesional con colores y tablas

---

## 🔧 PERSONALIZACION

### Cambiar Hora de Ejecucion

Edita la tarea en "Programador de Tareas" de Windows o:
```powershell
# Eliminar tarea actual
Unregister-ScheduledTask -TaskName "Backup GASELAG Automatico"

# Crear nueva con diferente hora
# (editar crear_tarea_programada.ps1)
```

### Cambiar Politica de Retencion

Edita cada script y modifica:
```php
'retention_policy' => [
    'daily' => 7,      # Cambiar cantidad
    'weekly' => 4,     # Cambiar cantidad
    'monthly' => 12    # Cambiar cantidad
]
```

### Activar/Desactivar Notificaciones

En `auto_backup.php`:
```php
'email_enabled' => true,  // false para desactivar
```

---

## 📝 LOGS

Todos los scripts generan logs en `logs/`:

- `backup_database.log` - Backups de base de datos
- `backup_uploads.log` - Backups de archivos
- `auto_backup.log` - Ejecucion automatica completa
- `cleanup.log` - Limpiezas realizadas
- `tarea_programada.log` - Ejecuciones de Windows Task

**Formato:**
```
[2025-11-14 02:00:00] [INFO] === INICIANDO BACKUP DE BASE DE DATOS ===
[2025-11-14 02:00:05] [SUCCESS] Backup completado exitosamente
[2025-11-14 02:00:05] [INFO] Tamano: 45.2 MB
```

---

## ✅ VERIFICACION

### Verificar que todo funciona:

1. **Estructura de carpetas creada:**
   ```bash
   dir backups\database\daily
   dir backups\uploads_backup\daily
   ```

2. **Tarea programada activa:**
   - Abre "Programador de Tareas"
   - Busca "Backup GASELAG Automatico"
   - Estado debe ser "Preparado"

3. **Google Drive sincronizando:**
   - Icono de Google Drive en bandeja del sistema
   - Carpeta backups visible en drive.google.com

4. **Scripts funcionan:**
   ```bash
   php scripts\backup_database.php
   # Debe crear archivo en database\daily\
   ```

5. **Panel web accesible:**
   - Navega a admin_backups.php
   - Debe mostrar estadisticas

---

## 🚨 PROBLEMAS COMUNES

### Error: "mysqldump no encontrado"
**Solucion:** Edita `backup_database.php` y ajusta la ruta de mysqldump

### Error: "Permission denied"
**Solucion:** Da permisos de escritura a la carpeta backups

### No sincroniza con Google Drive
**Solucion:** Verifica que Google Drive Desktop este ejecutandose

### No llegan emails
**Solucion:** 
1. Verifica el email en `auto_backup.php`
2. Considera usar PHPMailer (ver guia completa)

---

## 📖 DOCUMENTACION COMPLETA

Lee `GUIA_BACKUPS_GOOGLE_DRIVE.md` para:
- Instrucciones paso a paso
- Configuracion avanzada
- Restauracion de backups
- Solucion de problemas detallada
- Configuracion de PHPMailer
- Tips y mejores practicas

---

## 🔄 RESTAURACION

### Restaurar Base de Datos

1. Descarga el backup de Google Drive
2. Extrae el .sql del .zip
3. Ejecuta:
   ```bash
   mysql -u root -p --port=3307 gaselag_retiros < archivo.sql
   ```

### Restaurar Archivos

1. Descarga el backup de uploads
2. Extrae el contenido
3. Copia a `uploads/`

---

## 📞 SOPORTE

### Al reportar un problema incluye:

1. Contenido del log correspondiente
2. Captura de pantalla del error
3. Version de PHP: `php -v`
4. Sistema operativo y version

### Verificar estado del sistema:

```bash
# Ver ultimo backup
php scripts\backup_database.php

# Ver logs
type logs\auto_backup.log

# Ver espacio usado
dir /s backups\
```

---

## 🎯 CHECKLIST DE INSTALACION

- [ ] Estructura de carpetas creada
- [ ] Email configurado en auto_backup.php
- [ ] Rutas de PHP y MySQLDump verificadas
- [ ] Google Drive Desktop instalado
- [ ] Carpeta backups sincronizando
- [ ] Tarea programada creada
- [ ] Backup manual ejecutado exitosamente
- [ ] Panel web accesible
- [ ] Email de prueba recibido
- [ ] Backup aparece en Google Drive

---

## 📈 BENEFICIOS

✅ **Proteccion automatica** de datos  
✅ **Sincronizacion en la nube** con Google Drive  
✅ **Sin intervencion manual** necesaria  
✅ **Politica de retencion** inteligente  
✅ **Notificaciones** de estado  
✅ **Panel web** de administracion  
✅ **Logs detallados** para auditoria  
✅ **Facil restauracion** cuando se necesite  

---

## 🆘 COMANDOS UTILES

```bash
# Ver backups actuales
dir /s /b backups\*.zip

# Ejecutar backup manual
php scripts\auto_backup.php

# Limpiar backups antiguos
php scripts\cleanup_old_backups.php

# Ver logs en tiempo real
Get-Content logs\auto_backup.log -Wait

# Probar tarea programada
schtasks /Run /TN "Backup GASELAG Automatico"

# Ver estado de tarea
schtasks /Query /TN "Backup GASELAG Automatico" /V /FO LIST
```

---

**Sistema de Backups GASELAG v2.0**  
**Fecha:** Noviembre 2025  
**Estado:** ACTIVO 🟢  

---

**¡Tus datos estan protegidos! 🛡️**

