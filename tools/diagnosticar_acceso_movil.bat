@echo off
setlocal enabledelayedexpansion
color 0A
title GASELAG - Diagnostico de Acceso Movil

echo.
echo =====================================================
echo   DIAGNOSTICO DE ACCESO DESDE MOVIL - GASELAG
echo =====================================================
echo.

:: 1. Verificar IP Local
echo [1/5] Verificando direccion IP local...
echo.
set "IP_ENCONTRADA=NO"
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4"') do (
    set IP=%%a
    set IP=!IP:~1!
    if not "!IP!"=="" (
        if not "!IP!"=="127.0.0.1" (
            echo    [OK] IP Local encontrada: !IP!
            set "IP_ENCONTRADA=SI"
            set "MI_IP=!IP!"
        )
    )
)
if "!IP_ENCONTRADA!"=="NO" (
    echo    [ERROR] No se pudo obtener la IP local
    echo    Verifica tu conexion WiFi
)
echo.

:: 2. Verificar si Apache está corriendo
echo [2/5] Verificando si Apache esta corriendo...
echo.
netstat -ano | findstr :80 >nul
if %errorlevel% equ 0 (
    echo    [OK] Puerto 80 esta en uso (Apache probablemente corriendo)
    
    :: Verificar si escucha en 0.0.0.0
    netstat -an | findstr "0.0.0.0:80" >nul
    if %errorlevel% equ 0 (
        echo    [OK] Apache escuchando en todas las interfaces (0.0.0.0:80)
    ) else (
        echo    [ADVERTENCIA] Apache solo escucha en localhost
        echo    Necesitas configurar Apache para aceptar conexiones externas
        echo    Ver: GUIA_ACCESO_MOVIL.md
    )
) else (
    echo    [ERROR] Puerto 80 no esta en uso
    echo    Apache NO esta corriendo. Inicia Apache en XAMPP
)
echo.

:: 3. Verificar reglas de Firewall
echo [3/5] Verificando reglas de Firewall...
echo.
netsh advfirewall firewall show rule name=all | findstr /i "apache" >nul
if %errorlevel% equ 0 (
    echo    [OK] Se encontraron reglas de firewall para Apache
) else (
    echo    [ADVERTENCIA] No se encontraron reglas de firewall para Apache
    echo    Puede que necesites configurar el firewall
)
echo.

:: 4. Verificar si MySQL está corriendo
echo [4/5] Verificando si MySQL esta corriendo...
echo.
netstat -ano | findstr :3307 >nul
if %errorlevel% equ 0 (
    echo    [OK] MySQL corriendo en puerto 3307
) else (
    echo    [ADVERTENCIA] MySQL no detectado en puerto 3307
    echo    Verifica que MySQL este corriendo en XAMPP
)
echo.

:: 5. Test de conectividad local
echo [5/5] Probando acceso local...
echo.
curl -s -o nul -w "HTTP Status: %%{http_code}" http://localhost/form%%20gaselag%%20retiros/ 2>nul
if %errorlevel% equ 0 (
    echo.
    echo    [OK] El sistema responde localmente
) else (
    echo.
    echo    [ERROR] El sistema NO responde en localhost
    echo    Verifica que Apache este corriendo
)
echo.

:: Resumen
echo =====================================================
echo   RESUMEN Y RECOMENDACIONES
echo =====================================================
echo.

if "!IP_ENCONTRADA!"=="SI" (
    echo Tu IP Local: !MI_IP!
    echo.
    echo ACCEDE DESDE TU CELULAR EN:
    echo http://!MI_IP!/form%%20gaselag%%20retiros/
    echo.
    echo Para login directo:
    echo http://!MI_IP!/form%%20gaselag%%20retiros/login.php
    echo.
)

echo =====================================================
echo   PASOS SI NO FUNCIONA:
echo =====================================================
echo.
echo 1. VERIFICA RED WIFI:
echo    - PC y celular en la MISMA red WiFi
echo    - NO uses datos moviles en el celular
echo.
echo 2. VERIFICA XAMPP:
echo    - Apache con boton VERDE en XAMPP
echo    - MySQL con boton VERDE en XAMPP
echo.
echo 3. CONFIGURA FIREWALL:
echo    - Ejecuta: configurar_firewall.bat (como Administrador)
echo    - O desactiva temporalmente el firewall para probar
echo.
echo 4. CONFIGURA APACHE:
echo    - Si dice "Apache solo escucha en localhost"
echo    - Edita httpd.conf (ver GUIA_ACCESO_MOVIL.md)
echo.
echo 5. REINICIA SERVICIOS:
echo    - Detener Apache en XAMPP
echo    - Iniciar Apache en XAMPP
echo    - Espera 5 segundos y prueba de nuevo
echo.
echo =====================================================
echo.
pause
