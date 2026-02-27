<?php
// modules/empresas/editar.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require_once '../../includes/header.php';
$auth->requirePermission(['admin', 'rh']);

$conn = (new Database())->getConnection();

$id = $_GET['id'] ?? 0;

$estados = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

// Buscar empresa
$stmt = $conn->prepare("SELECT * FROM empresas WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$empresa = $stmt->fetch();

if (!$empresa) {
    $_SESSION['error'] = "Empresa não encontrada";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $tipo = $_POST['tipo'];
    $cnpj = $_POST['cnpj'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $cidade = $_POST['cidade'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    $query = "UPDATE empresas SET 
              nome = :nome, 
              tipo = :tipo, 
              cnpj = :cnpj, 
              endereco = :endereco, 
              cidade = :cidade, 
              estado = :estado, 
              telefone = :telefone,
              ativo = :ativo
              WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':tipo', $tipo);
    $stmt->bindParam(':cnpj', $cnpj);
    $stmt->bindParam(':endereco', $endereco);
    $stmt->bindParam(':cidade', $cidade);
    $stmt->bindParam(':estado', $estado);
    $stmt->bindParam(':telefone', $telefone);
    $stmt->bindParam(':ativo', $ativo);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    $_SESSION['success'] = "Empresa atualizada com sucesso!";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-pencil"></i> Editar Empresa</h2>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><?php echo htmlspecialchars($empresa['nome']); ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Nome da Empresa *</label>
                            <input type="text" class="form-control" name="nome" 
                                   value="<?php echo htmlspecialchars($empresa['nome']); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tipo *</label>
                            <select class="form-select" name="tipo" required>
                                <option value="matriz" <?php echo $empresa['tipo'] == 'matriz' ? 'selected' : ''; ?>>Matriz</option>
                                <option value="filial" <?php echo $empresa['tipo'] == 'filial' ? 'selected' : ''; ?>>Filial</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">CNPJ</label>
                            <input type="text" class="form-control" name="cnpj" 
                                   value="<?php echo htmlspecialchars($empresa['cnpj'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="telefone" 
                                   value="<?php echo htmlspecialchars($empresa['telefone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Endereço</label>
                        <textarea class="form-control" name="endereco" rows="2"><?php echo htmlspecialchars($empresa['endereco'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cidade</label>
                            <input type="text" class="form-control" name="cidade" 
                                   value="<?php echo htmlspecialchars($empresa['cidade'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="">Selecione...</option>
                                <?php foreach ($estados as $uf): ?>
                                <option value="<?php echo $uf; ?>" 
                                    <?php echo ($empresa['estado'] ?? '') == $uf ? 'selected' : ''; ?>>
                                    <?php echo $uf; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="ativo" id="ativo" 
                               <?php echo $empresa['ativo'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="ativo">Empresa Ativa</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Atualizar Empresa
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
