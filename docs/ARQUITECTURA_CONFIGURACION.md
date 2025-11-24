# 📚 Arquitectura de Configuración del Sistema

## 🎯 Separación de Responsabilidades

El sistema utiliza dos archivos principales de configuración con responsabilidades claramente definidas:

---

## 1️⃣ `config/environment.php` - **Configuración de Ambiente**

### **Responsabilidad:**
Gestiona la configuración que **CAMBIA entre desarrollo y producción**.

### **Contenido:**
- ✅ Constante `ENVIRONMENT` ('development' o 'production')
- ✅ Credenciales de base de datos (por ambiente)
- ✅ URLs base del sistema
- ✅ Configuración de debugging
- ✅ Rutas de uploads
- ✅ Zona horaria

### **Funciones proporcionadas:**
- `isDevelopment()` - Verifica si estamos en desarrollo
- `isProduction()` - Verifica si estamos en producción
- `url($path)` - Genera URLs del sistema
- `getEnvironmentInfo()` - Información del ambiente actual

### **Cuándo modificar:**
- ⚙️ Al cambiar de localhost a producción
- ⚙️ Al actualizar credenciales de BD
- ⚙️ Al cambiar el dominio del sitio

---

## 2️⃣ `config/AppConfig.php` - **Configuración de Aplicación**

### **Responsabilidad:**
Define constantes y funciones que **NO CAMBIAN entre ambientes**.

### **Contenido:**
- ✅ Nombre y versión de la aplicación
- ✅ Configuración de archivos (tamaño máximo, tipos permitidos)
- ✅ Configuración de sesiones
- ✅ Configuración de auditoría
- ✅ Configuración de paginación
- ✅ Funciones de utilidad (validación, sanitización, formateo)

### **Dependencia:**
⚠️ **REQUIERE `environment.php`** para usar `isDevelopment()` y `ENVIRONMENT`.

### **Funciones proporcionadas:**
- `getAppConfig()` - Obtiene configuración de la app
- `getAppUrl()` - Genera URL completa
- `isValidFile()` - Valida archivos subidos
- `jsonResponse()` - Respuestas JSON estandarizadas
- `validateFormData()` - Validación de formularios
- `sanitizeData()` - Sanitización de datos
- Y más...

### **Cuándo modificar:**
- 🔧 Al cambiar reglas de validación
- 🔧 Al ajustar límites de archivos
- 🔧 Al agregar nuevas funciones de utilidad

---

## 🔄 Flujo de Carga de Archivos

### **Forma 1: A través de database.php (Recomendado)**
```php
require_once 'config/database.php';
// ↓
// database.php carga environment.php automáticamente
// Ahora tienes acceso a:
// - Conexión PDO
// - Todas las funciones de BD
// - isDevelopment(), isProduction(), url()
```

### **Forma 2: AppConfig.php directamente**
```php
require_once 'config/AppConfig.php';
// ↓
// AppConfig.php carga environment.php automáticamente
// Ahora tienes acceso a:
// - Funciones de utilidad
// - isDevelopment(), isProduction()
// - Constantes de la aplicación
```

### **Forma 3: Ambos (Sin problemas de duplicación)**
```php
require_once 'config/database.php';
require_once 'config/AppConfig.php';
// ↓
// environment.php se carga solo UNA vez (require_once)
// Tienes acceso a TODO
```

---

## 📝 Ejemplo Práctico: Cambiar de Desarrollo a Producción

### **Solo necesitas editar `environment.php`:**

```php
// DESARROLLO (localhost)
define('ENVIRONMENT', 'development');

// PRODUCCIÓN (hosting)
define('ENVIRONMENT', 'production');
```

¡Y listo! Todo el sistema se ajusta automáticamente:
- ✅ Credenciales de BD correctas
- ✅ URLs correctas
- ✅ Nivel de debugging apropiado
- ✅ Todas las funciones usan el ambiente correcto

---

## 🛡️ Ventajas de Esta Arquitectura

1. **Un solo lugar para cambiar ambiente** (`environment.php`)
2. **Separación clara de responsabilidades**
3. **Sin duplicación de código**
4. **Dependencias explícitas** (no implícitas)
5. **Fácil mantenimiento**
6. **Previene errores** de configuración inconsistente

---

## ⚠️ IMPORTANTE: No modificar

- ❌ No crear constantes duplicadas (`APP_ENV` vs `ENVIRONMENT`)
- ❌ No usar `APP_ENV` directamente, usar `ENVIRONMENT`
- ❌ No declarar `isDevelopment()` en otros archivos
- ❌ No cargar archivos de configuración en orden diferente

---

## 📋 Resumen Rápido

| Archivo | Propósito | Cambia entre ambientes |
|---------|-----------|------------------------|
| `environment.php` | BD, URLs, Debug | ✅ SÍ |
| `AppConfig.php` | Constantes, Utilidades | ❌ NO |

---

**Última actualización:** 24/11/2025  
**Sistema:** GASELAG - Retiros de Medidores v2.0

