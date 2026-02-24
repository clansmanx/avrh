<?php
// modules/rh/integracao.php
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
    $usuario_id = $_POST['usuario_id'];
    $data_integracao = $_POST['data_integracao'];
    $conhece_missao = isset($_POST['conhece_missao']) ? 1 : 0;
    $conhece_visao = isset($_POST['conhece_visao']) ? 1 : 0;
    $conhece_valores = isset($_POST['conhece_valores']) ? 1 : 0;
    $assinou_termo = isset($_POST['assinou_termo']) ? 1 : 0;
    $observacoes = $_POST['observacoes'] ?? '';
    
    $query = "INSERT INTO integracao (usuario_id, data_integracao, conhece_missao, conhece_visao, conhece_valores, assinou_termo, observacoes, realizado_por) 
              VALUES (:usuario_id, :data_integracao, :conhece_missao, :conhece_visao, :conhece_valores, :assinou_termo, :observacoes, :realizado_por)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->bindParam(':data_integracao', $data_integracao);
    $stmt->bindParam(':conhece_missao', $conhece_missao);
    $stmt->bindParam(':conhece_visao', $conhece_visao);
    $stmt->bindParam(':conhece_valores', $conhece_valores);
    $stmt->bindParam(':assinou_termo', $assinou_termo);
    $stmt->bindParam(':observacoes', $observacoes);
    $stmt->bindParam(':realizado_por', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Integração registrada com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao registrar integração.";
    }
    
    ob_end_clean();
    header('Location: integracao.php');
    exit;
}

// Buscar registros
$query = "SELECT i.*, u.nome as colaborador, r.nome as realizador,
                 u.foto_perfil, u.email, c.nome as cargo_nome, d.nome as departamento_nome
          FROM integracao i
          JOIN usuarios u ON i.usuario_id = u.id
          JOIN usuarios r ON i.realizado_por = r.id
          LEFT JOIN cargos c ON u.cargo_id = c.id
          LEFT JOIN departamentos d ON u.departamento_id = d.id
          ORDER BY i.data_integracao DESC, i.data_criacao DESC";
$stmt = $conn->query($query);
$integracoes = $stmt->fetchAll();

// Buscar colaboradores que ainda não foram integrados
$query_colab = "SELECT u.id, u.nome 
                FROM usuarios u
                LEFT JOIN integracao i ON u.id = i.usuario_id
                WHERE i.id IS NULL AND u.ativo = 1
                ORDER BY u.nome";
$stmt_colab = $conn->query($query_colab);
$colaboradores = $stmt_colab->fetchAll();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Integração de Colaboradores</h2>
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
                        <h5 class="mb-0">Registrar Integração</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($colaboradores)): ?>
                        <div class="alert alert-info">
                            Todos os colaboradores já foram integrados.
                        </div>
                        <?php else: ?>
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
                                <label class="form-label">Data da Integração *</label>
                                <input type="date" class="form-control" name="data_integracao" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="card mb-3 bg-light">
                                <div class="card-body">
                                    <h6>Checklist de Integração</h6>
                                    
                                    <div class="mb-2 form-check">
                                        <input type="checkbox" class="form-check-input" name="conhece_missao" id="conhece_missao">
                                        <label class="form-check-label" for="conhece_missao">
                                            Conhece a Missão da empresa
                                        </label>
                                    </div>
                                    
                                    <div class="mb-2 form-check">
                                        <input type="checkbox" class="form-check-input" name="conhece_visao" id="conhece_visao">
                                        <label class="form-check-label" for="conhece_visao">
                                            Conhece a Visão da empresa
                                        </label>
                                    </div>
                                    
                                    <div class="mb-2 form-check">
                                        <input type="checkbox" class="form-check-input" name="conhece_valores" id="conhece_valores">
                                        <label class="form-check-label" for="conhece_valores">
                                            Conhece os Valores da empresa
                                        </label>
                                    </div>
                                    
                                    <div class="mb-2 form-check">
                                        <input type="checkbox" class="form-check-input" name="assinou_termo" id="assinou_termo">
                                        <label class="form-check-label" for="assinou_termo">
                                            Assinou o termo de integração
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Observações</label>
                                <textarea class="form-control" name="observacoes" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-save"></i> Registrar Integração
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Histórico de Integrações</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($integracoes)): ?>
                        <p class="text-muted text-center py-4">
                            <i class="bi bi-person-badge fs-1 d-block mb-3"></i>
                            Nenhuma integração registrada.
                        </p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Colaborador</th>
                                        <th>Cargo/Depto</th>
                                        <th>Checklist</th>
                                        <th>Realizado por</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($integracoes as $int): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($int['data_integracao'])); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($int['foto_perfil']): ?>
                                                <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $int['foto_perfil']; ?>" 
                                                     class="rounded-circle me-2" width="30" height="30">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($int['colaborador']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($int['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($int['cargo_nome'] ?? '-'); ?><br>
                                            <small><?php echo htmlspecialchars($int['departamento_nome'] ?? '-'); ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            $checklist = [];
                                            if ($int['conhece_missao']) $checklist[] = 'Missão';
                                            if ($int['conhece_visao']) $checklist[] = 'Visão';
                                            if ($int['conhece_valores']) $checklist[] = 'Valores';
                                            if ($int['assinou_termo']) $checklist[] = 'Termo';
                                            ?>
                                            <span class="badge bg-success"><?php echo count($checklist); ?>/4</span>
                                            <small class="d-block text-muted"><?php echo implode(', ', $checklist); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($int['realizador']); ?></td>
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
