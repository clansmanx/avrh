<?php
// modules/pdi/acompanhamentos.php
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

$pdi_id = $_GET['pdi_id'] ?? 0;

if (!$pdi_id) {
    $_SESSION['error'] = "ID do PDI não fornecido";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Buscar PDI
$query = "SELECT p.*, u_colab.nome as colaborador_nome, u_gestor.nome as gestor_nome
          FROM pdi p
          JOIN usuarios u_colab ON p.colaborador_id = u_colab.id
          JOIN usuarios u_gestor ON p.gestor_responsavel_id = u_gestor.id
          WHERE p.id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $pdi_id);
$stmt->execute();
$pdi = $stmt->fetch();

if (!$pdi) {
    $_SESSION['error'] = "PDI não encontrado";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Verificar permissão
$user_id = $auth->getUserId();
$user_tipo = $auth->getUserType();
if (!in_array($user_tipo, ['admin', 'rh']) && $user_id != $pdi['gestor_responsavel_id']) {
    $_SESSION['error'] = "Você não tem permissão para ver acompanhamentos deste PDI";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Processar novo acompanhamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data_acompanhamento = $_POST['data_acompanhamento'];
        $progresso_geral = intval($_POST['progresso_geral']);
        $topicos_discutidos = trim($_POST['topicos_discutidos']);
        $dificuldades_encontradas = trim($_POST['dificuldades_encontradas'] ?? '');
        $proximos_passos = trim($_POST['proximos_passos'] ?? '');
        $nova_data_revisao = !empty($_POST['nova_data_revisao']) ? $_POST['nova_data_revisao'] : null;
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        $conn->beginTransaction();
        
        // Inserir acompanhamento
        $query = "INSERT INTO pdi_acompanhamentos 
                  (pdi_id, responsavel_id, data_acompanhamento, progresso_geral, topicos_discutidos, dificuldades_encontradas, proximos_passos, nova_data_revisao, observacoes) 
                  VALUES 
                  (:pdi_id, :responsavel_id, :data_acompanhamento, :progresso_geral, :topicos_discutidos, :dificuldades_encontradas, :proximos_passos, :nova_data_revisao, :observacoes)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':pdi_id', $pdi_id);
        $stmt->bindParam(':responsavel_id', $_SESSION['user_id']);
        $stmt->bindParam(':data_acompanhamento', $data_acompanhamento);
        $stmt->bindParam(':progresso_geral', $progresso_geral);
        $stmt->bindParam(':topicos_discutidos', $topicos_discutidos);
        $stmt->bindParam(':dificuldades_encontradas', $dificuldades_encontradas);
        $stmt->bindParam(':proximos_passos', $proximos_passos);
        $stmt->bindParam(':nova_data_revisao', $nova_data_revisao);
        $stmt->bindParam(':observacoes', $observacoes);
        $stmt->execute();
        
        // Atualizar progresso geral do PDI
        $query = "UPDATE pdi SET progresso_geral = :progresso, data_revisao = COALESCE(:nova_data_revisao, data_revisao) WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':progresso', $progresso_geral);
        $stmt->bindParam(':nova_data_revisao', $nova_data_revisao);
        $stmt->bindParam(':id', $pdi_id);
        $stmt->execute();
        
        // Registrar histórico
        $descricao = "Realizou acompanhamento com progresso de {$progresso_geral}%";
        $query_hist = "INSERT INTO pdi_historico (pdi_id, usuario_id, acao, descricao) 
                       VALUES (:pdi_id, :usuario_id, 'acompanhou', :descricao)";
        $stmt_hist = $conn->prepare($query_hist);
        $stmt_hist->bindParam(':pdi_id', $pdi_id);
        $stmt_hist->bindParam(':usuario_id', $_SESSION['user_id']);
        $stmt_hist->bindParam(':descricao', $descricao);
        $stmt_hist->execute();
        
        $conn->commit();
        
        $_SESSION['success'] = "Acompanhamento registrado com sucesso!";
        ob_end_clean();
        header("Location: acompanhamentos.php?pdi_id=$pdi_id");
        exit;
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}

// Buscar acompanhamentos existentes
$query_acomp = "SELECT a.*, u.nome as responsavel_nome
                FROM pdi_acompanhamentos a
                JOIN usuarios u ON a.responsavel_id = u.id
                WHERE a.pdi_id = :pdi_id
                ORDER BY a.data_acompanhamento DESC";
$stmt_acomp = $conn->prepare($query_acomp);
$stmt_acomp->bindParam(':pdi_id', $pdi_id);
$stmt_acomp->execute();
$acompanhamentos = $stmt_acomp->fetchAll();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-calendar-check"></i> Acompanhamentos do PDI</h2>
            <div>
                <a href="visualizar.php?id=<?php echo $pdi_id; ?>" class="btn btn-info">
                    <i class="bi bi-eye"></i> Ver PDI
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <div class="alert alert-info">
            <strong>PDI:</strong> <?php echo htmlspecialchars($pdi['titulo']); ?> - 
            <strong>Colaborador:</strong> <?php echo htmlspecialchars($pdi['colaborador_nome']); ?>
        </div>

        <!-- Mensagens -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Formulário de novo acompanhamento -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Novo Acompanhamento</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data do Acompanhamento *</label>
                            <input type="date" class="form-control" name="data_acompanhamento" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Progresso Geral (%) *</label>
                            <div class="d-flex align-items-center">
                                <input type="range" class="form-range" name="progresso_geral" 
                                       min="0" max="100" step="5" value="<?php echo $pdi['progresso_geral'] ?? 0; ?>"
                                       oninput="this.nextElementSibling.value = this.value + '%'">
                                <output class="ms-2 fw-bold"><?php echo $pdi['progresso_geral'] ?? 0; ?>%</output>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tópicos Discutidos *</label>
                        <textarea class="form-control" name="topicos_discutidos" rows="4" required 
                                  placeholder="O que foi discutido neste acompanhamento?"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Dificuldades Encontradas</label>
                        <textarea class="form-control" name="dificuldades_encontradas" rows="3" 
                                  placeholder="Houve alguma dificuldade?"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Próximos Passos</label>
                        <textarea class="form-control" name="proximos_passos" rows="3" 
                                  placeholder="O que deve ser feito até o próximo acompanhamento?"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nova Data de Revisão (opcional)</label>
                            <input type="date" class="form-control" name="nova_data_revisao">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Registrar Acompanhamento
                    </button>
                </form>
            </div>
        </div>

        <!-- Histórico de acompanhamentos -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Histórico de Acompanhamentos</h5>
            </div>
            <div class="card-body">
                <?php if (empty($acompanhamentos)): ?>
                <p class="text-muted text-center py-4">
                    <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                    Nenhum acompanhamento registrado ainda.
                </p>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($acompanhamentos as $acomp): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h6 class="card-title">
                                    <i class="bi bi-calendar-check"></i> 
                                    <?php echo date('d/m/Y', strtotime($acomp['data_acompanhamento'])); ?>
                                </h6>
                                <span class="badge bg-info">Progresso: <?php echo $acomp['progresso_geral']; ?>%</span>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-8">
                                    <p><strong>Tópicos discutidos:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($acomp['topicos_discutidos'])); ?></p>
                                    
                                    <?php if (!empty($acomp['dificuldades_encontradas'])): ?>
                                    <p class="text-danger">
                                        <strong>Dificuldades:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($acomp['dificuldades_encontradas'])); ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($acomp['proximos_passos'])): ?>
                                    <p class="text-success">
                                        <strong>Próximos passos:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($acomp['proximos_passos'])); ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($acomp['nova_data_revisao'])): ?>
                                    <p><strong>Nova data de revisão:</strong> 
                                    <?php echo date('d/m/Y', strtotime($acomp['nova_data_revisao'])); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($acomp['observacoes'])): ?>
                                    <p><small class="text-muted">Obs: <?php echo nl2br(htmlspecialchars($acomp['observacoes'])); ?></small></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 text-end">
                                    <small class="text-muted">
                                        por <?php echo htmlspecialchars($acomp['responsavel_nome']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.timeline .card {
    border-left: 4px solid #4e73df;
    margin-bottom: 15px;
}
.timeline .card:last-child {
    margin-bottom: 0;
}
</style>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
