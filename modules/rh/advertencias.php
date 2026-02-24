<?php
// modules/rh/advertencias.php
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
    
    $query = "DELETE FROM advertencias WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    $_SESSION['success'] = "Registro excluído com sucesso!";
    ob_end_clean();
    header('Location: advertencias.php');
    exit;
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_POST['usuario_id'];
    $tipo = $_POST['tipo'];
    $data_registro = $_POST['data_registro'];
    $motivo = $_POST['motivo'];
    
    $query = "INSERT INTO advertencias (usuario_id, tipo, data_registro, motivo, registrado_por) 
              VALUES (:usuario_id, :tipo, :data_registro, :motivo, :registrado_por)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->bindParam(':tipo', $tipo);
    $stmt->bindParam(':data_registro', $data_registro);
    $stmt->bindParam(':motivo', $motivo);
    $stmt->bindParam(':registrado_por', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Registro salvo com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao salvar registro.";
    }
    
    ob_end_clean();
    header('Location: advertencias.php');
    exit;
}

// Buscar registros
$query = "SELECT a.*, u.nome as colaborador, r.nome as registrador,
                 u.foto_perfil
          FROM advertencias a
          JOIN usuarios u ON a.usuario_id = u.id
          JOIN usuarios r ON a.registrado_por = r.id
          ORDER BY a.data_registro DESC, a.data_criacao DESC";
$stmt = $conn->query($query);
$registros = $stmt->fetchAll();

// Buscar colaboradores para o select
$query_colab = "SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY nome";
$stmt_colab = $conn->query($query_colab);
$colaboradores = $stmt_colab->fetchAll();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Registro de Advertências e Orientações</h2>
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
                        <h5 class="mb-0">Novo Registro</h5>
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
                                <label class="form-label">Tipo *</label>
                                <select class="form-select" name="tipo" required>
                                    <option value="orientacao">📝 Orientação</option>
                                    <option value="advertencia">⚠️ Advertência</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Data do Registro *</label>
                                <input type="date" class="form-control" name="data_registro" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Motivo *</label>
                                <textarea class="form-control" name="motivo" rows="4" 
                                          placeholder="Descreva o motivo da orientação/advertência..." required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-save"></i> Salvar Registro
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Histórico de Registros</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($registros)): ?>
                        <p class="text-muted text-center py-4">
                            <i class="bi bi-journal-text fs-1 d-block mb-3"></i>
                            Nenhum registro encontrado.
                        </p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Colaborador</th>
                                        <th>Tipo</th>
                                        <th>Motivo</th>
                                        <th>Registrado por</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registros as $reg): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($reg['data_registro'])); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($reg['foto_perfil']): ?>
                                                <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $reg['foto_perfil']; ?>" 
                                                     class="rounded-circle me-2" width="30" height="30">
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($reg['colaborador']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($reg['tipo'] == 'advertencia'): ?>
                                            <span class="badge bg-danger">Advertência</span>
                                            <?php else: ?>
                                            <span class="badge bg-warning">Orientação</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars(substr($reg['motivo'], 0, 50)); ?>...
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#modalMotivo<?php echo $reg['id']; ?>">
                                                Ver mais
                                            </a>
                                            
                                            <div class="modal fade" id="modalMotivo<?php echo $reg['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Motivo do Registro</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><strong>Colaborador:</strong> <?php echo $reg['colaborador']; ?></p>
                                                            <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($reg['data_registro'])); ?></p>
                                                            <p><strong>Tipo:</strong> <?php echo ucfirst($reg['tipo']); ?></p>
                                                            <hr>
                                                            <p><?php echo nl2br(htmlspecialchars($reg['motivo'])); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($reg['registrador']); ?></td>
                                        <td>
                                            <?php if ($auth->hasPermission('admin')): ?>
                                            <a href="?delete=<?php echo $reg['id']; ?>" 
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

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
