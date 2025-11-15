# Script para crear tarea programada de backups en Windows
# Ejecutar como Administrador

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  CONFIGURACION DE TAREA PROGRAMADA" -ForegroundColor Cyan
Write-Host "  Sistema GASELAG - Backups Automaticos" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Configuracion
$taskName = "Backup GASELAG Automatico"
$phpPath = "C:\xampp\php\php.exe"
$scriptPath = "C:\xampp\htdocs\form gaselag retiros\backups\scripts\auto_backup.php"
$logPath = "C:\xampp\htdocs\form gaselag retiros\backups\logs\tarea_programada.log"

# Verificar que PHP existe
if (-not (Test-Path $phpPath)) {
    Write-Host "ERROR: PHP no encontrado en: $phpPath" -ForegroundColor Red
    Write-Host "Por favor, ajusta la ruta en este script" -ForegroundColor Yellow
    Read-Host "Presiona Enter para salir"
    exit
}

# Verificar que el script existe
if (-not (Test-Path $scriptPath)) {
    Write-Host "ERROR: Script no encontrado en: $scriptPath" -ForegroundColor Red
    Read-Host "Presiona Enter para salir"
    exit
}

Write-Host "Configuracion:" -ForegroundColor Yellow
Write-Host "  - Nombre: $taskName" -ForegroundColor White
Write-Host "  - PHP: $phpPath" -ForegroundColor White
Write-Host "  - Script: $scriptPath" -ForegroundColor White
Write-Host "  - Hora: 2:00 AM diariamente" -ForegroundColor White
Write-Host ""

# Verificar si la tarea ya existe
$existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue

if ($existingTask) {
    Write-Host "La tarea '$taskName' ya existe." -ForegroundColor Yellow
    $response = Read-Host "Deseas reemplazarla? (S/N)"
    
    if ($response -ne "S" -and $response -ne "s") {
        Write-Host "Operacion cancelada" -ForegroundColor Yellow
        Read-Host "Presiona Enter para salir"
        exit
    }
    
    # Eliminar tarea existente
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
    Write-Host "Tarea existente eliminada" -ForegroundColor Green
}

# Crear accion
$action = New-ScheduledTaskAction `
    -Execute $phpPath `
    -Argument "`"$scriptPath`"" `
    -WorkingDirectory (Split-Path $scriptPath)

# Crear disparador (trigger) - Diario a las 2:00 AM
$trigger = New-ScheduledTaskTrigger -Daily -At 2:00AM

# Configuracion de la tarea
$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RunOnlyIfNetworkAvailable:$false

# Crear tarea programada
try {
    Register-ScheduledTask `
        -TaskName $taskName `
        -Action $action `
        -Trigger $trigger `
        -Settings $settings `
        -Description "Backup automatico del sistema GASELAG. Ejecuta backups de BD y archivos diariamente." `
        -User "SYSTEM" `
        -RunLevel Highest | Out-Null
    
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "  TAREA CREADA EXITOSAMENTE" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Detalles de la tarea:" -ForegroundColor Yellow
    Write-Host "  - Nombre: $taskName" -ForegroundColor White
    Write-Host "  - Frecuencia: Diariamente a las 2:00 AM" -ForegroundColor White
    Write-Host "  - Estado: Habilitada" -ForegroundColor White
    Write-Host "  - Usuario: SYSTEM" -ForegroundColor White
    Write-Host ""
    Write-Host "Para verificar la tarea:" -ForegroundColor Cyan
    Write-Host "  1. Abre 'Programador de tareas' de Windows" -ForegroundColor White
    Write-Host "  2. Busca '$taskName'" -ForegroundColor White
    Write-Host "  3. Puedes ejecutarla manualmente o modificar la configuracion" -ForegroundColor White
    Write-Host ""
    Write-Host "Logs de ejecucion en: $logPath" -ForegroundColor Cyan
    Write-Host ""
    
} catch {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "  ERROR AL CREAR LA TAREA" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Error: $_" -ForegroundColor Red
    Write-Host ""
    Write-Host "Asegurate de ejecutar este script como Administrador" -ForegroundColor Yellow
    Write-Host ""
}

Read-Host "Presiona Enter para salir"

