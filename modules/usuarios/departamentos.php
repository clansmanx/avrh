<?php
// modules/usuarios/departamentos.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require_once '../../includes/header.php';
$auth->requirePermission(['admin', 'rh']);

$conn = (new Database())->getConnection();

// Buscar empresas para o select
$empresas = $conn->query("SELECT * FROM empresas WHERE ativo = 1 ORDER BY tipo, nome")->fetchAll();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['adicionar'])) {
        $nome = trim($_POST['nome']);
        $descricao = trim($_POST['descricao']);
        $empresa_id = !empty($_POST['empresa_id']) ? $_POST['empresa_id'] : null;
        
        $query = "INSERT INTO departamentos (nome, descricao, empresa_id) VALUES (:nome, :descricao, :empresa_id)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':empresa_id', $empresa_id);
        $stmt->execute();
        
        $_SESSION['success'] = "Departamento adicionado com sucesso!";
        
        ob_end_clean();
        header('Location: departamentos.php');
        exit;
    }
    
    if (isset($_POST['editar'])) {
        $id = $_POST['id'];
        $nome = trim($_POST['nome']);
        $descricao = trim($_POST['descricao']);
        $empresa_id = !empty($_POST['empresa_id']) ? $_POST['empresa_id'] : null;
        
        $query = "UPDATE departamentos SET nome = :nome, descricao = :descricao, empresa_id = :empresa_id WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':empresa_id', $empresa_id);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $_SESSION['success'] = "Departamento atualizado com sucesso!";
        
        ob_end_clean();
        header('Location: departamentos.php');
        exit;
    }
}

// Excluir departamento
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    
    // Verificar se há usuários no departamento
    $check = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE departamento_id = :id");
    $check->bindParam(':id', $id);
    $check->execute();
    $count = $check->fetchColumn();
    
    if ($count > 0) {
        $_SESSION['error'] = "Não é possível excluir: $count usuário(s) estão neste departamento.";
    } else {
        // Verificar se há cargos vinculados
        $check2 = $conn->prepare("SELECT COUNT(*) FROM cargo_departamento WHERE departamento_id = :id");
        $check2->bindParam(':id', $id);
        $check2->execute();
        $count2 = $check2->fetchColumn();
        
        if ($count2 > 0) {
            $del = $conn->prepare("DELETE FROM cargo_departamento WHERE departamento_id = :id");
            $del->bindParam(':id', $id);
            $del->execute();
        }
        
        $query = "DELETE FROM departamentos WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $_SESSION['success'] = "Departamento excluído com sucesso!";
    }
    
    ob_end_clean();
    header('Location: departamentos.php');
    exit;
}

// Buscar dados para edição
$editar = null;
if (isset($_GET['editar'])) {
    $stmt = $conn->prepare("SELECT * FROM departamentos WHERE id = :id");
    $stmt->bindParam(':id', $_GET['editar']);
    $stmt->execute();
    $editar = $stmt->fetch();
}

// Listar departamentos
$departamentos = $conn->query("
    SELECT d.*, e.nome as empresa_nome 
    FROM departamentos d
    LEFT JOIN empresas e ON d.empresa_id = e.id
    ORDER BY 
        CASE WHEN d.empresa_id IS NULL THEN 0 ELSE 1 END,
        e.nome, d.nome
")->fetchAll();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-building"></i> Gerenciar Departamentos</h2>
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
    <!-- Formulário de Novo/Editar Departamento -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><?php echo $editar ? 'Editar Departamento' : 'Novo Departamento'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php if ($editar): ?>
                        <input type="hidden" name="id" value="<?php echo $editar['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Empresa (opcional)</label>
                        <select class="form-select" name="empresa_id">
                            <option value="">Todas as empresas (comum)</option>
                            <?php foreach ($empresas as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>"
                                <?php echo ($editar && $editar['empresa_id'] == $emp['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['nome']); ?> (<?php echo ucfirst($emp['tipo']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Selecione uma empresa específica ou deixe em branco para disponível a todas</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nome do Departamento *</label>
                        <input type="text" class="form-control" name="nome" 
                               value="<?php echo $editar ? htmlspecialchars($editar['nome']) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" rows="3"><?php echo $editar ? htmlspecialchars($editar['descricao']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" name="<?php echo $editar ? 'editar' : 'adicionar'; ?>" class="btn btn-primary">
                        <i class="bi bi-save"></i> <?php echo $editar ? 'Atualizar' : 'Salvar'; ?>
                    </button>
                    
                    <?php if ($editar): ?>
                    <a href="departamentos.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <!-- Dica -->
        <div class="card mt-3 bg-light">
            <div class="card-body">
                <h6><i class="bi bi-info-circle"></i> Como funciona?</h6>
                <p class="small mb-0">
                    <strong>Departamentos comuns:</strong> Deixe "Empresa" em branco - ficam disponíveis para todas as empresas.<br>
                    <strong>Departamentos exclusivos:</strong> Selecione uma empresa específica - só aparecerão para ela.<br>
                    <strong>Exemplo:</strong> "ADMINISTRAÇÃO" pode ser exclusivo da Matriz.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Lista de Departamentos -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Departamentos Cadastrados</h5>
            </div>
            <div class="card-body">
                <?php if (empty($departamentos)): ?>
                <p class="text-muted text-center py-4">
                    <i class="bi bi-building fs-1 d-block mb-3"></i>
                    Nenhum departamento cadastrado.
                </p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Disponibilidade</th>
                                <th>Departamento</th>
                                <th>Descrição</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departamentos as $d): ?>
                            <tr>
                                <td>
                                    <?php if ($d['empresa_id']): ?>
                                    <span class="badge bg-primary">
                                        <i class="bi bi-building"></i> <?php echo htmlspecialchars($d['empresa_nome']); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-globe"></i> Todas as empresas
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($d['nome']); ?></strong></td>
                                <td><?php echo htmlspecialchars($d['descricao']); ?></td>
                                <td>
                                    <a href="?editar=<?php echo $d['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="?excluir=<?php echo $d['id']; ?>" 
                                       class="btn btn-sm btn-danger" title="Excluir"
                                       onclick="return confirm('Excluir este departamento?')">
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
