# 📋 Guía: Importación Masiva de Asignaciones desde Excel

## 🎯 ¿Para qué sirve?

Esta herramienta te permite asignar **cientos de OCs a técnicos en segundos**, usando un simple archivo Excel/CSV.

**Ejemplo de uso:**
- Tienes 1500 OCs cargadas
- Necesitas asignar 50 OCs específicas a Juan
- En lugar de hacerlo una por una → Usas Excel y lo haces en 2 minutos

---

## 📝 Paso a Paso

### **Paso 1: Preparar el archivo Excel**

Abre Excel y crea una tabla con 3 columnas:

| Numero_OC | Username_Tecnico | Notas_Opcionales |
|-----------|------------------|------------------|
| OC-73772  | 12345678         | Urgente          |
| 73773     | 12345678         | Coordinar        |
| OC-73774  | 87654321         |                  |
| 73775     | 12345678         | Revisar medidor  |

**Importante:**
- **Columna A (Numero_OC):** Puedes poner `OC-73772` o solo `73772`
- **Columna B (Username_Tecnico):** El username del técnico (ej: `12345678`)
- **Columna C (Notas_Opcionales):** Instrucciones para el técnico (opcional)

### **Paso 2: Guardar como CSV**

En Excel:
1. Click en **Archivo → Guardar Como**
2. Tipo de archivo: **CSV (delimitado por comas) (*.csv)**
3. Guardar

### **Paso 3: Subir el archivo**

En el sistema:
1. Ve a **Operaciones → Importar Asignaciones (Excel)**
2. Arrastra tu archivo CSV o haz click para seleccionar
3. Click en **"Procesar Archivo"**

### **Paso 4: Revisar y Confirmar**

El sistema te mostrará:
- ✅ Asignaciones válidas (en verde)
- ❌ Errores encontrados (en rojo)

Si todo está bien:
- Click en **"Confirmar y Asignar Todas"**
- ¡Listo! Las OCs fueron asignadas

---

## 💡 Tips y Trucos

### **Tip 1: Descargar Plantilla**
En la página hay un botón **"Descargar Plantilla CSV"** con ejemplos

### **Tip 2: Asignar a múltiples técnicos**
Puedes mezclar técnicos en el mismo archivo:

```
OC-73001, 12345678, Para Juan
OC-73002, 12345678, Para Juan
OC-73003, 87654321, Para María
OC-73004, 87654321, Para María
```

### **Tip 3: Copiar desde otro Excel**
Si ya tienes las OCs en otro Excel:
1. Copia las columnas
2. Pégalas en la plantilla
3. Agrega los usernames de técnicos
4. Guarda como CSV

### **Tip 4: Prefijo OC- opcional**
Estos formatos funcionan igual:
- `OC-73772` ✅
- `73772` ✅ (se agrega automáticamente)

---

## ⚠️ Errores Comunes

### "OC no existe en el sistema"
**Causa:** El número de OC está mal escrito o no fue importada aún  
**Solución:** Verifica el número en la lista de OCs

### "Técnico no existe o no está activo"
**Causa:** El username está mal o el usuario está inactivo  
**Solución:** Verifica el username en Gestión de Usuarios

### "OC ya tiene retiro registrado"
**Causa:** Esta OC ya fue completada por un técnico  
**Solución:** No necesitas asignarla

### "OC ya está asignada"
**Causa:** Esta OC ya fue asignada a otro técnico  
**Solución:** Cancela la asignación anterior primero

---

## 📊 Ventajas vs Asignación Manual

| Método                | 50 OCs | 200 OCs | 500 OCs |
|-----------------------|--------|---------|---------|
| **Manual (una x una)**| 15 min | 60 min  | 2.5 hrs |
| **Excel Import**      | 2 min  | 3 min   | 5 min   |

---

## 🎓 Ejemplos Reales

### Ejemplo 1: Asignar por zona
```csv
Numero_OC;Username_Tecnico;Notas_Opcionales
OC-73001;12345678;Zona HUAYCAN
OC-73002;12345678;Zona HUAYCAN
OC-73003;12345678;Zona HUAYCAN
OC-73050;87654321;Zona PORTALES
OC-73051;87654321;Zona PORTALES
```

### Ejemplo 2: Distribución equitativa
```csv
Numero_OC;Username_Tecnico;Notas_Opcionales
OC-73001;12345678;Lote 1
OC-73002;87654321;Lote 2
OC-73003;12345678;Lote 1
OC-73004;87654321;Lote 2
```

### Ejemplo 3: Por prioridad
```csv
Numero_OC;Username_Tecnico;Notas_Opcionales
OC-73001;12345678;URGENTE - Cliente VIP
OC-73002;12345678;URGENTE - Reclamo
OC-73003;87654321;Normal
```

---

## 📞 ¿Necesitas ayuda?

Si tienes dudas o encuentras algún problema, contacta al administrador del sistema.

---

**Última actualización:** Noviembre 2025  
**Versión:** 1.0

