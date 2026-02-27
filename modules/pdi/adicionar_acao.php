<?php
// modules/pdi/adicionar_acao.php
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

// Verificar se o PDI existe
$query = "SELECT * FROM pdi WHERE id = :id";
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

// Buscar metas do PDI para vincular
$query_metas = "SELECT id, titulo FROM pdi_metas WHERE pdi_id = :pdi_id AND status != 'concluida'";
$stmt_metas = $conn->prepare($query_metas);
$stmt_metas->bindParam(':pdi_id', $pdi_id);
$stmt_metas->execute();
$metas = $stmt_metas->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $titulo = trim($_POST['titulo']);
        $descricao = trim($_POST['descricao']);
        $tipo = $_POST['tipo'];
        $data_inicio = !empty($_POST['data_inicio']) ? $_POST['data_inicio'] : null;
        $data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;
        $recurso_necessario = trim($_POST['recurso_necessario'] ?? '');
        $custo_estimado = !empty($_POST['custo_estimado']) ? floatval($_POST['custo_estimado']) : null;
        $meta_id = !empty($_POST['meta_id']) ? $_POST['meta_id'] : null;
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        if (empty($titulo)) throw new Exception("Título é obrigatório");
        if (empty($descricao)) throw new Exception("Descrição é obrigatória");
        
        $query = "INSERT INTO pdi_acoes (pdi_id, meta_id, tipo, titulo, descricao, data_inicio, data_fim, recurso_necessario, custo_estimado, observacoes) 
                  VALUES (:pdi_id, :meta_id, :tipo, :titulo, :descricao, :data_inicio, :data_fim, :recurso_necessario, :custo_estimado, :observacoes)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':pdi_id', $pdi_id);
        $stmt->bindParam(':meta_id', $meta_id);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->bindParam(':recurso_necessario', $recurso_necessario);
        $stmt->bindParam(':custo_estimado', $custo_estimado);
        $stmt->bindParam(':observacoes', $observacoes);
        $stmt->execute();
        
        $_SESSION['success'] = "Ação adicionada com sucesso!";
        ob_end_clean();
        header("Location: visualizar.php?id=$pdi_id");
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-plus-circle"></i> Nova Ação de Desenvolvimento</h2>
            <a href="visualizar.php?id=<?php echo $pdi_id; ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Modelo 70-20-10</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4 text-center">
                    <div class="col-md-4">
                        <div class="border p-3 rounded bg-primary text-white">
                            <h3>70%</h3>
                            <p>Experiência Prática</p>
                            <small>Projetos, desafios, job rotation</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border p-3 rounded bg-success text-white">
                            <h3>20%</h3>
                            <p>Relacionamento</p>
                            <small>Mentoria, coaching, feedback</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border p-3 rounded bg-warning">
                            <h3>10%</h3>
                            <p>Educação Formal</p>
                            <small>Cursos, treinamentos, leituras</small>
                        </div>
                    </div>
                </div>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Título da Ação *</label>
                        <input type="text" class="form-control" name="titulo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição *</label>
                        <textarea class="form-control" name="descricao" rows="3" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Ação *</label>
                            <select class="form-select" name="tipo" required>
                                <option value="treinamento">📚 Treinamento (10%)</option>
                                <option value="mentoria">👥 Mentoria/Coaching (20%)</option>
                                <option value="feedback">💬 Feedback estruturado (20%)</option>
                                <option value="projeto">🛠️ Projeto desafiador (70%)</option>
                                <option value="job_rotation">🔄 Job Rotation (70%)</option>
                                <option value="leitura">📖 Leitura/Estudo (10%)</option>
                                <option value="workshop">🎯 Workshop (10%)</option>
                                <option value="outro">📌 Outro</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vincular a Meta (opcional)</label>
                            <select class="form-select" name="meta_id">
                                <option value="">Nenhuma</option>
                                <?php foreach ($metas as $meta): ?>
                                <option value="<?php echo $meta['id']; ?>">
                                    <?php echo htmlspecialchars($meta['titulo']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data de Início</label>
                            <input type="date" class="form-control" name="data_inicio" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data de Término</label>
                            <input type="date" class="form-control" name="data_fim">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Recursos Necessários</label>
                            <input type="text" class="form-control" name="recurso_necessario" 
                                   placeholder="Ex: Material didático, licença software...">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Custo Estimado (R$)</label>
                            <input type="number" class="form-control" name="custo_estimado" 
                                   step="0.01" min="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Adicionar Ação
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
