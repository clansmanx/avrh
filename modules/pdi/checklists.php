<?php
// modules/pdi/checklists.php
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

$auth->requireLogin();

$tipo = $_GET['tipo'] ?? ''; // 'meta' ou 'acao'
$item_id = $_GET['id'] ?? 0;
$pdi_id = $_GET['pdi_id'] ?? 0;

if (!$pdi_id) {
    $_SESSION['error'] = "ID do PDI não fornecido";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Buscar informações do item (meta ou ação)
if ($tipo == 'meta') {
    $query = "SELECT m.*, pdi.titulo as pdi_titulo
              FROM pdi_metas m
              JOIN pdi ON m.pdi_id = pdi.id
              WHERE m.id = :id AND m.pdi_id = :pdi_id";
} else {
    $query = "SELECT a.*, pdi.titulo as pdi_titulo
              FROM pdi_acoes a
              JOIN pdi ON a.pdi_id = pdi.id
              WHERE a.id = :id AND a.pdi_id = :pdi_id";
}

$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $item_id);
$stmt->bindParam(':pdi_id', $pdi_id);
$stmt->execute();
$item = $stmt->fetch();

if (!$item) {
    $_SESSION['error'] = ucfirst($tipo) . " não encontrado";
    ob_end_clean();
    header("Location: visualizar.php?id=$pdi_id");
    exit;
}

// Processar novo checklist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao'] ?? '');
    $data_prevista = !empty($_POST['data_prevista']) ? $_POST['data_prevista'] : null;
    $ordem = intval($_POST['ordem'] ?? 0);
    
    if (empty($titulo)) {
        $error = "Título do item é obrigatório";
    } else {
        $query = "INSERT INTO pdi_checklists (tipo, item_id, titulo, descricao, data_prevista, ordem) 
                  VALUES (:tipo, :item_id, :titulo, :descricao, :data_prevista, :ordem)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':data_prevista', $data_prevista);
        $stmt->bindParam(':ordem', $ordem);
        $stmt->execute();
        
        // Recalcular progresso
        if ($tipo == 'meta') {
            $functions->calcularProgressoMeta($item_id);
        } else {
            $functions->calcularProgressoAcao($item_id);
        }
        $functions->calcularProgressoGeralPDI($pdi_id);
        
        $_SESSION['success'] = "Item adicionado ao checklist";
        ob_end_clean();
        header("Location: checklists.php?tipo=$tipo&id=$item_id&pdi_id=$pdi_id");
        exit;
    }
}

// Processar marcar/desmarcar item
if (isset($_GET['checklist_id']) && isset($_GET['marcar'])) {
    $checklist_id = $_GET['checklist_id'];
    $marcar = $_GET['marcar'] == '1' ? 1 : 0;
    
    if ($marcar) {
        // Marcar como concluído
        $query = "INSERT INTO pdi_checklist_conclusoes (checklist_id, usuario_id) 
                  VALUES (:checklist_id, :usuario_id)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':checklist_id', $checklist_id);
        $stmt->bindParam(':usuario_id', $_SESSION['user_id']);
        $stmt->execute();
    } else {
        // Desmarcar
        $query = "DELETE FROM pdi_checklist_conclusoes 
                  WHERE checklist_id = :checklist_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':checklist_id', $checklist_id);
        $stmt->execute();
    }
    
    // Recalcular progresso
    if ($tipo == 'meta') {
        $functions->calcularProgressoMeta($item_id);
    } else {
        $functions->calcularProgressoAcao($item_id);
    }
    $functions->calcularProgressoGeralPDI($pdi_id);
    
    ob_end_clean();
    header("Location: checklists.php?tipo=$tipo&id=$item_id&pdi_id=$pdi_id");
    exit;
}

// Buscar checklists do item
$query_check = "SELECT pc.*, 
                       (SELECT COUNT(*) FROM pdi_checklist_conclusoes WHERE checklist_id = pc.id) as concluido,
                       (SELECT data_conclusao FROM pdi_checklist_conclusoes WHERE checklist_id = pc.id) as data_conclusao,
                       (SELECT usuario_id FROM pdi_checklist_conclusoes WHERE checklist_id = pc.id) as concluido_por
                FROM pdi_checklists pc
                WHERE pc.tipo = :tipo AND pc.item_id = :item_id
                ORDER BY pc.ordem, pc.data_prevista";
$stmt_check = $conn->prepare($query_check);
$stmt_check->bindParam(':tipo', $tipo);
$stmt_check->bindParam(':item_id', $item_id);
$stmt_check->execute();
$checklists = $stmt_check->fetchAll();

$total_itens = count($checklists);
$itens_concluidos = count(array_filter($checklists, fn($c) => $c['concluido'] > 0));
$progresso = $total_itens > 0 ? round(($itens_concluidos / $total_itens) * 100) : 0;

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="bi bi-check2-square"></i> 
                Checklist: <?php echo htmlspecialchars($item['titulo']); ?>
            </h2>
            <div>
                <a href="visualizar.php?id=<?php echo $pdi_id; ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar ao PDI
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Progresso da meta/ação -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5><?php echo ucfirst($tipo); ?>: <?php echo htmlspecialchars($item['titulo']); ?></h5>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($item['descricao'] ?? ''); ?></p>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <div class="progress flex-grow-1 me-3" style="height: 10px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $progresso; ?>%"></div>
                            </div>
                            <span class="h5 mb-0"><?php echo $progresso; ?>%</span>
                        </div>
                        <small class="text-muted">
                            <?php echo $itens_concluidos; ?> de <?php echo $total_itens; ?> itens concluídos
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulário para adicionar item -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Adicionar Item ao Checklist</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="adicionar" value="1">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Título do Item *</label>
                            <input type="text" class="form-control" name="titulo" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Ordem</label>
                            <input type="number" class="form-control" name="ordem" value="0" min="0">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Data Prevista</label>
                            <input type="date" class="form-control" name="data_prevista">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição (opcional)</label>
                        <textarea class="form-control" name="descricao" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus"></i> Adicionar Item
                    </button>
                </form>
            </div>
        </div>

        <!-- Lista de itens do checklist -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-check"></i> Itens do Checklist</h5>
            </div>
            <div class="card-body">
                <?php if (empty($checklists)): ?>
                <p class="text-muted text-center py-4">
                    <i class="bi bi-check2-square fs-1 d-block mb-3"></i>
                    Nenhum item adicionado ao checklist.
                </p>
                <?php else: ?>
                <div class="list-group">
                    <?php foreach ($checklists as $item): 
                        $concluido = $item['concluido'] > 0;
                        $data_prevista = $item['data_prevista'] ? date('d/m/Y', strtotime($item['data_prevista'])) : null;
                        $atrasado = $item['data_prevista'] && strtotime($item['data_prevista']) < time() && !$concluido;
                    ?>
                    <div class="list-group-item <?php echo $concluido ? 'list-group-item-success' : ($atrasado ? 'list-group-item-danger' : ''); ?>">
                        <div class="d-flex align-items-start">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" 
                                       <?php echo $concluido ? 'checked' : ''; ?>
                                       onchange="window.location.href='checklists.php?tipo=<?php echo $tipo; ?>&id=<?php echo $item_id; ?>&pdi_id=<?php echo $pdi_id; ?>&checklist_id=<?php echo $item['id']; ?>&marcar=' + (this.checked ? '1' : '0')">
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1 <?php echo $concluido ? 'text-decoration-line-through text-muted' : ''; ?>">
                                        <?php echo htmlspecialchars($item['titulo']); ?>
                                    </h6>
                                    <?php if ($data_prevista): ?>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar"></i> <?php echo $data_prevista; ?>
                                        <?php if ($atrasado): ?>
                                        <span class="badge bg-danger ms-2">Atrasado</span>
                                        <?php endif; ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                                <?php if ($item['descricao']): ?>
                                <p class="mb-1 small text-muted"><?php echo nl2br(htmlspecialchars($item['descricao'])); ?></p>
                                <?php endif; ?>
                                <?php if ($concluido && $item['data_conclusao']): ?>
                                <small class="text-success">
                                    <i class="bi bi-check-circle"></i> 
                                    Concluído em <?php echo date('d/m/Y H:i', strtotime($item['data_conclusao'])); ?>
                                </small>
                                <?php endif; ?>
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

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
