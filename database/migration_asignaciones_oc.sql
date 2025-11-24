-- ================================================
-- MIGRACIÓN: Sistema de Asignación de OCs a Técnicos
-- Fecha: 2025-11-06
-- Descripción: Permite a los administradores asignar OCs 
--              específicas a técnicos de forma individual o masiva
-- ================================================

USE gaselagc_sistema_retiro;

-- Tabla para asignaciones de OCs a técnicos
CREATE TABLE IF NOT EXISTS asignaciones_oc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Referencia a la OC
    orden_servicio_id INT NOT NULL,
    orden_servicio VARCHAR(50) NOT NULL,
    
    -- Técnico asignado
    tecnico_id INT NOT NULL,
    tecnico_nombre VARCHAR(100) NOT NULL, -- Desnormalizado para rapidez
    
    -- Admin que asignó
    admin_asigno_id INT NOT NULL,
    admin_nombre VARCHAR(100) NOT NULL, -- Desnormalizado para rapidez
    
    -- Estado de la asignación
    estado ENUM('pendiente', 'en_proceso', 'completada', 'cancelada') DEFAULT 'pendiente',
    
    -- Metadatos
    notas_admin TEXT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio TIMESTAMP NULL, -- Cuando el técnico empieza a trabajar
    fecha_completada TIMESTAMP NULL, -- Cuando se registra el retiro
    fecha_cancelada TIMESTAMP NULL,
    motivo_cancelacion TEXT NULL,
    
    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Relaciones
    FOREIGN KEY (orden_servicio_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE,
    FOREIGN KEY (tecnico_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_asigno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    -- Índices para optimización
    INDEX idx_tecnico (tecnico_id),
    INDEX idx_estado (estado),
    INDEX idx_orden_servicio (orden_servicio),
    INDEX idx_fecha_asignacion (fecha_asignacion),
    INDEX idx_tecnico_estado (tecnico_id, estado), -- Consulta común: OCs pendientes de un técnico
    
    -- Evitar duplicados: una OC solo puede estar asignada una vez en estado activo
    UNIQUE KEY unique_active_assignment (orden_servicio_id, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Asignaciones de órdenes de servicio a técnicos por parte de administradores';

-- Tabla para log de asignaciones masivas
CREATE TABLE IF NOT EXISTS asignaciones_masivas_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    admin_nombre VARCHAR(100) NOT NULL,
    tecnico_id INT NOT NULL,
    tecnico_nombre VARCHAR(100) NOT NULL,
    total_ocs_asignadas INT NOT NULL DEFAULT 0,
    ocs_asignadas TEXT NULL, -- JSON con lista de OCs
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notas TEXT NULL,
    
    FOREIGN KEY (admin_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (tecnico_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    INDEX idx_admin (admin_id),
    INDEX idx_tecnico (tecnico_id),
    INDEX idx_fecha (fecha_asignacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de asignaciones masivas para auditoría';

-- Agregar nuevos tipos de auditoría
ALTER TABLE auditoria_retiros 
MODIFY COLUMN accion ENUM(
    'login', 'logout',
    'busqueda_oc', 'intento_registro_oc', 'registro_oc',
    'consulta_registros', 'consulta_registro_detalle',
    'reasignacion_oc', 'reapertura_oc',
    'modificacion_registro', 'eliminacion_registro',
    'asignacion_oc_individual', 'asignacion_oc_masiva',
    'cancelacion_asignacion', 'inicio_trabajo_asignacion'
) NOT NULL;

-- Vista para consultas rápidas de asignaciones pendientes por técnico
CREATE OR REPLACE VIEW v_asignaciones_pendientes AS
SELECT 
    a.id,
    a.orden_servicio,
    a.tecnico_id,
    a.tecnico_nombre,
    a.admin_nombre,
    a.estado,
    a.fecha_asignacion,
    a.notas_admin,
    o.cliente,
    o.direccion,
    o.num_suministro,
    o.num_serie_medidor,
    o.programacion_dia_retiro,
    o.programacion_hora_retiro,
    CASE 
        WHEN r.id IS NOT NULL THEN 'REGISTRADO'
        ELSE 'PENDIENTE'
    END as estado_retiro,
    DATEDIFF(CURDATE(), a.fecha_asignacion) as dias_desde_asignacion
FROM asignaciones_oc a
INNER JOIN ordenes_servicio o ON a.orden_servicio_id = o.id
LEFT JOIN retiros_medidores r ON o.id = r.orden_servicio_id AND r.estado_registro = 'activo'
WHERE a.estado IN ('pendiente', 'en_proceso')
ORDER BY a.fecha_asignacion DESC;

-- Índices adicionales en ordenes_servicio para mejorar consultas de asignación
ALTER TABLE ordenes_servicio
ADD INDEX idx_cliente (cliente(50)),
ADD INDEX idx_programacion (programacion_dia_retiro, programacion_hora_retiro);

-- Procedimiento almacenado para marcar asignación como completada cuando se registre el retiro
DELIMITER $$

CREATE PROCEDURE sp_completar_asignacion(
    IN p_orden_servicio VARCHAR(50),
    IN p_tecnico_id INT
)
BEGIN
    UPDATE asignaciones_oc 
    SET estado = 'completada',
        fecha_completada = NOW()
    WHERE orden_servicio = p_orden_servicio
      AND tecnico_id = p_tecnico_id
      AND estado IN ('pendiente', 'en_proceso');
END$$

DELIMITER ;

-- Trigger para actualizar automáticamente el estado de asignación cuando se registra un retiro
DELIMITER $$

CREATE TRIGGER trg_after_retiro_insert
AFTER INSERT ON retiros_medidores
FOR EACH ROW
BEGIN
    -- Si el retiro fue registrado por un técnico que tiene esta OC asignada, marcar como completada
    UPDATE asignaciones_oc 
    SET estado = 'completada',
        fecha_completada = NOW()
    WHERE orden_servicio = NEW.orden_servicio
      AND tecnico_id = NEW.usuario_id
      AND estado IN ('pendiente', 'en_proceso');
END$$

DELIMITER ;

-- ================================================
-- FIN DE LA MIGRACIÓN
-- ================================================

-- Para verificar que todo se creó correctamente:
-- SELECT table_name FROM information_schema.tables WHERE table_schema = 'gaselag_retiros' AND table_name LIKE '%asignacion%';
-- SHOW TRIGGERS FROM gaselag_retiros LIKE '%retiro%';

