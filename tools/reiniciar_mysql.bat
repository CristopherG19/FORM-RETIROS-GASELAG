@echo off
echo ====================================
echo REINICIANDO MySQL - GASELAG
echo ====================================
echo.
echo Deteniendo MySQL...
net stop MySQL
timeout /t 3
echo.
echo Iniciando MySQL...
net start MySQL
echo.
echo ====================================
echo MySQL reiniciado correctamente
echo ====================================
pause
