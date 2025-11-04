# 📱 Header y Footer Profesional - Sistema GASELAG

## 📋 Descripción

Se han creado componentes de header y footer profesionales y completamente **responsive** para el sistema GASELAG, diseñados con **Bootstrap 5 puro** sin CSS personalizado complejo.

---

## 📁 Archivos Creados

| Archivo | Ubicación | Propósito |
|---------|-----------|-----------|
| `header.php` | `/includes/header.php` | Navbar profesional con menús responsive |
| `footer.php` | `/includes/footer.php` | Footer informativo con enlaces y copyright |
| `ejemplo_uso_header_footer.php` | `/includes/ejemplo_uso_header_footer.php` | Plantilla de ejemplo |

---

## 🎨 Características del Header

### Navbar Profesional
✅ **Logo y Branding**: Logo con icono y nombre GASELAG  
✅ **Menú Principal**: Enlaces a todas las secciones del sistema  
✅ **Menú Admin**: Dropdown exclusivo para administradores  
✅ **Menú Operaciones**: Acceso rápido a funciones principales  
✅ **Usuario**: Dropdown con información del usuario actual  
✅ **Responsive**: Menú hamburguesa en móvil  
✅ **Sticky**: Navbar fija al hacer scroll  
✅ **Gradiente**: Colores corporativos GASELAG  

### Menús Incluidos

#### 🏠 Menú Principal
- Inicio
- Administración (solo admin)
  - Gestión de Usuarios
  - Gestión de Retiros
  - Tipos de Imposibilidad
  - Gestión de Evidencias
- Operaciones
  - Seleccionar OCs
  - Registrar Retiro
  - Consultar Retiros
  - Importar Datos

#### 👤 Menú Usuario
- Información del usuario (nombre, username, rol)
- Cambiar Contraseña/PIN
- Cerrar Sesión

---

## 🎨 Características del Footer

### Secciones
✅ **Información de la Empresa**: Logo y descripción  
✅ **Enlaces Rápidos**: Acceso directo a páginas principales  
✅ **Soporte**: Información de contacto  
✅ **Copyright**: Año automático y versión del sistema  
✅ **Usuario Activo**: Muestra quién está logueado  

### Responsive
- **Desktop**: 3 columnas con toda la información
- **Tablet**: 2 columnas ajustadas
- **Móvil**: 1 columna apilada, textos optimizados

---

## 📱 Diseño Responsive

### 🖥️ Desktop (≥992px)
```
┌─────────────────────────────────────────────────────────────┐
│ [Logo] GASELAG  Inicio  Admin▾  Operaciones▾    👤Usuario▾ │
└─────────────────────────────────────────────────────────────┘
│                     CONTENIDO DE LA PÁGINA                   │
┌─────────────────────────────────────────────────────────────┐
│  [GASELAG]          Enlaces Rápidos        Soporte          │
│  Descripción        • Inicio               📞 Tel           │
│  📍 Guatemala       • Consultas            ✉️ Email         │
│                     • Usuarios             🕐 Horario        │
│                                            👤 Usuario: admin │
├─────────────────────────────────────────────────────────────┤
│  © 2025 GASELAG                    Versión 2.0 | Sistema... │
└─────────────────────────────────────────────────────────────┘
```

### 📱 Tablet (768px-991px)
```
┌───────────────────────────────────────────────┐
│ [Logo] GASELAG  Inicio  Admin▾  Oper▾  👤▾   │
└───────────────────────────────────────────────┘
│           CONTENIDO DE LA PÁGINA              │
┌───────────────────────────────────────────────┐
│  [GASELAG]              Enlaces | Soporte     │
│  Descripción            • Inicio  📞 Tel      │
│  📍 Guatemala           • Consultas ✉️ Email  │
├───────────────────────────────────────────────┤
│  © 2025 GASELAG      Versión 2.0 | Sistema   │
└───────────────────────────────────────────────┘
```

### 📱 Móvil (≤767px)
```
┌─────────────────────────────────┐
│ [Logo]  GASELAG          ☰ Menú │
└─────────────────────────────────┘
│     CONTENIDO DE LA PÁGINA      │
┌─────────────────────────────────┐
│  [GASELAG]                      │
│  Sistema de Gestión...          │
│  📍 Guatemala                   │
│                                 │
│  Enlaces Rápidos                │
│  • Inicio                       │
│  • Consultas                    │
│                                 │
│  Soporte                        │
│  📞 Tel                         │
│  ✉️ Email                       │
│  🕐 L-V: 8AM-5PM                │
├─────────────────────────────────┤
│  © 2025 GASELAG                 │
│  Versión 2.0                    │
│  👤 Juan Pérez | Técnico        │
└─────────────────────────────────┘
```

---

## 🚀 Cómo Usar

### Estructura Básica de Página

```php
<?php
// 1. SIEMPRE PRIMERO: Base de datos
require_once '../config/database.php';

// 2. Verificar permisos
requireRole(['admin', 'user']); // O solo ['admin']

// 3. OPCIONAL: Definir título personalizado
$pageTitle = 'Mi Página - Sistema GASELAG';

// 4. Incluir header
require_once '../includes/header.php';
?>

<!-- TU CONTENIDO AQUÍ -->
<div class="container my-4">
    <h1>Mi Contenido</h1>
    <!-- ... -->
</div>

<?php
// 5. SIEMPRE AL FINAL: Incluir footer
require_once '../includes/footer.php';
?>
```

### Rutas Según Ubicación

#### Desde raíz del proyecto (`index.php`)
```php
require_once 'config/database.php';
require_once 'includes/header.php';
// ... contenido ...
require_once 'includes/footer.php';
```

#### Desde carpeta `/pages/`
```php
require_once '../config/database.php';
require_once '../includes/header.php';
// ... contenido ...
require_once '../includes/footer.php';
```

---

## 🎯 Características Técnicas

### Bootstrap 5 Puro
✅ **Navbar**: `navbar`, `navbar-expand-lg`, `navbar-dark`  
✅ **Grid**: `container-fluid`, `row`, `col-*`  
✅ **Dropdowns**: `dropdown`, `dropdown-menu`, `dropdown-item`  
✅ **Responsive**: `d-none`, `d-md-block`, `d-lg-inline`  
✅ **Utilities**: `mb-*`, `py-*`, `text-*`, `bg-*`  
✅ **Icons**: Bootstrap Icons para todos los elementos  

### CSS Personalizado Mínimo
Solo se agregaron:
- Variables CSS para colores corporativos
- Gradiente del navbar
- Transiciones suaves en hover
- Ajustes responsive mínimos

### JavaScript Incluido
✅ **Auto-cierre**: Menú se cierra automáticamente en móvil  
✅ **Highlight**: Enlace activo se resalta automáticamente  
✅ **Bootstrap JS**: Funcionalidad completa de componentes  

---

## 📊 Ventajas del Sistema

| Aspecto | Beneficio |
|---------|-----------|
| **Consistencia** | Todas las páginas tienen el mismo look & feel |
| **Mantenibilidad** | Un solo lugar para actualizar header/footer |
| **Responsive** | Funciona en cualquier dispositivo |
| **Profesional** | Diseño moderno y limpio |
| **Accesibilidad** | Navegación clara y organizada |
| **Performance** | Solo Bootstrap, sin librerías adicionales |
| **SEO Friendly** | HTML semántico y estructurado |

---

## 🔧 Personalización

### Cambiar Colores
Edita las variables en `header.php`:
```css
:root {
    --gaselag-primary: #667eea;    /* Color principal */
    --gaselag-secondary: #764ba2;  /* Color secundario */
    --gaselag-dark: #2c3e50;       /* Color oscuro */
}
```

### Cambiar Logo
En `header.php` línea ~93:
```html
<i class="bi bi-speedometer2 me-2 fs-4"></i>
<span class="logo-text">TU EMPRESA</span>
```

### Agregar Enlaces
En `header.php` dentro de `<ul class="navbar-nav">`:
```html
<li class="nav-item">
    <a class="nav-link" href="tu-pagina.php">
        <i class="bi bi-tu-icono me-1"></i>
        Tu Enlace
    </a>
</li>
```

### Modificar Footer
En `footer.php` edita las secciones según necesites:
- Información de contacto
- Enlaces rápidos
- Texto de copyright

---

## ✅ Checklist de Implementación

Para implementar en una página existente:

- [ ] Verificar que la página tiene `require_once 'config/database.php'` al inicio
- [ ] Agregar `$pageTitle = 'Título'` (opcional)
- [ ] Agregar `require_once '../includes/header.php'` después de configs
- [ ] Verificar que el contenido está dentro de un `<div class="container">`
- [ ] Agregar `require_once '../includes/footer.php'` al final
- [ ] Eliminar `<!DOCTYPE>`, `<html>`, `<head>`, `<body>` duplicados
- [ ] Eliminar navbars o footers antiguos
- [ ] Probar en móvil, tablet y desktop

---

## 🎨 Ejemplo Visual

### Desktop
![Desktop View - Navbar con todos los menús expandidos, footer con 3 columnas]

### Tablet
![Tablet View - Navbar colapsado, footer con 2 columnas]

### Móvil
![Mobile View - Menú hamburguesa, footer apilado verticalmente]

---

## 📝 Notas Importantes

1. **Rutas automáticas**: El header detecta automáticamente si está en `/pages/` o en raíz
2. **Sesión requerida**: Header verifica que haya sesión activa, redirige al login si no
3. **Permisos**: Menú admin solo se muestra si `isAdmin() == true`
4. **Título dinámico**: Define `$pageTitle` antes del header para título personalizado
5. **Usuario actual**: Footer muestra información del usuario logueado
6. **Año automático**: Copyright se actualiza solo con `date('Y')`

---

## 🚀 Resultado Final

Un sistema con:
- ✅ Navegación profesional y consistente
- ✅ Diseño responsive en todos los dispositivos
- ✅ Acceso rápido a todas las funcionalidades
- ✅ Información clara del usuario activo
- ✅ Footer informativo con enlaces útiles
- ✅ 100% Bootstrap 5 (sin dependencias extra)
- ✅ Fácil de mantener y actualizar

¡Tu sistema ahora se ve profesional en cualquier dispositivo! 📱💻🖥️
