<?php
// index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require_once 'includes/header.php';

// Obter conexão PDO
$conn = (new Database())->getConnection();

$user = $auth->getUser();
$estatisticas = $functions->getEstatisticasDashboard($user['id'], $user['tipo']);
$notificacoes = $functions->getNotificacoesNaoLidas($user['id']);
?>

<div class="row">
    <div class="col-12">
        <h2>Dashboard</h2>
        <p>Bem-vindo, <?php echo $user['nome']; ?>!</p>
    </div>
</div>

<!-- Cards de estatísticas -->
<div class="row mt-4">
    <?php if ($auth->hasPermission(['admin', 'rh'])): ?>
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total de Usuários</h6>
                        <h2 class="mb-0"><?php echo $estatisticas['geral']['total_usuarios'] ?? 0; ?></h2>
                    </div>
                    <i class="bi bi-people-fill fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Ciclos Ativos</h6>
                        <h2 class="mb-0"><?php echo $estatisticas['geral']['total_ciclos'] ?? 0; ?></h2>
                    </div>
                    <i class="bi bi-calendar-check-fill fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Avaliações Pendentes</h6>
                        <h2 class="mb-0"><?php echo $estatisticas['minhas_pendentes'] ?? 0; ?></h2>
                    </div>
                    <i class="bi bi-clock-history fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($auth->hasPermission('gestor')): ?>
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Minha Equipe</h6>
                        <h2 class="mb-0"><?php echo $estatisticas['equipe']['total_equipe'] ?? 0; ?></h2>
                    </div>
                    <i class="bi bi-people-fill fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Tabela de avaliações pendentes -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Minhas Avaliações Pendentes</h5>
            </div>
            <div class="card-body">
                <?php
                $query = "SELECT a.*, 
                            u.nome as avaliado_nome,
                            c.nome as ciclo_nome,
                            c.data_fim
                         FROM avaliacoes a
                         JOIN usuarios u ON a.avaliado_id = u.id
                         JOIN ciclos_avaliacao c ON a.ciclo_id = c.id
                         WHERE a.avaliador_id = :avaliador_id 
                           AND a.status = 'pendente'
                         ORDER BY c.data_fim ASC";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':avaliador_id', $user['id']);
                $stmt->execute();
                $avaliacoes_pendentes = $stmt->fetchAll();
                ?>
                
                <?php if (empty($avaliacoes_pendentes)): ?>
                    <p class="text-muted">Nenhuma avaliação pendente no momento.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Avaliado</th>
                                    <th>Ciclo</th>
                                    <th>Prazo</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($avaliacoes_pendentes as $av): ?>
                                <tr>
                                    <td><?php echo $av['avaliado_nome']; ?></td>
                                    <td><?php echo $av['ciclo_nome']; ?></td>
                                    <td><?php echo $functions->formatDate($av['data_fim']); ?></td>
                                    <td>
                                        <span class="badge bg-warning">Pendente</span>
                                    </td>
                                    <td>
                                        <a href="modules/avaliacoes/responder.php?id=<?php echo $av['id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i> Responder
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
    </div>
</div>

<!-- Ciclos ativos -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Ciclos de Avaliação Ativos</h5>
            </div>
            <div class="card-body">
                <?php
                $query = "SELECT c.*, 
                            (SELECT COUNT(*) FROM avaliacoes WHERE ciclo_id = c.id) as total_avaliacoes,
                            (SELECT COUNT(*) FROM avaliacoes WHERE ciclo_id = c.id AND status = 'concluida') as concluidas
                         FROM ciclos_avaliacao c
                         WHERE c.status = 'em_andamento'
                         ORDER BY c.data_fim ASC
                         LIMIT 5";
                
                $stmt = $conn->query($query);
                $ciclos_ativos = $stmt->fetchAll();
                ?>
                
                <?php if (empty($ciclos_ativos)): ?>
                    <p class="text-muted">Nenhum ciclo ativo no momento.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Período</th>
                                    <th>Progresso</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ciclos_ativos as $ciclo): 
                                    $progresso = $ciclo['total_avaliacoes'] > 0 
                                        ? round(($ciclo['concluidas'] / $ciclo['total_avaliacoes']) * 100) 
                                        : 0;
                                ?>
                                <tr>
                                    <td><?php echo $ciclo['nome']; ?></td>
                                    <td>
                                        <?php echo $functions->formatDate($ciclo['data_inicio']); ?> até 
                                        <?php echo $functions->formatDate($ciclo['data_fim']); ?>
                                    </td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo $progresso; ?>%"
                                                 aria-valuenow="<?php echo $progresso; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $progresso; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Em Andamento</span>
                                    </td>
                                    <td>
                                        <a href="modules/ciclos/visualizar.php?id=<?php echo $ciclo['id']; ?>" 
                                           class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i> Ver
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
    </div>
</div>

<?php 
require_once 'includes/footer.php';
ob_end_flush(); 
?>
