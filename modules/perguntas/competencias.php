<?php
// modules/perguntas/competencias.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require_once '../../config/database.php';
require_once '../../includes/auth.php';

$database = new Database();
$conn = $database->getConnection();
$auth = new Auth();

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
        $_SESSION['success'] = "Competência adicionada com sucesso!";
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

<style>
.badge-comportamental {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    font-size: 0.75rem;
    padding: 6px 12px;
    border-radius: 50px;
    display: inline-block;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
}
.badge-tecnica {
    background: linear-gradient(135deg, #17a2b8, #0dcaf0);
    color: white;
    font-size: 0.75rem;
    padding: 6px 12px;
    border-radius: 50px;
    display: inline-block;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(23, 162, 184, 0.2);
}
.badge-organizacional {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
    color: #212529;
    font-size: 0.75rem;
    padding: 6px 12px;
    border-radius: 50px;
    display: inline-block;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(255, 193, 7, 0.2);
}
.competencia-card {
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.05);
    border-radius: 12px;
    margin-bottom: 12px;
}
.competencia-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    border-color: transparent;
}
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-stars text-primary"></i> Competências</h2>
            <a href="../perguntas/" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Mensagens -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Coluna do Formulário -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-plus-circle me-2"></i>
                            Nova Competência
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-tag me-1"></i> Nome da Competência
                                </label>
                                <input type="text" class="form-control form-control-lg" 
                                       name="nome" placeholder="Ex: Liderança" required>
                                <small class="text-muted">Digite um nome único e descritivo</small>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-chat-text me-1"></i> Descrição
                                </label>
                                <textarea class="form-control" name="descricao" rows="4" 
                                          placeholder="Descreva o que esta competência significa..."></textarea>
                                <small class="text-muted">Opcional, mas ajuda na compreensão</small>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-diagram-3 me-1"></i> Tipo
                                </label>
                                <select class="form-select form-select-lg" name="tipo">
                                    <option value="comportamental" class="py-2">🧠 Comportamental</option>
                                    <option value="técnica" class="py-2">💻 Técnica</option>
                                    <option value="organizacional" class="py-2">🏢 Organizacional</option>
                                </select>
                                <div class="mt-2 d-flex gap-2">
                                    <span class="badge-comportamental small">Comportamental</span>
                                    <span class="badge-tecnica small">Técnica</span>
                                    <span class="badge-organizacional small">Organizacional</span>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-3">
                                <i class="bi bi-save me-2"></i> Salvar Competência
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Card de Informações -->
                <div class="card bg-light border-0 mt-4">
                    <div class="card-body">
                        <h6><i class="bi bi-info-circle me-2"></i> Sobre Competências</h6>
                        <p class="small text-muted mb-2">
                            Competências são utilizadas para agrupar perguntas nos formulários de avaliação.
                        </p>
                        <hr class="my-2">
                        <div class="small">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge-comportamental me-2">🧠</span>
                                <span><strong>Comportamental:</strong> Habilidades interpessoais e atitudes</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge-tecnica me-2">💻</span>
                                <span><strong>Técnica:</strong> Conhecimentos específicos do cargo</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge-organizacional me-2">🏢</span>
                                <span><strong>Organizacional:</strong> Cultura, processos e visão</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna da Lista -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-info text-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-list-check me-2"></i>
                            Competências Cadastradas
                        </h5>
                        <span class="badge bg-white text-info rounded-pill px-3 py-2">
                            Total: <?php echo count($competencias); ?>
                        </span>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($competencias)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-emoji-neutral fs-1 text-muted d-block mb-3"></i>
                                <h5 class="text-muted">Nenhuma competência cadastrada</h5>
                                <p class="text-muted">Comece adicionando sua primeira competência ao lado.</p>
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($competencias as $c): ?>
                                    <?php
                                        $badge_class = 'badge-comportamental';
                                        $icone = '🧠';
                                        $titulo_tipo = 'Comportamental';
                                        
                                        if ($c['tipo'] == 'técnica' || $c['tipo'] == 'tecnica') {
                                            $badge_class = 'badge-tecnica';
                                            $icone = '💻';
                                            $titulo_tipo = 'Técnica';
                                        } elseif ($c['tipo'] == 'organizacional') {
                                            $badge_class = 'badge-organizacional';
                                            $icone = '🏢';
                                            $titulo_tipo = 'Organizacional';
                                        }
                                    ?>
                                    <div class="col-md-6">
                                        <div class="competencia-card card h-100 border-0 shadow-sm">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($c['nome']); ?></h5>
                                                    <?php if ($auth->hasPermission('admin')): ?>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="?delete=<?php echo $c['id']; ?>" 
                                                                   onclick="return confirm('Excluir esta competência?\n\nEsta ação não pode ser desfeita.')">
                                                                    <i class="bi bi-trash me-2"></i> Excluir
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <?php if (!empty($c['descricao'])): ?>
                                                    <p class="text-muted small mb-3">
                                                        <?php echo nl2br(htmlspecialchars($c['descricao'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <div class="d-flex justify-content-between align-items-center mt-3">
                                                    <span class="<?php echo $badge_class; ?>">
                                                        <?php echo $icone . ' ' . $titulo_tipo; ?>
                                                    </span>
                                                    
                                                    <?php
                                                    // Contar perguntas vinculadas
                                                    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM perguntas WHERE competencia_id = ?");
                                                    $stmt->execute([$c['id']]);
                                                    $total_perguntas = $stmt->fetch()['total'];
                                                    ?>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-2">
                                                        <i class="bi bi-chat me-1"></i> <?php echo $total_perguntas; ?> perguntas
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Legenda -->
                            <div class="mt-4 pt-3 border-top">
                                <div class="d-flex gap-4 justify-content-center">
                                    <span><span class="badge-comportamental px-3 py-2">🧠 Comportamental</span></span>
                                    <span><span class="badge-tecnica px-3 py-2">💻 Técnica</span></span>
                                    <span><span class="badge-organizacional px-3 py-2">🏢 Organizacional</span></span>
                                </div>
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
