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
    lectura_m3 INT NULL,

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

    -- Campo simplificado para exportación
    tiene_foto ENUM('SI', 'NO') NOT NULL DEFAULT 'NO',

    -- Sistema de aislamiento de datos
    usuario_id INT NULL, -- Quién registró el retiro
    tecnico_responsable VARCHAR(100) NULL,
    estado_registro ENUM('activo', 'reabierto', 'reasignado') NOT NULL DEFAULT 'activo',
    usuario_reasignado_por INT NULL, -- Admin que reasignó
    fecha_reasignacion TIMESTAMP NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Metadata
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (orden_servicio_id) REFERENCES ordenes_servicio(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_reasignado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_orden_servicio (orden_servicio),
    INDEX idx_fecha_registro (fecha_registro),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_estado_registro (estado_registro),
    INDEX idx_fecha_asignacion (fecha_asignacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para la sesión temporal de OCs seleccionadas
CREATE TABLE IF NOT EXISTS sesiones_oc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    orden_servicio VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para auditoría de acciones del sistema
CREATE TABLE IF NOT EXISTS auditoria_retiros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    retiro_id INT NULL, -- NULL para acciones que no involucran un retiro específico
    usuario_id INT NOT NULL,
    accion ENUM(
        'login', 'logout',
        'busqueda_oc', 'intento_registro_oc', 'registro_oc',
        'consulta_registros', 'consulta_registro_detalle',
        'reasignacion_oc', 'reapertura_oc',
        'modificacion_registro', 'eliminacion_registro'
    ) NOT NULL,
    detalles TEXT NULL, -- Información adicional sobre la acción
    orden_servicio VARCHAR(50) NULL, -- OC involucrada (si aplica)
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (retiro_id) REFERENCES retiros_medidores(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_retiro_id (retiro_id),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_fecha_accion (fecha_accion),
    INDEX idx_accion (accion),
    INDEX idx_orden_servicio (orden_servicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de tipos de imposibilidad de retiro
CREATE TABLE IF NOT EXISTS tipos_imposibilidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    descripcion VARCHAR(100) NOT NULL,
    categoria ENUM('acceso', 'medidor', 'cliente', 'seguridad', 'otros') NOT NULL,
    activo ENUM('SI', 'NO') NOT NULL DEFAULT 'SI',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_categoria (categoria),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar tipos de imposibilidad predefinidos
INSERT INTO tipos_imposibilidad (codigo, descripcion, categoria) VALUES
('NIPLE', 'Se encontró conexión con niple', 'medidor'),
('OPOSICION', 'Usuario se opuso al retiro', 'cliente'),
('INTERIOR', 'Servicio en interior de la propiedad', 'acceso'),
('PELIGROSA', 'Zona peligrosa o de difícil acceso', 'seguridad'),
('NO_COINCIDE', 'Medidor no coincide con la orden', 'medidor'),
('SIN_CONTOMETRO', 'Sin contómetro o dispositivo de medición', 'medidor'),
('OBRA', 'Obra en progreso en la propiedad', 'acceso'),
('AUSENTE', 'Cliente/usuario ausente', 'cliente'),
('DANADO', 'Medidor dañado o averiado', 'medidor'),
('NO_LOCALIZADO', 'Medidor no localizado en la dirección', 'acceso'),
('OTROS', 'Otros motivos', 'otros');

-- Modificar tabla de retiros para incluir tipo de imposibilidad
ALTER TABLE retiros_medidores
ADD COLUMN tipo_imposibilidad_id INT NULL,
ADD COLUMN detalles_imposibilidad TEXT NULL,
ADD FOREIGN KEY (tipo_imposibilidad_id) REFERENCES tipos_imposibilidad(id),
ADD INDEX idx_tipo_imposibilidad (tipo_imposibilidad_id);

-- Tabla de usuarios para el sistema de autenticación
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    rol ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    ultimo_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_rol (rol),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuarios por defecto
INSERT INTO usuarios (username, password, nombre_completo, email, rol) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador del Sistema', 'admin@gaselag.com', 'admin'),
('tecnico1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Técnico de Retiro 1', 'tecnico1@gaselag.com', 'user'),
('tecnico2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Técnico de Retiro 2', 'tecnico2@gaselag.com', 'user');

