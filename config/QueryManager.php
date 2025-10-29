<?php
/**
 * Clase para manejo optimizado de consultas SQL
 * GASELAG - Sistema de Retiros de Medidores
 */

class QueryManager {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getConnection();
    }
    
    /**
     * Ejecuta consulta con parámetros preparados
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error en consulta SQL: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtiene un solo registro
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Obtiene múltiples registros
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Ejecuta consulta de inserción y retorna el ID
     */
    public function insert($sql, $params = []) {
        $this->execute($sql, $params);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Ejecuta consulta de actualización
     */
    public function update($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Ejecuta consulta de eliminación
     */
    public function delete($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Inicia transacción
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Confirma transacción
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Revierte transacción
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
}

/**
 * Clase para gestión de retiros optimizada
 */
class RetiroManager {
    private $queryManager;
    
    public function __construct() {
        $this->queryManager = new QueryManager();
    }
    
    /**
     * Busca orden de servicio por código
     */
    public function buscarOrdenServicio($codigoOC) {
        $sql = "SELECT * FROM ordenes_servicio WHERE orden_servicio = ?";
        return $this->queryManager->fetchOne($sql, [$codigoOC]);
    }
    
    /**
     * Obtiene retiros con filtros optimizados
     */
    public function obtenerRetiros($filtros = []) {
        $sql = "SELECT r.*, u.nombre_completo as tecnico_responsable,
                       o.cliente, o.direccion, o.num_serie_medidor,
                       ti.descripcion as tipo_imposibilidad_desc
                FROM retiros_medidores r
                LEFT JOIN usuarios u ON r.usuario_id = u.id
                INNER JOIN ordenes_servicio o ON r.orden_servicio_id = o.id
                LEFT JOIN tipos_imposibilidad ti ON r.tipo_imposibilidad_id = ti.id
                WHERE 1=1";
        
        $params = [];
        
        // Filtro por usuario (si es técnico)
        if (!isAdmin() && isset($filtros['usuario_id'])) {
            $sql .= " AND r.usuario_id = ?";
            $params[] = $filtros['usuario_id'];
        }
        
        // Filtro por fecha
        if (isset($filtros['fecha_inicio']) && isset($filtros['fecha_fin'])) {
            $sql .= " AND DATE(r.fecha_registro) BETWEEN ? AND ?";
            $params[] = $filtros['fecha_inicio'];
            $params[] = $filtros['fecha_fin'];
        }
        
        // Filtro por estado de retiro
        if (isset($filtros['medidor_retirado'])) {
            $sql .= " AND r.medidor_retirado = ?";
            $params[] = $filtros['medidor_retirado'];
        }
        
        // Filtro por tipo de imposibilidad
        if (isset($filtros['tipo_imposibilidad_id'])) {
            $sql .= " AND r.tipo_imposibilidad_id = ?";
            $params[] = $filtros['tipo_imposibilidad_id'];
        }
        
        // Filtro por técnico responsable
        if (isset($filtros['tecnico_responsable'])) {
            $sql .= " AND u.nombre_completo LIKE ?";
            $params[] = '%' . $filtros['tecnico_responsable'] . '%';
        }
        
        $sql .= " ORDER BY r.fecha_registro DESC";
        
        // Límite de resultados
        if (isset($filtros['limit'])) {
            $sql .= " LIMIT " . intval($filtros['limit']);
        }
        
        return $this->queryManager->fetchAll($sql, $params);
    }
    
    /**
     * Crea nuevo retiro
     */
    public function crearRetiro($datos) {
        $sql = "INSERT INTO retiros_medidores 
                (orden_servicio_id, orden_servicio, medidor_retirado, lectura_m3,
                 puntero_girando, medidor_con_precinto, visor_imposibilidad_lectura,
                 medidor_tiene_filtro, filtro_buen_estado, solidos_retenidos_filtro,
                 info_caja_medidor, observacion, foto_imposibilidad, tiene_foto,
                 usuario_id, tecnico_responsable, tipo_imposibilidad_id, detalles_imposibilidad)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $datos['orden_servicio_id'],
            $datos['orden_servicio'],
            $datos['medidor_retirado'],
            $datos['lectura_m3'] ?? null,
            $datos['puntero_girando'] ?? null,
            $datos['medidor_con_precinto'] ?? null,
            $datos['visor_imposibilidad_lectura'] ?? null,
            $datos['medidor_tiene_filtro'] ?? null,
            $datos['filtro_buen_estado'] ?? null,
            $datos['solidos_retenidos_filtro'] ?? null,
            $datos['info_caja_medidor'] ?? null,
            $datos['observacion'] ?? null,
            $datos['foto_imposibilidad'] ?? null,
            $datos['tiene_foto'] ?? 'NO',
            $datos['usuario_id'] ?? null,
            $datos['tecnico_responsable'] ?? null,
            $datos['tipo_imposibilidad_id'] ?? null,
            $datos['detalles_imposibilidad'] ?? null
        ];
        
        return $this->queryManager->insert($sql, $params);
    }
    
    /**
     * Actualiza retiro existente
     */
    public function actualizarRetiro($id, $datos) {
        $sql = "UPDATE retiros_medidores SET 
                medidor_retirado = ?, lectura_m3 = ?, observacion = ?,
                foto_imposibilidad = ?, tiene_foto = ?, tipo_imposibilidad_id = ?,
                detalles_imposibilidad = ?, updated_at = NOW()
                WHERE id = ?";
        
        $params = [
            $datos['medidor_retirado'],
            $datos['lectura_m3'] ?? null,
            $datos['observacion'] ?? null,
            $datos['foto_imposibilidad'] ?? null,
            $datos['tiene_foto'] ?? 'NO',
            $datos['tipo_imposibilidad_id'] ?? null,
            $datos['detalles_imposibilidad'] ?? null,
            $id
        ];
        
        return $this->queryManager->update($sql, $params);
    }
    
    /**
     * Obtiene estadísticas de retiros
     */
    public function obtenerEstadisticas($fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT 
                    COUNT(*) as total_retiros,
                    COUNT(CASE WHEN medidor_retirado = 'SI' THEN 1 END) as retiros_exitosos,
                    COUNT(CASE WHEN medidor_retirado = 'NO' THEN 1 END) as retiros_imposibilidad,
                    COUNT(CASE WHEN foto_imposibilidad IS NOT NULL AND foto_imposibilidad != '' THEN 1 END) as con_foto
                FROM retiros_medidores r";
        
        $params = [];
        
        if ($fechaInicio && $fechaFin) {
            $sql .= " WHERE DATE(fecha_registro) BETWEEN ? AND ?";
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        }
        
        return $this->queryManager->fetchOne($sql, $params);
    }
}

/**
 * Clase para gestión de usuarios optimizada
 */
class UsuarioManager {
    private $queryManager;
    
    public function __construct() {
        $this->queryManager = new QueryManager();
    }
    
    /**
     * Obtiene todos los usuarios activos
     */
    public function obtenerUsuariosActivos() {
        $sql = "SELECT id, username, nombre_completo, email, rol, ultimo_login
                FROM usuarios 
                WHERE estado = 'activo'
                ORDER BY nombre_completo";
        
        return $this->queryManager->fetchAll($sql);
    }
    
    /**
     * Obtiene usuario por ID
     */
    public function obtenerUsuarioPorId($id) {
        $sql = "SELECT * FROM usuarios WHERE id = ?";
        return $this->queryManager->fetchOne($sql, [$id]);
    }
    
    /**
     * Crea nuevo usuario
     */
    public function crearUsuario($datos) {
        $sql = "INSERT INTO usuarios (username, password, nombre_completo, email, rol, estado)
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $datos['username'],
            password_hash($datos['password'], PASSWORD_DEFAULT),
            $datos['nombre_completo'],
            $datos['email'] ?? null,
            $datos['rol'] ?? 'user',
            $datos['estado'] ?? 'activo'
        ];
        
        return $this->queryManager->insert($sql, $params);
    }
    
    /**
     * Actualiza usuario
     */
    public function actualizarUsuario($id, $datos) {
        $sql = "UPDATE usuarios SET 
                nombre_completo = ?, email = ?, rol = ?, estado = ?, updated_at = NOW()";
        
        $params = [
            $datos['nombre_completo'],
            $datos['email'] ?? null,
            $datos['rol'] ?? 'user',
            $datos['estado'] ?? 'activo'
        ];
        
        // Si se proporciona nueva contraseña
        if (!empty($datos['password'])) {
            $sql .= ", password = ?";
            $params[] = password_hash($datos['password'], PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        return $this->queryManager->update($sql, $params);
    }
}

/**
 * Clase para gestión de auditoría optimizada
 */
class AuditoriaManager {
    private $queryManager;
    
    public function __construct() {
        $this->queryManager = new QueryManager();
    }
    
    /**
     * Obtiene logs de auditoría con filtros
     */
    public function obtenerLogs($filtros = []) {
        $sql = "SELECT a.*, u.username, u.nombre_completo
                FROM auditoria_retiros a
                LEFT JOIN usuarios u ON a.usuario_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        // Filtro por usuario
        if (isset($filtros['usuario_id'])) {
            $sql .= " AND a.usuario_id = ?";
            $params[] = $filtros['usuario_id'];
        }
        
        // Filtro por acción
        if (isset($filtros['accion'])) {
            $sql .= " AND a.accion = ?";
            $params[] = $filtros['accion'];
        }
        
        // Filtro por fecha
        if (isset($filtros['fecha_inicio']) && isset($filtros['fecha_fin'])) {
            $sql .= " AND DATE(a.fecha_accion) BETWEEN ? AND ?";
            $params[] = $filtros['fecha_inicio'];
            $params[] = $filtros['fecha_fin'];
        }
        
        $sql .= " ORDER BY a.fecha_accion DESC";
        
        // Límite de resultados
        if (isset($filtros['limit'])) {
            $sql .= " LIMIT " . intval($filtros['limit']);
        }
        
        return $this->queryManager->fetchAll($sql, $params);
    }
    
    /**
     * Registra acción en auditoría
     */
    public function registrarAccion($retiroId, $userId, $accion, $detalles = '', $ordenServicio = null) {
        $sql = "INSERT INTO auditoria_retiros 
                (retiro_id, usuario_id, accion, detalles, orden_servicio, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $retiroId,
            $userId,
            $accion,
            $detalles,
            $ordenServicio,
            getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        return $this->queryManager->insert($sql, $params);
    }
}

?>
