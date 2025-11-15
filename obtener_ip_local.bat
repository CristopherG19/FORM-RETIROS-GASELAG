@echo off
echo ========================================
echo  GASELAG - Obtener IP Local para Movil
echo ========================================
echo.
echo Obteniendo tu direccion IP local...
echo.

for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4"') do (
    set IP=%%a
    set IP=!IP:~1!
    echo Tu IP Local es: !IP!
    echo.
    echo ACCEDE DESDE TU CELULAR EN:
    echo http://!IP!/form%%20gaselag%%20retiros/
    echo.
    echo Para el login directo:
    echo http://!IP!/form%%20gaselag%%20retiros/login.php
)

echo.
echo ========================================
echo  RECUERDA:
echo  1. PC y celular en la misma red WiFi
echo  2. Apache debe estar corriendo (XAMPP)
echo  3. Firewall debe permitir puerto 80
echo ========================================
echo.
pause

