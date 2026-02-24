<?php
// modules/perguntas/index.php
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

// Processar exclusão
if (isset($_GET['delete']) && $auth->hasPermission('admin')) {
    $id = $_GET['delete'];
    
    $query = "DELETE FROM perguntas WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Pergunta excluída com sucesso!";
    }
    
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Buscar perguntas com joins
$query = "SELECT p.*, f.nome as formulario_nome, f.tipo as formulario_tipo, c.nome as competencia_nome
          FROM perguntas p
          LEFT JOIN formularios f ON p.formulario_id = f.id
          LEFT JOIN competencias c ON p.competencia_id = c.id
          ORDER BY f.nome, p.ordem";
$stmt = $conn->query($query);
$perguntas = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Perguntas das Avaliações</h2>
            <div>
                <a href="competencias.php" class="btn btn-info me-2">
                    <i class="bi bi-tags"></i> Competências
                </a>
                <a href="adicionar.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nova Pergunta
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (empty($perguntas)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-question-circle fs-1 d-block mb-3"></i>
            <h4>Nenhuma pergunta cadastrada</h4>
            <p class="mb-0">Clique em "Nova Pergunta" para começar.</p>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ordem</th>
                                <th>Pergunta</th>
                                <th>Formulário</th>
                                <th>Competência</th>
                                <th>Tipo</th>
                                <th>Peso</th>
                                <th>Obrig.</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perguntas as $p): ?>
                            <tr>
                                <td><?php echo $p['ordem']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars(substr($p['texto'], 0, 60)); ?></strong>
                                    <?php if (strlen($p['texto']) > 60): ?>...<?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    // Aplicar classe CSS baseada no tipo do formulário
                                    $form_class = '';
                                    switch ($p['formulario_tipo']) {
                                        case 'rh':
                                            $form_class = 'badge-form-rh';
                                            break;
                                        case 'rotina':
                                            $form_class = 'badge-form-rotina';
                                            break;
                                        case 'autoavaliacao':
                                            $form_class = 'badge-form-autoavaliacao';
                                            break;
                                        case 'gestor':
                                            $form_class = 'badge-form-gestor';
                                            break;
                                        case '360':
                                            $form_class = 'badge-form-360';
                                            break;
                                        default:
                                            $form_class = 'badge bg-secondary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $form_class; ?>">
                                        <?php echo htmlspecialchars($p['formulario_nome']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($p['competencia_nome']): ?>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($p['competencia_nome']); ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $tipos = [
                                        'escala_1_5' => '<span class="badge bg-success">Escala 1-5</span>',
                                        'sim_nao' => '<span class="badge bg-warning text-dark">Sim/Não</span>',
                                        'texto' => '<span class="badge bg-info">Texto</span>',
                                        'nota' => '<span class="badge bg-danger">Nota 0-10</span>'
                                    ];
                                    
                                    if (isset($tipos[$p['tipo_resposta']])) {
                                        echo $tipos[$p['tipo_resposta']];
                                    } else {
                                        echo '<span class="badge bg-secondary">' . htmlspecialchars($p['tipo_resposta']) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td class="text-center"><?php echo $p['peso']; ?></td>
                                <td class="text-center">
                                    <?php if ($p['obrigatorio']): ?>
                                    <i class="bi bi-check-lg text-success fs-5"></i>
                                    <?php else: ?>
                                    <i class="bi bi-x-lg text-danger fs-5"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="editar.php?id=<?php echo $p['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($auth->hasPermission('admin')): ?>
                                        <a href="?delete=<?php echo $p['id']; ?>" 
                                           class="btn btn-sm btn-danger" title="Excluir"
                                           onclick="return confirm('Tem certeza que deseja excluir esta pergunta?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php 
require_once '../../includes/footer.php';
ob_end_flush();
?>
