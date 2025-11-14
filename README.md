# 🚀 GASELAG - Sistema de Retiro de Medidores

Sistema web profesional para la gestión y registro de retiros de medidores de agua.

## 📋 Características

- ✅ **Sistema de Autenticación** con roles de usuario (Admin/Técnico)
- ✅ **Aislamiento de datos** - Cada técnico ve solo sus registros
- ✅ **Validación anti-duplicación** - Una OC solo se registra una vez
- ✅ **Tipos de Imposibilidad** - Catálogo estructurado de motivos de no retiro
- ✅ **Gestión de Imposibilidad** - Admin puede agregar/editar tipos de casos
- ✅ **Control de evidencia fotográfica** - Sistema de 6 horas para adjuntar evidencia
- ✅ **Sistema de sanciones** - Control de cumplimiento de evidencia
- ✅ **Control de acceso** basado en permisos por rol
- ✅ **Sistema de auditoría completo** - Trazabilidad total de acciones
- ✅ **Reasignación de registros** - Admin puede cambiar técnico responsable
- ✅ **Reapertura de OCs** - Admin puede liberar OCs para re-registro
- ✅ **Importación masiva** de órdenes de servicio desde Excel *(Solo Admin)*
- ✅ **Búsqueda rápida** por código OC con validación previa
- ✅ **Formularios inteligentes** que se adaptan según el estado del retiro
- ✅ **Gestión de fotos** con nomenclatura automática
- ✅ **Consulta avanzada** con múltiples filtros
- ✅ **Exportación a CSV** compatible con Excel *(Solo Admin)*
- ✅ **Gestión de usuarios** para administradores *(Solo Admin)*
- ✅ **Gestión de retiros** con control total para administradores *(Solo Admin)*
- ✅ **Interfaz moderna** con Bootstrap 5
- ✅ **Responsive** (funciona en tablets y móviles)

---

## 🛠️ Requisitos del Sistema

- **PHP** 7.4 o superior
- **MySQL** 5.7 o superior
- **Servidor web** (Apache/Nginx)
- **XAMPP** recomendado para Windows

---

## 📥 Instalación Rápida

### 1. Iniciar Servicios
```
1. Abre XAMPP Control Panel
2. Click "Start" en Apache ✅
3. Click "Start" en MySQL ✅
```

### 2. Acceso Directo
```
Opción A: Doble click en INICIAR_AQUI.html
Opción B: http://localhost/form%20gaselag%20retiros/instalar.php
```

### 3. Seguir el Instalador
El instalador automático:
- ✅ Crea la base de datos `gaselag_retiros`
- ✅ Crea las 4 tablas necesarias (incluyendo usuarios)
- ✅ Importa datos de ejemplo (opcional)
- ✅ Crea usuarios por defecto para el sistema de autenticación

### 4. Sistema de Autenticación
Después de la instalación, el sistema crea automáticamente:

**Usuarios por defecto:**
- **Administrador:** `admin` / `password`
- **Técnico 1:** `tecnico1` / `password`
- **Técnico 2:** `tecnico2` / `password`

**Para instalaciones existentes:**

**Paso 1 - Autenticación y Aislamiento:**
```
Visita: http://localhost/form%20gaselag%20retiros/actualizar_aislamiento.php
```

**Paso 2 - Tipos de Imposibilidad:**
```
Visita: http://localhost/form%20gaselag%20retiros/actualizar_imposibilidad.php
```

**O desde la página de inicio:**
```
http://localhost/form%20gaselag%20retiros/INICIAR_AQUI.html
→ Click en "Actualizar Sistema" (autenticación)
→ Click en "Tipos de Imposibilidad" (catálogo de motivos)
```

**Acceso al sistema:**
```
URL de login: http://localhost/form%20gaselag%20retiros/login.php
```

---

## ⚙️ Configuración

### Puerto MySQL Personalizado
Si tu MySQL usa un puerto diferente al 3306:

1. Edita `config/database.php`
2. Modifica la línea:
   ```php
   define('DB_PORT', '3307'); // Tu puerto
   ```

### Credenciales de Base de Datos
Por defecto usa:
- **Host:** localhost
- **Puerto:** 3307
- **Usuario:** root
- **Contraseña:** (vacía)
- **Base de datos:** gaselag_retiros

---

## 👥 Roles y Permisos

### 🔐 Administrador
**Acceso completo a todas las funciones:**
- ✅ Importar datos desde Excel
- ✅ **Gestión de retiros** (ver todos, reasignar, reabrir)
- ✅ **Gestión de tipos de imposibilidad** (crear, editar, activar/desactivar)
- ✅ **Gestión de evidencias** (control de evidencia fotográfica y sanciones)
- ✅ **Sistema de auditoría** (ver todas las acciones)
- ✅ Consultar todos los registros (de todos los técnicos)
- ✅ Ver casos críticos (sin evidencia fotográfica)
- ✅ Exportar datos a Excel
- ✅ **Gestión de usuarios** (crear, activar/desactivar, eliminar)
- ✅ **Reasignación de registros** entre técnicos
- ✅ **Reapertura de OCs** para nuevo registro
- ✅ **Aplicar sanciones** por incumplimiento de evidencia

### 👨‍🔧 Técnico
**Acceso limitado con aislamiento de datos:**
- ❌ Importar datos (bloqueado)
- ✅ **Solo ve sus propios registros** (aislamiento de datos)
- ✅ **Validación anti-duplicación** (no puede registrar OC ya procesada)
- ✅ **Auto-asignación** (el sistema asigna automáticamente el usuario)
- ✅ **Control de evidencia fotográfica** (ventana de 6 horas para adjuntar)
- ✅ **Editar sus propios registros** (dentro del tiempo límite)
- ✅ Registrar retiros de medidores
- ✅ Consultar **solo sus propios registros**
- ❌ Ver casos críticos (bloqueado)
- ❌ Exportar datos (bloqueado)
- ❌ Gestión de usuarios (bloqueado)
- ❌ Ver registros de otros técnicos (bloqueado)
- ❌ Aplicar sanciones (bloqueado)

---

## 📖 Guía de Uso

### 🔐 1. Acceso al Sistema
```
1. Abrir: http://localhost/form%20gaselag%20retiros/login.php
2. Ingresar usuario y contraseña
3. Seleccionar el rol correspondiente (Admin/Técnico)
4. Click "Iniciar Sesión"
```

### 📊 2. Importar Datos *(Solo Administrador)*
```
1. Menu → "Importar Datos"
2. Abrir Excel con las órdenes de servicio
3. Seleccionar SOLO las filas de datos (sin encabezados)
4. Copiar (Ctrl+C)
5. Pegar en el formulario
6. Click "Importar Datos"
```

**Importante:** No incluir la fila de encabezados, solo los datos.

### ⚙️ 3. Gestión de Retiros *(Solo Administrador)*
```
1. Menu → "Gestión de Retiros"
2. Ver todos los registros de todos los técnicos
3. Filtrar por OC, técnico, estado o fecha
4. Reasignar registros a otros técnicos si es necesario
5. Reabrir OCs para nuevo registro si hay errores
6. Ver historial de auditoría completo
```

**Funciones disponibles:**
- **Reasignación:** Cambiar técnico responsable de una OC
- **Reapertura:** Liberar OC para que otro técnico la registre
- **Auditoría:** Ver quién accedió, cuándo y qué hizo
- **Filtros avanzados:** Buscar por múltiples criterios

### 🔧 4. Registrar Retiro *(Todos los usuarios)*
```
1. Menu → "Registrar Retiro"
2. Buscar por código OC (ej: OC-00001)
3. Agregar las OCs necesarias
4. Click "Continuar con Vista Previa"
5. Completar formulario para cada OC:
   - Si SÍ se retiró: Información del medidor y caja
   - Si NO se retiró: Seleccionar tipo de imposibilidad + detalles + observaciones
6. Click "Guardar"
```

**Tipos de imposibilidad disponibles:**
- 🚪 **Acceso:** Sin acceso, interior, no localizado, obra
- ⚡ **Medidor:** Niple, no coincide, sin contómetro, dañado
- 👤 **Cliente:** Oposición, ausente
- ⚠️ **Seguridad:** Zona peligrosa
- 📋 **Otros:** Cualquier motivo no contemplado

### 🔍 5. Consultar Registros *(Todos los usuarios)*
```
1. Menu → "Consultar Registros"
2. Aplicar filtros opcionales
3. Click ícono 👁️ para ver detalles
```

**Para Técnicos:** Solo ven sus propios registros
**Para Administradores:** Ven todos los registros de todos los técnicos

### ⚙️ 6. Gestión de Retiros *(Solo Administrador)*
```
1. Menu → "Gestión de Retiros"
2. Ver todos los registros de todos los técnicos
3. Filtrar por OC, técnico, estado o fecha
4. Reasignar registros a otros técnicos si es necesario
5. Reabrir OCs para nuevo registro si hay errores
6. Ver historial de auditoría completo
```

### ⚠️ 7. Tipos de Imposibilidad *(Solo Administrador)*
```
1. Menu → "Tipos de Imposibilidad"
2. Ver todos los tipos de imposibilidad disponibles
3. Crear nuevos tipos según necesidades operativas
4. Editar descripciones y categorías
5. Activar/desactivar tipos según uso
6. Ver estadísticas de uso de cada tipo
```

### ⚙️ 8. Gestión de Evidencias *(Solo Administrador)*
```
1. Menu → "Gestión de Evidencias"
2. Ver registros pendientes de evidencia fotográfica
3. Aplicar sanciones por incumplimiento de evidencia
4. Monitorear cumplimiento de evidencia por técnico
5. Ver estadísticas de evidencia completada vs pendiente
```

### ⚠️ 9. Casos Críticos *(Solo Administrador)*
```
1. Menu → "Casos Críticos"
2. Identificar registros no retirados sin evidencia fotográfica
3. Gestionar seguimiento de casos problemáticos
```

### 👥 10. Gestión de Usuarios *(Solo Administrador)*
```
1. Menu → "Gestión de Usuarios"
2. Crear nuevos usuarios (Admin/Técnico)
3. Activar/desactivar cuentas
4. Asignar roles y permisos
```

### 📤 11. Exportar Datos *(Solo Administrador)*
```
1. Menu → "Exportar Datos"
2. Aplicar filtros según necesites
3. Descargar reporte en formato Excel
```

---

## 📊 Estructura de Base de Datos

### Tabla: `ordenes_servicio`
Almacena toda la información importada del Excel (33 campos).

### Tabla: `retiros_medidores` *(Actualizada)*
Registra cada retiro realizado:
- Estado del retiro (SI/NO)
- Lecturas del medidor
- Reportes visuales
- Información del filtro
- Observaciones
- Fotos de imposibilidad

**Nuevos campos para aislamiento de datos:**
- `usuario_id` - Técnico que registró el retiro
- `estado_registro` - activo/reabierto/reasignado
- `usuario_reasignado_por` - Admin que hizo reasignación
- `fecha_reasignacion` - Cuándo se reasignó
- `fecha_asignacion` - Cuándo se asignó originalmente

**Nuevos campos para control de evidencia fotográfica:**
- `tipo_imposibilidad_id` - Referencia al tipo de imposibilidad
- `detalles_imposibilidad` - Detalles adicionales del motivo
- `evidencia_obligatoria` - SI/NO si requiere evidencia fotográfica
- `fecha_limite_evidencia` - Fecha límite para adjuntar evidencia (6 horas)
- `evidencia_completa` - SI/NO si ya se adjuntó la evidencia
- `sancion_aplicada` - SI/NO si se aplicó sanción por incumplimiento
- `motivo_sancion` - Razón de la sanción aplicada
- `fecha_sancion` - Cuándo se aplicó la sanción
- `admin_sancion_id` - Admin que aplicó la sanción

### Tabla: `sesiones_oc`
Maneja las OCs seleccionadas durante la sesión de trabajo.

### Tabla: `usuarios` *(Nueva)*
Sistema de autenticación y control de roles:

- Credenciales de usuario (username/password encriptada)
- Roles (admin/user) con diferentes permisos
- Estado del usuario (activo/inactivo)
- Información de contacto y timestamps
- Control de último acceso

### Tabla: `auditoria_retiros` *(Nueva)*
Sistema completo de auditoría y trazabilidad:

- `retiro_id` - Registro relacionado (puede ser NULL)
- `usuario_id` - Usuario que realizó la acción
- `accion` - Tipo de acción (login, registro, consulta, etc.)
- `detalles` - Información adicional de la acción
- `orden_servicio` - OC involucrada (si aplica)
- `ip_address` - IP desde donde se realizó la acción
- `user_agent` - Información del navegador
- `fecha_accion` - Timestamp exacto de la acción

**Tipos de acciones registradas:**
- **Login/Logout:** Control de sesiones
- **Búsqueda:** Intentos de acceso a OCs
- **Registro:** Registros exitosos de retiros
- **Consulta:** Visualización de registros
- **Reasignación:** Cambios de técnico responsable
- **Reapertura:** Liberación de OCs para re-registro

### Tabla: `tipos_imposibilidad` *(Nueva)*
Catálogo de motivos de imposibilidad de retiro:

- `codigo` - Identificador único del tipo
- `descripcion` - Descripción del motivo de imposibilidad
- `categoria` - Clasificación (acceso, medidor, cliente, seguridad, otros)
- `activo` - Estado del tipo (disponible o no para nuevos registros)

**Tipos predefinidos incluidos:**
- **Acceso:** Sin acceso, interior, no localizado, obra
- **Medidor:** Niple, no coincide, sin contómetro, dañado
- **Cliente:** Oposición, ausente
- **Seguridad:** Zona peligrosa
- **Otros:** Motivos no categorizados

---

## 📸 Nomenclatura de Fotos

### Fotos de Imposibilidad (Inmediatas)
Las fotos de imposibilidad se guardan con el formato:
```
OC-xxxxx_NumSuministro_FechaHora.extension
```

**Ejemplo:**
```
OC-00001_5367165_20251025_143022.jpg
```

### Evidencias Fotográficas (Posteriores - 6 horas)
Las evidencias adjuntadas posteriormente se guardan con el formato:
```
evidencia_RetiroID_FechaHora_Hash.extension
```

**Ejemplo:**
```
evidencia_123_20251025_143022_a1b2c3d4.jpg
```

Esto permite:
- ✅ Identificación única por OC y suministro
- ✅ Trazabilidad completa
- ✅ Ordenamiento cronológico
- ✅ Búsqueda rápida y eficiente

---

## 🔧 Solución de Problemas

### Error de Conexión
```
Solución:
1. Verifica que MySQL esté corriendo (verde en XAMPP)
2. Revisa config/database.php
3. Confirma que existe la BD "gaselag_retiros"
```

### Página en Blanco
```
Solución:
1. Activa display_errors en php.ini
2. Reinicia Apache
3. Revisa el error específico
```

### No se Pueden Subir Fotos
```
Solución:
1. Verifica permisos de carpeta uploads/
2. En Windows: Propiedades → Desmarcar "Solo lectura"
3. En php.ini: upload_max_filesize = 10M
```

### Error al Importar Excel
```
Solución:
1. Copia SOLO las filas de datos (sin encabezados)
2. Copia desde Excel (mantiene tabuladores)
3. Verifica que tengas las 33 columnas
```

---

## 📁 Estructura del Proyecto

```
form gaselag retiros/
├── config/                    # Configuración del sistema
│   ├── database.php          # Configuración de BD
│   ├── AppConfig.php         # Configuración general
│   ├── SecurityConfig.php    # Configuración de seguridad
│   └── ...                   # Otros archivos de configuración
├── database/                  # Scripts de base de datos
│   ├── schema.sql            # Script de creación
│   └── migration_*.sql       # Migraciones
├── pages/                     # Páginas del sistema
│   ├── buscar_oc.php         # Búsqueda de OCs
│   ├── consultar_retiros.php # Consulta y filtros
│   ├── detalle_retiro.php    # Vista detallada
│   ├── exportar_excel.php    # Exportación CSV
│   ├── formulario_retiro.php # Registro de retiro
│   ├── gestion_*.php         # Módulos de gestión (admin)
│   ├── importar_datos.php    # Importación masiva
│   └── ...                   # Otras páginas
├── includes/                  # Componentes compartidos
│   ├── header.php            # Encabezado común
│   ├── footer.php            # Pie de página común
│   └── session_middleware.php # Middleware de sesión
├── uploads/                   # Archivos subidos
│   ├── perfiles/             # Fotos de perfil
│   └── *.png, *.jpg          # Evidencias de imposibilidad
├── docs/                      # 📚 Documentación técnica
│   ├── CHANGELOG.md          # Registro de cambios
│   ├── GUIA_*.md             # Guías técnicas
│   └── ...                   # Otra documentación
├── tools/                     # 🔧 Herramientas y scripts
│   ├── verificar_instalacion.php  # Verificador
│   ├── limpiar_bloqueos.php  # Limpieza de bloqueos
│   ├── diagnostico_*.php     # Scripts de diagnóstico
│   └── ...                   # Otras herramientas
├── templates/                 # 📄 Plantillas de importación
│   ├── plantilla_importacion_gaselag.csv
│   └── data ejemplo programa.xlsm
├── backups/                   # 💾 Respaldos del sistema
│   └── backups_actualizacion_*/
├── index.php                  # Página principal
├── login.php                  # Página de login
├── logout.php                 # Cerrar sesión
├── instalar.php              # Instalador automático
├── INICIAR_AQUI.html         # Acceso rápido
└── README.md                  # Esta documentación
```

---

## 📂 Organización de Carpetas

El proyecto está organizado de manera óptima para facilitar el mantenimiento y escalabilidad:

### 📚 `docs/` - Documentación
Toda la documentación técnica, guías, changelogs y diagnósticos del sistema.
- Historial de actualizaciones y correcciones
- Guías técnicas de funcionalidades
- Documentación de arquitectura y flujos
- Ver [docs/README.md](docs/README.md) para más detalles

### 🔧 `tools/` - Herramientas
Scripts de mantenimiento, diagnóstico y administración del sistema.
- Verificación de instalación
- Scripts de limpieza y optimización
- Herramientas de diagnóstico
- Generadores de utilidades
- Ver [tools/README.md](tools/README.md) para más detalles

### 📄 `templates/` - Plantillas
Archivos de ejemplo y plantillas para importación de datos.
- Plantillas CSV para importación
- Archivos Excel de ejemplo
- Ver [templates/README.md](templates/README.md) para más detalles

### 💾 `backups/` - Respaldos
Carpeta para almacenar respaldos del sistema y actualizaciones anteriores.
- Backups de base de datos
- Backups de archivos
- Versiones anteriores del sistema

### 📦 Otras Carpetas Principales
- **`config/`** - Archivos de configuración del sistema
- **`database/`** - Scripts SQL y migraciones
- **`pages/`** - Páginas funcionales del sistema
- **`includes/`** - Componentes compartidos (header, footer, middleware)
- **`uploads/`** - Archivos subidos por usuarios (evidencias, perfiles)

---

## 🌐 URLs del Sistema

### Desarrollo Local
```
Login:         http://localhost/form%20gaselag%20retiros/login.php
Principal:     http://localhost/form%20gaselag%20retiros/
Instalador:    http://localhost/form%20gaselag%20retiros/instalar.php
Verificador:   http://localhost/form%20gaselag%20retiros/tools/verificar_instalacion.php
Logout:        http://localhost/form%20gaselag%20retiros/logout.php
```

### URLs de Gestión *(Solo Administrador)*
```
Gestión Retiros:      http://localhost/form%20gaselag%20retiros/pages/gestion_retiros.php
Tipos Imposibilidad:  http://localhost/form%20gaselag%20retiros/pages/gestion_imposibilidad.php
Gestión Evidencias:   http://localhost/form%20gaselag%20retiros/pages/gestion_evidencias.php
Usuarios:             http://localhost/form%20gaselag%20retiros/pages/gestion_usuarios.php
Casos Críticos:       http://localhost/form%20gaselag%20retiros/pages/reporte_imposibilidad.php
Exportar:             http://localhost/form%20gaselag%20retiros/pages/exportar_excel.php
```

### URLs de Actualización *(Para instalaciones existentes)*
```
Actualizar Aislamiento: http://localhost/form%20gaselag%20retiros/actualizar_aislamiento.php
Actualizar Imposibilidad: http://localhost/form%20gaselag%20retiros/actualizar_imposibilidad.php
Página de Inicio:     http://localhost/form%20gaselag%20retiros/INICIAR_AQUI.html
```

### URLs Operativas *(Todos los usuarios)*
```
Buscar OC:     http://localhost/form%20gaselag%20retiros/pages/buscar_oc.php
Consultar:     http://localhost/form%20gaselag%20retiros/pages/consultar_retiros.php
Importar:      http://localhost/form%20gaselag%20retiros/pages/importar_datos.php *(Solo Admin)*
```

---

## 🎨 Tecnologías Utilizadas

- **Backend:** PHP 7.4+ con PDO
- **Base de datos:** MySQL 5.7+
- **Frontend:** Bootstrap 5.3
- **Iconos:** Bootstrap Icons
- **Diseño:** CSS3 responsivo

---

## 📄 Formato de Datos de Excel

### Columnas Requeridas (33 en total):
1. Item
2. Orden de servicio
3. Fecha OS
4. Cantidad de medidores
5. Tipo de Servicio
6. Programación Dia Retiro
7. Programación Hora Retiro
8. Programación dia VP
9. Programación Hora VP
10. CODIGO SEGURIDAD
11. Cliente
12. Centro de Servicio
13. Remesa
14. Usuario - Reclamante
15. Dirección
16. CUS
17. CUP
18. N° de Suministro
19. N° de Serie del Medidor
20. Marca del medidor
21. Modelo del medidor
22. Año de Fabricacion
23. Fabricante
24. Procedencia
25. Tipo Medidor
26. Diámetro Nominal (mm)
27. Q3 (m3/h)
28. Alcance
29. PMA (bar)
30. TMA (°C)
31. Clase de sensibilidad
32. Certificado de aprobación
33. N° de Certificado

**Importante:** Los datos deben estar separados por tabuladores (copiar desde Excel).

---

## ⚠️ Consideraciones de Seguridad

Este sistema está diseñado para **uso en red local**. Para producción:

- ⚠️ Implementar autenticación de usuarios
- ⚠️ Agregar validación adicional de datos
- ⚠️ Implementar protección CSRF
- ⚠️ Sanitizar archivos subidos
- ⚠️ Usar HTTPS

---

## 💾 Respaldo de Datos

### Exportar Base de Datos
```sql
mysqldump -u root -p gaselag_retiros > backups/backup_FECHA.sql
```

### Restaurar Base de Datos
```sql
mysql -u root -p gaselag_retiros < backups/backup_FECHA.sql
```

### Respaldo de Fotos
Copiar la carpeta `uploads/` regularmente a `backups/uploads_FECHA/`.

**Nota:** La carpeta `backups/` está lista para almacenar todos tus respaldos.

---

## 📞 Soporte

Para problemas técnicos o consultas:
1. Revisa esta documentación
2. Ejecuta `verificar_instalacion.php`
3. Revisa los logs de Apache/PHP
4. Contacta al administrador del sistema

---

## 📝 Licencia

Sistema desarrollado para uso interno de GASELAG.

---

## 🔄 Versión

**Versión:** 1.0.0  
**Fecha:** Octubre 2025  
**Estado:** Producción

---

## ✅ Checklist de Instalación

- [ ] XAMPP instalado y funcionando
- [ ] Apache corriendo (puerto 80)
- [ ] MySQL corriendo (puerto 3307)
- [ ] Base de datos creada
- [ ] Tablas creadas
- [ ] Datos de ejemplo importados
- [ ] Permisos en carpeta uploads/
- [ ] Sistema accesible vía localhost

---

**¡Listo para usar! 🎉**

Para comenzar, abre `INICIAR_AQUI.html` o ve a:
`http://localhost/form%20gaselag%20retiros/instalar.php`
