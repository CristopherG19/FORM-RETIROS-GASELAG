@echo off
:: Este script debe ejecutarse como Administrador
color 0E
title Configurar Firewall para GASELAG

echo.
echo =====================================================
echo   CONFIGURAR FIREWALL - GASELAG
echo =====================================================
echo.

:: Verificar si se ejecuta como administrador
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Este script requiere privilegios de Administrador
    echo.
    echo Click derecho en el archivo y selecciona "Ejecutar como administrador"
    echo.
    pause
    exit /b 1
)

echo [OK] Ejecutando con privilegios de administrador
echo.

:: Buscar Apache
echo Buscando instalacion de Apache...
set "APACHE_PATH=C:\xampp\apache\bin\httpd.exe"

if exist "%APACHE_PATH%" (
    echo [OK] Apache encontrado en: %APACHE_PATH%
) else (
    echo [ADVERTENCIA] Apache no encontrado en la ruta por defecto
    echo Ruta esperada: %APACHE_PATH%
    echo.
    set /p APACHE_PATH="Ingresa la ruta completa de httpd.exe: "
)

if not exist "%APACHE_PATH%" (
    echo [ERROR] No se pudo encontrar Apache
    pause
    exit /b 1
)

echo.
echo Configurando reglas de firewall...
echo.

:: Eliminar reglas existentes (si existen)
echo [1/3] Eliminando reglas antiguas (si existen)...
netsh advfirewall firewall delete rule name="GASELAG - Apache HTTP" >nul 2>&1
netsh advfirewall firewall delete rule name="GASELAG - Apache HTTPS" >nul 2>&1
echo    [OK] Reglas antiguas eliminadas

:: Crear regla para puerto 80 (HTTP)
echo [2/3] Creando regla para puerto 80 (HTTP)...
netsh advfirewall firewall add rule name="GASELAG - Apache HTTP" dir=in action=allow protocol=TCP localport=80 enable=yes profile=any
if %errorlevel% equ 0 (
    echo    [OK] Regla HTTP creada exitosamente
) else (
    echo    [ERROR] No se pudo crear la regla HTTP
)

:: Crear regla para puerto 443 (HTTPS) - opcional
echo [3/3] Creando regla para puerto 443 (HTTPS)...
netsh advfirewall firewall add rule name="GASELAG - Apache HTTPS" dir=in action=allow protocol=TCP localport=443 enable=yes profile=any
if %errorlevel% equ 0 (
    echo    [OK] Regla HTTPS creada exitosamente
) else (
    echo    [ERROR] No se pudo crear la regla HTTPS
)

echo.
echo =====================================================
echo   CONFIGURACION COMPLETADA
echo =====================================================
echo.
echo Las siguientes reglas fueron creadas:
echo.
netsh advfirewall firewall show rule name="GASELAG - Apache HTTP"
echo.

echo =====================================================
echo   PASOS SIGUIENTES:
echo =====================================================
echo.
echo 1. Reinicia Apache en XAMPP (Stop y luego Start)
echo 2. Ejecuta: diagnosticar_acceso_movil.bat
echo 3. Intenta acceder desde tu celular
echo.
echo Si aun no funciona, verifica:
echo - PC y celular en la misma red WiFi
echo - La IP puede haber cambiado
echo.
pause
