<?php
require_once '../config/database.php';

// Verificar autenticación y rol de administrador
requireRole(['admin']);

// Obtener información del usuario actual
$currentUser = getCurrentUser();

// Procesar acciones
$action = $_GET['action'] ?? '';
$tipoId = $_GET['id'] ?? 0;

// Crear nuevo tipo de imposibilidad
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_tipo'])) {
    $codigo = trim($_POST['codigo']);
    $descripcion = trim($_POST['descripcion']);
    $categoria = trim($_POST['categoria']);

    if (createTipoImposibilidad($codigo, $descripcion, $categoria, $_SESSION['user_id'])) {
        $success = "Tipo de imposibilidad creado correctamente";
    } else {
        $error = "Error al crear el tipo de imposibilidad";
    }
}

// Actualizar tipo de imposibilidad
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_tipo'])) {
    $id = $_POST['id'];
    $codigo = trim($_POST['codigo']);
    $descripcion = trim($_POST['descripcion']);
    $categoria = trim($_POST['categoria']);
    $activo = $_POST['activo'];

    if (updateTipoImposibilidad($id, $codigo, $descripcion, $categoria, $activo, $_SESSION['user_id'])) {
        $success = "Tipo de imposibilidad actualizado correctamente";
    } else {
        $error = "Error al actualizar el tipo de imposibilidad";
    }
}

// Eliminar (desactivar) tipo de imposibilidad
if ($action === 'desactivar' && $tipoId) {
    if (deleteTipoImposibilidad($tipoId, $_SESSION['user_id'])) {
        $success = "Tipo de imposibilidad desactivado correctamente";
    } else {
        $error = "Error al desactivar el tipo de imposibilidad";
    }
}

// Obtener datos para edición
$editTipo = null;
if ($action === 'editar' && $tipoId) {
    $editTipo = getTipoImposibilidad($tipoId);
    if (!$editTipo) {
        $error = "Tipo de imposibilidad no encontrado";
    }
}

// Obtener todos los tipos de imposibilidad
$tiposImposibilidad = getTiposImposibilidad();

// Obtener estadísticas de uso por tipo
$pdo = getConnection();
try {
    $statsSql = "SELECT
                    ti.descripcion,
                    ti.categoria,
                    COUNT(r.id) as cantidad_usos,
                    MAX(r.fecha_registro) as ultimo_uso
                 FROM tipos_imposibilidad ti
                 LEFT JOIN retiros_medidores r ON ti.id = r.tipo_imposibilidad_id AND r.medidor_retirado = 'NO'
                 GROUP BY ti.id, ti.descripcion, ti.categoria
                 ORDER BY cantidad_usos DESC";
    $statsStmt = $pdo->prepare($statsSql);
    $statsStmt->execute();
    $estadisticas = $statsStmt->fetchAll();
} catch (PDOException $e) {
    $estadisticas = [];
}

$pageTitle = 'Gestión de Imposibilidad - Sistema GASELAG';
require_once '../includes/header.php';
?>

<style>
        .tipo-card {
            transition: box-shadow 0.2s;
        }
        .tipo-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .categoria-badge {
            font-size: 0.75rem;
        }
        .stats-badge {
            font-size: 0.75rem;
        }
</style>

<div class="container-fluid py-4">

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <span class="navbar-text">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($currentUser['nombre_completo']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="navbar-text">
                            <i class="bi bi-shield-check ms-3 me-1"></i>
                            <span class="badge bg-warning text-dark">Administrador</span>
                        </span>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i>Gestión
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../index.php">
                                <i class="bi bi-house me-2"></i>Panel Principal
                            </a></li>
                            <li><a class="dropdown-item" href="gestion_retiros.php">
                                <i class="bi bi-clipboard-check me-2"></i>Retiros
                            </a></li>
                            <li><a class="dropdown-item" href="gestion_usuarios_mejorado.php">
                                <i class="bi bi-people me-2"></i>Usuarios
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">
                            <i class="bi bi-exclamation-triangle text-primary me-2"></i>
                            Gestión de Tipos de Imposibilidad
                        </h2>
                        <p class="text-muted mb-0">Administrar motivos de imposibilidad de retiro</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="bi bi-plus-circle me-2"></i>Agregar Tipo
                    </button>
                </div>
            </div>
        </div>

        <!-- Alertas -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas generales -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-primary"><?php echo count($tiposImposibilidad); ?></h5>
                        <p class="card-text mb-0">Tipos Activos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-success">
                            <?php echo array_sum(array_column($estadisticas, 'cantidad_usos')); ?>
                        </h5>
                        <p class="card-text mb-0">Total Usos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-info">
                            <?php echo count(array_filter($estadisticas, function($stat) { return $stat['cantidad_usos'] > 0; })); ?>
                        </h5>
                        <p class="card-text mb-0">En Uso</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de tipos de imposibilidad -->
        <div class="row">
            <?php foreach ($tiposImposibilidad as $tipo): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card tipo-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">
                                        <?php
                                        $categoriaIcon = match($tipo['categoria']) {
                                            'acceso' => '🚪',
                                            'medidor' => '⚡',
                                            'cliente' => '👤',
                                            'seguridad' => '⚠️',
                                            'otros' => '📋',
                                            default => '❓'
                                        };
                                        echo "$categoriaIcon " . htmlspecialchars($tipo['descripcion']);
                                        ?>
                                    </h6>
                                    <small class="text-muted">Código: <?= htmlspecialchars($tipo['codigo']) ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success categoria-badge">
                                        <?= ucfirst($tipo['categoria']) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Estadísticas de uso -->
                            <?php
                            $tipoStats = array_filter($estadisticas, function($stat) use ($tipo) {
                                return $stat['descripcion'] === $tipo['descripcion'];
                            });
                            $tipoStats = reset($tipoStats);
                            ?>
                            <?php if ($tipoStats): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="stats-badge">Usos: <strong><?= $tipoStats['cantidad_usos'] ?></strong></span>
                                        <?php if ($tipoStats['ultimo_uso']): ?>
                                            <small class="text-muted">
                                                Último: <?= date('d/m/Y', strtotime($tipoStats['ultimo_uso'])) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <span class="stats-badge text-muted">Sin uso registrado</span>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary flex-grow-1"
                                        onclick="editTipo(<?php echo $tipo['id']; ?>, '<?= htmlspecialchars($tipo['codigo']) ?>', '<?= htmlspecialchars($tipo['descripcion']) ?>', '<?= htmlspecialchars($tipo['categoria']) ?>')"
                                        title="Editar tipo">
                                    <i class="bi bi-pencil me-1"></i>Editar
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="deleteTipo(<?php echo $tipo['id']; ?>, '<?= htmlspecialchars($tipo['descripcion']) ?>')"
                                        title="Desactivar tipo">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Modal para crear tipo -->
        <div class="modal fade" id="createModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle me-2"></i>Agregar Tipo de Imposibilidad
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="codigo" class="form-label">Código *</label>
                                <input type="text" class="form-control" id="codigo" name="codigo"
                                       placeholder="Ej: NIPLE, OPOSICION" required maxlength="20">
                                <div class="form-text">Código único (máximo 20 caracteres)</div>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción *</label>
                                <input type="text" class="form-control" id="descripcion" name="descripcion"
                                       placeholder="Ej: Se encontró conexión con niple" required>
                            </div>
                            <div class="mb-3">
                                <label for="categoria" class="form-label">Categoría *</label>
                                <select class="form-select" id="categoria" name="categoria" required>
                                    <option value="acceso">🚪 Acceso</option>
                                    <option value="medidor">⚡ Medidor</option>
                                    <option value="cliente">👤 Cliente</option>
                                    <option value="seguridad">⚠️ Seguridad</option>
                                    <option value="otros">📋 Otros</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" name="crear_tipo" class="btn btn-primary">Crear Tipo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal para editar tipo -->
        <div class="modal fade" id="editModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil me-2"></i>Editar Tipo de Imposibilidad
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="edit_codigo" class="form-label">Código *</label>
                                <input type="text" class="form-control" id="edit_codigo" name="codigo"
                                       placeholder="Ej: NIPLE, OPOSICION" required maxlength="20">
                            </div>
                            <div class="mb-3">
                                <label for="edit_descripcion" class="form-label">Descripción *</label>
                                <input type="text" class="form-control" id="edit_descripcion" name="descripcion"
                                       placeholder="Ej: Se encontró conexión con niple" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_categoria" class="form-label">Categoría *</label>
                                <select class="form-select" id="edit_categoria" name="categoria" required>
                                    <option value="acceso">🚪 Acceso</option>
                                    <option value="medidor">⚡ Medidor</option>
                                    <option value="cliente">👤 Cliente</option>
                                    <option value="seguridad">⚠️ Seguridad</option>
                                    <option value="otros">📋 Otros</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="edit_activo" class="form-label">Estado</label>
                                <select class="form-select" id="edit_activo" name="activo" required>
                                    <option value="SI">✅ Activo</option>
                                    <option value="NO">❌ Inactivo</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" name="actualizar_tipo" class="btn btn-primary">Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editTipo(id, codigo, descripcion, categoria) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_codigo').value = codigo;
            document.getElementById('edit_descripcion').value = descripcion;
            document.getElementById('edit_categoria').value = categoria;
            document.getElementById('edit_activo').value = 'SI';

            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function deleteTipo(id, descripcion) {
            if (confirm(`¿Está seguro que desea desactivar el tipo "${descripcion}"?\n\nLos registros existentes se mantendrán pero no se podrá usar en nuevos formularios.`)) {
                window.location.href = `?action=desactivar&id=${id}`;
            }
        }

        // Auto-recargar después de acciones exitosas
        <?php if (isset($success)): ?>
            setTimeout(function() {
                location.reload();
            }, 2000);
        <?php endif; ?>
    </script>

<?php require_once '../includes/footer.php'; ?>
