# 📊 Estructura de la Base de Datos - GASELAG Retiros

## 🗄️ Resumen General

Base de datos: `gaselag_retiros`
Charset: `utf8mb4_unicode_ci`

## 📋 Tablas del Sistema

### 1️⃣ **Tablas Principales** (schema.sql)

| Tabla | Descripción | Registros |
|-------|-------------|-----------|
| `usuarios` | Usuarios del sistema (admins y técnicos) | 3 por defecto |
| `ordenes_servicio` | Órdenes de servicio importadas desde Excel | Variable |
| `retiros_medidores` | Registros de retiros realizados por técnicos | Variable |
| `tipos_imposibilidad` | Catálogo de motivos de imposibilidad | 11 predefinidos |
| `sesiones_oc` | Sesiones temporales de OCs seleccionadas | Temporal |

### 2️⃣ **Tablas de Seguridad y Auditoría** (schema.sql)

| Tabla | Descripción |
|-------|-------------|
| `auditoria_retiros` | Log de todas las acciones del sistema |
| `dispositivos_autorizados` | Dispositivos autorizados para login |
| `login_attempts` | Intentos de login (rate limiting) |
| `password_history` | Historial de contraseñas (prevenir reutilización) |

### 3️⃣ **Sistema de Asignaciones** (migration_asignaciones_oc.sql)

| Tabla | Descripción |
|-------|-------------|
| `asignaciones_oc` | Asignaciones de OCs a técnicos específicos |
| `asignaciones_masivas_log` | Log de asignaciones masivas (auditoría) |

### 4️⃣ **Vistas**

| Vista | Descripción |
|-------|-------------|
| `v_asignaciones_pendientes` | Vista optimizada de OCs pendientes por técnico |

## 🔧 Instalación

### Opción 1: Instalador Automático (Recomendado)
```
http://localhost/form%20gaselag%20retiros/instalar.php
```

El instalador ejecuta automáticamente:
1. ✅ `database/schema.sql` - Tablas principales
2. ✅ `database/migration_usuarios_perfil.sql` - Perfiles de usuario
3. ✅ `database/migration_asignaciones_oc.sql` - Sistema de asignaciones

### Opción 2: Manual

```sql
-- 1. Crear base de datos
CREATE DATABASE gaselag_retiros CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gaselag_retiros;

-- 2. Ejecutar migraciones en orden
SOURCE database/schema.sql;
SOURCE database/migration_usuarios_perfil.sql;
SOURCE database/migration_asignaciones_oc.sql;
```

## 👥 Usuarios Predeterminados

| Usuario | Contraseña | Rol | Timeout |
|---------|------------|-----|---------|
| `admin` | `password` | admin | 30 min |
| `12345678` | `password` | user (técnico) | 2 horas |
| `87654321` | `password` | user (técnico) | 2 horas |

⚠️ **IMPORTANTE:** Todos deben cambiar su contraseña en el primer login.

## 🔗 Relaciones Principales

```
usuarios (1) ──< retiros_medidores (N)
usuarios (1) ──< asignaciones_oc (N)
ordenes_servicio (1) ──< retiros_medidores (N)
ordenes_servicio (1) ──< asignaciones_oc (N)
tipos_imposibilidad (1) ──< retiros_medidores (N)
```

## 📝 Notas

- **Foreign Keys:** Todas las tablas usan `ON DELETE CASCADE` o `ON DELETE SET NULL` según el caso
- **Timestamps:** Todas las tablas tienen `created_at` y `updated_at` automáticos
- **Índices:** Optimizados para búsquedas comunes (orden_servicio, usuario_id, fechas)
- **Triggers:** `trg_after_retiro_insert` actualiza automáticamente el estado de asignaciones

## 🔍 Verificación

Para verificar que todas las tablas existen:

```sql
USE gaselag_retiros;
SHOW TABLES;
```

Deberías ver **11 tablas** en total.

## 🆘 Solución de Problemas

### Error: "Table doesn't exist"
```bash
# Ejecutar desde terminal
cd "c:\xampp\htdocs\form gaselag retiros"
php ejecutar_migracion_asignaciones.php
```

### Reinstalar desde cero
```
http://localhost/form%20gaselag%20retiros/instalar.php?paso=2
```

Esto eliminará la base de datos existente y la recreará con todas las migraciones.

