# Script para crear estructura de carpetas de backups
$basePath = "C:\xampp\htdocs\form gaselag retiros\backups"

# Carpetas de backups de base de datos
$folders = @(
    "$basePath\database",
    "$basePath\database\daily",
    "$basePath\database\weekly",
    "$basePath\database\monthly",
    "$basePath\uploads_backup",
    "$basePath\uploads_backup\daily",
    "$basePath\uploads_backup\weekly",
    "$basePath\uploads_backup\monthly",
    "$basePath\system",
    "$basePath\logs",
    "$basePath\scripts"
)

foreach ($folder in $folders) {
    if (-not (Test-Path $folder)) {
        New-Item -ItemType Directory -Path $folder -Force | Out-Null
        Write-Host "OK Creada: $folder" -ForegroundColor Green
    } else {
        Write-Host "EXISTE: $folder" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "Estructura de carpetas completada" -ForegroundColor Green

