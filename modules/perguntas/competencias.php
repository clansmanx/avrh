<?php
// modules/perguntas/competencias.php
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

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $tipo = $_POST['tipo'] ?? 'comportamental';
    
    if (!empty($nome)) {
        $query = "INSERT INTO competencias (nome, descricao, tipo) VALUES (:nome, :descricao, :tipo)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->execute();
        $_SESSION['success'] = "Competência adicionada!";
    }
    
    ob_end_clean();
    header('Location: competencias.php');
    exit;
}

// Processar exclusão
if (isset($_GET['delete']) && $auth->hasPermission('admin')) {
    $id = $_GET['delete'];
    
    // Verificar se há perguntas usando esta competência
    $query = "SELECT COUNT(*) as total FROM perguntas WHERE competencia_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $total = $stmt->fetch()['total'];
    
    if ($total > 0) {
        $_SESSION['error'] = "Não é possível excluir: existem perguntas usando esta competência";
    } else {
        $query = "DELETE FROM competencias WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $_SESSION['success'] = "Competência excluída!";
    }
    
    ob_end_clean();
    header('Location: competencias.php');
    exit;
}

// Buscar competências
$query = "SELECT * FROM competencias ORDER BY tipo, nome";
$stmt = $conn->query($query);
$competencias = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Competências</h2>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <h5>Nova Competência</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nome *</label>
                                <input type="text" class="form-control" name="nome" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descrição</label>
                                <textarea class="form-control" name="descricao" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" name="tipo">
                                    <option value="comportamental">Comportamental</option>
                                    <option value="tecnica">Técnica</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Salvar
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <h5>Competências Cadastradas</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($competencias)): ?>
                        <p class="text-muted">Nenhuma competência cadastrada.</p>
                        <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($competencias as $c): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($c['nome']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($c['descricao'] ?? ''); ?></small>
                                    <span class="badge bg-<?php echo $c['tipo'] == 'tecnica' ? 'info' : 'success'; ?> ms-2">
                                        <?php echo ucfirst($c['tipo']); ?>
                                    </span>
                                </div>
                                <?php if ($auth->hasPermission('admin')): ?>
                                <a href="?delete=<?php echo $c['id']; ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Excluir esta competência?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once '../../includes/footer.php';
ob_end_flush();
?>
