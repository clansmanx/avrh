<?php
// modules/relatorios/dashboard_gestor.php
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

$auth->requirePermission(['gestor', 'admin', 'rh']);

$gestor_id = $auth->getUserId();
$user_tipo = $auth->getUserType();

// Se for admin/rh, pode escolher um gestor para ver
$gestor_selecionado = $_GET['gestor_id'] ?? $gestor_id;

// Se for admin/rh, buscar lista de gestores
$gestores = [];
if (in_array($user_tipo, ['admin', 'rh'])) {
    $query_gestores = "SELECT id, nome FROM usuarios WHERE tipo = 'gestor' AND ativo = 1 ORDER BY nome";
    $stmt_gestores = $conn->query($query_gestores);
    $gestores = $stmt_gestores->fetchAll();
}

// ===========================================
// 1. DADOS DA EQUIPE DO GESTOR
// ===========================================
$query_equipe = "SELECT 
            u.id,
            u.nome,
            u.email,
            u.foto_perfil,
            u.data_contratacao,
            c.nome as cargo,
            d.nome as departamento,
            e.nome as empresa,
            (SELECT AVG(nota_final) FROM avaliacoes WHERE avaliado_id = u.id AND status = 'concluida') as media_geral,
            (SELECT COUNT(*) FROM avaliacoes WHERE avaliado_id = u.id AND status = 'pendente') as avaliacoes_pendentes,
            (SELECT COUNT(*) FROM avaliacoes WHERE avaliado_id = u.id AND status = 'concluida') as avaliacoes_concluidas
          FROM usuarios u
          LEFT JOIN cargos c ON u.cargo_id = c.id
          LEFT JOIN departamentos d ON u.departamento_id = d.id
          LEFT JOIN empresas e ON u.empresa_id = e.id
          WHERE u.gestor_id = :gestor_id AND u.ativo = 1
          ORDER BY u.nome";

$stmt_equipe = $conn->prepare($query_equipe);
$stmt_equipe->bindParam(':gestor_id', $gestor_selecionado);
$stmt_equipe->execute();
$equipe = $stmt_equipe->fetchAll();

// ===========================================
// 2. ESTATÍSTICAS DA EQUIPE
// ===========================================
$total_equipe = count($equipe);
$total_avaliacoes = 0;
$total_concluidas = 0;
$soma_notas = 0;
$qtd_com_nota = 0;

foreach ($equipe as $membro) {
    $total_avaliacoes += $membro['avaliacoes_pendentes'] + $membro['avaliacoes_concluidas'];
    $total_concluidas += $membro['avaliacoes_concluidas'];
    if ($membro['media_geral']) {
        $soma_notas += $membro['media_geral'];
        $qtd_com_nota++;
    }
}

$media_equipe = $qtd_com_nota > 0 ? round($soma_notas / $qtd_com_nota, 1) : 0;
$taxa_conclusao = $total_avaliacoes > 0 ? round(($total_concluidas / $total_avaliacoes) * 100) : 0;

// ===========================================
// 3. AVALIAÇÕES PENDENTES DO GESTOR
// ===========================================
$query_pendentes = "SELECT a.*, u.nome as avaliado_nome, ci.nome as ciclo_nome, ci.data_fim
                    FROM avaliacoes a
                    JOIN usuarios u ON a.avaliado_id = u.id
                    JOIN ciclos_avaliacao ci ON a.ciclo_id = ci.id
                    WHERE a.avaliador_id = :gestor_id AND a.status = 'pendente'
                    ORDER BY ci.data_fim ASC";
$stmt_pendentes = $conn->prepare($query_pendentes);
$stmt_pendentes->bindParam(':gestor_id', $gestor_id);
$stmt_pendentes->execute();
$pendentes = $stmt_pendentes->fetchAll();

// ===========================================
// 4. TOP 3 COLABORADORES DA EQUIPE
// ===========================================
$top_equipe = array_slice($equipe, 0, 3);

require_once '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="bi bi-people-fill me-2"></i> Dashboard do Gestor</h2>
            <?php if (in_array($user_tipo, ['admin', 'rh'])): ?>
            <div class="d-flex">
                <select class="form-select me-2" id="gestorSelect" style="width: 250px;">
                    <option value="">Selecione um gestor...</option>
                    <?php foreach ($gestores as $g): ?>
                    <option value="<?php echo $g['id']; ?>" <?php echo $g['id'] == $gestor_selecionado ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($g['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" onclick="window.location.href='?gestor_id=' + document.getElementById('gestorSelect').value">
                    <i class="bi bi-search"></i> Ver
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cards de Resumo -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6 class="card-title">Total da Equipe</h6>
                <h2><?php echo $total_equipe; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6 class="card-title">Média da Equipe</h6>
                <h2><?php echo $media_equipe; ?>%</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h6 class="card-title">Avaliações Concluídas</h6>
                <h2><?php echo $total_concluidas; ?> / <?php echo $total_avaliacoes; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h6 class="card-title">Taxa de Conclusão</h6>
                <h2><?php echo $taxa_conclusao; ?>%</h2>
            </div>
        </div>
    </div>
</div>

<!-- Avaliações Pendentes -->
<?php if (!empty($pendentes)): ?>
<div class="card mb-4 border-warning">
    <div class="card-header bg-warning text-white">
        <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i> Avaliações Pendentes (Você precisa responder)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Avaliado</th>
                        <th>Ciclo</th>
                        <th>Prazo</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendentes as $p): 
                        $dias = floor((strtotime($p['data_fim']) - time()) / (60*60*24));
                        $classe_prazo = $dias < 0 ? 'text-danger fw-bold' : ($dias == 0 ? 'text-warning fw-bold' : '');
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['avaliado_nome']); ?></td>
                        <td><?php echo htmlspecialchars($p['ciclo_nome']); ?></td>
                        <td class="<?php echo $classe_prazo; ?>">
                            <?php echo $functions->formatDate($p['data_fim']); ?>
                            <?php if ($dias < 0): ?> (Atrasado)<?php endif; ?>
                        </td>
                        <td>
                            <a href="../avaliacoes/responder.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i> Responder
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Top Colaboradores -->
<?php if (!empty($top_equipe)): ?>
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="bi bi-trophy me-2"></i> Destaques da Equipe</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php foreach ($top_equipe as $index => $membro): ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100 border-<?php echo $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'info'); ?>">
                    <div class="card-body text-center">
                        <?php if ($membro['foto_perfil']): ?>
                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $membro['foto_perfil']; ?>" 
                             class="rounded-circle mb-3" width="80" height="80" style="object-fit: cover;">
                        <?php else: ?>
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" 
                             style="width: 80px; height: 80px;">
                            <i class="bi bi-person-circle fs-1"></i>
                        </div>
                        <?php endif; ?>
                        
                        <h5><?php echo htmlspecialchars($membro['nome']); ?></h5>
                        <p class="text-muted small"><?php echo htmlspecialchars($membro['cargo'] ?? 'Sem cargo'); ?></p>
                        
                        <?php if ($membro['media_geral']): ?>
                        <h3 class="text-<?php echo $index == 0 ? 'warning' : ($index == 1 ? 'secondary' : 'info'); ?>">
                            <?php echo number_format($membro['media_geral'], 1); ?>%
                        </h3>
                        <p>Média Geral</p>
                        <?php else: ?>
                        <p class="text-muted">Sem avaliações</p>
                        <?php endif; ?>
                        
                        <a href="../usuarios/visualizar.php?id=<?php echo $membro['id']; ?>" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> Ver perfil
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tabela da Equipe -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-table me-2"></i> Minha Equipe</h5>
    </div>
    <div class="card-body">
        <?php if (empty($equipe)): ?>
        <p class="text-muted text-center py-4">
            <i class="bi bi-people fs-1 d-block mb-3"></i>
            Nenhum colaborador na sua equipe.
        </p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Colaborador</th>
                        <th>Cargo</th>
                        <th>Departamento</th>
                        <th>Empresa</th>
                        <th>Média Geral</th>
                        <th>Avaliações</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipe as $membro): 
                        $progresso = $membro['avaliacoes_pendentes'] + $membro['avaliacoes_concluidas'] > 0 
                            ? round(($membro['avaliacoes_concluidas'] / ($membro['avaliacoes_pendentes'] + $membro['avaliacoes_concluidas'])) * 100) 
                            : 0;
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if ($membro['foto_perfil']): ?>
                                <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $membro['foto_perfil']; ?>" 
                                     class="rounded-circle me-2" width="30" height="30">
                                <?php else: ?>
                                <i class="bi bi-person-circle me-2"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($membro['nome']); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($membro['cargo'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($membro['departamento'] ?? '-'); ?></td>
                        <td>
                            <?php if ($membro['empresa']): ?>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($membro['empresa']); ?></span>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($membro['media_geral']): ?>
                            <span class="badge bg-<?php 
                                echo $membro['media_geral'] >= 80 ? 'success' : 
                                    ($membro['media_geral'] >= 60 ? 'warning' : 'danger'); 
                            ?>">
                                <?php echo number_format($membro['media_geral'], 1); ?>%
                            </span>
                            <?php else: ?>
                            <span class="badge bg-secondary">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-info">Pend: <?php echo $membro['avaliacoes_pendentes']; ?></span>
                            <span class="badge bg-success">Conc: <?php echo $membro['avaliacoes_concluidas']; ?></span>
                        </td>
                        <td style="width: 100px;">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $progresso; ?>%"></div>
                            </div>
                        </td>
                        <td>
                            <a href="../usuarios/visualizar.php?id=<?php echo $membro['id']; ?>" 
                               class="btn btn-sm btn-info" title="Visualizar">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('gestorSelect')?.addEventListener('change', function() {
    if (this.value) {
        window.location.href = '?gestor_id=' + this.value;
    }
});
</script>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
