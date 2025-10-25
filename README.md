# 🚀 GASELAG - Sistema de Retiro de Medidores

Sistema web profesional para la gestión y registro de retiros de medidores de agua.

## 📋 Características

- ✅ **Importación masiva** de órdenes de servicio desde Excel
- ✅ **Búsqueda rápida** por código OC
- ✅ **Formularios inteligentes** que se adaptan según el estado del retiro
- ✅ **Gestión de fotos** con nomenclatura automática
- ✅ **Consulta avanzada** con múltiples filtros
- ✅ **Exportación a CSV** compatible con Excel
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
- ✅ Crea las 3 tablas necesarias
- ✅ Importa datos de ejemplo (opcional)

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

## 📖 Guía de Uso

### 1. Importar Datos
```
1. Menu → "Importar Datos"
2. Abrir Excel con las órdenes de servicio
3. Seleccionar SOLO las filas de datos (sin encabezados)
4. Copiar (Ctrl+C)
5. Pegar en el formulario
6. Click "Importar Datos"
```

**Importante:** No incluir la fila de encabezados, solo los datos.

### 2. Registrar Retiro
```
1. Menu → "Registrar Retiro"
2. Buscar por código OC (ej: OC-00001)
3. Agregar las OCs necesarias
4. Click "Continuar con Vista Previa"
5. Completar formulario para cada OC:
   - Si NO se retiró: Solo observación + foto
   - Si SÍ se retiró: Todos los campos del formulario
6. Click "Guardar"
```

### 3. Consultar Registros
```
1. Menu → "Consultar Registros"
2. Aplicar filtros:
   - Por código OC
   - Por fecha de retiro
   - Por estado (retirado/no retirado)
3. Click ícono 👁️ para ver detalles
4. Click "Exportar a Excel" para descargar CSV
```

---

## 📊 Estructura de Base de Datos

### Tabla: `ordenes_servicio`
Almacena toda la información importada del Excel (33 campos).

### Tabla: `retiros_medidores`
Registra cada retiro realizado:
- Estado del retiro (SI/NO)
- Lecturas del medidor
- Reportes visuales
- Información del filtro
- Observaciones
- Fotos de imposibilidad

### Tabla: `sesiones_oc`
Maneja las OCs seleccionadas durante la sesión de trabajo.

---

## 📸 Nomenclatura de Fotos

Las fotos de imposibilidad se guardan con el formato:
```
OC-xxxxx_NumSuministro_NumSerie_FechaHora.extension
```

**Ejemplo:**
```
OC-00001_5367165_EA22282911_20251025_143022.jpg
```

Esto permite:
- ✅ Identificación única
- ✅ Trazabilidad completa
- ✅ Ordenamiento por fecha
- ✅ Búsqueda fácil

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
├── config/
│   └── database.php          # Configuración de BD
├── database/
│   └── schema.sql             # Script de creación
├── pages/
│   ├── buscar_oc.php         # Búsqueda de OCs
│   ├── consultar_retiros.php # Consulta y filtros
│   ├── detalle_retiro.php    # Vista detallada
│   ├── exportar_excel.php    # Exportación CSV
│   ├── finalizar.php         # Página de éxito
│   ├── formulario_retiro.php # Registro de retiro
│   ├── importar_datos.php    # Importación masiva
│   └── vista_previa.php      # Preview de selección
├── uploads/                   # Fotos de imposibilidad
├── datos_ejemplo.txt          # Datos de prueba
├── index.php                  # Página principal
├── instalar.php              # Instalador automático
├── INICIAR_AQUI.html         # Acceso rápido
├── verificar_instalacion.php # Verificador
└── README.md                  # Esta documentación
```

---

## 🌐 URLs del Sistema

### Desarrollo Local
```
Principal:     http://localhost/form%20gaselag%20retiros/
Instalador:    http://localhost/form%20gaselag%20retiros/instalar.php
Verificador:   http://localhost/form%20gaselag%20retiros/verificar_instalacion.php
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
mysqldump -u root -p gaselag_retiros > backup.sql
```

### Restaurar Base de Datos
```sql
mysql -u root -p gaselag_retiros < backup.sql
```

### Respaldo de Fotos
Copiar la carpeta `uploads/` regularmente.

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
