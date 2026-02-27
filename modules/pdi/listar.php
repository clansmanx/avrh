<?php
// modules/pdi/listar.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$database = new Database();
$conn = $database->getConnection();
$auth = new Auth();
$functions = new Functions();

$auth->requireLogin();

$user_id = $auth->getUserId();
$user_tipo = $auth->getUserType();
$filtro = $_GET['filtro'] ?? 'todos';

// ===========================================
// FILTROS
// ===========================================
$where = "WHERE 1=1";
$params = [];

// Filtro por status
if ($filtro != 'todos') {
    $where .= " AND p.status = :status";
    $params[':status'] = $filtro;
}

// Filtro por gestor (para gestores)
if ($user_tipo == 'gestor' && !in_array($user_tipo, ['admin', 'rh'])) {
    $where .= " AND p.gestor_responsavel_id = :gestor_id";
    $params[':gestor_id'] = $user_id;
}

// Filtro "meus" (para colaborador ver seus PDIs)
if (isset($_GET['meus']) && $_GET['meus'] == 'true') {
    $where .= " AND p.colaborador_id = :colaborador_id";
    $params[':colaborador_id'] = $user_id;
}

// Buscar PDIs com join
$query = "SELECT p.*, 
                 u_colab.nome as colaborador_nome,
                 u_colab.foto_perfil as colaborador_foto,
                 u_gestor.nome as gestor_nome,
                 c.nome as cargo_colaborador,
                 d.nome as departamento_colaborador,
                 (SELECT COUNT(*) FROM pdi_metas WHERE pdi_id = p.id) as total_metas,
                 (SELECT COUNT(*) FROM pdi_metas WHERE pdi_id = p.id AND status = 'concluida') as metas_concluidas,
                 (SELECT COUNT(*) FROM pdi_acoes WHERE pdi_id = p.id) as total_acoes,
                 (SELECT COUNT(*) FROM pdi_acoes WHERE pdi_id = p.id AND status = 'concluida') as acoes_concluidas
          FROM pdi p
          JOIN usuarios u_colab ON p.colaborador_id = u_colab.id
          JOIN usuarios u_gestor ON p.gestor_responsavel_id = u_gestor.id
          LEFT JOIN cargos c ON u_colab.cargo_id = c.id
          LEFT JOIN departamentos d ON u_colab.departamento_id = d.id
          $where
          ORDER BY p.data_criacao DESC";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$pdis = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-list-task"></i> Lista de PDIs</h2>
            <div>
                <a href="criar.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Novo PDI
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <select class="form-select" name="filtro">
                            <option value="todos" <?php echo $filtro == 'todos' ? 'selected' : ''; ?>>Todos os status</option>
                            <option value="ativo" <?php echo $filtro == 'ativo' ? 'selected' : ''; ?>>Ativos</option>
                            <option value="em_andamento" <?php echo $filtro == 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                            <option value="concluido" <?php echo $filtro == 'concluido' ? 'selected' : ''; ?>>Concluídos</option>
                            <option value="cancelado" <?php echo $filtro == 'cancelado' ? 'selected' : ''; ?>>Cancelados</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de PDIs -->
        <?php if (empty($pdis)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-diagram-3 fs-1 d-block mb-3"></i>
            <h4>Nenhum PDI encontrado</h4>
            <p>Clique em "Novo PDI" para começar.</p>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Colaborador</th>
                                <th>Cargo/Depto</th>
                                <th>Gestor</th>
                                <th>Título</th>
                                <th>Criação</th>
                                <th>Progresso</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pdis as $pdi): 
                                $status_class = [
                                    'ativo' => 'primary',
                                    'em_andamento' => 'warning',
                                    'concluido' => 'success',
                                    'cancelado' => 'danger'
                                ][$pdi['status']] ?? 'secondary';
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($pdi['colaborador_foto']): ?>
                                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $pdi['colaborador_foto']; ?>" 
                                             class="rounded-circle me-2" width="30" height="30" style="object-fit: cover;">
                                        <?php else: ?>
                                        <i class="bi bi-person-circle me-2"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($pdi['colaborador_nome']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($pdi['cargo_colaborador'] ?? '-'); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($pdi['departamento_colaborador'] ?? '-'); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($pdi['gestor_nome']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($pdi['titulo']); ?></strong>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($pdi['data_criacao'])); ?></td>
                                <td style="min-width: 120px;">
                                    <div class="progress mb-1" style="height: 6px;">
                                        <div class="progress-bar bg-success" 
                                             style="width: <?php echo $pdi['progresso_geral'] ?? 0; ?>%"></div>
                                    </div>
                                    <small class="text-muted">
                                        Metas: <?php echo $pdi['metas_concluidas']; ?>/<?php echo $pdi['total_metas']; ?> | 
                                        Ações: <?php echo $pdi['acoes_concluidas']; ?>/<?php echo $pdi['total_acoes']; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($pdi['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="visualizar.php?id=<?php echo $pdi['id']; ?>" 
                                       class="btn btn-sm btn-info" title="Visualizar">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (in_array($user_tipo, ['admin', 'rh']) || $user_id == $pdi['gestor_responsavel_id']): ?>
                                    <a href="editar.php?id=<?php echo $pdi['id']; ?>" 
                                       class="btn btn-sm btn-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
