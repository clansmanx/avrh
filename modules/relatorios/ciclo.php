<?php
// modules/relatorios/ciclo.php
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

$auth->requirePermission(['admin', 'rh', 'gestor']);

$ciclo_id = $_GET['id'] ?? 0;

if (!$ciclo_id) {
    $_SESSION['error'] = "ID do ciclo não fornecido";
    ob_end_clean();
    header('Location: ../ciclos/');
    exit;
}

// Buscar dados do ciclo
$query = "SELECT * FROM ciclos_avaliacao WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $ciclo_id);
$stmt->execute();
$ciclo = $stmt->fetch();

if (!$ciclo) {
    $_SESSION['error'] = "Ciclo não encontrado";
    ob_end_clean();
    header('Location: ../ciclos/');
    exit;
}

// Buscar avaliações do ciclo
$query_avaliacoes = "SELECT a.*, 
                            u.nome as avaliado_nome,
                            u.email as avaliado_email,
                            c.nome as cargo_nome,
                            d.nome as departamento_nome,
                            g.nome as gestor_nome
                     FROM avaliacoes a
                     JOIN usuarios u ON a.avaliado_id = u.id
                     LEFT JOIN cargos c ON u.cargo_id = c.id
                     LEFT JOIN departamentos d ON u.departamento_id = d.id
                     LEFT JOIN usuarios g ON u.gestor_id = g.id
                     WHERE a.ciclo_id = :ciclo_id AND a.status = 'concluida'
                     ORDER BY a.nota_final DESC";

$stmt_avaliacoes = $conn->prepare($query_avaliacoes);
$stmt_avaliacoes->bindParam(':ciclo_id', $ciclo_id);
$stmt_avaliacoes->execute();
$avaliacoes = $stmt_avaliacoes->fetchAll();

// Estatísticas gerais
$total_avaliacoes = count($avaliacoes);
$soma_notas = 0;
$notas = [];

foreach ($avaliacoes as $av) {
    $soma_notas += $av['nota_final'];
    $notas[] = $av['nota_final'];
}

$media_geral = $total_avaliacoes > 0 ? $soma_notas / $total_avaliacoes : 0;
$maior_nota = $total_avaliacoes > 0 ? max($notas) : 0;
$menor_nota = $total_avaliacoes > 0 ? min($notas) : 0;

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Relatório do Ciclo: <?php echo htmlspecialchars($ciclo['nome']); ?></h2>
            <a href="../ciclos/" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Informações do Ciclo -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Informações do Ciclo</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Período:</strong><br>
                        <?php echo $functions->formatDate($ciclo['data_inicio']); ?> até<br>
                        <?php echo $functions->formatDate($ciclo['data_fim']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Tipo:</strong><br>
                        <span class="badge bg-info"><?php echo $ciclo['tipo']; ?>°</span>
                    </div>
                    <div class="col-md-3">
                        <strong>Total Avaliações:</strong><br>
                        <span class="badge bg-primary"><?php echo $total_avaliacoes; ?></span>
                    </div>
                    <div class="col-md-3">
                        <strong>Status:</strong><br>
                        <span class="badge bg-<?php echo $ciclo['status'] == 'finalizado' ? 'success' : 'warning'; ?>">
                            <?php echo ucfirst($ciclo['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h6 class="card-title">Média Geral</h6>
                        <h2><?php echo number_format($media_geral, 2); ?>%</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h6 class="card-title">Maior Nota</h6>
                        <h2><?php echo number_format($maior_nota, 2); ?>%</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h6 class="card-title">Menor Nota</h6>
                        <h2><?php echo number_format($menor_nota, 2); ?>%</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ranking dos Colaboradores -->
        <div class="card">
            <div class="card-header">
                <h5>Ranking dos Colaboradores</h5>
            </div>
            <div class="card-body">
                <?php if (empty($avaliacoes)): ?>
                <p class="text-muted text-center py-4">
                    <i class="bi bi-bar-chart fs-1 d-block mb-3"></i>
                    Nenhuma avaliação concluída neste ciclo.
                </p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Colaborador</th>
                                <th>Departamento</th>
                                <th>Cargo</th>
                                <th>Gestor</th>
                                <th>Nota</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($avaliacoes as $index => $av): ?>
                            <tr>
                                <td><strong><?php echo $index + 1; ?>°</strong></td>
                                <td>
                                    <?php echo htmlspecialchars($av['avaliado_nome']); ?><br>
                                    <small><?php echo htmlspecialchars($av['avaliado_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($av['departamento_nome'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($av['cargo_nome'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($av['gestor_nome'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $av['nota_final'] >= 80 ? 'success' : 
                                            ($av['nota_final'] >= 60 ? 'warning' : 'danger'); 
                                    ?> fs-6">
                                        <?php echo number_format($av['nota_final'], 2); ?>%
                                    </span>
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
require_once '../../includes/footer.php';
ob_end_flush();
?>
