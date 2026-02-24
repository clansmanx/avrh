<?php
// modules/calibracao/index.php
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

$auth->requirePermission(['admin', 'rh']);

// Buscar ciclos finalizados ou em andamento para calibração
$query = "SELECT c.*, 
            (SELECT COUNT(*) FROM avaliacoes WHERE ciclo_id = c.id) as total_avaliacoes,
            (SELECT COUNT(*) FROM calibracao WHERE ciclo_id = c.id) as total_calibradas
          FROM ciclos_avaliacao c
          WHERE c.status IN ('finalizado', 'em_andamento')
          ORDER BY c.data_fim DESC";

$stmt = $conn->query($query);
$ciclos = $stmt->fetchAll();

// Estatísticas de calibração
$query_stats = "SELECT 
                  AVG(nota_calibrada - nota_original) as media_ajuste,
                  COUNT(*) as total_calibracoes,
                  SUM(CASE WHEN nota_calibrada > nota_original THEN 1 ELSE 0 END) as ajustes_positivos,
                  SUM(CASE WHEN nota_calibrada < nota_original THEN 1 ELSE 0 END) as ajustes_negativos
                FROM calibracao";
$stmt_stats = $conn->query($query_stats);
$stats = $stmt_stats->fetch();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Calibração de Avaliações</h2>
            <span class="badge bg-info p-2">Comitê de Calibração</span>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <!-- Lista de Ciclos para Calibração -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Ciclos Disponíveis para Calibração</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($ciclos)): ?>
                        <p class="text-muted text-center py-4">
                            <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                            Nenhum ciclo disponível para calibração no momento.
                        </p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Ciclo</th>
                                        <th>Período</th>
                                        <th>Avaliações</th>
                                        <th>Calibradas</th>
                                        <th>Progresso</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ciclos as $ciclo): 
                                        $progresso = $ciclo['total_avaliacoes'] > 0 
                                            ? round(($ciclo['total_calibradas'] / $ciclo['total_avaliacoes']) * 100) 
                                            : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($ciclo['nome']); ?></strong><br>
                                            <small class="text-muted"><?php echo $ciclo['tipo']; ?>°</small>
                                        </td>
                                        <td>
                                            <?php echo $functions->formatDate($ciclo['data_inicio']); ?><br>
                                            <small>até <?php echo $functions->formatDate($ciclo['data_fim']); ?></small>
                                        </td>
                                        <td class="text-center"><?php echo $ciclo['total_avaliacoes']; ?></td>
                                        <td class="text-center"><?php echo $ciclo['total_calibradas']; ?></td>
                                        <td style="width: 150px;">
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-warning" role="progressbar" 
                                                     style="width: <?php echo $progresso; ?>%"></div>
                                            </div>
                                            <small><?php echo $progresso; ?>%</small>
                                        </td>
                                        <td>
                                            <a href="ciclo.php?id=<?php echo $ciclo['id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-graph-up"></i> Calibrar
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

            <div class="col-md-4">
                <!-- Estatísticas de Calibração -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Estatísticas de Calibração</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h3><?php echo number_format($stats['media_ajuste'] ?? 0, 2); ?></h3>
                            <p class="text-muted">Média de Ajuste</p>
                        </div>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <h4 class="text-success"><?php echo $stats['ajustes_positivos'] ?? 0; ?></h4>
                                    <small>Ajustes Positivos</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <h4 class="text-danger"><?php echo $stats['ajustes_negativos'] ?? 0; ?></h4>
                                    <small>Ajustes Negativos</small>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mt-3">
                            <p><strong>Total de Calibrações:</strong> <?php echo $stats['total_calibracoes'] ?? 0; ?></p>
                            <?php
                            $query_ultima = "SELECT data_calibracao FROM calibracao ORDER BY data_calibracao DESC LIMIT 1";
                            $stmt_ultima = $conn->query($query_ultima);
                            $ultima = $stmt_ultima->fetch();
                            ?>
                            <p><strong>Última Calibração:</strong> 
                                <?php echo $ultima ? $functions->formatDate($ultima['data_calibracao'], 'd/m/Y H:i') : 'Nenhuma'; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Dicas de Calibração -->
                <div class="card bg-light">
                    <div class="card-header">
                        <h5>Dicas para Calibração</h5>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li class="mb-2">📊 Compare avaliações de colaboradores similares</li>
                            <li class="mb-2">⚖️ Identifique gestores muito "bonzinhos" ou "rígidos"</li>
                            <li class="mb-2">💬 Discuta com fatos e exemplos, não apenas intuição</li>
                            <li class="mb-2">📝 Registre sempre a justificativa do ajuste</li>
                            <li class="mb-2">🎯 Busque consenso no comitê</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once '../../includes/footer.php';
ob_end_flush();
?>
