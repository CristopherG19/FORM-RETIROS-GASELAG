-- Script de actualización para agregar el campo tiene_foto a registros existentes
-- Ejecutar después de actualizar el schema.sql

USE gaselag_retiros;

-- Agregar el campo tiene_foto a la tabla existente
ALTER TABLE retiros_medidores
ADD COLUMN tiene_foto ENUM('SI', 'NO') NOT NULL DEFAULT 'NO' AFTER foto_imposibilidad;

-- Actualizar registros existentes: si tienen foto_imposibilidad, marcar tiene_foto como 'SI'
UPDATE retiros_medidores
SET tiene_foto = 'SI'
WHERE foto_imposibilidad IS NOT NULL AND foto_imposibilidad != '';

-- Verificar la actualización
SELECT
    COUNT(*) as total_registros,
    SUM(CASE WHEN tiene_foto = 'SI' THEN 1 ELSE 0 END) as con_foto,
    SUM(CASE WHEN tiene_foto = 'NO' THEN 1 ELSE 0 END) as sin_foto
FROM retiros_medidores;
