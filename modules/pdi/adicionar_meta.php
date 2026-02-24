<?php
// modules/pdi/adicionar_meta.php
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

// Buscar competências do PDI
$query_comp = "SELECT pc.id, c.nome, c.tipo
               FROM pdi_competencias pc
               JOIN competencias c ON pc.competencia_id = c.id
               WHERE pc.pdi_id = :pdi_id
               ORDER BY c.nome";
$stmt_comp = $conn->prepare($query_comp);
$stmt_comp->bindParam(':pdi_id', $pdi_id);
$stmt_comp->execute();
$competencias = $stmt_comp->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $titulo = trim($_POST['titulo']);
        $descricao = trim($_POST['descricao']);
        $criterio_sucesso = trim($_POST['criterio_sucesso']);
        $data_prazo = $_POST['data_prazo'];
        $peso = intval($_POST['peso'] ?? 1);
        $prioridade = $_POST['prioridade'] ?? 'media';
        $competencia_id = !empty($_POST['competencia_id']) ? $_POST['competencia_id'] : null;
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        if (empty($titulo)) $errors[] = "Título é obrigatório";
        if (empty($descricao)) $errors[] = "Descrição é obrigatória";
        if (empty($criterio_sucesso)) $errors[] = "Critério de sucesso é obrigatório";
        if (empty($data_prazo)) $errors[] = "Prazo é obrigatório";
        
        if (empty($errors)) {
            $query = "INSERT INTO pdi_metas (pdi_id, competencia_id, titulo, descricao, criterio_sucesso, data_prazo, peso, prioridade, observacoes) 
                      VALUES (:pdi_id, :competencia_id, :titulo, :descricao, :criterio_sucesso, :data_prazo, :peso, :prioridade, :observacoes)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':pdi_id', $pdi_id);
            $stmt->bindParam(':competencia_id', $competencia_id);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':criterio_sucesso', $criterio_sucesso);
            $stmt->bindParam(':data_prazo', $data_prazo);
            $stmt->bindParam(':peso', $peso);
            $stmt->bindParam(':prioridade', $prioridade);
            $stmt->bindParam(':observacoes', $observacoes);
            $stmt->execute();
            
            $_SESSION['success'] = "Meta adicionada com sucesso!";
            ob_end_clean();
            header("Location: visualizar.php?id=$pdi_id");
            exit;
        }
    } catch (Exception $e) {
        $errors[] = "Erro: " . $e->getMessage();
    }
}

require_once '../../includes/header.php';
?>

<style>
/* Garantir que textos em cards com fundo colorido fiquem brancos */
.card-header.bg-success,
.card-header.bg-success * {
    color: white !important;
}
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-plus-circle"></i> Nova Meta SMART</h2>
            <a href="visualizar.php?id=<?php echo $pdi_id; ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="alert alert-info">
            <strong>PDI:</strong> <?php echo htmlspecialchars($pdi['titulo']); ?>
        </div>

        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-bullseye me-2"></i>Critérios SMART</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="border p-3 rounded text-center bg-primary text-white">
                            <strong>S</strong><br>
                            <small>Específica</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="border p-3 rounded text-center bg-success text-white">
                            <strong>M</strong><br>
                            <small>Mensurável</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="border p-3 rounded text-center bg-warning text-dark">
                            <strong>A</strong><br>
                            <small>Alcançável</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="border p-3 rounded text-center bg-info text-white">
                            <strong>R</strong><br>
                            <small>Relevante</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="border p-3 rounded text-center bg-danger text-white">
                            <strong>T</strong><br>
                            <small>Temporal</small>
                        </div>
                    </div>
                </div>

                <form method="POST">
                    <div class="mb-3">
                        <label for="titulo" class="form-label fw-bold">Título da Meta *</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" 
                               placeholder="Ex: Concluir treinamento em Excel Avançado" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label fw-bold">Descrição *</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3" 
                                  placeholder="Detalhe o que precisa ser feito..." required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="criterio_sucesso" class="form-label fw-bold">Critério de Sucesso * (Como medir?)</label>
                        <input type="text" class="form-control" id="criterio_sucesso" name="criterio_sucesso" 
                               placeholder="Ex: Nota mínima 90% no teste final" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="data_prazo" class="form-label fw-bold">Prazo *</label>
                            <input type="date" class="form-control" id="data_prazo" name="data_prazo" 
                                   value="<?php echo date('Y-m-d', strtotime('+3 months')); ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="peso" class="form-label fw-bold">Peso</label>
                            <input type="number" class="form-control" id="peso" name="peso" 
                                   value="1" min="1" max="5">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="prioridade" class="form-label fw-bold">Prioridade</label>
                            <select class="form-select" id="prioridade" name="prioridade">
                                <option value="baixa">Baixa</option>
                                <option value="media" selected>Média</option>
                                <option value="alta">Alta</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="competencia_id" class="form-label fw-bold">Vincular a Competência (opcional)</label>
                        <select class="form-select" id="competencia_id" name="competencia_id">
                            <option value="">Nenhuma</option>
                            <?php foreach ($competencias as $c): ?>
                            <option value="<?php echo $c['id']; ?>">
                                <?php echo htmlspecialchars($c['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Vincule esta meta a uma competência específica do PDI</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label fw-bold">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                    </div>
                    
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-save"></i> Adicionar Meta
                        </button>
                        <a href="visualizar.php?id=<?php echo $pdi_id; ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
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
