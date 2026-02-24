<?php
// modules/usuarios/cargos.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require_once '../../includes/header.php';
$auth->requirePermission(['admin', 'rh']);

$conn = (new Database())->getConnection();

// Processar formulário (adicionar/editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['adicionar'])) {
        $nome = $_POST['nome'];
        $nivel = $_POST['nivel'];
        $descricao = $_POST['descricao'];
        
        $query = "INSERT INTO cargos (nome, nivel, descricao) VALUES (:nome, :nivel, :descricao)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':nivel', $nivel);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->execute();
        
        $_SESSION['success'] = "Cargo adicionado com sucesso!";
        
        ob_end_clean();
        header('Location: cargos.php');
        exit;
    }
    
    if (isset($_POST['editar'])) {
        $id = $_POST['id'];
        $nome = $_POST['nome'];
        $nivel = $_POST['nivel'];
        $descricao = $_POST['descricao'];
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        $query = "UPDATE cargos SET nome = :nome, nivel = :nivel, descricao = :descricao, ativo = :ativo WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':nivel', $nivel);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':ativo', $ativo);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $_SESSION['success'] = "Cargo atualizado com sucesso!";
        
        ob_end_clean();
        header('Location: cargos.php');
        exit;
    }
}

// Excluir cargo
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    
    // Verificar se há usuários usando este cargo
    $check = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE cargo_id = :id");
    $check->bindParam(':id', $id);
    $check->execute();
    $count = $check->fetchColumn();
    
    if ($count > 0) {
        $_SESSION['error'] = "Não é possível excluir: $count usuário(s) estão vinculados a este cargo.";
    } else {
        // Verificar se há vínculos com departamentos
        $check2 = $conn->prepare("SELECT COUNT(*) FROM cargo_departamento WHERE cargo_id = :id");
        $check2->bindParam(':id', $id);
        $check2->execute();
        $count2 = $check2->fetchColumn();
        
        if ($count2 > 0) {
            $del = $conn->prepare("DELETE FROM cargo_departamento WHERE cargo_id = :id");
            $del->bindParam(':id', $id);
            $del->execute();
        }
        
        $query = "DELETE FROM cargos WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $_SESSION['success'] = "Cargo excluído com sucesso!";
    }
    
    ob_end_clean();
    header('Location: cargos.php');
    exit;
}

// Buscar dados para edição
$editar = null;
if (isset($_GET['editar'])) {
    $stmt = $conn->prepare("SELECT * FROM cargos WHERE id = :id");
    $stmt->bindParam(':id', $_GET['editar']);
    $stmt->execute();
    $editar = $stmt->fetch();
}

// Listar cargos (são globais, sem vínculo com empresa)
$cargos = $conn->query("SELECT * FROM cargos ORDER BY nome")->fetchAll();

$niveis = ['Estágio', 'Júnior', 'Pleno', 'Sênior', 'Especialista', 'Coordenador', 'Gerente', 'Diretor'];

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-person-badge"></i> Gerenciar Cargos</h2>
            <a href="../usuarios/" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Mensagens -->
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
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><?php echo $editar ? 'Editar Cargo' : 'Novo Cargo'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php if ($editar): ?>
                        <input type="hidden" name="id" value="<?php echo $editar['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Nome do Cargo *</label>
                        <input type="text" class="form-control" name="nome" 
                               value="<?php echo $editar ? htmlspecialchars($editar['nome']) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nível</label>
                        <select class="form-select" name="nivel">
                            <option value="">Sem nível</option>
                            <?php foreach ($niveis as $n): ?>
                            <option value="<?php echo $n; ?>" 
                                <?php echo ($editar && $editar['nivel'] == $n) ? 'selected' : ''; ?>>
                                <?php echo $n; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" rows="3"><?php echo $editar ? htmlspecialchars($editar['descricao']) : ''; ?></textarea>
                    </div>
                    
                    <?php if ($editar): ?>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="ativo" id="ativo" 
                               <?php echo $editar['ativo'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="ativo">Cargo Ativo</label>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" name="<?php echo $editar ? 'editar' : 'adicionar'; ?>" class="btn btn-primary">
                        <i class="bi bi-save"></i> <?php echo $editar ? 'Atualizar' : 'Salvar'; ?>
                    </button>
                    
                    <?php if ($editar): ?>
                    <a href="cargos.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <div class="card mt-3 bg-light">
            <div class="card-body">
                <h6><i class="bi bi-info-circle"></i> Cargos são globais</h6>
                <p class="small mb-0">
                    Os cargos são compartilhados entre todas as empresas. 
                    Use a tela de "Vincular Cargos" para associar cargos a departamentos.
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Cargos Cadastrados</h5>
            </div>
            <div class="card-body">
                <?php if (empty($cargos)): ?>
                <p class="text-muted text-center py-4">
                    <i class="bi bi-person-badge fs-1 d-block mb-3"></i>
                    Nenhum cargo cadastrado.
                </p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Cargo</th>
                                <th>Nível</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cargos as $c): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($c['nome']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($c['descricao']); ?></small>
                                </td>
                                <td><?php echo $c['nivel'] ?: '-'; ?></td>
                                <td>
                                    <?php if ($c['ativo']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?editar=<?php echo $c['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="?excluir=<?php echo $c['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Excluir este cargo?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
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

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
