<?php
// modules/relatorios/index.php
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

// Dados para gráfico de competências
$query_comp = "SELECT c.nome, AVG(r.resposta_nota) as media
               FROM respostas r
               JOIN perguntas p ON r.pergunta_id = p.id
               JOIN competencias c ON p.competencia_id = c.id
               WHERE r.resposta_nota IS NOT NULL
               GROUP BY c.id
               ORDER BY media DESC
               LIMIT 10";
$stmt_comp = $conn->query($query_comp);
$competencias_data = $stmt_comp->fetchAll();

// Dados para gráfico de evolução
$query_evolucao = "SELECT c.nome, AVG(a.nota_final) as media
                   FROM avaliacoes a
                   JOIN ciclos_avaliacao c ON a.ciclo_id = c.id
                   WHERE a.status = 'concluida'
                   GROUP BY c.id
                   ORDER BY c.data_inicio ASC
                   LIMIT 6";
$stmt_evolucao = $conn->query($query_evolucao);
$evolucao_data = $stmt_evolucao->fetchAll();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Relatórios e Análises</h2>
            <span class="badge bg-info p-2">Visão Geral</span>
        </div>
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
                <?php if (empty($competencias_data)): ?>
                <p class="text-muted text-center py-4">
                    <i class="bi bi-bar-chart fs-1 d-block mb-3"></i>
                    Nenhum dado disponível para gráfico.
                </p>
                <?php else: ?>
                <canvas id="competenciasChart" height="300"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5>Evolução por Ciclo</h5>
            </div>
            <div class="card-body">
                <?php if (empty($evolucao_data)): ?>
                <p class="text-muted text-center py-4">
                    <i class="bi bi-graph-up fs-1 d-block mb-3"></i>
                    Nenhum ciclo finalizado para análise.
                </p>
                <?php else: ?>
                <canvas id="evolucaoChart" height="300"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Relatórios Disponíveis -->
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
                                    <i class="bi bi-file-text"></i> Gerar
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
                                    <i class="bi bi-file-text"></i> Gerar
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
                                    <i class="bi bi-file-text"></i> Gerar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Relatório por Colaborador -->
<div class="modal fade" id="modalColaborador" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Relatório por Colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="exportar.php" method="GET" target="_blank">
                <div class="modal-body">
                    <input type="hidden" name="tipo" value="colaborador">
                    
                    <div class="mb-3">
                        <label class="form-label">Colaborador *</label>
                        <select class="form-select" name="usuario_id" required>
                            <option value="">Selecione...</option>
                            <?php
                            $query = "SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY nome";
                            $stmt = $conn->query($query);
                            while ($row = $stmt->fetch()) {
                                echo "<option value='{$row['id']}'>{$row['nome']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Período</label>
                        <select class="form-select" name="periodo">
                            <option value="todos">Todos os ciclos</option>
                            <option value="ultimo">Último ciclo</option>
                            <option value="ano">Último ano</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Formato</label>
                        <select class="form-select" name="formato">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-download"></i> Gerar Relatório
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Relatório por Departamento -->
<div class="modal fade" id="modalDepartamento" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Relatório por Departamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="exportar.php" method="GET" target="_blank">
                <div class="modal-body">
                    <input type="hidden" name="tipo" value="departamento">
                    
                    <div class="mb-3">
                        <label class="form-label">Departamento *</label>
                        <select class="form-select" name="departamento_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($departamentos as $depto): ?>
                            <option value="<?php echo $depto['id']; ?>"><?php echo $depto['nome']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ciclo</label>
                        <select class="form-select" name="ciclo_id">
                            <option value="">Último ciclo</option>
                            <?php foreach ($ciclos as $ciclo): ?>
                            <option value="<?php echo $ciclo['id']; ?>"><?php echo $ciclo['nome']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Formato</label>
                        <select class="form-select" name="formato">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-download"></i> Gerar Relatório
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Relatório por Ciclo -->
<div class="modal fade" id="modalCiclo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Relatório por Ciclo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="exportar.php" method="GET" target="_blank">
                <div class="modal-body">
                    <input type="hidden" name="tipo" value="ciclo">
                    
                    <div class="mb-3">
                        <label class="form-label">Ciclo *</label>
                        <select class="form-select" name="ciclo_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($ciclos as $ciclo): ?>
                            <option value="<?php echo $ciclo['id']; ?>">
                                <?php echo $ciclo['nome']; ?> (<?php echo $ciclo['status']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Agrupar por</label>
                        <select class="form-select" name="agrupar">
                            <option value="departamento">Departamento</option>
                            <option value="cargo">Cargo</option>
                            <option value="gestor">Gestor</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Formato</label>
                        <select class="form-select" name="formato">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-download"></i> Gerar Relatório
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (!empty($competencias_data)): ?>
// Gráfico de Competências
const ctxCompetencias = document.getElementById('competenciasChart').getContext('2d');
new Chart(ctxCompetencias, {
    type: 'radar',
    data: {
        labels: <?php echo json_encode(array_column($competencias_data, 'nome')); ?>,
        datasets: [{
            label: 'Média por Competência',
            data: <?php echo json_encode(array_column($competencias_data, 'media')); ?>,
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
<?php endif; ?>

<?php if (!empty($evolucao_data)): ?>
// Gráfico de Evolução
const ctxEvolucao = document.getElementById('evolucaoChart').getContext('2d');
new Chart(ctxEvolucao, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($evolucao_data, 'nome')); ?>,
        datasets: [{
            label: 'Média de Desempenho',
            data: <?php echo json_encode(array_column($evolucao_data, 'media')); ?>,
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
<?php endif; ?>
</script>

<?php 
require_once '../../includes/footer.php';
ob_end_flush();
?>
