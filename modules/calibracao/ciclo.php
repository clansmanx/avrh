<?php
// modules/calibracao/ciclo.php
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

$ciclo_id = $_GET['id'] ?? 0;

if (!$ciclo_id) {
    $_SESSION['error'] = "ID do ciclo não fornecido";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Buscar informações do ciclo
$query = "SELECT * FROM ciclos_avaliacao WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $ciclo_id);
$stmt->execute();
$ciclo = $stmt->fetch();

if (!$ciclo) {
    $_SESSION['error'] = "Ciclo não encontrado";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Processar calibração via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calibrar'])) {
    try {
        $avaliacao_id = $_POST['avaliacao_id'] ?? 0;
        $nota_calibrada = floatval($_POST['nota_calibrada'] ?? 0);
        $justificativa = trim($_POST['justificativa'] ?? '');
        
        // Validar percentual 0-100
        if ($nota_calibrada < 0 || $nota_calibrada > 100) {
            throw new Exception("Nota deve estar entre 0 e 100%");
        }
        
        if (empty($justificativa)) {
            throw new Exception("Justificativa é obrigatória");
        }
        
        // Buscar nota original
        $query = "SELECT nota_final FROM avaliacoes WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $avaliacao_id);
        $stmt->execute();
        $avaliacao = $stmt->fetch();
        
        if (!$avaliacao) {
            throw new Exception("Avaliação não encontrada");
        }
        
        $nota_original = $avaliacao['nota_final'];
        
        // Inserir calibração
        $query = "INSERT INTO calibracao 
                  (ciclo_id, avaliacao_id, usuario_calibrador_id, nota_original, nota_calibrada, justificativa) 
                  VALUES 
                  (:ciclo_id, :avaliacao_id, :calibrador_id, :nota_original, :nota_calibrada, :justificativa)";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':ciclo_id', $ciclo_id);
        $stmt->bindParam(':avaliacao_id', $avaliacao_id);
        $stmt->bindParam(':calibrador_id', $_SESSION['user_id']);
        $stmt->bindParam(':nota_original', $nota_original);
        $stmt->bindParam(':nota_calibrada', $nota_calibrada);
        $stmt->bindParam(':justificativa', $justificativa);
        
        if ($stmt->execute()) {
            // Atualizar nota final da avaliação
            $query = "UPDATE avaliacoes SET nota_final = :nota WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nota', $nota_calibrada);
            $stmt->bindParam(':id', $avaliacao_id);
            $stmt->execute();
            
            $_SESSION['success'] = "Calibração realizada com sucesso!";
        } else {
            throw new Exception("Erro ao salvar calibração");
        }
        
        ob_end_clean();
        header("Location: ciclo.php?id=$ciclo_id");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro: " . $e->getMessage();
        ob_end_clean();
        header("Location: ciclo.php?id=$ciclo_id");
        exit;
    }
}

// Buscar avaliações para calibração
$query_avaliacoes = "SELECT a.*, 
                            u.nome as avaliado_nome,
                            u.email,
                            c.nome as cargo_nome,
                            d.nome as departamento_nome,
                            g.nome as gestor_nome,
                            (SELECT COUNT(*) FROM calibracao WHERE avaliacao_id = a.id) as ja_calibrada
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

// Buscar histórico de calibrações
$query_historico = "SELECT c.*, 
                           u.nome as avaliado_nome,
                           uc.nome as calibrador_nome
                    FROM calibracao c
                    JOIN avaliacoes a ON c.avaliacao_id = a.id
                    JOIN usuarios u ON a.avaliado_id = u.id
                    JOIN usuarios uc ON c.usuario_calibrador_id = uc.id
                    WHERE c.ciclo_id = :ciclo_id
                    ORDER BY c.data_calibracao DESC";

$stmt_historico = $conn->prepare($query_historico);
$stmt_historico->bindParam(':ciclo_id', $ciclo_id);
$stmt_historico->execute();
$historico = $stmt_historico->fetchAll();

// Calcular estatísticas do ciclo
$notas = array_column($avaliacoes, 'nota_final');
$media = count($notas) > 0 ? array_sum($notas) / count($notas) : 0;
$max_nota = count($notas) > 0 ? max($notas) : 0;
$min_nota = count($notas) > 0 ? min($notas) : 0;

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Calibração: <?php echo htmlspecialchars($ciclo['nome']); ?></h2>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Alertas -->
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

        <!-- Cards de estatísticas (agora em percentual) -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h6 class="card-title">Total Avaliações</h6>
                        <h3><?php echo count($avaliacoes); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h6 class="card-title">Média Geral</h6>
                        <h3><?php echo number_format($media, 1); ?>%</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h6 class="card-title">Maior Nota</h6>
                        <h3><?php echo number_format($max_nota, 1); ?>%</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h6 class="card-title">Menor Nota</h6>
                        <h3><?php echo number_format($min_nota, 1); ?>%</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Matriz de Calibração -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Avaliações para Calibrar</h5>
            </div>
            <div class="card-body">
                <?php if (empty($avaliacoes)): ?>
                <p class="text-muted text-center py-4">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    Nenhuma avaliação concluída para calibrar.
                </p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Colaborador</th>
                                <th>Departamento</th>
                                <th>Cargo</th>
                                <th>Gestor</th>
                                <th>Nota Original</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($avaliacoes as $av): 
                                // CORREÇÃO DA COR BASEADA NA NOTA PERCENTUAL
                                $classe_nota = '';
                                if ($av['nota_final'] >= 80) {
                                    $classe_nota = 'excelente';
                                } elseif ($av['nota_final'] >= 60) {
                                    $classe_nota = 'medio';
                                } else {
                                    $classe_nota = 'baixo';
                                }
                            ?>
                            <tr class="<?php echo $av['ja_calibrada'] ? 'table-success' : ''; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($av['avaliado_nome']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($av['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($av['departamento_nome'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($av['cargo_nome'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($av['gestor_nome'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge-percentual <?php echo $classe_nota; ?>">
                                        <?php echo number_format($av['nota_final'], 1); ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php if ($av['ja_calibrada']): ?>
                                    <span class="badge bg-success">Calibrada</span>
                                    <?php else: ?>
                                    <span class="badge bg-warning">Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" 
                                            onclick="abrirCalibracao(
                                                <?php echo $av['id']; ?>, 
                                                '<?php echo htmlspecialchars($av['avaliado_nome']); ?>', 
                                                <?php echo $av['nota_final']; ?>
                                            )"
                                            <?php echo $av['ja_calibrada'] ? 'disabled' : ''; ?>>
                                        <i class="bi bi-sliders2"></i> Calibrar
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Histórico de Calibrações -->
        <?php if (!empty($historico)): ?>
        <div class="card">
            <div class="card-header">
                <h5>Histórico de Calibrações</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Colaborador</th>
                                <th>Calibrador</th>
                                <th>Nota Original</th>
                                <th>Nota Calibrada</th>
                                <th>Diferença</th>
                                <th>Justificativa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historico as $reg): 
                                $diferenca = $reg['nota_calibrada'] - $reg['nota_original'];
                                $classe_diferenca = $diferenca > 0 ? 'positiva' : ($diferenca < 0 ? 'negativa' : 'neutra');
                            ?>
                            <tr>
                                <td><?php echo $functions->formatDate($reg['data_calibracao'], 'd/m/Y H:i'); ?></td>
                                <td><?php echo htmlspecialchars($reg['avaliado_nome']); ?></td>
                                <td><?php echo htmlspecialchars($reg['calibrador_nome']); ?></td>
                                <td><?php echo number_format($reg['nota_original'], 1); ?>%</td>
                                <td><?php echo number_format($reg['nota_calibrada'], 1); ?>%</td>
                                <td>
                                    <span class="diferenca-<?php echo $classe_diferenca; ?>">
                                        <?php echo $diferenca > 0 ? '+' : ''; ?><?php echo number_format($diferenca, 1); ?>%
                                    </span>
                                </td>
                                <td><small><?php echo htmlspecialchars($reg['justificativa']); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Legenda de Cores -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="legenda-cores">
                    <h6><i class="bi bi-info-circle"></i> Legenda de Notas:</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="legenda-item">
                                <span class="legenda-cor alta"></span>
                                <span><strong>Excelente:</strong> 80% - 100%</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="legenda-item">
                                <span class="legenda-cor media"></span>
                                <span><strong>Médio:</strong> 60% - 79%</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="legenda-item">
                                <span class="legenda-cor baixa"></span>
                                <span><strong>Baixo:</strong> 0% - 59%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Calibração (CORRIGIDO) -->
<div class="modal fade" id="modalCalibracao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Calibrar Avaliação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="avaliacao_id" id="avaliacao_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Colaborador</label>
                        <input type="text" class="form-control" id="avaliado_nome" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nota Original</label>
                        <input type="text" class="form-control" id="nota_original" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nova Nota (%) *</label>
                        <input type="number" class="form-control" name="nota_calibrada" id="nota_calibrada" 
                               step="0.1" min="0" max="100" required>
                        <small class="text-muted">Use valores percentuais de 0 a 100%</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Justificativa *</label>
                        <textarea class="form-control" name="justificativa" rows="3" required
                                  placeholder="Explique o motivo da calibração..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Critérios de avaliação:</strong><br>
                        - 🔴 Vermelho: 0% - 59% (Baixo)<br>
                        - 🟡 Amarelo: 60% - 79% (Médio)<br>
                        - 🟢 Verde: 80% - 100% (Alto)
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="calibrar" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Confirmar Calibração
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirCalibracao(id, nome, nota) {
    document.getElementById('avaliacao_id').value = id;
    document.getElementById('avaliado_nome').value = nome;
    document.getElementById('nota_original').value = nota.toFixed(1) + '%';
    document.getElementById('nota_calibrada').value = nota.toFixed(1);
    
    var modal = new bootstrap.Modal(document.getElementById('modalCalibracao'));
    modal.show();
}
</script>

<?php 
require_once '../../includes/footer.php';
ob_end_flush();
?>
