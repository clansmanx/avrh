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
// FUNÇÃO PARA DETECTAR ESCALA
// ==========================================
function detectarEscala($conn, $usuario_id) {
    // Verificar a maior nota para determinar a escala
    $stmt = $conn->prepare("
        SELECT MAX(resposta_nota) as max_nota 
        FROM respostas r
        JOIN avaliacoes a ON r.avaliacao_id = a.id
        WHERE a.avaliado_id = :usuario_id AND r.resposta_nota IS NOT NULL
    ");
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
    $max_nota = $stmt->fetch()['max_nota'] ?? 0;
    
    if ($max_nota > 5) {
        return 10; // Escala 0-10
    } elseif ($max_nota > 1) {
        return 5; // Escala 1-5
    } else {
        return 5; // Padrão
    }
}

// ==========================================
// DADOS DO USUÁRIO SELECIONADO
// ==========================================
$usuario = null;
$avaliacoes = [];
$evolucao_labels = [];
$evolucao_dados = [];
$competencias = [];
$ultima_avaliacao = null;
$formularios_ultima_avaliacao = [];
$escala = 5; // Padrão

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
        // Detectar escala
        $escala = detectarEscala($conn, $filtro_usuario);
        
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
        
        // ==========================================
        // GRÁFICO POR FORMULÁRIO NA ÚLTIMA AVALIAÇÃO
        // ==========================================
        if (!empty($avaliacoes)) {
            // Pegar a primeira avaliação da lista (mais recente) que esteja concluída
            foreach ($avaliacoes as $av) {
                if ($av['status'] == 'concluida' && !empty($av['nota_final'])) {
                    $ultima_avaliacao = $av;
                    break;
                }
            }
            
            if ($ultima_avaliacao) {
                // Buscar médias por formulário DENTRO desta avaliação
                $stmt_form = $conn->prepare("
                    SELECT 
                        f.id,
                        f.nome as formulario_nome,
                        COUNT(DISTINCT r.id) as total_respostas,
                        AVG(r.resposta_nota) as media_formulario
                    FROM respostas r
                    JOIN perguntas p ON r.pergunta_id = p.id
                    JOIN formularios f ON p.formulario_id = f.id
                    WHERE r.avaliacao_id = :avaliacao_id
                      AND r.resposta_nota IS NOT NULL
                    GROUP BY f.id
                    ORDER BY media_formulario DESC
                ");
                $stmt_form->bindParam(':avaliacao_id', $ultima_avaliacao['id']);
                $stmt_form->execute();
                $formularios_ultima_avaliacao = $stmt_form->fetchAll();
            }
        }
        
        // Dados para gráfico de evolução (linha do tempo)
        $stmt_evolucao = $conn->prepare("
            SELECT 
                c.nome as ciclo_nome,
                DATE_FORMAT(c.data_fim, '%d/%m/%Y') as data_fim_formatada,
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
            $evolucao_labels[] = $ev['ciclo_nome'] . ' (' . $ev['data_fim_formatada'] . ')';
            // Ajustar nota para escala 0-10 se necessário
            $nota = (float)$ev['nota_final'];
            if ($escala == 5) {
                $nota = $nota * 2; // Converte 0-5 para 0-10
            }
            $evolucao_dados[] = $nota;
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
        $competencias_raw = $stmt_comp->fetchAll();
        
        // Ajustar médias das competências para a escala correta
        foreach ($competencias_raw as $comp) {
            $media_ajustada = (float)$comp['media'];
            if ($escala == 5) {
                $media_ajustada = $media_ajustada * 2; // Converte para 0-10
            }
            $competencias[] = [
                'nome' => $comp['nome'],
                'media' => $media_ajustada,
                'total_avaliacoes' => $comp['total_avaliacoes']
            ];
        }
    }
}

// Calcular estatísticas
$total_avaliacoes = count($avaliacoes);
$avaliacoes_concluidas = array_filter($avaliacoes, fn($a) => $a['status'] == 'concluida' && !empty($a['nota_final']));

// Calcular médias já ajustadas para 0-10
$media_geral = 0;
$melhor_avaliacao = 0;
$pior_avaliacao = 10;

if (count($avaliacoes_concluidas) > 0) {
    $soma = 0;
    foreach ($avaliacoes_concluidas as $av) {
        $nota = (float)$av['nota_final'];
        if ($escala == 5) {
            $nota = $nota * 2;
        }
        $soma += $nota;
        
        if ($nota > $melhor_avaliacao) $melhor_avaliacao = $nota;
        if ($nota < $pior_avaliacao) $pior_avaliacao = $nota;
    }
    $media_geral = $soma / count($avaliacoes_concluidas);
}
?>

<style>
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

        <!-- ========================================== -->
        <!-- GRÁFICO 1: COMPARATIVO POR FORMULÁRIO     -->
        <!-- ========================================== -->
        <?php if (!empty($formularios_ultima_avaliacao)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-pie-chart"></i> 
                    Desempenho por Formulário - Última Avaliação
                    <small class="text-muted ms-2">(<?php echo $ultima_avaliacao['ciclo_nome']; ?>)</small>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($formularios_ultima_avaliacao as $form): 
                        $media_ajustada = (float)$form['media_formulario'];
                        if ($escala == 5) {
                            $media_ajustada = $media_ajustada * 2;
                        }
                    ?>
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold"><?php echo htmlspecialchars($form['formulario_nome']); ?></span>
                                    <span class="badge bg-primary"><?php echo $form['total_respostas']; ?> respostas</span>
                                </div>
                                <div class="mt-2 d-flex align-items-center">
                                    <div class="flex-grow-1 me-3">
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar <?php echo $media_ajustada >= 7 ? 'bg-success' : ($media_ajustada >= 5 ? 'bg-warning' : 'bg-danger'); ?>" 
                                                 style="width: <?php echo ($media_ajustada / 10) * 100; ?>%;"></div>
                                        </div>
                                    </div>
                                    <span class="fw-bold fs-5 <?php echo $media_ajustada >= 7 ? 'text-success' : ($media_ajustada >= 5 ? 'text-warning' : 'text-danger'); ?>">
                                        <?php echo number_format($media_ajustada, 1); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- GRÁFICO 2: EVOLUÇÃO HISTÓRICA             -->
        <!-- ========================================== -->
        <?php if (count($evolucao_dados) >= 2): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Evolução Histórica do Desempenho</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="evolucaoChart"></canvas>
                </div>
                <div class="mt-3 small text-muted text-center">
                    <span class="me-3"><i class="bi bi-circle-fill text-primary"></i> Média Geral por Ciclo</span>
                </div>
            </div>
        </div>
        <?php elseif (count($evolucao_dados) == 1): ?>
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="row">
                    <div class="col-12">
                        <h5>Última Avaliação</h5>
                        <div class="display-1 fw-bold text-primary">
                            <?php echo number_format($evolucao_dados[0], 1); ?>
                        </div>
                        <p class="text-muted"><?php echo $evolucao_labels[0]; ?></p>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle"></i> 
                            Colaborador possui 1 avaliação concluída. O gráfico de evolução será exibido quando houver pelo menos 2 avaliações.
                        </div>
                    </div>
                </div>
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
                            <span class="fw-bold <?php echo $comp['media'] >= 7 ? 'text-success' : ($comp['media'] >= 5 ? 'text-warning' : 'text-danger'); ?>">
                                <?php echo number_format($comp['media'], 1); ?>
                            </span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar <?php echo $comp['media'] >= 7 ? 'bg-success' : ($comp['media'] >= 5 ? 'bg-warning' : 'bg-danger'); ?>" 
                                 style="width: <?php echo ($comp['media'] / 10) * 100; ?>%;"></div>
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
                                <th>Formulários</th>
                                <th>Data</th>
                                <th>Nota</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($avaliacoes as $av): 
                                // Ajustar nota para exibição
                                $nota_exibicao = (float)$av['nota_final'];
                                if ($escala == 5 && $nota_exibicao > 0) {
                                    $nota_exibicao = $nota_exibicao * 2;
                                }
                                
                                // Contar formulários
                                $stmt_qtd = $conn->prepare("
                                    SELECT COUNT(DISTINCT f.id) as total
                                    FROM respostas r
                                    JOIN perguntas p ON r.pergunta_id = p.id
                                    JOIN formularios f ON p.formulario_id = f.id
                                    WHERE r.avaliacao_id = :avaliacao_id
                                ");
                                $stmt_qtd->bindParam(':avaliacao_id', $av['id']);
                                $stmt_qtd->execute();
                                $qtd_form = $stmt_qtd->fetch()['total'] ?? 1;
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($av['ciclo_nome']); ?></strong><br>
                                    <small class="text-muted"><?php echo $functions->formatDate($av['data_inicio']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($av['avaliador_nome'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $qtd_form; ?> formulário(s)</span>
                                </td>
                                <td><?php echo $av['data_conclusao'] ? $functions->formatDate($av['data_conclusao']) : '-'; ?></td>
                                <td class="text-center">
                                    <?php if ($av['nota_final']): ?>
                                    <span class="fw-bold <?php echo $nota_exibicao >= 7 ? 'text-success' : ($nota_exibicao >= 5 ? 'text-warning' : 'text-danger'); ?>">
                                        <?php echo number_format($nota_exibicao, 1); ?>
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
// ==========================================
// GRÁFICO DE EVOLUÇÃO HISTÓRICA
// ==========================================
let evolucaoChartInstance = null;

<?php if (count($evolucao_dados) >= 2): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('evolucaoChart')?.getContext('2d');
    if (!ctx) return;
    
    if (evolucaoChartInstance) {
        evolucaoChartInstance.destroy();
    }
    
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

// Funções de exportação (manter iguais)
function exportarPDF() {
    // ... (manter a mesma função)
}

function exportarExcel() {
    // ... (manter a mesma função)
}
</script>

<?php 
require_once '../../includes/footer.php';
ob_end_flush(); 
?>
