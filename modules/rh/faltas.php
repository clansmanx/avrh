<?php
// modules/rh/faltas.php
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
    
    $query = "DELETE FROM faltas WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    $_SESSION['success'] = "Registro excluído com sucesso!";
    ob_end_clean();
    header('Location: faltas.php');
    exit;
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_POST['usuario_id'];
    $data_falta = $_POST['data_falta'];
    $justificada = isset($_POST['justificada']) ? 1 : 0;
    $justificativa = $_POST['justificativa'] ?? '';
    
    $query = "INSERT INTO faltas (usuario_id, data_falta, justificada, justificativa, registrado_por) 
              VALUES (:usuario_id, :data_falta, :justificada, :justificativa, :registrado_por)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->bindParam(':data_falta', $data_falta);
    $stmt->bindParam(':justificada', $justificada);
    $stmt->bindParam(':justificativa', $justificativa);
    $stmt->bindParam(':registrado_por', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Falta registrada com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao registrar falta.";
    }
    
    ob_end_clean();
    header('Location: faltas.php');
    exit;
}

// Buscar registros
$query = "SELECT f.*, u.nome as colaborador, r.nome as registrador,
                 u.foto_perfil
          FROM faltas f
          JOIN usuarios u ON f.usuario_id = u.id
          JOIN usuarios r ON f.registrado_por = r.id
          ORDER BY f.data_falta DESC, f.data_criacao DESC";
$stmt = $conn->query($query);
$faltas = $stmt->fetchAll();

// Buscar colaboradores para o select
$query_colab = "SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY nome";
$stmt_colab = $conn->query($query_colab);
$colaboradores = $stmt_colab->fetchAll();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Controle de Faltas</h2>
        </div>

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

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Registrar Falta</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Colaborador *</label>
                                <select class="form-select" name="usuario_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($colaboradores as $colab): ?>
                                    <option value="<?php echo $colab['id']; ?>">
                                        <?php echo htmlspecialchars($colab['nome']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Data da Falta *</label>
                                <input type="date" class="form-control" name="data_falta" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="justificada" id="justificada">
                                <label class="form-check-label" for="justificada">
                                    Falta justificada
                                </label>
                            </div>
                            
                            <div class="mb-3" id="justificativaDiv" style="display: none;">
                                <label class="form-label">Justificativa</label>
                                <textarea class="form-control" name="justificativa" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-save"></i> Registrar Falta
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Histórico de Faltas</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($faltas)): ?>
                        <p class="text-muted text-center py-4">
                            <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                            Nenhuma falta registrada.
                        </p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Colaborador</th>
                                        <th>Status</th>
                                        <th>Justificativa</th>
                                        <th>Registrado por</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($faltas as $falta): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($falta['data_falta'])); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($falta['foto_perfil']): ?>
                                                <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $falta['foto_perfil']; ?>" 
                                                     class="rounded-circle me-2" width="30" height="30">
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($falta['colaborador']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($falta['justificada']): ?>
                                            <span class="badge bg-success">Justificada</span>
                                            <?php else: ?>
                                            <span class="badge bg-danger">Injustificada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($falta['justificativa']): ?>
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#modalJustificativa<?php echo $falta['id']; ?>">
                                                Ver justificativa
                                            </a>
                                            
                                            <div class="modal fade" id="modalJustificativa<?php echo $falta['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Justificativa da Falta</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><strong>Colaborador:</strong> <?php echo $falta['colaborador']; ?></p>
                                                            <p><strong>Data da Falta:</strong> <?php echo date('d/m/Y', strtotime($falta['data_falta'])); ?></p>
                                                            <hr>
                                                            <p><?php echo nl2br(htmlspecialchars($falta['justificativa'])); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php else: ?>
                                            -
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($falta['registrador']); ?></td>
                                        <td>
                                            <?php if ($auth->hasPermission('admin')): ?>
                                            <a href="?delete=<?php echo $falta['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Excluir este registro?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('justificada').addEventListener('change', function() {
    document.getElementById('justificativaDiv').style.display = this.checked ? 'block' : 'none';
});
</script>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
