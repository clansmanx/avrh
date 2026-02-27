<?php
// modules/pdi/editar_meta.php
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

$meta_id = $_GET['id'] ?? 0;

// Buscar meta
$query = "SELECT m.*, pdi.id as pdi_id, pdi.titulo as pdi_titulo
          FROM pdi_metas m
          JOIN pdi ON m.pdi_id = pdi.id
          WHERE m.id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $meta_id);
$stmt->execute();
$meta = $stmt->fetch();

if (!$meta) {
    $_SESSION['error'] = "Meta não encontrada";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

$pdi_id = $meta['pdi_id'];

// Verificar permissão
$user_id = $auth->getUserId();
$user_tipo = $auth->getUserType();

$query_gestor = "SELECT gestor_responsavel_id FROM pdi WHERE id = :pdi_id";
$stmt_gestor = $conn->prepare($query_gestor);
$stmt_gestor->bindParam(':pdi_id', $pdi_id);
$stmt_gestor->execute();
$pdi_info = $stmt_gestor->fetch();

if (!in_array($user_tipo, ['admin', 'rh']) && $user_id != $pdi_info['gestor_responsavel_id']) {
    $_SESSION['error'] = "Você não tem permissão para editar esta meta";
    ob_end_clean();
    header("Location: visualizar.php?id=$pdi_id");
    exit;
}

// Buscar competências do PDI
$query_comp = "SELECT pc.id, c.nome 
               FROM pdi_competencias pc
               JOIN competencias c ON pc.competencia_id = c.id
               WHERE pc.pdi_id = :pdi_id";
$stmt_comp = $conn->prepare($query_comp);
$stmt_comp->bindParam(':pdi_id', $pdi_id);
$stmt_comp->execute();
$competencias = $stmt_comp->fetchAll();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $titulo = trim($_POST['titulo']);
        $descricao = trim($_POST['descricao']);
        $criterio_sucesso = trim($_POST['criterio_sucesso']);
        $data_prazo = $_POST['data_prazo'];
        $peso = intval($_POST['peso'] ?? 1);
        $prioridade = $_POST['prioridade'] ?? 'media';
        $progresso = intval($_POST['progresso'] ?? 0);
        $status = $_POST['status'];
        $competencia_id = !empty($_POST['competencia_id']) ? $_POST['competencia_id'] : null;
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        if (empty($titulo)) throw new Exception("Título é obrigatório");
        if (empty($descricao)) throw new Exception("Descrição é obrigatória");
        if (empty($criterio_sucesso)) throw new Exception("Critério de sucesso é obrigatório");
        if (empty($data_prazo)) throw new Exception("Prazo é obrigatório");
        
        $query = "UPDATE pdi_metas SET 
                  competencia_id = :competencia_id,
                  titulo = :titulo,
                  descricao = :descricao,
                  criterio_sucesso = :criterio_sucesso,
                  data_prazo = :data_prazo,
                  peso = :peso,
                  prioridade = :prioridade,
                  progresso = :progresso,
                  status = :status,
                  observacoes = :observacoes
                  WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':competencia_id', $competencia_id);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':criterio_sucesso', $criterio_sucesso);
        $stmt->bindParam(':data_prazo', $data_prazo);
        $stmt->bindParam(':peso', $peso);
        $stmt->bindParam(':prioridade', $prioridade);
        $stmt->bindParam(':progresso', $progresso);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':observacoes', $observacoes);
        $stmt->bindParam(':id', $meta_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Meta atualizada com sucesso!";
            ob_end_clean();
            header("Location: visualizar.php?id=$pdi_id");
            exit;
        } else {
            throw new Exception("Erro ao atualizar meta");
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
            <h2><i class="bi bi-pencil"></i> Editar Meta SMART</h2>
            <a href="visualizar.php?id=<?php echo $pdi_id; ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="alert alert-info">
            <strong>PDI:</strong> <?php echo htmlspecialchars($meta['pdi_titulo']); ?>
        </div>

        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0">Editar Meta</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Título da Meta *</label>
                        <input type="text" class="form-control" name="titulo" 
                               value="<?php echo htmlspecialchars($meta['titulo']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição *</label>
                        <textarea class="form-control" name="descricao" rows="3" required><?php echo htmlspecialchars($meta['descricao']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Critério de Sucesso *</label>
                        <input type="text" class="form-control" name="criterio_sucesso" 
                               value="<?php echo htmlspecialchars($meta['criterio_sucesso']); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Prazo *</label>
                            <input type="date" class="form-control" name="data_prazo" 
                                   value="<?php echo $meta['data_prazo']; ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Peso</label>
                            <input type="number" class="form-control" name="peso" 
                                   value="<?php echo $meta['peso']; ?>" min="1" max="5">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Prioridade</label>
                            <select class="form-select" name="prioridade">
                                <option value="baixa" <?php echo $meta['prioridade'] == 'baixa' ? 'selected' : ''; ?>>Baixa</option>
                                <option value="media" <?php echo $meta['prioridade'] == 'media' ? 'selected' : ''; ?>>Média</option>
                                <option value="alta" <?php echo $meta['prioridade'] == 'alta' ? 'selected' : ''; ?>>Alta</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Progresso (%)</label>
                            <input type="number" class="form-control" name="progresso" 
                                   value="<?php echo $meta['progresso']; ?>" min="0" max="100" step="5">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="pendente" <?php echo $meta['status'] == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                <option value="em_andamento" <?php echo $meta['status'] == 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                                <option value="concluida" <?php echo $meta['status'] == 'concluida' ? 'selected' : ''; ?>>Concluída</option>
                                <option value="cancelada" <?php echo $meta['status'] == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Vincular a Competência</label>
                        <select class="form-select" name="competencia_id">
                            <option value="">Nenhuma</option>
                            <?php foreach ($competencias as $c): ?>
                            <option value="<?php echo $c['id']; ?>"
                                <?php echo $c['id'] == $meta['competencia_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"><?php echo htmlspecialchars($meta['observacoes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Atualizar Meta
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
