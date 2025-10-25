-- Base de datos para el sistema de retiro de medidores GASELAG
-- Creación de la base de datos
CREATE DATABASE IF NOT EXISTS gaselag_retiros CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gaselag_retiros;

-- Tabla principal de órdenes de servicio (datos del Excel)
CREATE TABLE IF NOT EXISTS ordenes_servicio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item VARCHAR(10) NOT NULL,
    orden_servicio VARCHAR(50) NOT NULL UNIQUE,
    fecha_os DATE,
    cantidad_medidores INT,
    tipo_servicio VARCHAR(50),
    programacion_dia_retiro DATE,
    programacion_hora_retiro VARCHAR(10),
    programacion_dia_vp DATE,
    programacion_hora_vp VARCHAR(10),
    codigo_seguridad VARCHAR(20),
    cliente VARCHAR(100),
    centro_servicio VARCHAR(100),
    remesa VARCHAR(50),
    usuario_reclamante VARCHAR(150),
    direccion TEXT,
    cus VARCHAR(50),
    cup VARCHAR(50),
    num_suministro VARCHAR(50),
    num_serie_medidor VARCHAR(50),
    marca_medidor VARCHAR(50),
    modelo_medidor VARCHAR(50),
    anio_fabricacion INT,
    fabricante VARCHAR(100),
    procedencia VARCHAR(50),
    tipo_medidor VARCHAR(50),
    diametro_nominal INT,
    q3 DECIMAL(10,2),
    alcance VARCHAR(20),
    pma INT,
    tma INT,
    clase_sensibilidad VARCHAR(50),
    certificado_aprobacion VARCHAR(100),
    num_certificado VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_orden_servicio (orden_servicio),
    INDEX idx_num_serie (num_serie_medidor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para registrar los retiros de medidores
CREATE TABLE IF NOT EXISTS retiros_medidores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_servicio_id INT NOT NULL,
    orden_servicio VARCHAR(50) NOT NULL,
    medidor_retirado ENUM('SI', 'NO') NOT NULL,
    
    -- Información del medidor
    lectura_m3 DECIMAL(10,3) NULL,
    
    -- Reporte visual del medidor
    puntero_girando ENUM('SI', 'NO') NULL,
    medidor_con_precinto ENUM('SI', 'NO') NULL,
    visor_imposibilidad_lectura ENUM('SI', 'NO') NULL,
    
    -- Reporte visual del filtro
    medidor_tiene_filtro ENUM('SI', 'NO') NULL,
    filtro_buen_estado ENUM('SI', 'NO') NULL,
    solidos_retenidos_filtro ENUM('SI', 'NO') NULL,
    
    -- Información adicional
    info_caja_medidor TEXT NULL,
    observacion TEXT NULL,
    
    -- Foto de imposibilidad (si no se retiró)
    foto_imposibilidad VARCHAR(255) NULL,
    
    -- Metadata
    tecnico_responsable VARCHAR(100) NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (orden_servicio_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE,
    INDEX idx_orden_servicio (orden_servicio),
    INDEX idx_fecha_registro (fecha_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para la sesión temporal de OCs seleccionadas
CREATE TABLE IF NOT EXISTS sesiones_oc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    orden_servicio VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

