@echo off
setlocal enabledelayedexpansion
color 0B
title Configurar Apache para Red Local - GASELAG

echo.
echo =====================================================
echo   CONFIGURAR APACHE PARA RED LOCAL - GASELAG
echo =====================================================
echo.

:: Verificar si httpd.conf existe
set "HTTPD_CONF=C:\xampp\apache\conf\httpd.conf"

if not exist "%HTTPD_CONF%" (
    echo [ERROR] No se encontro httpd.conf en: %HTTPD_CONF%
    echo.
    set /p HTTPD_CONF="Ingresa la ruta completa de httpd.conf: "
)

if not exist "%HTTPD_CONF%" (
    echo [ERROR] Archivo no encontrado
    pause
    exit /b 1
)

echo [OK] Archivo encontrado: %HTTPD_CONF%
echo.

:: Crear backup
set "BACKUP=%HTTPD_CONF%.backup_%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%"
set "BACKUP=%BACKUP: =0%"

echo Creando backup...
copy "%HTTPD_CONF%" "%BACKUP%" >nul
echo [OK] Backup creado: %BACKUP%
echo.

:: Verificar configuración actual
echo Verificando configuracion actual...
findstr /C:"Listen 0.0.0.0:80" "%HTTPD_CONF%" >nul
if %errorlevel% equ 0 (
    echo [OK] Apache ya esta configurado para escuchar en todas las interfaces
    echo.
    echo No se requieren cambios.
    echo.
    pause
    exit /b 0
)

echo [INFO] Apache necesita ser configurado para aceptar conexiones externas
echo.

:: Preguntar al usuario
echo ATENCION: Este script modificara httpd.conf
echo Se creara un backup automaticamente
echo.
set /p CONFIRMAR="Deseas continuar? (S/N): "
if /i not "%CONFIRMAR%"=="S" (
    echo.
    echo Operacion cancelada
    pause
    exit /b 0
)

echo.
echo Modificando configuracion...

:: Crear archivo temporal con las modificaciones
set "TEMP_FILE=%TEMP%\httpd_conf_temp.txt"

:: Leer y modificar el archivo
(
    set "LISTEN_ADDED=NO"
    for /f "usebackq delims=" %%a in ("%HTTPD_CONF%") do (
        set "LINE=%%a"
        echo !LINE!
        
        :: Si encontramos "Listen 80", agregar "Listen 0.0.0.0:80" después
        echo !LINE! | findstr /C:"Listen 80" >nul
        if !errorlevel! equ 0 (
            if "!LISTEN_ADDED!"=="NO" (
                echo Listen 0.0.0.0:80
                set "LISTEN_ADDED=SI"
            )
        )
    )
) > "%TEMP_FILE%"

:: Reemplazar archivo original
move /y "%TEMP_FILE%" "%HTTPD_CONF%" >nul

if %errorlevel% equ 0 (
    echo [OK] Configuracion actualizada exitosamente
) else (
    echo [ERROR] No se pudo actualizar la configuracion
    echo Restaurando backup...
    copy "%BACKUP%" "%HTTPD_CONF%" >nul
    pause
    exit /b 1
)

echo.
echo =====================================================
echo   CONFIGURACION COMPLETADA
echo =====================================================
echo.
echo Cambios realizados:
echo - Agregada linea: Listen 0.0.0.0:80
echo - Backup guardado en: %BACKUP%
echo.
echo =====================================================
echo   PASOS SIGUIENTES:
echo =====================================================
echo.
echo 1. Cierra XAMPP completamente
echo 2. Abre XAMPP de nuevo
echo 3. Inicia Apache (boton Start)
echo 4. Ejecuta: diagnosticar_acceso_movil.bat
echo 5. Intenta acceder desde tu celular
echo.
echo Si Apache no inicia, restaura el backup:
echo    copy "%BACKUP%" "%HTTPD_CONF%"
echo.
pause
