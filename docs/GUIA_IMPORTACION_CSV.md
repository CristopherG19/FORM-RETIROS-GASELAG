# 📥 Guía de Importación de Datos CSV - GASELAG

## 🎯 Resumen Rápido

Esta guía te ayudará a importar órdenes de servicio al sistema usando archivos CSV.

---

## ✅ Paso a Paso

### 1️⃣ Descargar la Plantilla

1. Ve a **"Importar Datos"** en el menú
2. Abre la pestaña **"Plantilla"**
3. Haz clic en **"Descargar Plantilla"**
4. Se descargará el archivo `plantilla_importacion_gaselag.csv`

### 2️⃣ Editar la Plantilla

1. **Abre el archivo** con Excel o Google Sheets
2. **NO BORRES** la fila de encabezados (primera fila)
3. La segunda fila contiene **un ejemplo de referencia**
4. **Completa tus datos** a partir de la tercera fila

### 3️⃣ Formato de Datos

#### 📅 **FECHAS (MUY IMPORTANTE)**
- **Formato obligatorio:** `DD/MM/YYYY` o `D/M/YYYY` (con o sin ceros iniciales)
- ✅ **Válido:** `13/12/2024`, `06/01/2025`, `6/01/2025`, `25/03/2024`
- ❌ **Inválido:** `2024-12-13`, `12-13-2024`, `13/12/24`, `13-12-2024`

#### 🕐 **HORAS (FORMATO ESPECIAL)**
- **Formato obligatorio:** `HH.MM` (con **punto decimal**, no dos puntos)
- **Interpretación:** El decimal representa **decenas de minutos**
- ✅ **Válido:** 
  - `10.3` = 10:30 (diez y treinta)
  - `8.15` = 08:15 (ocho y quince)
  - `14` = 14:00 (catorce horas)
  - `12.3` = 12:30 (doce y treinta)
- ❌ **Inválido:** `10:30`, `08:15` (no usar dos puntos)

#### 📋 **Campos Obligatorios**

| Campo | Columna | Descripción | Ejemplo |
|-------|---------|-------------|---------|
| **Item** | 1 | Número de ítem | `00001` |
| **Orden de servicio** | 2 | Código de OC (único) | `OC-00001` |
| **Cliente** | 11 | Nombre del cliente | `Juan Pérez` |
| **N° de Suministro** | 18 | Número de suministro | `5367165` |
| **N° de Serie del Medidor** | 19 | Serie del medidor | `EA22282911` |

#### 🔢 **Campos Numéricos Opcionales**

| Campo | Tipo | Ejemplo |
|-------|------|---------|
| Cantidad de medidores | Número entero | `1` |
| Año de Fabricación | Año | `2023` |
| Diámetro Nominal (mm) | Número entero | `15` |
| Q3 (m3/h) | Decimal | `2.5` |
| PMA (bar) | Número entero | `10` |
| TMA (°C) | Número entero | `50` |

### 4️⃣ Guardar el Archivo

1. **Guarda el archivo como CSV**
   - En Excel: `Archivo → Guardar como → CSV (delimitado por comas)`
   - En Google Sheets: `Archivo → Descargar → Valores separados por comas (.csv)`

2. **IMPORTANTE:** Asegúrate de que:
   - El separador sea **punto y coma (;)**
   - La codificación sea **UTF-8**
   - Las fechas estén en formato **DD/MM/YYYY**

### 5️⃣ Importar el Archivo

1. Ve a la pestaña **"Subir CSV"**
2. **Arrastra** tu archivo o **haz clic** para seleccionarlo
3. Verifica que aparezca el nombre del archivo
4. Haz clic en **"Importar Archivo al Sistema"**
5. Espera a que termine el proceso

---

## 🔧 Solución de Problemas

### ❌ "Formato de fecha inválido"

**Problema:** Las fechas no están en el formato correcto.

**Solución:**
1. Abre tu archivo CSV en Excel
2. Selecciona las columnas de fechas:
   - Columna C: Fecha OS
   - Columna F: Programación Día Retiro
   - Columna H: Programación día VP
3. Cambia el formato a **personalizado** con patrón: `DD/MM/YYYY` o simplemente **texto**
4. Asegúrate de que se vea así: `13/12/2024` o `6/01/2025` (no `2024-12-13`)
5. Guarda nuevamente como CSV

### ❌ "Formato de hora inválido"

**Problema:** Las horas deben usar punto decimal, no dos puntos.

**Solución:**
1. Las horas deben estar en formato `HH.MM` (con **punto**)
2. Ejemplos correctos:
   - `10.3` = 10:30
   - `8.15` = 08:15
   - `14` = 14:00
   - `9.45` = 09:45
3. Si tienes horas con dos puntos (`10:30`):
   - En Excel: Buscar y reemplazar `:` por `.`
   - O formatea la celda como **texto** y escribe `10.3`

### ❌ "Campos obligatorios faltantes"

**Problema:** Faltan datos en campos requeridos.

**Solución:**
- Verifica que estas columnas NO estén vacías:
  - A: Item
  - B: Orden de servicio
  - K: Cliente
  - R: N° de Suministro
  - S: N° de Serie del Medidor

### ❌ "Columnas insuficientes"

**Problema:** El archivo no tiene todas las columnas necesarias.

**Solución:**
1. Descarga nuevamente la plantilla oficial
2. Copia tus datos a la nueva plantilla
3. NO agregues ni quites columnas
4. Respeta el orden de las columnas

### ❌ Excel cambia el formato de las fechas automáticamente

**Problema:** Excel convierte las fechas a su propio formato (YYYY-MM-DD).

**Solución:**

**Opción 1 (Más fácil):**
1. Antes de abrir el CSV, abre Excel (vacío)
2. Ve a `Datos → Desde texto/CSV`
3. Selecciona tu archivo CSV
4. En el asistente, marca las columnas de fecha como **"Texto"**
5. Termina la importación

**Opción 2 (Alternativa):**
1. Abre el CSV con un editor de texto (Notepad, Notepad++)
2. Verifica que las fechas estén en formato `DD/MM/YYYY`
3. Si están bien, úsalo directamente (no abras con Excel)
4. Sube el archivo al sistema

---

## 💡 Consejos y Buenas Prácticas

### ✅ HACER

- ✅ Usa siempre la **plantilla oficial** descargada del sistema
- ✅ Completa **todos los campos obligatorios**
- ✅ Usa el formato de fecha **DD/MM/YYYY**
- ✅ Guarda con codificación **UTF-8**
- ✅ Revisa tu archivo antes de importar
- ✅ Importa en lotes pequeños para probar (10-50 registros)

### ❌ NO HACER

- ❌ NO cambies el orden de las columnas
- ❌ NO agregues ni quites columnas
- ❌ NO uses formato de fecha YYYY-MM-DD
- ❌ NO dejes campos obligatorios vacíos
- ❌ NO uses Excel directamente sin verificar formato
- ❌ NO uses caracteres especiales en números

---

## 📊 Estructura Completa del CSV

| # | Columna | Tipo | Obligatorio | Ejemplo |
|---|---------|------|-------------|---------|
| 1 | Item | Texto | ✅ Sí | `00001` |
| 2 | Orden de servicio | Texto | ✅ Sí | `OC-00001` |
| 3 | Fecha OS | Fecha | ⚪ No | `13/12/2024` |
| 4 | Cantidad de medidores | Número | ⚪ No | `1` |
| 5 | Tipo de Servicio | Texto | ⚪ No | `Reclamo` |
| 6 | Programación Día Retiro | Fecha | ⚪ No | `06/01/2025` |
| 7 | Programación Hora Retiro | Hora | ⚪ No | `08:00` |
| 8 | Programación día VP | Fecha | ⚪ No | `07/01/2025` |
| 9 | Programación Hora VP | Hora | ⚪ No | `10:00` |
| 10 | CODIGO SEGURIDAD | Texto | ⚪ No | `ABC123` |
| 11 | Cliente | Texto | ✅ Sí | `Cliente Ejemplo` |
| 12 | Centro de Servicio | Texto | ⚪ No | `Centro 001` |
| 13 | Remesa | Texto | ⚪ No | `REM001` |
| 14 | Usuario - Reclamante | Texto | ⚪ No | `Juan Pérez` |
| 15 | Dirección | Texto | ⚪ No | `Calle Principal 123` |
| 16 | CUS | Texto | ⚪ No | `CUS001` |
| 17 | CUP | Texto | ⚪ No | `CUP001` |
| 18 | N° de Suministro | Texto | ✅ Sí | `5367165` |
| 19 | N° de Serie del Medidor | Texto | ✅ Sí | `EA22282911` |
| 20 | Marca del medidor | Texto | ⚪ No | `Marca Ejemplo` |
| 21 | Modelo del medidor | Texto | ⚪ No | `Modelo 001` |
| 22 | Año de Fabricacion | Número | ⚪ No | `2023` |
| 23 | Fabricante | Texto | ⚪ No | `Fabricante Ejemplo` |
| 24 | Procedencia | Texto | ⚪ No | `Nacional` |
| 25 | Tipo Medidor | Texto | ⚪ No | `Residencial` |
| 26 | Diámetro Nominal (mm) | Número | ⚪ No | `15` |
| 27 | Q3 (m3/h) | Decimal | ⚪ No | `2.5` |
| 28 | Alcance | Texto | ⚪ No | `R160` |
| 29 | PMA (bar) | Número | ⚪ No | `10` |
| 30 | TMA (°C) | Número | ⚪ No | `50` |
| 31 | Clase de sensibilidad | Texto | ⚪ No | `1.5` |
| 32 | Certificado de aprobación | Texto | ⚪ No | `Cert001` |
| 33 | N° de Certificado | Texto | ⚪ No | `CERT001` |

---

## 🆘 ¿Necesitas Ayuda?

Si sigues teniendo problemas:

1. **Verifica el archivo de ejemplo** incluido en la plantilla
2. **Revisa los mensajes de error** detallados después de importar
3. **Importa por lotes pequeños** para identificar filas problemáticas
4. **Contacta al administrador** del sistema

---

**📌 Recuerda:** La clave del éxito está en usar el **formato correcto de fechas (DD/MM/YYYY)** y completar todos los **campos obligatorios**.

