-- ========================================
-- MIGRACIÓN DE SEGURIDAD - GASELAG RETIROS
-- Fecha: 31 de octubre de 2025
-- Descripción: Agrega sistema de seguridad mejorado
-- ========================================

USE gaselag_retiros;

-- ========================================
-- 1. MODIFICAR TABLA USUARIOS
-- ========================================

-- Agregar columnas de seguridad si no existen
SET @dbname = DATABASE();
SET @tablename = 'usuarios';

-- intentos_fallidos
SET @columnname = 'intentos_fallidos';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
   AND TABLE_NAME = @tablename
   AND COLUMN_NAME = @columnname) > 0,
  'SELECT "Column intentos_fallidos already exists" AS msg;',
  'ALTER TABLE usuarios ADD COLUMN intentos_fallidos INT DEFAULT 0;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- bloqueado_hasta
SET @columnname = 'bloqueado_hasta';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
   AND TABLE_NAME = @tablename
   AND COLUMN_NAME = @columnname) > 0,
  'SELECT "Column bloqueado_hasta already exists" AS msg;',
  'ALTER TABLE usuarios ADD COLUMN bloqueado_hasta TIMESTAMP NULL;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ultimo_intento
SET @columnname = 'ultimo_intento';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
   AND TABLE_NAME = @tablename
   AND COLUMN_NAME = @columnname) > 0,
  'SELECT "Column ultimo_intento already exists" AS msg;',
  'ALTER TABLE usuarios ADD COLUMN ultimo_intento TIMESTAMP NULL;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- force_password_change
SET @columnname = 'force_password_change';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
   AND TABLE_NAME = @tablename
   AND COLUMN_NAME = @columnname) > 0,
  'SELECT "Column force_password_change already exists" AS msg;',
  'ALTER TABLE usuarios ADD COLUMN force_password_change BOOLEAN DEFAULT TRUE;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- password_changed_at
SET @columnname = 'password_changed_at';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
   AND TABLE_NAME = @tablename
   AND COLUMN_NAME = @columnname) > 0,
  'SELECT "Column password_changed_at already exists" AS msg;',
  'ALTER TABLE usuarios ADD COLUMN password_changed_at TIMESTAMP NULL;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- session_timeout
SET @columnname = 'session_timeout';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
   AND TABLE_NAME = @tablename
   AND COLUMN_NAME = @columnname) > 0,
  'SELECT "Column session_timeout already exists" AS msg;',
  'ALTER TABLE usuarios ADD COLUMN session_timeout INT DEFAULT 7200;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- last_activity
SET @columnname = 'last_activity';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
   AND TABLE_NAME = @tablename
   AND COLUMN_NAME = @columnname) > 0,
  'SELECT "Column last_activity already exists" AS msg;',
  'ALTER TABLE usuarios ADD COLUMN last_activity TIMESTAMP NULL;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ========================================
-- 2. CREAR TABLA DISPOSITIVOS_AUTORIZADOS
-- ========================================

CREATE TABLE IF NOT EXISTS dispositivos_autorizados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    device_fingerprint VARCHAR(64) NOT NULL,
    device_name VARCHAR(255) DEFAULT 'Dispositivo Desconocido',
    device_type ENUM('mobile', 'tablet', 'desktop') DEFAULT 'desktop',
    user_agent TEXT,
    ip_address VARCHAR(45),
    first_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_uso TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_device (usuario_id, device_fingerprint),
    INDEX idx_usuario_activo (usuario_id, activo),
    INDEX idx_fingerprint (device_fingerprint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 3. CREAR TABLA LOGIN_ATTEMPTS
-- ========================================

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    success BOOLEAN DEFAULT FALSE,
    blocked BOOLEAN DEFAULT FALSE,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username_time (username, attempt_time),
    INDEX idx_ip_time (ip_address, attempt_time),
    INDEX idx_success (success),
    INDEX idx_attempt_time (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 4. CREAR TABLA PASSWORD_HISTORY
-- ========================================

CREATE TABLE IF NOT EXISTS password_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_created (usuario_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 5. CREAR/ACTUALIZAR USUARIOS DE PRUEBA
-- ========================================

-- Usuario Admin (si no existe)
INSERT IGNORE INTO usuarios (username, password, nombre_completo, email, rol, force_password_change, session_timeout, estado)
VALUES (
    'admin',
    '$2y$10$QuXFSxn0ZmGvxGj5ldxdx.GcLwwOSaw0ax4QZlcA8oM.lhSplRfkm', -- password = "password"
    'Administrador Principal',
    'admin@gaselag.com',
    'admin',
    TRUE,
    1800, -- 30 minutos
    'activo'
);

-- Técnico de prueba 1 (DNI: 12345678)
INSERT IGNORE INTO usuarios (username, password, nombre_completo, email, rol, force_password_change, session_timeout, estado)
VALUES (
    '12345678',
    '$2y$10$Yj/D/EgRkQGuDevH0WeoquDfec4PQY3I0oDIuDokEdnonNHChLwVW', -- PIN = "1234"
    'Técnico de Prueba 1',
    'tecnico1@gaselag.com',
    'user',
    TRUE,
    7200, -- 2 horas
    'activo'
);

-- Técnico de prueba 2 (DNI: 87654321)
INSERT IGNORE INTO usuarios (username, password, nombre_completo, email, rol, force_password_change, session_timeout, estado)
VALUES (
    '87654321',
    '$2y$10$Yj/D/EgRkQGuDevH0WeoquDfec4PQY3I0oDIuDokEdnonNHChLwVW', -- PIN = "1234"
    'Técnico de Prueba 2',
    'tecnico2@gaselag.com',
    'user',
    TRUE,
    7200, -- 2 horas
    'activo'
);

-- ========================================
-- 6. LIMPIAR DATOS DE PRUEBA (OPCIONAL)
-- ========================================

-- Resetear contadores de intentos fallidos
UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE 1=1;

-- ========================================
-- MIGRACIÓN COMPLETADA
-- ========================================

SELECT '========================================' AS '';
SELECT 'MIGRACIÓN DE SEGURIDAD COMPLETADA' AS 'ESTADO';
SELECT '========================================' AS '';
SELECT 'Tablas creadas/actualizadas:' AS '';
SELECT '  - usuarios (campos de seguridad agregados)' AS '';
SELECT '  - dispositivos_autorizados' AS '';
SELECT '  - login_attempts' AS '';
SELECT '  - password_history' AS '';
SELECT '' AS '';
SELECT 'Usuarios de prueba creados:' AS '';
SELECT '  - admin / password' AS '';
SELECT '  - 12345678 / 1234' AS '';
SELECT '  - 87654321 / 1234' AS '';
SELECT '' AS '';
SELECT '⚠️  Todos deben cambiar contraseña en primer login' AS 'IMPORTANTE';
SELECT '========================================' AS '';
