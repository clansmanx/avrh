<?php
// modules/pdi/index.php
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

// ===========================================
// DASHBOARD PERSONALIZADO POR PERFIL
// ===========================================

if (in_array($user_tipo, ['admin', 'rh'])) {
    // Visão do RH/Admin - Todos os PDIs
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
              ORDER BY p.data_criacao DESC";
    $stmt = $conn->query($query);
    $pdis = $stmt->fetchAll();
    
    // Estatísticas gerais
    $query_stats = "SELECT 
                        COUNT(*) as total_pdis,
                        SUM(CASE WHEN status = 'ativo' THEN 1 ELSE 0 END) as ativos,
                        SUM(CASE WHEN status = 'em_andamento' THEN 1 ELSE 0 END) as em_andamento,
                        SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as concluidos
                    FROM pdi";
    $stmt_stats = $conn->query($query_stats);
    $estatisticas = $stmt_stats->fetch();
    
} elseif ($user_tipo == 'gestor') {
    // 🔥 CORREÇÃO: Gestor vê PDIs onde ele é o gestor responsável (coluna: gestor_responsavel_id)
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
              WHERE p.gestor_responsavel_id = :gestor_id  -- 🔥 CORREÇÃO: coluna correta é gestor_responsavel_id
              ORDER BY p.data_criacao DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':gestor_id', $user_id);
    $stmt->execute();
    $pdis = $stmt->fetchAll();
    
    // Estatísticas da equipe
    $estatisticas = [
        'total_pdis' => count($pdis),
        'ativos' => count(array_filter($pdis, function($p) { return $p['status'] == 'ativo'; })),
        'em_andamento' => count(array_filter($pdis, function($p) { return $p['status'] == 'em_andamento'; })),
        'concluidos' => count(array_filter($pdis, function($p) { return $p['status'] == 'concluido'; }))
    ];
    
} else {
    // Visão do Colaborador - Seus próprios PDIs
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
              WHERE p.colaborador_id = :colaborador_id
              ORDER BY p.data_criacao DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':colaborador_id', $user_id);
    $stmt->execute();
    $pdis = $stmt->fetchAll();
    
    // Estatísticas pessoais
    $estatisticas = [
        'total_pdis' => count($pdis),
        'ativos' => count(array_filter($pdis, fn($p) => $p['status'] == 'ativo')),
        'em_andamento' => count(array_filter($pdis, fn($p) => $p['status'] == 'em_andamento')),
        'concluidos' => count(array_filter($pdis, fn($p) => $p['status'] == 'concluido'))
    ];
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-diagram-3"></i> Plano de Desenvolvimento Individual (PDI)</h2>
            <?php if (in_array($user_tipo, ['admin', 'rh', 'gestor'])): ?>
            <a href="criar.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Novo PDI
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Informação para gestor -->
<?php if ($user_tipo == 'gestor'): ?>
<div class="alert alert-info mb-3">
    <i class="bi bi-info-circle"></i> Você está vendo apenas os PDIs dos colaboradores que você gerencia.
</div>
<?php endif; ?>

<!-- Cards de estatísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6 class="card-title">Total PDIs</h6>
                <h2><?php echo $estatisticas['total_pdis'] ?? 0; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h6 class="card-title">Ativos</h6>
                <h2><?php echo $estatisticas['ativos'] ?? 0; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h6 class="card-title">Em Andamento</h6>
                <h2><?php echo $estatisticas['em_andamento'] ?? 0; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6 class="card-title">Concluídos</h6>
                <h2><?php echo $estatisticas['concluidos'] ?? 0; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Lista de PDIs -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">PDIs Cadastrados</h5>
    </div>
    <div class="card-body">
        <?php if (empty($pdis)): ?>
        <div class="alert alert-info text-center py-4">
            <i class="bi bi-diagram-3 fs-1 d-block mb-3"></i>
            <h5>Nenhum PDI encontrado</h5>
            <?php if (in_array($user_tipo, ['admin', 'rh', 'gestor'])): ?>
            <p>Clique em "Novo PDI" para começar.</p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Colaborador</th>
                        <th>Cargo/Depto</th>
                        <th>Gestor</th>
                        <th>Data Criação</th>
                        <th>Progresso</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pdis as $pdi): ?>
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
                            <small><?php echo htmlspecialchars($pdi['departamento_colaborador'] ?? '-'); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($pdi['gestor_nome']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($pdi['data_criacao'])); ?></td>
                        <td style="min-width: 150px;">
                            <div class="progress mb-1" style="height: 6px;">
                                <div class="progress-bar bg-success" 
                                     style="width: <?php echo $pdi['progresso_geral']; ?>%"></div>
                            </div>
                            <small class="text-muted">
                                Metas: <?php echo $pdi['metas_concluidas']; ?>/<?php echo $pdi['total_metas']; ?> | 
                                Ações: <?php echo $pdi['acoes_concluidas']; ?>/<?php echo $pdi['total_acoes']; ?>
                            </small>
                        </td>
                        <td>
                            <?php
                            $status_class = [
                                'ativo' => 'primary',
                                'em_andamento' => 'warning',
                                'concluido' => 'success',
                                'cancelado' => 'danger'
                            ][$pdi['status']] ?? 'secondary';
                            ?>
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
                            <a href="acompanhamentos.php?pdi_id=<?php echo $pdi['id']; ?>" 
                               class="btn btn-sm btn-warning" title="Acompanhamentos">
                                <i class="bi bi-calendar-check"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
