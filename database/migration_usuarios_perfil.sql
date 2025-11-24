-- Migración: Agregar campos adicionales de perfil a usuarios
-- Fecha: 1 de noviembre de 2025

USE gaselagc_sistema_retiro;

-- Agregar nuevos campos a la tabla usuarios
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS foto_perfil VARCHAR(255) NULL COMMENT 'Ruta de la foto de perfil',
ADD COLUMN IF NOT EXISTS telefono VARCHAR(20) NULL COMMENT 'Teléfono de contacto',
ADD COLUMN IF NOT EXISTS direccion TEXT NULL COMMENT 'Dirección completa',
ADD COLUMN IF NOT EXISTS fecha_nacimiento DATE NULL COMMENT 'Fecha de nacimiento',
ADD COLUMN IF NOT EXISTS documento_identidad VARCHAR(20) NULL COMMENT 'Número de documento',
ADD COLUMN IF NOT EXISTS cargo VARCHAR(100) NULL COMMENT 'Cargo o puesto',
ADD COLUMN IF NOT EXISTS fecha_ingreso DATE NULL COMMENT 'Fecha de ingreso a la empresa',
ADD COLUMN IF NOT EXISTS notas TEXT NULL COMMENT 'Notas o comentarios adicionales',
ADD COLUMN IF NOT EXISTS estado_laboral ENUM('activo', 'vacaciones', 'licencia', 'inactivo') DEFAULT 'activo' COMMENT 'Estado laboral del empleado';

-- Crear directorio para fotos de perfil (esto debe hacerse manualmente)
-- Crear carpeta: uploads/perfiles/

-- Índices adicionales
ALTER TABLE usuarios 
ADD INDEX IF NOT EXISTS idx_documento (documento_identidad),
ADD INDEX IF NOT EXISTS idx_telefono (telefono);

-- Mostrar estructura actualizada
DESCRIBE usuarios;
