# Guía Rápida de Comandos Git

## Inicializar Repositorio (Primera Vez)

```bash
# Ir al directorio del proyecto
cd "c:\xampp\htdocs\form gaselag retiros"

# Inicializar Git
git init

# Configurar nombre y email (primera vez)
git config user.name "Tu Nombre"
git config user.email "tuemail@ejemplo.com"

# Agregar todos los archivos
git add .

# Primer commit
git commit -m "Version 1.0.0 - Sistema base funcional"

# Crear tag de versión
git tag -a v1.0.0 -m "Version 1.0.0 - Producción inicial"
```

## Conectar con Repositorio Remoto (GitHub/GitLab/Bitbucket)

```bash
# Opción A: GitHub
git remote add origin https://github.com/tuusuario/gaselag-retiros.git
git branch -M main
git push -u origin main
git push origin v1.0.0

# Opción B: GitLab
git remote add origin https://gitlab.com/tuusuario/gaselag-retiros.git
git push -u origin main
git push origin v1.0.0

# Opción C: Bitbucket
git remote add origin https://bitbucket.org/tuusuario/gaselag-retiros.git
git push -u origin main
git push origin v1.0.0
```

## Flujo de Trabajo Diario

```bash
# 1. Ver cambios
git status

# 2. Agregar archivos modificados
git add .
# O agregar archivos específicos
git add pages/nueva_pagina.php config/AppConfig.php

# 3. Commit con mensaje descriptivo
git commit -m "Agregada funcionalidad de notificaciones"

# 4. Subir a repositorio remoto
git push origin main
```

## Crear Nueva Funcionalidad (Ramas)

```bash
# 1. Crear y cambiar a nueva rama
git checkout -b feature/notificaciones

# 2. Hacer cambios y commits
git add .
git commit -m "Agregado sistema de notificaciones"

# 3. Volver a rama principal
git checkout main

# 4. Fusionar cambios
git merge feature/notificaciones

# 5. Eliminar rama (opcional)
git branch -d feature/notificaciones

# 6. Subir cambios
git push origin main
```

## Versionado

```bash
# Crear nueva versión
git tag -a v1.1.0 -m "Version 1.1.0 - Agregado sistema de notificaciones"

# Subir tag al repositorio
git push origin v1.1.0

# Ver todas las versiones
git tag

# Ver detalles de una versión
git show v1.1.0

# Volver a una versión específica
git checkout v1.0.0
```

## Actualizar desde Repositorio Remoto

```bash
# Descargar cambios
git fetch origin

# Fusionar cambios
git merge origin/main

# O hacer ambos en un comando
git pull origin main
```

## Comandos Útiles

```bash
# Ver historial de commits
git log

# Ver historial resumido
git log --oneline

# Ver cambios no confirmados
git diff

# Ver cambios de un archivo específico
git diff pages/login.php

# Descartar cambios no guardados
git checkout -- archivo.php

# Ver ramas
git branch

# Cambiar de rama
git checkout nombre-rama

# Ver estado del repositorio
git status
```

## Ignorar Archivos

```bash
# Crear .gitignore (ya existe en el proyecto)
# Agregar archivos/carpetas a ignorar:
uploads/*.jpg
config/environment.php
backups/*
*.log

# Dejar de rastrear archivo ya agregado
git rm --cached config/environment.php
git commit -m "Removido environment.php del repositorio"
```

## Comandos de Emergencia

```bash
# Deshacer último commit (mantiene cambios)
git reset --soft HEAD~1

# Deshacer último commit (descarta cambios)
git reset --hard HEAD~1

# Ver archivos ignorados
git status --ignored

# Limpiar archivos no rastreados
git clean -n  # Ver qué se borraría
git clean -f  # Borrar realmente
```

## Configuración Útil

```bash
# Guardar credenciales (evita ingresar usuario/password siempre)
git config --global credential.helper wincred  # Windows
git config --global credential.helper store    # Linux/Mac

# Ver configuración
git config --list

# Ver configuración de usuario
git config user.name
git config user.email

# Establecer editor
git config --global core.editor "code --wait"  # VS Code
```

## Ejemplo de Flujo Completo

```bash
# 1. Hacer cambios en el código
# ... editar archivos ...

# 2. Ver qué cambió
git status

# 3. Agregar cambios
git add .

# 4. Commit
git commit -m "Mejorado diseño del dashboard"

# 5. Crear versión si es necesario
git tag -a v1.1.1 -m "Version 1.1.1 - Mejoras visuales"

# 6. Subir a repositorio
git push origin main
git push origin v1.1.1
```

## Consejos de Mensajes de Commit

```
✅ Bien:
- "Agregado filtro de búsqueda por fecha"
- "Corregido bug en exportación CSV"
- "Mejorado rendimiento de consultas"
- "Actualizada documentación de instalación"

❌ Mal:
- "cambios"
- "fix"
- "update"
- "asdfsadf"
```

## Verificar Configuración de Git

```bash
# Ver si Git está instalado
git --version

# Ver repositorio remoto configurado
git remote -v

# Ver última actividad
git log -1

# Ver archivos rastreados
git ls-files
```
