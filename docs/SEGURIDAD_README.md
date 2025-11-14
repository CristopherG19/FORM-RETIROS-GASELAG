# 🔒 SISTEMA DE SEGURIDAD MEJORADO - GASELAG

## ✅ Implementación Completada

Se ha implementado un sistema de seguridad robusto diferenciado por roles (Técnicos vs Administradores).

---

## 📋 INSTRUCCIONES DE INSTALACIÓN

### 1. Actualizar Base de Datos

Ejecutar el archivo SQL actualizado:

```sql
mysql -u root -P 3307 < database/schema.sql
```

O importar manualmente en phpMyAdmin el archivo `database/schema.sql`.

### 2. Verificar Configuración

Los archivos de configuración ya están creados en `config/`:
- `SecurityConfig.php` - Constantes de seguridad
- `PasswordPolicy.php` - Validación de contraseñas/PINs
- `RateLimiter.php` - Control de intentos
- `ping.php` - Endpoint AJAX para sesiones

### 3. Usuarios de Prueba

**Administrador:**
- Usuario: `admin`
- Password: `password` (cambiar en primer login)

**Técnicos:**
- Usuario: `12345678` (DNI)
- PIN: `1234` (cambiar en primer login)

- Usuario: `87654321` (DNI)
- PIN: `1234` (cambiar en primer login)

---

## 🎯 CARACTERÍSTICAS IMPLEMENTADAS

### Para TÉCNICOS (Usuarios de Campo):

✅ **Login Simplificado:**
- Usuario: DNI (8 dígitos numéricos)
- Contraseña: PIN de 4-6 dígitos
- Teclado numérico optimizado para móviles
- Sin caracteres especiales

✅ **Seguridad Balanceada:**
- Máximo 10 intentos fallidos
- Bloqueo temporal de 10 minutos
- Sesión de 2 horas de duración
- Advertencia 5 minutos antes de expirar

✅ **Validación de PIN:**
- No secuencias (1234, 4321)
- No todos iguales (0000, 1111)
- No igual al DNI
- No PINs comunes

### Para ADMINISTRADORES:

✅ **Seguridad Estricta:**
- Contraseñas complejas (12+ caracteres)
- Mayúsculas + minúsculas + números + símbolos
- Máximo 5 intentos fallidos
- Bloqueo temporal de 5 minutos
- Sesión de 30 minutos
- Cambio obligatorio cada 90 días

✅ **Herramientas de Administración:**
- Panel de cuentas bloqueadas
- Estadísticas de login (24h)
- Desbloqueo manual de cuentas
- Auditoría completa de intentos
- Top IPs con intentos fallidos
- Limpieza de registros antiguos

### Características Comunes:

✅ **Gestión de Sesiones:**
- Timeout por inactividad
- Modal de advertencia 5 min antes
- Botón "Continuar Trabajando"
- Actualización automática con actividad

✅ **Seguridad Adicional:**
- Tokens CSRF en formularios
- Registro de dispositivos autorizados
- Rate limiting por IP
- Bloqueo permanente (15+ intentos)
- Historial de contraseñas (últimas 5)
- Auditoría completa de acciones

✅ **Cambio de Contraseña/PIN:**
- Página dedicada con validación en tiempo real
- Forzado en primer login
- Indicador de fortaleza (admins)
- Requisitos claros y visibles

---

## 📊 CONFIGURACIÓN POR ROL

### Técnicos:
```php
SESSION_TIMEOUT: 7200 segundos (2 horas)
PIN_LENGTH: 4-6 dígitos
MAX_ATTEMPTS: 10 intentos
BLOCK_TIME: 600 segundos (10 min)
PASSWORD_EXPIRY: Sin expiración
```

### Administradores:
```php
SESSION_TIMEOUT: 1800 segundos (30 min)
PASSWORD_LENGTH: 12+ caracteres
MAX_ATTEMPTS: 5 intentos
BLOCK_TIME: 300 segundos (5 min)
PASSWORD_EXPIRY: 90 días
```

---

## 🗂️ ARCHIVOS CREADOS/MODIFICADOS

### Nuevos Archivos:
```
config/
├── SecurityConfig.php          (Constantes de seguridad)
├── PasswordPolicy.php          (Validación de contraseñas)
├── RateLimiter.php            (Control de intentos)
└── ping.php                    (Endpoint AJAX)

includes/
└── session_middleware.php      (Verificación automática)

pages/
├── cambiar_password.php        (Cambio de PIN/Password)
└── admin_desbloquear_cuentas.php (Panel de seguridad)
```

### Archivos Modificados:
```
database/schema.sql             (Nuevas tablas y campos)
config/database.php             (Funciones de seguridad)
login.php                       (UI mejorada + CSRF + bloqueos)
index.php                       (Middleware + módulo seguridad)
pages/formulario_retiro.php     (Middleware incluido)
pages/vista_previa.php          (Middleware incluido)
pages/buscar_oc.php             (Middleware incluido)
pages/importar_datos.php        (Middleware incluido)
```

---

## 🔍 NUEVAS TABLAS EN BASE DE DATOS

### `dispositivos_autorizados`
- Registro de dispositivos por usuario
- Fingerprint único del dispositivo
- Tipo (mobile/tablet/desktop)
- Último uso

### `login_attempts`
- Historial de intentos de login
- IP, user agent, éxito/fallo
- Usado para rate limiting

### `password_history`
- Historial de contraseñas (últimas 5)
- Prevención de reutilización

### Campos Nuevos en `usuarios`:
- `intentos_fallidos`
- `bloqueado_hasta`
- `ultimo_intento`
- `force_password_change`
- `password_changed_at`
- `session_timeout`
- `last_activity`

---

## 🚀 PRUEBAS RECOMENDADAS

### 1. Login Exitoso:
- ✅ Técnico con DNI + PIN
- ✅ Admin con username + password
- ✅ Redirección correcta

### 2. Validación de PIN:
- ❌ PIN con secuencia (1234)
- ❌ PIN todos iguales (0000)
- ❌ PIN igual al DNI
- ✅ PIN válido (5678)

### 3. Bloqueo por Intentos:
- Intentar 10 veces con PIN incorrecto (técnico)
- Verificar bloqueo temporal
- Esperar o desbloquear desde admin

### 4. Timeout de Sesión:
- Esperar 5 minutos sin actividad
- Verificar modal de advertencia
- Probar botón "Continuar Trabajando"

### 5. Cambio de Contraseña:
- Primer login (debe forzar cambio)
- Cambio voluntario desde perfil
- Validación de requisitos

### 6. Panel de Administración:
- Ver cuentas bloqueadas
- Desbloquear manualmente
- Ver estadísticas de login

---

## 📱 OPTIMIZACIONES MÓVILES

- Teclado numérico automático para DNI/PIN
- Inputs grandes (font-size: 1.1rem)
- Botones táctiles amplios
- Modal responsive
- Focus automático en campos

---

## 🔐 SEGURIDAD ADICIONAL

### Protección CSRF:
- Tokens en todos los formularios
- Validación en cada POST
- Regeneración automática

### Rate Limiting:
- 5 intentos por IP en 5 minutos
- Bloqueo temporal de IP agresivas

### Auditoría:
- Todos los intentos registrados
- IP, user agent, timestamp
- Exportable para análisis

---

## ⚠️ NOTAS IMPORTANTES

1. **Primer Login Obligatorio:**
   - Todos los usuarios deben cambiar su contraseña/PIN la primera vez

2. **Migración de Usuarios Existentes:**
   - Flag `force_password_change = TRUE` automático
   - Timeout por defecto según rol

3. **Limpieza Automática:**
   - Ejecutar `cleanupOldLoginAttempts()` periódicamente
   - O implementar cron job

4. **Backup:**
   - Respaldar base de datos antes de actualizar
   - Guardar configuración actual

---

## 📞 SOPORTE

Para problemas o dudas:
- Revisar logs en `error_log` de PHP
- Verificar consola de navegador (errores JS)
- Comprobar permisos de directorios

---

**Fecha de Implementación:** 31 de octubre de 2025  
**Versión:** 2.0 - Sistema de Seguridad Mejorado  
**Estado:** ✅ Completo y Listo para Producción
