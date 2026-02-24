<?php
// modules/relatorios/index.php
require_once '../../includes/header.php';

// Obter conexão PDO
$conn = (new Database())->getConnection();

// Verificar permissão
$auth->requirePermission(['admin', 'rh', 'gestor']);

// Buscar ciclos para filtro
$query = "SELECT id, nome, data_inicio, data_fim, status FROM ciclos_avaliacao ORDER BY data_inicio DESC";
$stmt = $conn->query($query);
$ciclos = $stmt->fetchAll();

// Buscar departamentos
$query = "SELECT * FROM departamentos ORDER BY nome";
$stmt = $conn->query($query);
$departamentos = $stmt->fetchAll();

// Estatísticas gerais
$query = "SELECT 
            COUNT(*) as total_avaliacoes,
            SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) as concluidas,
            AVG(CASE WHEN status = 'concluida' THEN nota_final ELSE NULL END) as media_geral
          FROM avaliacoes";
$stmt = $conn->query($query);
$estatisticas = $stmt->fetch();
?>

<div class="row">
    <div class="col-12">
        <h2>Relatórios e Análises</h2>
        <p class="text-muted">Visualize dados e métricas das avaliações de desempenho</p>
    </div>
</div>

<!-- Cards de métricas gerais -->
<div class="row mt-3">
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Taxa de Resposta</h6>
                        <?php 
                        $taxa = $estatisticas['total_avaliacoes'] > 0 
                            ? round(($estatisticas['concluidas'] / $estatisticas['total_avaliacoes']) * 100) 
                            : 0;
                        ?>
                        <h2 class="mb-0"><?php echo $taxa; ?>%</h2>
                    </div>
                    <i class="bi bi-pie-chart-fill fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Média Geral</h6>
                        <h2 class="mb-0"><?php echo number_format($estatisticas['media_geral'] ?? 0, 1); ?></h2>
                    </div>
                    <i class="bi bi-star-fill fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Avaliações Concluídas</h6>
                        <h2 class="mb-0"><?php echo $estatisticas['concluidas'] ?? 0; ?></h2>
                    </div>
                    <i class="bi bi-check-circle-fill fs-1"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total de Avaliações</h6>
                        <h2 class="mb-0"><?php echo $estatisticas['total_avaliacoes'] ?? 0; ?></h2>
                    </div>
                    <i class="bi bi-clipboard-data fs-1"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos e relatórios -->
<div class="row mt-4">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5>Desempenho por Competência</h5>
            </div>
            <div class="card-body">
                <canvas id="competenciasChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5>Evolução por Ciclo</h5>
            </div>
            <div class="card-body">
                <canvas id="evolucaoChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Relatórios disponíveis -->
<div class="row mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Relatórios Disponíveis</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="bi bi-people-fill fs-1 text-primary mb-3"></i>
                                <h6>Relatório por Colaborador</h6>
                                <p class="small text-muted">Análise individual de desempenho</p>
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalColaborador">
                                    <i class="bi bi-download"></i> Gerar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="bi bi-building fs-1 text-success mb-3"></i>
                                <h6>Relatório por Departamento</h6>
                                <p class="small text-muted">Desempenho por área</p>
                                <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalDepartamento">
                                    <i class="bi bi-download"></i> Gerar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="bi bi-calendar-check fs-1 text-info mb-3"></i>
                                <h6>Relatório por Ciclo</h6>
                                <p class="small text-muted">Análise completa do ciclo</p>
                                <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalCiclo">
                                    <i class="bi bi-download"></i> Gerar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modais (igual ao código anterior, mantive igual) -->
<!-- ... (código dos modais igual ao anterior) ... -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Gráfico de Competências
<?php
$query = "SELECT c.nome, AVG(r.resposta_nota) as media
          FROM respostas r
          JOIN perguntas p ON r.pergunta_id = p.id
          JOIN competencias c ON p.competencia_id = c.id
          WHERE r.resposta_nota IS NOT NULL
          GROUP BY c.id
          ORDER BY media DESC
          LIMIT 10";
$stmt = $conn->query($query);
$competencias_data = $stmt->fetchAll();

$labels = [];
$data = [];
foreach ($competencias_data as $row) {
    $labels[] = $row['nome'];
    $data[] = $row['media'];
}
?>

const ctxCompetencias = document.getElementById('competenciasChart')?.getContext('2d');
if (ctxCompetencias) {
    new Chart(ctxCompetencias, {
        type: 'radar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Média por Competência',
                data: <?php echo json_encode($data); ?>,
                backgroundColor: 'rgba(78, 115, 223, 0.2)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(78, 115, 223, 1)'
            }]
        },
        options: {
            scales: {
                r: {
                    beginAtZero: true,
                    max: 5,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Gráfico de Evolução
<?php
$query = "SELECT c.nome, AVG(a.nota_final) as media
          FROM avaliacoes a
          JOIN ciclos_avaliacao c ON a.ciclo_id = c.id
          WHERE a.status = 'concluida'
          GROUP BY c.id
          ORDER BY c.data_inicio ASC
          LIMIT 6";
$stmt = $conn->query($query);
$evolucao_data = $stmt->fetchAll();

$labels_evolucao = [];
$data_evolucao = [];
foreach ($evolucao_data as $row) {
    $labels_evolucao[] = $row['nome'];
    $data_evolucao[] = $row['media'];
}
?>

const ctxEvolucao = document.getElementById('evolucaoChart')?.getContext('2d');
if (ctxEvolucao) {
    new Chart(ctxEvolucao, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels_evolucao); ?>,
            datasets: [{
                label: 'Média de Desempenho',
                data: <?php echo json_encode($data_evolucao); ?>,
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
                    max: 5,
                    ticks: {
                        stepSize: 0.5
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
