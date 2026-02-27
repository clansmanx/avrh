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
// FUNÇÕES AUXILIARES
// ==========================================

/**
 * Converte resposta SIM/NAO para valor numérico
 * Considera: 'S', 's', 'sim', 'SIM', '1', 1 como SIM
 */
function converterRespostaSimNao($resposta) {
    if (is_numeric($resposta)) {
        return (float)$resposta > 0 ? 100 : 0;
    }
    
    $resposta = strtoupper(trim($resposta));
    $valores_positivos = ['S', 'SIM', '1', 'TRUE', 'VERDADEIRO', 'YES', 'Y'];
    
    return in_array($resposta, $valores_positivos) ? 100 : 0;
}

/**
 * Calcula percentual de acertos considerando pesos
 */
function calcularPercentualCompetencia($respostas, $perguntas) {
    $total_peso = 0;
    $total_pontos = 0;
    
    foreach ($respostas as $resposta) {
        $pergunta_id = $resposta['pergunta_id'];
        $peso = $perguntas[$pergunta_id]['peso'] ?? 1;
        $total_peso += $peso;
        
        // Converter resposta para percentual
        $valor = converterRespostaSimNao($resposta['resposta_texto'] ?? $resposta['resposta_nota']);
        $total_pontos += ($valor / 100) * $peso * 100; // (0-1) * peso * 100
    }
    
    return $total_peso > 0 ? ($total_pontos / $total_peso) : 0;
}

// ==========================================
// DADOS DO USUÁRIO SELECIONADO
// ==========================================
$usuario = null;
$avaliacoes = [];
$ultimas_duas_avaliacoes = [];
$competencias_comparativo = [];

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
        // Buscar as 2 últimas avaliações CONCLUÍDAS
        $stmt = $conn->prepare("
            SELECT a.*, 
                   c.nome as ciclo_nome,
                   c.data_fim,
                   f.nome as formulario_nome
            FROM avaliacoes a
            JOIN ciclos_avaliacao c ON a.ciclo_id = c.id
            LEFT JOIN formularios f ON a.formulario_id = f.id
            WHERE a.avaliado_id = :avaliado_id 
              AND a.status = 'concluida'
            ORDER BY c.data_fim DESC
            LIMIT 2
        ");
        $stmt->bindParam(':avaliado_id', $filtro_usuario);
        $stmt->execute();
        $ultimas_duas_avaliacoes = $stmt->fetchAll();
        
        // Se tem pelo menos 2 avaliações, calcular comparativo por competência
        if (count($ultimas_duas_avaliacoes) >= 2) {
            $avaliacao_atual = $ultimas_duas_avaliacoes[0];
            $avaliacao_anterior = $ultimas_duas_avaliacoes[1];
            
            // Buscar TODAS as perguntas com suas competências e pesos
            $stmt_perguntas = $conn->query("
                SELECT p.id, p.pergunta, p.peso, p.tipo_resposta,
                       c.id as competencia_id, c.nome as competencia_nome
                FROM perguntas p
                JOIN competencias c ON p.competencia_id = c.id
                WHERE p.ativo = 1
            ");
            $perguntas = [];
            while ($row = $stmt_perguntas->fetch()) {
                $perguntas[$row['id']] = [
                    'competencia_id' => $row['competencia_id'],
                    'competencia_nome' => $row['competencia_nome'],
                    'peso' => $row['peso'],
                    'tipo' => $row['tipo_resposta']
                ];
            }
            
            // Buscar respostas da avaliação atual
            $stmt_respostas_atual = $conn->prepare("
                SELECT pergunta_id, resposta_nota, resposta_texto
                FROM respostas
                WHERE avaliacao_id = :avaliacao_id
            ");
            $stmt_respostas_atual->bindParam(':avaliacao_id', $avaliacao_atual['id']);
            $stmt_respostas_atual->execute();
            $respostas_atual = $stmt_respostas_atual->fetchAll();
            
            // Buscar respostas da avaliação anterior
            $stmt_respostas_anterior = $conn->prepare("
                SELECT pergunta_id, resposta_nota, resposta_texto
                FROM respostas
                WHERE avaliacao_id = :avaliacao_id
            ");
            $stmt_respostas_anterior->bindParam(':avaliacao_id', $avaliacao_anterior['id']);
            $stmt_respostas_anterior->execute();
            $respostas_anterior = $stmt_respostas_anterior->fetchAll();
            
            // Agrupar respostas por competência
            $competencias_temp = [];
            
            // Processar respostas da avaliação atual
            foreach ($respostas_atual as $resp) {
                $pergunta_id = $resp['pergunta_id'];
                if (!isset($perguntas[$pergunta_id])) continue;
                
                $comp_id = $perguntas[$pergunta_id]['competencia_id'];
                $comp_nome = $perguntas[$pergunta_id]['competencia_nome'];
                $peso = $perguntas[$pergunta_id]['peso'];
                
                if (!isset($competencias_temp[$comp_id])) {
                    $competencias_temp[$comp_id] = [
                        'nome' => $comp_nome,
                        'atual_peso_total' => 0,
                        'atual_pontos' => 0,
                        'anterior_peso_total' => 0,
                        'anterior_pontos' => 0
                    ];
                }
                
                // Calcular valor da resposta (0-100)
                $valor = 0;
                if ($perguntas[$pergunta_id]['tipo'] == 'sim_nao') {
                    $valor = converterRespostaSimNao($resp['resposta_texto'] ?? $resp['resposta_nota']);
                } else {
                    // Para outros tipos, normalizar para 0-100
                    $nota = (float)($resp['resposta_nota'] ?? 0);
                    $valor = ($nota / 10) * 100;
                }
                
                $competencias_temp[$comp_id]['atual_peso_total'] += $peso;
                $competencias_temp[$comp_id]['atual_pontos'] += ($valor / 100) * $peso * 100;
            }
            
            // Processar respostas da avaliação anterior
            foreach ($respostas_anterior as $resp) {
                $pergunta_id = $resp['pergunta_id'];
                if (!isset($perguntas[$pergunta_id])) continue;
                
                $comp_id = $perguntas[$pergunta_id]['competencia_id'];
                $peso = $perguntas[$pergunta_id]['peso'];
                
                if (!isset($competencias_temp[$comp_id])) {
                    $competencias_temp[$comp_id] = [
                        'nome' => $perguntas[$pergunta_id]['competencia_nome'],
                        'atual_peso_total' => 0,
                        'atual_pontos' => 0,
                        'anterior_peso_total' => 0,
                        'anterior_pontos' => 0
                    ];
                }
                
                $valor = 0;
                if ($perguntas[$pergunta_id]['tipo'] == 'sim_nao') {
                    $valor = converterRespostaSimNao($resp['resposta_texto'] ?? $resp['resposta_nota']);
                } else {
                    $nota = (float)($resp['resposta_nota'] ?? 0);
                    $valor = ($nota / 10) * 100;
                }
                
                $competencias_temp[$comp_id]['anterior_peso_total'] += $peso;
                $competencias_temp[$comp_id]['anterior_pontos'] += ($valor / 100) * $peso * 100;
            }
            
            // Calcular percentuais finais
            foreach ($competencias_temp as $comp_id => $comp) {
                $percentual_atual = $comp['atual_peso_total'] > 0 
                    ? round($comp['atual_pontos'] / $comp['atual_peso_total'], 1)
                    : 0;
                    
                $percentual_anterior = $comp['anterior_peso_total'] > 0 
                    ? round($comp['anterior_pontos'] / $comp['anterior_peso_total'], 1)
                    : 0;
                
                $competencias_comparativo[] = [
                    'nome' => $comp['nome'],
                    'atual' => $percentual_atual,
                    'anterior' => $percentual_anterior,
                    'diferenca' => $percentual_atual - $percentual_anterior,
                    'total_perguntas' => $comp['atual_peso_total'] + $comp['anterior_peso_total']
                ];
            }
            
            // Ordenar por maior diferença (maior evolução primeiro)
            usort($competencias_comparativo, function($a, $b) {
                return abs($b['diferenca']) <=> abs($a['diferenca']);
            });
        }
    }
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-file-person"></i> Relatório Individual</h2>
            <div>
                <a href="javascript:history.back()" class="btn btn-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRÁFICO COMPARATIVO DE COMPETÊNCIAS -->
        <?php if (!empty($competencias_comparativo) && count($ultimas_duas_avaliacoes) >= 2): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-bar-chart-steps"></i> 
                    Comparativo de Competências
                </h5>
                <small class="text-muted">
                    Comparando: 
                    <strong><?php echo $ultimas_duas_avaliacoes[1]['ciclo_nome']; ?></strong> 
                    <i class="bi bi-arrow-right"></i> 
                    <strong><?php echo $ultimas_duas_avaliacoes[0]['ciclo_nome']; ?></strong>
                </small>
            </div>
            <div class="card-body">
                <!-- Cards de resumo -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <span class="text-muted">AVALIAÇÃO ANTERIOR</span>
                                <h5><?php echo $ultimas_duas_avaliacoes[1]['ciclo_nome']; ?></h5>
                                <span class="badge bg-info"><?php echo date('d/m/Y', strtotime($ultimas_duas_avaliacoes[1]['data_fim'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <span class="text-muted">AVALIAÇÃO ATUAL</span>
                                <h5><?php echo $ultimas_duas_avaliacoes[0]['ciclo_nome']; ?></h5>
                                <span class="badge bg-success"><?php echo date('d/m/Y', strtotime($ultimas_duas_avaliacoes[0]['data_fim'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de barras comparativo -->
                <div class="chart-container" style="height: 400px;">
                    <canvas id="competenciasChart"></canvas>
                </div>

                <!-- Legenda -->
                <div class="mt-3 text-center">
                    <span class="me-3">
                        <i class="bi bi-square-fill text-secondary"></i> Avaliação Anterior
                    </span>
                    <span class="me-3">
                        <i class="bi bi-square-fill text-primary"></i> Avaliação Atual
                    </span>
                </div>

                <!-- Tabela de detalhamento -->
                <div class="mt-4">
                    <h6>Detalhamento por Competência</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Competência</th>
                                    <th class="text-center">Anterior</th>
                                    <th class="text-center">Atual</th>
                                    <th class="text-center">Evolução</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($competencias_comparativo as $comp): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($comp['nome']); ?></strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">
                                            <?php echo number_format($comp['anterior'], 1); ?>%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">
                                            <?php echo number_format($comp['atual'], 1); ?>%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        $diff = $comp['diferenca'];
                                        $classe = $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted');
                                        $icone = $diff > 0 ? 'arrow-up' : ($diff < 0 ? 'arrow-down' : 'dash');
                                        ?>
                                        <span class="<?php echo $classe; ?>">
                                            <i class="bi bi-<?php echo $icone; ?>-circle"></i>
                                            <?php echo $diff > 0 ? '+' : ''; ?><?php echo number_format($diff, 1); ?>%
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('competenciasChart')?.getContext('2d');
            if (!ctx) return;
            
            const competencias = <?php echo json_encode(array_column($competencias_comparativo, 'nome')); ?>;
            const dadosAnterior = <?php echo json_encode(array_column($competencias_comparativo, 'anterior')); ?>;
            const dadosAtual = <?php echo json_encode(array_column($competencias_comparativo, 'atual')); ?>;
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: competencias,
                    datasets: [
                        {
                            label: 'Avaliação Anterior',
                            data: dadosAnterior,
                            backgroundColor: 'rgba(108, 117, 125, 0.7)',
                            borderColor: 'rgb(108, 117, 125)',
                            borderWidth: 1
                        },
                        {
                            label: 'Avaliação Atual',
                            data: dadosAtual,
                            backgroundColor: 'rgba(13, 110, 253, 0.7)',
                            borderColor: 'rgb(13, 110, 253)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Percentual de Acertos (%)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        });
        </script>
        <?php elseif (count($ultimas_duas_avaliacoes) == 1): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> 
            Colaborador possui apenas 1 avaliação concluída. O comparativo será exibido quando houver a segunda avaliação.
        </div>
        <?php endif; ?>

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

<?php 
require_once '../../includes/footer.php';
ob_end_flush(); 
?>
