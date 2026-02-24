<?php
// modules/relatorios/dashboard_individual.php
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

// Buscar dados do usuário
$query = "SELECT u.*, c.nome as cargo, d.nome as departamento
          FROM usuarios u
          LEFT JOIN cargos c ON u.cargo_id = c.id
          LEFT JOIN departamentos d ON u.departamento_id = d.id
          WHERE u.id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user_data = $stmt->fetch();

// Histórico de avaliações recebidas
$query = "SELECT a.*, av.nome as avaliador_nome, c.nome as ciclo_nome, f.nome as formulario_nome
          FROM avaliacoes a
          JOIN usuarios av ON a.avaliador_id = av.id
          JOIN ciclos_avaliacao c ON a.ciclo_id = c.id
          JOIN formularios f ON a.formulario_id = f.id
          WHERE a.avaliado_id = :user_id AND a.status = 'concluida'
          ORDER BY a.data_conclusao DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$avaliacoes = $stmt->fetchAll();

// Média por competência
$query = "SELECT 
            comp.nome as competencia,
            AVG(r.resposta_nota) as media,
            COUNT(r.id) as total_respostas
          FROM respostas r
          JOIN perguntas p ON r.pergunta_id = p.id
          JOIN competencias comp ON p.competencia_id = comp.id
          JOIN avaliacoes a ON r.avaliacao_id = a.id
          WHERE a.avaliado_id = :user_id AND r.resposta_nota IS NOT NULL
          GROUP BY comp.id
          ORDER BY media DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$competencias = $stmt->fetchAll();

// Evolução temporal
$query = "SELECT 
            DATE_FORMAT(a.data_conclusao, '%Y-%m') as mes,
            AVG(a.nota_final) as media
          FROM avaliacoes a
          WHERE a.avaliado_id = :user_id AND a.status = 'concluida'
          GROUP BY DATE_FORMAT(a.data_conclusao, '%Y-%m')
          ORDER BY mes ASC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$evolucao = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2><i class="bi bi-person-circle"></i> Meu Dashboard de Desempenho</h2>
    </div>
</div>

<!-- Perfil Resumido -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-2 text-center">
                <?php if ($user_data['foto_perfil']): ?>
                <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $user_data['foto_perfil']; ?>" 
                     class="rounded-circle img-fluid" style="width: 100px; height: 100px; object-fit: cover;">
                <?php else: ?>
                <i class="bi bi-person-circle" style="font-size: 5rem;"></i>
                <?php endif; ?>
            </div>
            <div class="col-md-5">
                <h3><?php echo htmlspecialchars($user_data['nome']); ?></h3>
                <p class="text-muted mb-1"><?php echo htmlspecialchars($user_data['cargo']); ?></p>
                <p class="text-muted"><?php echo htmlspecialchars($user_data['departamento']); ?></p>
            </div>
            <div class="col-md-5">
                <div class="row">
                    <div class="col-6">
                        <div class="bg-light p-3 rounded text-center">
                            <span class="h4"><?php echo count($avaliacoes); ?></span><br>
                            <small class="text-muted">Avaliações</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-light p-3 rounded text-center">
                            <span class="h4">
                                <?php 
                                $media_geral = array_sum(array_column($avaliacoes, 'nota_final')) / (count($avaliacoes) ?: 1);
                                echo number_format($media_geral, 1); ?>%
                            </span><br>
                            <small class="text-muted">Média Geral</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Gráfico de Competências -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Minhas Competências</h5>
            </div>
            <div class="card-body">
                <canvas id="competenciasChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Gráfico de Evolução -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Minha Evolução</h5>
            </div>
            <div class="card-body">
                <canvas id="evolucaoChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Histórico de Avaliações -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Histórico de Avaliações</h5>
    </div>
    <div class="card-body">
        <?php if (empty($avaliacoes)): ?>
        <p class="text-muted text-center py-4">Nenhuma avaliação concluída ainda.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Ciclo</th>
                        <th>Avaliador</th>
                        <th>Formulário</th>
                        <th>Nota</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($avaliacoes as $av): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($av['data_conclusao'])); ?></td>
                        <td><?php echo htmlspecialchars($av['ciclo_nome']); ?></td>
                        <td><?php echo htmlspecialchars($av['avaliador_nome']); ?></td>
                        <td><?php echo htmlspecialchars($av['formulario_nome']); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $av['nota_final'] >= 80 ? 'success' : 
                                    ($av['nota_final'] >= 60 ? 'warning' : 'danger'); 
                            ?>">
                                <?php echo number_format($av['nota_final'], 1); ?>%
                            </span>
                        </td>
                        <td>
                            <a href="../avaliacoes/visualizar.php?id=<?php echo $av['id']; ?>" class="btn btn-sm btn-info">
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Gráfico de Competências
<?php
$comp_labels = [];
$comp_data = [];
foreach ($competencias as $c) {
    $comp_labels[] = $c['competencia'];
    $comp_data[] = $c['media'];
}
?>
const ctxComp = document.getElementById('competenciasChart').getContext('2d');
new Chart(ctxComp, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($comp_labels); ?>,
        datasets: [{
            label: 'Média (0-5)',
            data: <?php echo json_encode($comp_data); ?>,
            backgroundColor: 'rgba(78, 115, 223, 0.7)',
            borderColor: 'rgba(78, 115, 223, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                max: 5,
                ticks: { stepSize: 1 }
            }
        }
    }
});

// Gráfico de Evolução
<?php
$evo_labels = [];
$evo_data = [];
foreach ($evolucao as $e) {
    $meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    $mes_num = (int)substr($e['mes'], 5, 2);
    $evo_labels[] = $meses[$mes_num - 1] . '/' . substr($e['mes'], 2, 2);
    $evo_data[] = $e['media'];
}
?>
const ctxEvo = document.getElementById('evolucaoChart').getContext('2d');
new Chart(ctxEvo, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($evo_labels); ?>,
        datasets: [{
            label: 'Minha Média (%)',
            data: <?php echo json_encode($evo_data); ?>,
            borderColor: 'rgba(28, 200, 138, 1)',
            backgroundColor: 'rgba(28, 200, 138, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: { stepSize: 20 }
            }
        }
    }
});
</script>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
