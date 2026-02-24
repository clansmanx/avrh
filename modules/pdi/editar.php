<?php
// modules/pdi/editar.php
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

$pdi_id = $_GET['id'] ?? 0;

// Buscar PDI
$query = "SELECT p.*, u_colab.nome as colaborador_nome
          FROM pdi p
          JOIN usuarios u_colab ON p.colaborador_id = u_colab.id
          WHERE p.id = :id";
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

// Verificar permissão
$user_id = $auth->getUserId();
$user_tipo = $auth->getUserType();
if (!in_array($user_tipo, ['admin', 'rh']) && $user_id != $pdi['gestor_responsavel_id']) {
    $_SESSION['error'] = "Você não tem permissão para editar este PDI";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Buscar colaboradores para o select
$query_colab = "SELECT id, nome FROM usuarios WHERE ativo = 1 AND tipo = 'colaborador' ORDER BY nome";
$stmt_colab = $conn->query($query_colab);
$colaboradores = $stmt_colab->fetchAll();

// Buscar ciclos finalizados
$query_ciclos = "SELECT id, nome FROM ciclos_avaliacao WHERE status = 'finalizado' ORDER BY data_fim DESC";
$stmt_ciclos = $conn->query($query_ciclos);
$ciclos = $stmt_ciclos->fetchAll();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $colaborador_id = $_POST['colaborador_id'];
        $titulo = trim($_POST['titulo']);
        $ciclo_id = !empty($_POST['ciclo_id']) ? $_POST['ciclo_id'] : null;
        $data_revisao = !empty($_POST['data_revisao']) ? $_POST['data_revisao'] : null;
        $status = $_POST['status'];
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        if (empty($colaborador_id)) throw new Exception("Selecione um colaborador");
        if (empty($titulo)) throw new Exception("Título é obrigatório");
        
        $query = "UPDATE pdi SET 
                  colaborador_id = :colaborador_id,
                  titulo = :titulo,
                  ciclo_id = :ciclo_id,
                  data_revisao = :data_revisao,
                  status = :status,
                  observacoes_gerais = :observacoes
                  WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':colaborador_id', $colaborador_id);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':ciclo_id', $ciclo_id);
        $stmt->bindParam(':data_revisao', $data_revisao);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':observacoes', $observacoes);
        $stmt->bindParam(':id', $pdi_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "PDI atualizado com sucesso!";
            ob_end_clean();
            header("Location: visualizar.php?id=$pdi_id");
            exit;
        } else {
            throw new Exception("Erro ao atualizar PDI");
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
            <h2><i class="bi bi-pencil"></i> Editar PDI</h2>
            <a href="visualizar.php?id=<?php echo $pdi_id; ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0">Informações do PDI</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Colaborador *</label>
                            <select class="form-select" name="colaborador_id" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($colaboradores as $c): ?>
                                <option value="<?php echo $c['id']; ?>" 
                                    <?php echo $c['id'] == $pdi['colaborador_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Título do PDI *</label>
                            <input type="text" class="form-control" name="titulo" 
                                   value="<?php echo htmlspecialchars($pdi['titulo']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vincular a um Ciclo (opcional)</label>
                            <select class="form-select" name="ciclo_id">
                                <option value="">Nenhum</option>
                                <?php foreach ($ciclos as $ciclo): ?>
                                <option value="<?php echo $ciclo['id']; ?>"
                                    <?php echo $ciclo['id'] == $pdi['ciclo_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ciclo['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data de Revisão</label>
                            <input type="date" class="form-control" name="data_revisao" 
                                   value="<?php echo $pdi['data_revisao']; ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="ativo" <?php echo $pdi['status'] == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                                <option value="em_andamento" <?php echo $pdi['status'] == 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                                <option value="concluido" <?php echo $pdi['status'] == 'concluido' ? 'selected' : ''; ?>>Concluído</option>
                                <option value="cancelado" <?php echo $pdi['status'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="4"><?php echo htmlspecialchars($pdi['observacoes_gerais'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Atualizar PDI
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
