<?php
// modules/relatorios/individual.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require_once '../../includes/header.php';
$auth->requirePermission(['admin', 'rh', 'gestor']);

$conn = (new Database())->getConnection();

// ==========================================
// FILTROS
// ==========================================
$filtro_empresa = $_GET['empresa'] ?? '';
$filtro_departamento = $_GET['departamento'] ?? '';
$filtro_usuario = $_GET['usuario'] ?? '';

// Buscar empresas
$empresas = $conn->query("SELECT id, nome FROM empresas WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Buscar departamentos
$departamentos = [];
if ($filtro_empresa) {
    $stmt = $conn->prepare("SELECT id, nome FROM departamentos WHERE empresa_id = :empresa_id OR empresa_id IS NULL ORDER BY nome");
    $stmt->bindParam(':empresa_id', $filtro_empresa);
    $stmt->execute();
    $departamentos = $stmt->fetchAll();
}

// Buscar usuários
$usuarios = [];
if ($filtro_empresa && $filtro_departamento) {
    $stmt = $conn->prepare("
        SELECT u.id, u.nome 
        FROM usuarios u
        WHERE u.ativo = 1 
          AND u.empresa_id = :empresa 
          AND u.departamento_id = :departamento
        ORDER BY u.nome
    ");
    $stmt->bindParam(':empresa', $filtro_empresa);
    $stmt->bindParam(':departamento', $filtro_departamento);
    $stmt->execute();
    $usuarios = $stmt->fetchAll();
} elseif ($filtro_empresa) {
    $stmt = $conn->prepare("
        SELECT u.id, u.nome 
        FROM usuarios u
        WHERE u.ativo = 1 AND u.empresa_id = :empresa
        ORDER BY u.nome
    ");
    $stmt->bindParam(':empresa', $filtro_empresa);
    $stmt->execute();
    $usuarios = $stmt->fetchAll();
}

// ==========================================
// DADOS DO USUÁRIO SELECIONADO
// ==========================================
$usuario = null;
$avaliacoes = [];
$evolucao_labels = [];
$evolucao_dados = [];
$competencias = [];

if ($filtro_usuario) {
    // Buscar dados do usuário
    $stmt = $conn->prepare("
        SELECT u.*, 
               c.nome as cargo_nome, 
               d.nome as departamento_nome,
               e.nome as empresa_nome,
               g.nome as gestor_nome
        FROM usuarios u
        LEFT JOIN cargos c ON u.cargo_id = c.id
        LEFT JOIN departamentos d ON u.departamento_id = d.id
        LEFT JOIN empresas e ON u.empresa_id = e.id
        LEFT JOIN usuarios g ON u.gestor_id = g.id
        WHERE u.id = :id
    ");
    $stmt->bindParam(':id', $filtro_usuario);
    $stmt->execute();
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        // Buscar avaliações do usuário
        $stmt = $conn->prepare("
            SELECT a.*, 
                   c.nome as ciclo_nome,
                   c.data_inicio,
                   c.data_fim,
                   av.nome as avaliador_nome,
                   f.nome as formulario_nome
            FROM avaliacoes a
            JOIN ciclos_avaliacao c ON a.ciclo_id = c.id
            LEFT JOIN usuarios av ON a.avaliador_id = av.id
            LEFT JOIN formularios f ON a.formulario_id = f.id
            WHERE a.avaliado_id = :avaliado_id
            ORDER BY c.data_fim DESC
        ");
        $stmt->bindParam(':avaliado_id', $filtro_usuario);
        $stmt->execute();
        $avaliacoes = $stmt->fetchAll();
        
        // Dados para gráfico - filtrando apenas notas válidas
        $stmt_evolucao = $conn->prepare("
            SELECT 
                c.nome as ciclo_nome,
                c.data_fim,
                a.nota_final
            FROM avaliacoes a
            JOIN ciclos_avaliacao c ON a.ciclo_id = c.id
            WHERE a.avaliado_id = :avaliado_id 
              AND a.status = 'concluida'
              AND a.nota_final IS NOT NULL
              AND a.nota_final > 0
            ORDER BY c.data_fim ASC
        ");
        $stmt_evolucao->bindParam(':avaliado_id', $filtro_usuario);
        $stmt_evolucao->execute();
        $evolucao = $stmt_evolucao->fetchAll();
        
        foreach ($evolucao as $ev) {
            $evolucao_labels[] = $ev['ciclo_nome'] . ' (' . date('d/m/Y', strtotime($ev['data_fim'])) . ')';
            $evolucao_dados[] = (float)$ev['nota_final'];
        }
        
        // Buscar competências
        $stmt_comp = $conn->prepare("
            SELECT 
                c.nome,
                AVG(r.resposta_nota) as media,
                COUNT(*) as total_avaliacoes
            FROM respostas r
            JOIN perguntas p ON r.pergunta_id = p.id
            JOIN competencias c ON p.competencia_id = c.id
            JOIN avaliacoes a ON r.avaliacao_id = a.id
            WHERE a.avaliado_id = :avaliado_id 
              AND r.resposta_nota IS NOT NULL
            GROUP BY c.id
            ORDER BY media DESC
            LIMIT 5
        ");
        $stmt_comp->bindParam(':avaliado_id', $filtro_usuario);
        $stmt_comp->execute();
        $competencias = $stmt_comp->fetchAll();
    }
}

// Calcular estatísticas
$total_avaliacoes = count($avaliacoes);
$avaliacoes_concluidas = array_filter($avaliacoes, fn($a) => $a['status'] == 'concluida' && !empty($a['nota_final']));
$media_geral = count($avaliacoes_concluidas) > 0 
    ? array_sum(array_column($avaliacoes_concluidas, 'nota_final')) / count($avaliacoes_concluidas) 
    : 0;

$melhor_avaliacao = count($avaliacoes_concluidas) > 0 
    ? max(array_column($avaliacoes_concluidas, 'nota_final')) 
    : 0;

$pior_avaliacao = count($avaliacoes_concluidas) > 0 
    ? min(array_column($avaliacoes_concluidas, 'nota_final')) 
    : 0;
?>

<style>
/* Garantir que o canvas não expanda infinitamente */
canvas {
    max-width: 100%;
    height: auto !important;
}

.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-file-person"></i> Relatório Individual</h2>
            <div>
                <a href="javascript:history.back()" class="btn btn-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
                <?php if ($filtro_usuario && $usuario && count($avaliacoes_concluidas) > 0): ?>
                <button class="btn btn-success me-2" onclick="exportarPDF()">
                    <i class="bi bi-file-pdf"></i> PDF
                </button>
                <button class="btn btn-success" onclick="exportarExcel()">
                    <i class="bi bi-file-excel"></i> Excel
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtrar Colaborador</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Empresa</label>
                        <select class="form-select" name="empresa" onchange="this.form.submit()">
                            <option value="">Selecione uma empresa</option>
                            <?php foreach ($empresas as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>" 
                                <?php echo $filtro_empresa == $emp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Departamento</label>
                        <select class="form-select" name="departamento" onchange="this.form.submit()" 
                                <?php echo !$filtro_empresa ? 'disabled' : ''; ?>>
                            <option value="">Selecione um departamento</option>
                            <?php foreach ($departamentos as $depto): ?>
                            <option value="<?php echo $depto['id']; ?>" 
                                <?php echo $filtro_departamento == $depto['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($depto['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Colaborador</label>
                        <select class="form-select" name="usuario" onchange="this.form.submit()"
                                <?php echo !$filtro_departamento ? 'disabled' : ''; ?>>
                            <option value="">Selecione um colaborador</option>
                            <?php foreach ($usuarios as $user): ?>
                            <option value="<?php echo $user['id']; ?>" 
                                <?php echo $filtro_usuario == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($filtro_usuario && $usuario): ?>
        
        <!-- Header do Colaborador -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center gap-4">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-3">
                                <i class="bi bi-person-circle fs-1 text-primary"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($usuario['nome']); ?></h3>
                                <div class="d-flex flex-wrap gap-3 text-muted">
                                    <span><i class="bi bi-briefcase me-1"></i> <?php echo htmlspecialchars($usuario['cargo_nome'] ?? 'Cargo não informado'); ?></span>
                                    <span><i class="bi bi-building me-1"></i> <?php echo htmlspecialchars($usuario['empresa_nome'] ?? 'Empresa não informada'); ?></span>
                                    <span><i class="bi bi-diagram-3 me-1"></i> <?php echo htmlspecialchars($usuario['departamento_nome'] ?? 'Departamento não informado'); ?></span>
                                    <?php if ($usuario['gestor_nome']): ?>
                                    <span><i class="bi bi-person-up me-1"></i> Gestor: <?php echo htmlspecialchars($usuario['gestor_nome']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="fw-bold fs-4"><?php echo $total_avaliacoes; ?></div>
                                <small class="text-muted">Total</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold fs-4 text-success"><?php echo count($avaliacoes_concluidas); ?></div>
                                <small class="text-muted">Concluídas</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold fs-4 text-warning"><?php echo $total_avaliacoes - count($avaliacoes_concluidas); ?></div>
                                <small class="text-muted">Pendentes</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards de Métricas -->
        <?php if (count($avaliacoes_concluidas) > 0): ?>
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="text-muted small text-uppercase">Média Geral</span>
                        <div class="d-flex align-items-end justify-content-between">
                            <span class="display-4 fw-bold <?php echo $media_geral >= 7 ? 'text-success' : ($media_geral >= 5 ? 'text-warning' : 'text-danger'); ?>">
                                <?php echo number_format($media_geral, 1); ?>
                            </span>
                            <span class="text-muted mb-2">/10</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="text-muted small text-uppercase">Melhor Nota</span>
                        <div class="d-flex align-items-end justify-content-between">
                            <span class="display-4 fw-bold text-success">
                                <?php echo number_format($melhor_avaliacao, 1); ?>
                            </span>
                            <span class="text-muted mb-2">/10</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="text-muted small text-uppercase">Pior Nota</span>
                        <div class="d-flex align-items-end justify-content-between">
                            <span class="display-4 fw-bold text-danger">
                                <?php echo number_format($pior_avaliacao, 1); ?>
                            </span>
                            <span class="text-muted mb-2">/10</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="text-muted small text-uppercase">Feedbacks</span>
                        <div class="d-flex align-items-end justify-content-between">
                            <span class="display-4 fw-bold text-info">
                                <?php echo count(array_filter($avaliacoes, fn($a) => !empty($a['feedback_gerado']))); ?>
                            </span>
                            <span class="text-muted mb-2">gerados</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Gráfico de Evolução - CONTROLADO -->
        <?php if (count($evolucao_dados) >= 2): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Evolução do Desempenho</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="evolucaoChart"></canvas>
                </div>
                <div class="mt-3 small text-muted text-center">
                    <span class="me-3"><i class="bi bi-circle-fill text-primary"></i> Média Geral</span>
                </div>
            </div>
        </div>
        <?php elseif (count($evolucao_dados) == 1): ?>
        <div class="card mb-4">
            <div class="card-body text-center text-muted py-4">
                <i class="bi bi-bar-chart fs-1 d-block mb-3"></i>
                <p>Colaborador possui 1 avaliação concluída. O gráfico de evolução será exibido quando houver pelo menos 2 avaliações.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Competências Destacadas -->
        <?php if (!empty($competencias)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-star"></i> Competências em Destaque</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($competencias as $comp): ?>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex justify-content-between">
                            <span>
                                <strong><?php echo htmlspecialchars($comp['nome']); ?></strong>
                                <small class="text-muted">(<?php echo $comp['total_avaliacoes']; ?>x)</small>
                            </span>
                            <span class="fw-bold text-primary"><?php echo number_format($comp['media'], 1); ?></span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" style="width: <?php echo ($comp['media'] / 5) * 100; ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Histórico de Avaliações -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Histórico de Avaliações</h5>
                <span class="badge bg-primary"><?php echo count($avaliacoes); ?> registros</span>
            </div>
            <div class="card-body">
                <?php if (empty($avaliacoes)): ?>
                <p class="text-muted text-center py-4">
                    <i class="bi bi-clipboard-x fs-1 d-block mb-3"></i>
                    Nenhuma avaliação encontrada para este colaborador.
                </p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ciclo</th>
                                <th>Avaliador</th>
                                <th>Formulário</th>
                                <th>Data</th>
                                <th>Nota</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($avaliacoes as $av): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($av['ciclo_nome']); ?></strong><br>
                                    <small class="text-muted"><?php echo $functions->formatDate($av['data_inicio']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($av['avaliador_nome'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($av['formulario_nome'] ?? 'Geral'); ?></td>
                                <td><?php echo $av['data_conclusao'] ? $functions->formatDate($av['data_conclusao']) : '-'; ?></td>
                                <td class="text-center">
                                    <?php if ($av['nota_final']): ?>
                                    <span class="fw-bold <?php echo $av['nota_final'] >= 7 ? 'text-success' : ($av['nota_final'] >= 5 ? 'text-warning' : 'text-danger'); ?>">
                                        <?php echo number_format($av['nota_final'], 1); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($av['status'] == 'concluida'): ?>
                                    <span class="badge bg-success">Concluída</span>
                                    <?php else: ?>
                                    <span class="badge bg-warning">Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="../avaliacoes/visualizar.php?id=<?php echo $av['id']; ?>" 
                                       class="btn btn-sm btn-info" title="Ver Detalhes">
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

        <?php elseif ($filtro_usuario && !$usuario): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> Colaborador não encontrado.
        </div>
        <?php elseif (!$filtro_usuario): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Selecione um colaborador para visualizar o relatório individual.
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
// Variável global para controlar o gráfico
let evolucaoChartInstance = null;

<?php if (count($evolucao_dados) >= 2): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('evolucaoChart')?.getContext('2d');
    if (!ctx) return;
    
    // Destruir instância anterior se existir
    if (evolucaoChartInstance) {
        evolucaoChartInstance.destroy();
    }
    
    // Criar novo gráfico
    evolucaoChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($evolucao_labels); ?>,
            datasets: [{
                label: 'Média Geral',
                data: <?php echo json_encode($evolucao_dados); ?>,
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                borderWidth: 3,
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#4361ee',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { 
                    callbacks: {
                        label: function(context) {
                            return 'Nota: ' + context.parsed.y.toFixed(1) + '/10';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10,
                    grid: { color: '#e9ecef' },
                    title: {
                        display: true,
                        text: 'Nota'
                    }
                }
            }
        }
    });
});
<?php endif; ?>

// Funções de exportação
function exportarPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    doc.setFontSize(18);
    doc.text('Relatório Individual - <?php echo htmlspecialchars(addslashes($usuario['nome'] ?? '')); ?>', 14, 22);
    
    doc.setFontSize(11);
    doc.text('Gerado em: ' + new Date().toLocaleDateString('pt-BR'), 14, 30);
    
    const headers = [['Ciclo', 'Avaliador', 'Data', 'Nota', 'Status']];
    const data = <?php 
        $dados_tabela = array_map(function($av) {
            return [
                $av['ciclo_nome'],
                $av['avaliador_nome'] ?? 'N/A',
                $av['data_conclusao'] ? date('d/m/Y', strtotime($av['data_conclusao'])) : 'Pendente',
                $av['nota_final'] ? number_format($av['nota_final'], 1) . '/10' : 'Pendente',
                $av['status'] == 'concluida' ? 'Concluída' : 'Pendente'
            ];
        }, $avaliacoes);
        echo json_encode($dados_tabela); 
    ?>;
    
    doc.autoTable({
        head: headers,
        body: data,
        startY: 40,
        theme: 'striped',
        styles: { fontSize: 9 }
    });
    
    doc.save('relatorio_<?php echo $usuario['id']; ?>.pdf');
}

function exportarExcel() {
    const wb = XLSX.utils.book_new();
    
    const infoData = [
        ['Relatório Individual - <?php echo htmlspecialchars(addslashes($usuario['nome'] ?? '')); ?>'],
        ['Gerado em:', new Date().toLocaleDateString('pt-BR')],
        [],
        ['Informações do Colaborador'],
        ['Nome', '<?php echo htmlspecialchars(addslashes($usuario['nome'] ?? '')); ?>'],
        ['Empresa', '<?php echo htmlspecialchars(addslashes($usuario['empresa_nome'] ?? '')); ?>'],
        ['Departamento', '<?php echo htmlspecialchars(addslashes($usuario['departamento_nome'] ?? '')); ?>'],
        ['Cargo', '<?php echo htmlspecialchars(addslashes($usuario['cargo_nome'] ?? '')); ?>'],
        ['Gestor', '<?php echo htmlspecialchars(addslashes($usuario['gestor_nome'] ?? '')); ?>'],
        [],
        ['Métricas'],
        ['Total Avaliações', '<?php echo $total_avaliacoes; ?>'],
        ['Concluídas', '<?php echo count($avaliacoes_concluidas); ?>'],
        ['Média Geral', '<?php echo number_format($media_geral, 1); ?>/10'],
        ['Melhor Nota', '<?php echo number_format($melhor_avaliacao, 1); ?>/10'],
        ['Pior Nota', '<?php echo number_format($pior_avaliacao, 1); ?>/10']
    ];
    
    const infoSheet = XLSX.utils.aoa_to_sheet(infoData);
    XLSX.utils.book_append_sheet(wb, infoSheet, 'Resumo');
    
    const avaliacoesData = [
        ['Ciclo', 'Avaliador', 'Data', 'Nota', 'Status'],
        <?php foreach ($avaliacoes as $av): ?>
        [
            '<?php echo htmlspecialchars(addslashes($av['ciclo_nome'])); ?>',
            '<?php echo htmlspecialchars(addslashes($av['avaliador_nome'] ?? 'N/A')); ?>',
            '<?php echo $av['data_conclusao'] ? date('d/m/Y', strtotime($av['data_conclusao'])) : 'Pendente'; ?>',
            '<?php echo $av['nota_final'] ? number_format($av['nota_final'], 1) : 'Pendente'; ?>',
            '<?php echo $av['status'] == 'concluida' ? 'Concluída' : 'Pendente'; ?>'
        ],
        <?php endforeach; ?>
    ];
    
    const avaliacoesSheet = XLSX.utils.aoa_to_sheet(avaliacoesData);
    XLSX.utils.book_append_sheet(wb, avaliacoesSheet, 'Avaliações');
    
    XLSX.writeFile(wb, 'relatorio_<?php echo $usuario['id']; ?>.xlsx');
}
</script>

<?php 
require_once '../../includes/footer.php';
ob_end_flush(); 
?>
