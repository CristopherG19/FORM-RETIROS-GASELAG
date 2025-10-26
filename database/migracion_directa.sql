-- =====================================================
-- MIGRACIÓN DIRECTA PARA BASE DE DATOS
-- =====================================================
-- Ejecutar estos comandos en phpMyAdmin o MySQL console
-- =====================================================

USE gaselag_retiros;

-- 1. Agregar la columna tiene_foto
ALTER TABLE retiros_medidores
ADD COLUMN tiene_foto ENUM('SI', 'NO') NOT NULL DEFAULT 'NO'
AFTER foto_imposibilidad;

-- 2. Actualizar registros existentes basándose en si tienen foto
UPDATE retiros_medidores
SET tiene_foto = 'SI'
WHERE foto_imposibilidad IS NOT NULL AND foto_imposibilidad != '';

-- 3. Verificar que todo esté correcto
SELECT
    'MIGRACIÓN COMPLETADA' as estado,
    COUNT(*) as total_registros,
    SUM(CASE WHEN tiene_foto = 'SI' THEN 1 ELSE 0 END) as registros_con_foto,
    SUM(CASE WHEN tiene_foto = 'NO' THEN 1 ELSE 0 END) as registros_sin_foto
FROM retiros_medidores;

-- 4. Mostrar algunos ejemplos de registros actualizados
SELECT
    orden_servicio,
    medidor_retirado,
    CASE WHEN foto_imposibilidad IS NOT NULL AND foto_imposibilidad != ''
         THEN 'SI' ELSE 'NO' END as tiene_foto_archivo,
    tiene_foto as tiene_foto_campo
FROM retiros_medidores
LIMIT 5;
