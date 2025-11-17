@echo off
setlocal enabledelayedexpansion
color 0B
title GASELAG - Solucion Automatica de Acceso Movil

echo.
echo =====================================================
echo   SOLUCION AUTOMATICA DE ACCESO MOVIL - GASELAG
echo =====================================================
echo.
echo Este script realizara automaticamente:
echo  1. Diagnostico completo del sistema
echo  2. Obtencion de tu IP actual
echo  3. Verificacion de configuracion
echo  4. Recomendaciones personalizadas
echo.
echo =====================================================
echo.
pause
cls

:: ==================================================================
:: PASO 1: DIAGNOSTICO
:: ==================================================================
color 0E
echo.
echo =====================================================
echo   PASO 1/4: DIAGNOSTICO DEL SISTEMA
echo =====================================================
echo.

:: Verificar IP Local
echo [Verificando IP Local...]
set "IP_ENCONTRADA=NO"
set "MI_IP="
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4"') do (
    set IP=%%a
    set IP=!IP:~1!
    if not "!IP!"=="" (
        if not "!IP!"=="127.0.0.1" (
            set "IP_ENCONTRADA=SI"
            set "MI_IP=!IP!"
            goto :IP_FOUND
        )
    )
)
:IP_FOUND

if "!IP_ENCONTRADA!"=="SI" (
    echo [OK] IP Local: !MI_IP!
) else (
    echo [ERROR] No se pudo obtener la IP local
    set "PROBLEMA_IP=SI"
)

:: Verificar Apache
echo [Verificando Apache...]
netstat -ano | findstr :80 >nul
if %errorlevel% equ 0 (
    echo [OK] Puerto 80 en uso - Apache corriendo
    
    netstat -an | findstr "0.0.0.0:80" >nul
    if %errorlevel% equ 0 (
        echo [OK] Apache escuchando en todas las interfaces
        set "APACHE_OK=SI"
    ) else (
        echo [ADVERTENCIA] Apache solo escucha en localhost
        set "PROBLEMA_APACHE=SI"
    )
) else (
    echo [ERROR] Apache NO esta corriendo
    set "PROBLEMA_APACHE_DOWN=SI"
)

:: Verificar MySQL
echo [Verificando MySQL...]
netstat -ano | findstr :3307 >nul
if %errorlevel% equ 0 (
    echo [OK] MySQL corriendo en puerto 3307
    set "MYSQL_OK=SI"
) else (
    echo [ADVERTENCIA] MySQL no detectado
    set "PROBLEMA_MYSQL=SI"
)

:: Verificar Firewall
echo [Verificando Firewall...]
netsh advfirewall firewall show rule name=all | findstr /i "apache" >nul
if %errorlevel% equ 0 (
    echo [OK] Reglas de firewall encontradas
    set "FIREWALL_OK=SI"
) else (
    echo [ADVERTENCIA] No se encontraron reglas de firewall
    set "PROBLEMA_FIREWALL=SI"
)

echo.
pause
cls

:: ==================================================================
:: PASO 2: ANALISIS
:: ==================================================================
color 0B
echo.
echo =====================================================
echo   PASO 2/4: ANALISIS DE PROBLEMAS
echo =====================================================
echo.

set "PROBLEMAS_ENCONTRADOS=0"

if defined PROBLEMA_IP (
    set /a PROBLEMAS_ENCONTRADOS+=1
    echo [!] PROBLEMA: No se pudo obtener la IP local
    echo     Solucion: Verifica tu conexion WiFi
    echo.
)

if defined PROBLEMA_APACHE_DOWN (
    set /a PROBLEMAS_ENCONTRADOS+=1
    echo [!] PROBLEMA: Apache no esta corriendo
    echo     Solucion: Inicia Apache en XAMPP Control Panel
    echo.
)

if defined PROBLEMA_APACHE (
    set /a PROBLEMAS_ENCONTRADOS+=1
    echo [!] PROBLEMA: Apache solo escucha en localhost
    echo     Solucion: Necesitas configurar Apache para red local
    echo.
)

if defined PROBLEMA_MYSQL (
    set /a PROBLEMAS_ENCONTRADOS+=1
    echo [!] PROBLEMA: MySQL no esta corriendo
    echo     Solucion: Inicia MySQL en XAMPP Control Panel
    echo.
)

if defined PROBLEMA_FIREWALL (
    set /a PROBLEMAS_ENCONTRADOS+=1
    echo [!] PROBLEMA: Firewall no tiene reglas para Apache
    echo     Solucion: Configurar reglas de firewall
    echo.
)

if !PROBLEMAS_ENCONTRADOS! equ 0 (
    echo [OK] No se encontraron problemas de configuracion
    echo.
    echo Causas posibles si aun no funciona:
    echo  - Tu IP cambio (muy comun)
    echo  - PC y celular en redes WiFi diferentes
    echo  - Antivirus bloqueando conexiones
    echo.
) else (
    echo.
    echo Total de problemas encontrados: !PROBLEMAS_ENCONTRADOS!
    echo.
)

pause
cls

:: ==================================================================
:: PASO 3: SOLUCION AUTOMATICA
:: ==================================================================
color 0A
echo.
echo =====================================================
echo   PASO 3/4: APLICAR SOLUCIONES
echo =====================================================
echo.

if defined PROBLEMA_FIREWALL (
    echo Configurando Firewall...
    echo.
    echo NOTA: Esto requiere privilegios de administrador
    echo Si no funciona, ejecuta manualmente:
    echo    configurar_firewall.bat (como Administrador)
    echo.
    
    net session >nul 2>&1
    if %errorlevel% equ 0 (
        echo [+] Ejecutando con privilegios de administrador
        
        netsh advfirewall firewall delete rule name="GASELAG - Apache HTTP" >nul 2>&1
        netsh advfirewall firewall add rule name="GASELAG - Apache HTTP" dir=in action=allow protocol=TCP localport=80 enable=yes profile=any >nul 2>&1
        
        if !errorlevel! equ 0 (
            echo [OK] Firewall configurado correctamente
        ) else (
            echo [ERROR] No se pudo configurar el firewall
        )
    ) else (
        echo [!] No se tienen privilegios de administrador
        echo     Ejecuta manualmente: configurar_firewall.bat
    )
    echo.
)

if defined PROBLEMA_APACHE (
    echo [!] ADVERTENCIA: Apache necesita ser reconfigurado
    echo.
    echo Para solucionar esto:
    echo  1. Ejecuta: configurar_apache_red_local.bat
    echo  2. Reinicia Apache en XAMPP
    echo.
)

if defined PROBLEMA_APACHE_DOWN (
    echo [!] ADVERTENCIA: Debes iniciar Apache manualmente
    echo.
    echo  1. Abre XAMPP Control Panel
    echo  2. Click en "Start" al lado de Apache
    echo.
)

if defined PROBLEMA_MYSQL (
    echo [!] ADVERTENCIA: Debes iniciar MySQL manualmente
    echo.
    echo  1. Abre XAMPP Control Panel
    echo  2. Click en "Start" al lado de MySQL
    echo.
)

pause
cls

:: ==================================================================
:: PASO 4: INFORMACION PARA ACCEDER
:: ==================================================================
color 0B
echo.
echo =====================================================
echo   PASO 4/4: INFORMACION PARA ACCEDER DESDE MOVIL
echo =====================================================
echo.

if "!IP_ENCONTRADA!"=="SI" (
    echo TU IP LOCAL ACTUAL:
    echo.
    echo     !MI_IP!
    echo.
    echo =====================================================
    echo   ACCEDE DESDE TU CELULAR EN:
    echo =====================================================
    echo.
    echo URL Principal:
    echo   http://!MI_IP!/form%%20gaselag%%20retiros/
    echo.
    echo URL Login Directo:
    echo   http://!MI_IP!/form%%20gaselag%%20retiros/login.php
    echo.
    echo =====================================================
    echo.
    
    echo Abre esta URL en el navegador de tu celular
    echo (Asegurate de estar en la MISMA red WiFi)
    echo.
) else (
    echo [ERROR] No se pudo obtener la IP
    echo Verifica tu conexion WiFi y ejecuta:
    echo    obtener_ip_local.bat
    echo.
)

echo =====================================================
echo   CHECKLIST FINAL:
echo =====================================================
echo.
echo Verifica que:
if "!APACHE_OK!"=="SI" (
    echo  [X] Apache corriendo
) else (
    echo  [ ] Apache corriendo ^^^<--- PENDIENTE
)

if "!MYSQL_OK!"=="SI" (
    echo  [X] MySQL corriendo
) else (
    echo  [ ] MySQL corriendo ^^^<--- PENDIENTE
)

if "!FIREWALL_OK!"=="SI" (
    echo  [X] Firewall configurado
) else (
    echo  [ ] Firewall configurado ^^^<--- PENDIENTE
)

if "!IP_ENCONTRADA!"=="SI" (
    echo  [X] IP obtenida: !MI_IP!
) else (
    echo  [ ] IP obtenida ^^^<--- PENDIENTE
)

echo  [ ] PC y celular en la MISMA red WiFi
echo  [ ] Celular NO usando datos moviles
echo.

echo =====================================================
echo   SI AUN NO FUNCIONA:
echo =====================================================
echo.
echo 1. Tu IP puede haber CAMBIADO
echo    - Ejecuta: obtener_ip_local.bat
echo    - Usa la IP nueva que te muestre
echo.
echo 2. Verifica la red WiFi
echo    - PC y celular en la MISMA red
echo    - Celular sin datos moviles activos
echo.
echo 3. Desactiva temporalmente:
echo    - Antivirus
echo    - VPN
echo    - Firewall (solo para probar)
echo.
echo 4. Reinicia todo:
echo    - Detener Apache y MySQL en XAMPP
echo    - Cerrar XAMPP
echo    - Abrir XAMPP de nuevo
echo    - Iniciar Apache y MySQL
echo    - Ejecutar este script de nuevo
echo.
echo 5. Lee la guia completa:
echo    - Abre: SOLUCION_ACCESO_MOVIL.md
echo.
echo =====================================================
echo.

if "!IP_ENCONTRADA!"=="SI" (
    echo RECUERDA: La URL es http://!MI_IP!/form%%20gaselag%%20retiros/
    echo.
)

pause

:: Opcion de abrir pagina de acceso movil
echo.
set /p ABRIR="Deseas abrir la pagina de Acceso Movil con QR? (S/N): "
if /i "!ABRIR!"=="S" (
    start http://localhost/form%%20gaselag%%20retiros/acceso_movil.php
)

exit /b 0

