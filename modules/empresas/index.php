<?php
// modules/empresas/index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require_once '../../includes/header.php';
$auth->requirePermission(['admin', 'rh']);

$conn = (new Database())->getConnection();

// Processar exclusão
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    
    // Verificar se há departamentos vinculados
    $check = $conn->prepare("SELECT COUNT(*) FROM departamentos WHERE empresa_id = :id");
    $check->bindParam(':id', $id);
    $check->execute();
    $count_deptos = $check->fetchColumn();
    
    // Verificar se há usuários vinculados
    $check2 = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id = :id");
    $check2->bindParam(':id', $id);
    $check2->execute();
    $count_usuarios = $check2->fetchColumn();
    
    if ($count_deptos > 0 || $count_usuarios > 0) {
        $_SESSION['error'] = "Não é possível excluir: $count_deptos departamento(s) e $count_usuarios usuário(s) vinculados.";
    } else {
        $query = "DELETE FROM empresas WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $_SESSION['success'] = "Empresa excluída com sucesso!";
    }
    
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Buscar empresas
$empresas = $conn->query("SELECT * FROM empresas ORDER BY tipo, nome")->fetchAll();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-building"></i> Empresas (Matriz/Filiais)</h2>
            <div>
                <a href="adicionar.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nova Empresa
                </a>
                <a href="../usuarios/" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <!-- Mensagens -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Cards de Empresas -->
        <div class="row">
            <?php foreach ($empresas as $emp): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 <?php echo $emp['tipo'] == 'matriz' ? 'border-primary' : 'border-secondary'; ?>">
                    <div class="card-header <?php echo $emp['tipo'] == 'matriz' ? 'bg-primary text-white' : 'bg-secondary text-white'; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-building"></i> <?php echo htmlspecialchars($emp['nome']); ?>
                            </h5>
                            <span class="badge bg-light text-dark">
                                <?php echo ucfirst($emp['tipo']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p><strong>CNPJ:</strong> <?php echo $emp['cnpj'] ?: 'Não informado'; ?></p>
                        <p><strong>Cidade/UF:</strong> <?php echo $emp['cidade'] ?: 'Não informado'; ?> <?php echo $emp['estado'] ? '/ ' . $emp['estado'] : ''; ?></p>
                        <p><strong>Telefone:</strong> <?php echo $emp['telefone'] ?: 'Não informado'; ?></p>
                        
                        <?php
                        // Contar departamentos
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM departamentos WHERE empresa_id = :id");
                        $stmt->bindParam(':id', $emp['id']);
                        $stmt->execute();
                        $total_deptos = $stmt->fetchColumn();
                        
                        // Contar usuários
                        $stmt2 = $conn->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id = :id");
                        $stmt2->bindParam(':id', $emp['id']);
                        $stmt2->execute();
                        $total_usuarios = $stmt2->fetchColumn();
                        ?>
                        
                        <div class="d-flex justify-content-between text-muted small">
                            <span><i class="bi bi-diagram-3"></i> <?php echo $total_deptos; ?> deptos</span>
                            <span><i class="bi bi-people"></i> <?php echo $total_usuarios; ?> usuários</span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <a href="editar.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-warning">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <a href="?excluir=<?php echo $emp['id']; ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Excluir esta empresa?')">
                            <i class="bi bi-trash"></i> Excluir
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
