# ⚙️ CONFIGURAR SINCRONIZACIÓN CON GOOGLE DRIVE

## Tu Configuración
- **Cuenta:** gaselagvp@gmail.com
- **Ruta Google Drive:** G:\Mi unidad
- **Carpeta a sincronizar:** C:\xampp\htdocs\form gaselag retiros\backups

---

## OPCIÓN A: SINCRONIZAR CARPETA ACTUAL (RECOMENDADO) ⭐

### Paso 1: Abrir Configuración de Google Drive

1. **Busca el icono de Google Drive** en la bandeja del sistema (esquina inferior derecha)
   - Es una nube azul/blanca o un ícono con forma de triángulo
   
2. **Click en el icono de Google Drive**

3. **Click en el ícono de configuración** ⚙️ (engranaje) en la ventana que se abre

4. **Selecciona "Preferencias"** (o "Settings" si está en inglés)

### Paso 2: Agregar Carpeta para Sincronizar

1. En la ventana de Preferencias, busca la pestaña **"Mi PC"** (o "My Computer")

2. Click en el botón **"Agregar carpeta"** (o "Add folder")

3. En el explorador que se abre, navega a:
   ```
   C:\xampp\htdocs\form gaselag retiros\backups
   ```

4. Selecciona la carpeta **backups** y click en **"Seleccionar carpeta"**

5. Asegúrate de que la opción sea:
   ✅ **"Sincronizar con Google Drive"** (o "Sync with Google Drive")
   
   NO selecciones "Stream" - debe ser "Sync"

6. Click en **"Listo"** o **"Done"**

### Paso 3: Esperar Sincronización

1. Google Drive comenzará a sincronizar los archivos
2. Verás un ícono de sincronización en la bandeja del sistema
3. Espera unos minutos hasta que termine

**Cuando termine, verás un mensaje:** "Sincronización completada" o "Up to date"

### Paso 4: Verificar en Google Drive Web

1. Abre tu navegador
2. Ve a: **https://drive.google.com**
3. Inicia sesión con: **gaselagvp@gmail.com**
4. Deberías ver una carpeta llamada **"backups"**
5. Ábrela y verifica que veas:
   - database/
   - uploads_backup/
   - logs/
   - scripts/

**Si ves estas carpetas, ¡PERFECTO! ✅**

---

## OPCIÓN B: COPIAR CARPETA A GOOGLE DRIVE

Si prefieres tener los backups directamente en la carpeta de Google Drive:

### Paso 1: Crear Carpeta en Google Drive

1. Abre el Explorador de Windows
2. Ve a: `G:\Mi unidad\`
3. Crea una carpeta llamada: `GASELAG_BACKUPS`

### Paso 2: Copiar Carpeta Backups

1. Copia toda la carpeta:
   ```
   C:\xampp\htdocs\form gaselag retiros\backups\
   ```

2. Pégala dentro de:
   ```
   G:\Mi unidad\GASELAG_BACKUPS\
   ```

### Paso 3: Actualizar Rutas en Scripts

Necesitarás actualizar las rutas en cada script PHP.

**Ejecuta este script para actualizar automáticamente:**

```bash
# PENDIENTE - Crear script de actualización de rutas
```

**⚠️ NOTA:** Esta opción requiere más pasos. Te recomiendo la Opción A.

---

## ✅ VERIFICACIÓN FINAL

Una vez configurada la sincronización (Opción A o B), verifica:

### 1. En Windows (Local)
- La carpeta backups tiene un ícono de nube con check ✓
- Al abrir propiedades, debería indicar que está sincronizada

### 2. En Google Drive Web
```
https://drive.google.com
```
- Deberías ver la carpeta "backups" o "GASELAG_BACKUPS"
- Dentro deberías ver los 4 archivos .zip creados

### 3. Espacio Usado
- Ve a Google Drive web
- En la esquina inferior izquierda verás: "0.74 MB de 15 GB usados"

---

## 🎉 ¡LISTO!

Con la **Opción A**, tus backups se sincronizarán automáticamente con Google Drive sin necesidad de cambiar nada en los scripts.

**Cada vez que se cree un nuevo backup:**
1. Se guardará en: `C:\xampp\htdocs\form gaselag retiros\backups\`
2. Google Drive lo detectará automáticamente
3. Lo subirá a la nube en segundos
4. Estará disponible en: https://drive.google.com

---

## 🔜 SIGUIENTE PASO

Una vez que confirmes que los backups aparecen en Google Drive, continúa con:

**PASO 2: Crear Tarea Programada**

Ejecuta en PowerShell como Administrador:
```powershell
cd "C:\xampp\htdocs\form gaselag retiros\backups\scripts"
.\crear_tarea_programada.ps1
```

---

**¿Dudas?** Pregúntame antes de continuar.





