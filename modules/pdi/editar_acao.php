<?php
// modules/pdi/editar_acao.php
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

$acao_id = $_GET['id'] ?? 0;

// Buscar ação
$query = "SELECT a.*, pdi.id as pdi_id, pdi.titulo as pdi_titulo
          FROM pdi_acoes a
          JOIN pdi ON a.pdi_id = pdi.id
          WHERE a.id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $acao_id);
$stmt->execute();
$acao = $stmt->fetch();

if (!$acao) {
    $_SESSION['error'] = "Ação não encontrada";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

$pdi_id = $acao['pdi_id'];

// Verificar permissão
$user_id = $auth->getUserId();
$user_tipo = $auth->getUserType();

$query_gestor = "SELECT gestor_responsavel_id FROM pdi WHERE id = :pdi_id";
$stmt_gestor = $conn->prepare($query_gestor);
$stmt_gestor->bindParam(':pdi_id', $pdi_id);
$stmt_gestor->execute();
$pdi_info = $stmt_gestor->fetch();

if (!in_array($user_tipo, ['admin', 'rh']) && $user_id != $pdi_info['gestor_responsavel_id']) {
    $_SESSION['error'] = "Você não tem permissão para editar esta ação";
    ob_end_clean();
    header("Location: visualizar.php?id=$pdi_id");
    exit;
}

// Buscar metas do PDI para vincular
$query_metas = "SELECT id, titulo FROM pdi_metas WHERE pdi_id = :pdi_id AND status != 'concluida'";
$stmt_metas = $conn->prepare($query_metas);
$stmt_metas->bindParam(':pdi_id', $pdi_id);
$stmt_metas->execute();
$metas = $stmt_metas->fetchAll();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $titulo = trim($_POST['titulo']);
        $descricao = trim($_POST['descricao']);
        $tipo = $_POST['tipo'];
        $data_inicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
        $data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;
        $recurso_necessario = trim($_POST['recurso_necessario'] ?? '');
        $custo_estimado = !empty($_POST['custo_estimado']) ? floatval($_POST['custo_estimado']) : null;
        $progresso = intval($_POST['progresso'] ?? 0);
        $status = $_POST['status'];
        $meta_id = !empty($_POST['meta_id']) ? $_POST['meta_id'] : null;
        $evidencia = trim($_POST['evidencia'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        if (empty($titulo)) throw new Exception("Título é obrigatório");
        if (empty($descricao)) throw new Exception("Descrição é obrigatória");
        
        $query = "UPDATE pdi_acoes SET 
                  meta_id = :meta_id,
                  tipo = :tipo,
                  titulo = :titulo,
                  descricao = :descricao,
                  data_inicio = :data_inicio,
                  data_fim = :data_fim,
                  recurso_necessario = :recurso_necessario,
                  custo_estimado = :custo_estimado,
                  progresso = :progresso,
                  status = :status,
                  evidencia = :evidencia,
                  observacoes = :observacoes
                  WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':meta_id', $meta_id);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->bindParam(':recurso_necessario', $recurso_necessario);
        $stmt->bindParam(':custo_estimado', $custo_estimado);
        $stmt->bindParam(':progresso', $progresso);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':evidencia', $evidencia);
        $stmt->bindParam(':observacoes', $observacoes);
        $stmt->bindParam(':id', $acao_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Ação atualizada com sucesso!";
            ob_end_clean();
            header("Location: visualizar.php?id=$pdi_id");
            exit;
        } else {
            throw new Exception("Erro ao atualizar ação");
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-pencil"></i> Editar Ação de Desenvolvimento</h2>
            <a href="visualizar.php?id=<?php echo $pdi_id; ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="alert alert-info">
            <strong>PDI:</strong> <?php echo htmlspecialchars($acao['pdi_titulo']); ?>
        </div>

        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0">Editar Ação</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Título da Ação *</label>
                        <input type="text" class="form-control" name="titulo" 
                               value="<?php echo htmlspecialchars($acao['titulo']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição *</label>
                        <textarea class="form-control" name="descricao" rows="3" required><?php echo htmlspecialchars($acao['descricao']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Ação *</label>
                            <select class="form-select" name="tipo" required>
                                <option value="treinamento" <?php echo $acao['tipo'] == 'treinamento' ? 'selected' : ''; ?>>📚 Treinamento (10%)</option>
                                <option value="mentoria" <?php echo $acao['tipo'] == 'mentoria' ? 'selected' : ''; ?>>👥 Mentoria/Coaching (20%)</option>
                                <option value="feedback" <?php echo $acao['tipo'] == 'feedback' ? 'selected' : ''; ?>>💬 Feedback estruturado (20%)</option>
                                <option value="projeto" <?php echo $acao['tipo'] == 'projeto' ? 'selected' : ''; ?>>🛠️ Projeto desafiador (70%)</option>
                                <option value="job_rotation" <?php echo $acao['tipo'] == 'job_rotation' ? 'selected' : ''; ?>>🔄 Job Rotation (70%)</option>
                                <option value="leitura" <?php echo $acao['tipo'] == 'leitura' ? 'selected' : ''; ?>>📖 Leitura/Estudo (10%)</option>
                                <option value="workshop" <?php echo $acao['tipo'] == 'workshop' ? 'selected' : ''; ?>>🎯 Workshop (10%)</option>
                                <option value="outro" <?php echo $acao['tipo'] == 'outro' ? 'selected' : ''; ?>>📌 Outro</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vincular a Meta</label>
                            <select class="form-select" name="meta_id">
                                <option value="">Nenhuma</option>
                                <?php foreach ($metas as $meta): ?>
                                <option value="<?php echo $meta['id']; ?>"
                                    <?php echo $meta['id'] == $acao['meta_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($meta['titulo']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Data de Início</label>
                            <input type="date" class="form-control" name="data_inicio" 
                                   value="<?php echo $acao['data_inicio']; ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Data de Término</label>
                            <input type="date" class="form-control" name="data_fim" 
                                   value="<?php echo $acao['data_fim']; ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Progresso (%)</label>
                            <input type="number" class="form-control" name="progresso" 
                                   value="<?php echo $acao['progresso']; ?>" min="0" max="100" step="5">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Recursos Necessários</label>
                            <input type="text" class="form-control" name="recurso_necessario" 
                                   value="<?php echo htmlspecialchars($acao['recurso_necessario'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Custo Estimado (R$)</label>
                            <input type="number" class="form-control" name="custo_estimado" 
                                   value="<?php echo $acao['custo_estimado']; ?>" step="0.01" min="0">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="pendente" <?php echo $acao['status'] == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                <option value="em_andamento" <?php echo $acao['status'] == 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                                <option value="concluida" <?php echo $acao['status'] == 'concluida' ? 'selected' : ''; ?>>Concluída</option>
                                <option value="cancelada" <?php echo $acao['status'] == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Evidência (link/comprovante)</label>
                            <input type="url" class="form-control" name="evidencia" 
                                   value="<?php echo htmlspecialchars($acao['evidencia'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"><?php echo htmlspecialchars($acao['observacoes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Atualizar Ação
                        </button>
                        <a href="visualizar.php?id=<?php echo $pdi_id; ?>" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
