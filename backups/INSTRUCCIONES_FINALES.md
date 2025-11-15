# 🚀 INSTRUCCIONES FINALES - 3 PASOS SIMPLES
## Sistema de Backups GASELAG

**Tu sistema de backups está instalado y funcionando!** ✅  
**Email configurado:** gaselagvp@gmail.com ✅

Solo faltan **3 pasos más** para completar la instalación:

---

## ⏱️ TIEMPO TOTAL: 20 MINUTOS

```
Paso 1: Google Drive Desktop  →  15 minutos
Paso 2: Tarea Programada      →   3 minutos  
Paso 3: Verificación           →   2 minutos
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TOTAL:                            20 minutos
```

---

## 📝 PASO 1: INSTALAR GOOGLE DRIVE DESKTOP (15 min)

### A. Descargar e Instalar

1. **Abre tu navegador**
2. **Ve a:** https://www.google.com/drive/download/
3. **Click en:** "Descargar Drive para computadoras de escritorio"
4. **Ejecuta** el instalador descargado
5. **Inicia sesión** con tu cuenta de Google
6. **Sigue** el asistente de instalación

### B. Configurar Sincronización (MUY IMPORTANTE)

**OPCIÓN 1 - RECOMENDADA (Sincronizar carpeta actual):**

1. En la bandeja del sistema de Windows, busca el icono de **Google Drive** (nube azul/blanca)
2. **Click derecho** en el icono
3. Selecciona **"Preferencias"** (o Settings)
4. Ve a la pestaña **"Mi PC"** (o My Computer)
5. Click en **"Agregar carpeta"**
6. Navega y selecciona:
   ```
   C:\xampp\htdocs\form gaselag retiros\backups
   ```
7. Elige **"Sincronizar con Google Drive"**
8. Click en **"Listo"** o **"Done"**

**IMPORTANTE:** Espera a que aparezca un mensaje de "Sincronización completada"

### C. Verificar

1. Abre tu navegador
2. Ve a: **drive.google.com**
3. Deberías ver una carpeta llamada **"backups"**
4. Al abrirla, deberías ver:
   - database
   - uploads_backup
   - logs
   - scripts
   - system

**Si ves estas carpetas, ¡PERFECTO! ✅**

---

## 📝 PASO 2: CREAR TAREA PROGRAMADA (3 min)

### A. Abrir PowerShell como Administrador

**Método 1 (Recomendado):**
1. Click en el **menú Inicio** de Windows
2. Busca **"PowerShell"**
3. Click derecho en **"Windows PowerShell"**
4. Selecciona **"Ejecutar como administrador"**
5. Click **"Sí"** en el cuadro de permisos

**Método 2 (Alternativo):**
1. Presiona **Windows + X**
2. Selecciona **"Windows PowerShell (Administrador)"**

### B. Ejecutar el Script

En la ventana de PowerShell, **copia y pega** estos comandos (uno por uno):

```powershell
cd "C:\xampp\htdocs\form gaselag retiros\backups\scripts"
```
**Presiona ENTER**

Luego:
```powershell
.\crear_tarea_programada.ps1
```
**Presiona ENTER**

### C. Resultado Esperado

Deberías ver:
```
========================================
  TAREA CREADA EXITOSAMENTE
========================================

Nombre: Backup GASELAG Automatico
Frecuencia: Diariamente a las 2:00 AM
Estado: Habilitada
```

**Si ves esto, ¡PERFECTO! ✅**

### D. Verificar la Tarea

1. Presiona **Windows + R**
2. Escribe: **taskschd.msc**
3. Presiona **ENTER**
4. Busca en la lista: **"Backup GASELAG Automatico"**
5. Debería aparecer con estado **"Preparado"** o **"Ready"**

---

## 📝 PASO 3: VERIFICACIÓN FINAL (2 min)

### A. Verificar en Google Drive

1. Ve a **drive.google.com**
2. Abre la carpeta **backups**
3. Abre **database > daily**
4. Deberías ver archivos **.zip** con la fecha de hoy

### B. Verificar Panel Web

1. Abre tu navegador
2. Ve a: 
   ```
   http://localhost/form%20gaselag%20retiros/pages/admin_backups.php
   ```
3. Inicia sesión como **administrador**
4. Deberías ver:
   - **Backups totales:** 4 (o más)
   - **Espacio usado:** ~0.74 MB
   - **Último backup:** Fecha de hoy
   - **Estado:** ACTIVO (verde)

### C. Revisar tu Email

1. Abre tu email: **gaselagvp@gmail.com**
2. Busca: **"Reporte de Backup - Sistema GASELAG"**
3. Si no lo ves en la bandeja principal, revisa:
   - **Spam**
   - **Promociones** (si usas Gmail)

El email debería tener:
- Fondo profesional con colores
- Tabla con detalles de backups
- Estado: EXITOSO
- Fecha de hoy

---

## ✅ CHECKLIST FINAL

Marca cada item cuando lo completes:

### Paso 1: Google Drive Desktop
- [ ] Google Drive Desktop instalado
- [ ] Sesión iniciada con mi cuenta de Google
- [ ] Carpeta backups agregada a sincronización
- [ ] Sincronización completada (icono verde/check)
- [ ] Carpeta backups visible en drive.google.com

### Paso 2: Tarea Programada
- [ ] PowerShell abierto como Administrador
- [ ] Script crear_tarea_programada.ps1 ejecutado
- [ ] Mensaje "TAREA CREADA EXITOSAMENTE" mostrado
- [ ] Tarea visible en Programador de Tareas de Windows
- [ ] Estado de la tarea: "Preparado"

### Paso 3: Verificación
- [ ] Backups visibles en Google Drive
- [ ] Panel web admin_backups.php accesible
- [ ] Estadísticas correctas mostradas
- [ ] Email de backup recibido
- [ ] Todo funcionando correctamente

---

## 🎯 DESPUÉS DE COMPLETAR LOS 3 PASOS

### ¡Tu sistema está listo! 🎉

**Lo que sucederá automáticamente:**

1. **Todos los días a las 2:00 AM:**
   - Se ejecutará el backup automático
   - Se creará backup de la base de datos
   - Se creará backup de archivos uploads
   - Los archivos se comprimirán en .zip
   - Se enviarán a Google Drive automáticamente
   - Recibirás un email de confirmación

2. **Automáticamente se limpiarán:**
   - Backups diarios mayores a 7 días
   - Backups semanales mayores a 4 semanas
   - Backups mensuales mayores a 12 meses

3. **Siempre tendrás:**
   - Acceso a tus backups desde cualquier lugar (Google Drive)
   - Panel web para administrar (admin_backups.php)
   - Notificaciones por email del estado
   - Logs detallados de todas las operaciones

---

## 🔔 NOTIFICACIONES QUE RECIBIRÁS

### Cada día a las ~2:01 AM

Recibirás un email en **gaselagvp@gmail.com** con:

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Reporte de Backup Automático
Sistema GASELAG - Gestión de Retiros
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Estado General: ✓ EXITOSO
Fecha y Hora: [fecha actual]

Detalles de Backups Ejecutados:

┌────────────────┬─────────┬──────────┬────────────────────┐
│ Backup         │ Estado  │ Duración │ Detalles           │
├────────────────┼─────────┼──────────┼────────────────────┤
│ DATABASE       │ ✓       │ 0.5s     │ Completado         │
│ UPLOADS        │ ✓       │ 0.2s     │ Completado         │
└────────────────┴─────────┴──────────┴────────────────────┘

Los backups se sincronizarán automáticamente con Google Drive
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

**Si hay algún error, también te notificará por email.**

---

## 🆘 ¿PROBLEMAS?

### Problema: "No veo la carpeta en Google Drive"

**Solución:**
1. Verifica que Google Drive Desktop esté corriendo (icono en bandeja)
2. Click en el icono de Google Drive
3. Verifica que diga "Sincronización completada"
4. Espera unos minutos y actualiza drive.google.com
5. Si no aparece, repite el Paso 1, sección B

### Problema: "PowerShell no me deja ejecutar el script"

**Solución:**
```powershell
# Ejecuta este comando primero:
Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy Bypass -Force

# Luego intenta de nuevo:
.\crear_tarea_programada.ps1
```

### Problema: "No recibo el email"

**Solución:**
1. Revisa SPAM y Promociones
2. Los emails pueden tardar 5-10 minutos
3. Verifica en el log: `backups\logs\auto_backup.log`
4. Busca la línea: "Email enviado exitosamente"
5. Si dice "Email enviado exitosamente" pero no lo recibes, revisa configuración de tu correo

### Más Ayuda

Lee la documentación completa en:
- `backups\README.md` - Guía rápida
- `backups\GUIA_BACKUPS_GOOGLE_DRIVE.md` - Guía completa (459 líneas)
- `backups\IMPLEMENTACION_COMPLETADA.md` - Detalles técnicos

---

## 📱 ACCESOS RÁPIDOS

### Panel de Administración Web
```
http://localhost/form%20gaselag%20retiros/pages/admin_backups.php
```

### Google Drive
```
https://drive.google.com
```

### Carpeta de Backups Local
```
C:\xampp\htdocs\form gaselag retiros\backups\
```

### Logs
```
C:\xampp\htdocs\form gaselag retiros\backups\logs\
```

---

## 🎓 CONSEJOS FINALES

### ✅ Buenas Prácticas

1. **Revisa tu email diario** para confirmar que el backup se ejecutó
2. **Una vez al mes** descarga un backup desde Google Drive para verificar
3. **Cada 3 meses** prueba restaurar un backup en un ambiente de prueba
4. **Mantén espacio libre** en Google Drive (mínimo 5 GB disponibles)
5. **No elimines** la carpeta backups local mientras uses el sistema

### ⚠️ Importante

- **No modifiques** los scripts en `backups\scripts\` a menos que sepas lo que haces
- **No elimines** los logs, se limpian automáticamente después de 30 días
- **Mantén XAMPP corriendo** o programa que inicie antes de las 2:00 AM
- **Revisa tu espacio** en Google Drive periódicamente

---

## 🏁 RESUMEN ULTRA-RÁPIDO

```
1. Instalar Google Drive Desktop        →  www.google.com/drive/download
2. Sincronizar carpeta backups          →  Click derecho icono > Preferencias
3. Ejecutar: crear_tarea_programada.ps1 →  PowerShell como Admin
4. ¡Listo! Backups automáticos a las 2 AM todos los días
```

---

## 🎉 ¡FELICITACIONES!

Una vez que completes estos 3 pasos:

✅ Tus datos estarán **100% protegidos**  
✅ Backups **automáticos** todos los días  
✅ Sincronización con **Google Drive**  
✅ **Notificaciones** por email  
✅ **Panel web** de administración  

**¡Tu sistema GASELAG está completamente seguro! 🛡️**

---

**Tiempo estimado:** 20 minutos  
**Dificultad:** Fácil  
**Resultado:** Sistema profesional de backups  

**¿Preguntas?** Lee la documentación completa en:  
📖 `backups\GUIA_BACKUPS_GOOGLE_DRIVE.md`

---

**Sistema GASELAG v2.0**  
**Módulo de Backups Automatizados**  
**Implementado:** 14 de Noviembre de 2025  
**Estado:** ✅ LISTO PARA USAR

