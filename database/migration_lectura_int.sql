-- Migración: Cambiar lectura_m3 de DECIMAL a INT
-- Fecha: 2025-10-25
-- Descripción: Convierte el campo lectura_m3 a tipo entero

USE gaselag_retiros;

-- Cambiar el tipo de dato de lectura_m3 de DECIMAL(10,3) a INT
-- Los valores decimales se redondearán automáticamente
ALTER TABLE retiros_medidores 
MODIFY COLUMN lectura_m3 INT NULL;

-- Verificación
SELECT 'Migración completada: lectura_m3 ahora es INT' AS mensaje;

