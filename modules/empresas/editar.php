<?php
// modules/empresas/editar.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require_once '../../includes/header.php';
$auth->requirePermission(['admin', 'rh']);

$conn = (new Database())->getConnection();

$id = $_GET['id'] ?? 0;

// Buscar dados da empresa
$query = "SELECT * FROM empresas WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$empresa = $stmt->fetch();

if (!$empresa) {
    $_SESSION['error'] = "Empresa não encontrada";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

$estados = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

$erros = [];
$dados = $empresa; // Inicializa com os dados da empresa

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coletar dados
    $dados = $_POST;
    $dados['id'] = $id;
    
    // Validar formulário
    $regras = [
        'nome' => [
            'required' => true,
            'tipo' => 'nome',
            'max_length' => 100
        ],
        'cnpj' => [
            'cnpj' => true
        ],
        'telefone' => [
            'telefone' => true
        ]
    ];
    
    $erros = $functions->validarFormulario($dados, $regras);
    
    if (empty($erros)) {
        // Formatar dados
        $nome = $functions->validarEFormatarInput($dados['nome'], 'nome');
        $tipo = $dados['tipo'] ?? 'filial';
        $cnpj = $functions->validarEFormatarInput($dados['cnpj'] ?? '', 'cnpj');
        $telefone = $functions->validarEFormatarInput($dados['telefone'] ?? '', 'telefone');
        $endereco = trim($dados['endereco'] ?? '');
        $cidade = trim($dados['cidade'] ?? '');
        $estado = $dados['estado'] ?? '';
        
        try {
            $query = "UPDATE empresas SET 
                      nome = :nome, 
                      tipo = :tipo, 
                      cnpj = :cnpj, 
                      endereco = :endereco, 
                      cidade = :cidade, 
                      estado = :estado, 
                      telefone = :telefone 
                      WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':cnpj', $cnpj);
            $stmt->bindParam(':endereco', $endereco);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':telefone', $telefone);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $_SESSION['success'] = "Empresa atualizada com sucesso!";
            ob_end_clean();
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $erros['geral'][] = "Erro ao atualizar: " . $e->getMessage();
        }
    }
}

require_once '../../includes/header.php';
?>

<!-- Incluir máscaras JS -->
<?php echo $functions->mascaraJS(); ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-pencil-square"></i> Editar Empresa</h2>
            <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
        </div>

        <?php if (!empty($erros['geral'])): ?>
            <div class="alert alert-danger">
                <?php echo implode('<br>', $erros['geral']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Informações da Empresa</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Nome da Empresa *</label>
                            <input type="text" 
                                   class="form-control <?php echo isset($erros['nome']) ? 'is-invalid' : ''; ?>" 
                                   name="nome" 
                                   onkeyup="apenasLetras(this); formatarNomeInput(this)"
                                   maxlength="100"
                                   value="<?php echo htmlspecialchars($dados['nome'] ?? ''); ?>"
                                   required>
                            <?php if (isset($erros['nome'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo implode(', ', $erros['nome']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tipo *</label>
                            <select class="form-select" name="tipo" required>
                                <option value="matriz" <?php echo (isset($dados['tipo']) && $dados['tipo'] == 'matriz') ? 'selected' : ''; ?>>Matriz</option>
                                <option value="filial" <?php echo (!isset($dados['tipo']) || $dados['tipo'] == 'filial') ? 'selected' : ''; ?>>Filial</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">CNPJ</label>
                            <input type="text" 
                                   class="form-control <?php echo isset($erros['cnpj']) ? 'is-invalid' : ''; ?>" 
                                   name="cnpj" 
                                   onkeyup="mascaraCNPJ(this)"
                                   maxlength="18"
                                   value="<?php echo htmlspecialchars($dados['cnpj'] ?? ''); ?>"
                                   placeholder="00.000.000/0000-00">
                            <?php if (isset($erros['cnpj'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo implode(', ', $erros['cnpj']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" 
                                   class="form-control <?php echo isset($erros['telefone']) ? 'is-invalid' : ''; ?>" 
                                   name="telefone" 
                                   onkeyup="mascaraTelefone(this)"
                                   maxlength="15"
                                   value="<?php echo htmlspecialchars($dados['telefone'] ?? ''); ?>"
                                   placeholder="(00) 0000-0000">
                            <?php if (isset($erros['telefone'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo implode(', ', $erros['telefone']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Endereço</label>
                        <textarea class="form-control" name="endereco" rows="2"><?php echo htmlspecialchars($dados['endereco'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cidade</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="cidade"
                                   onkeyup="apenasLetras(this); formatarNomeInput(this)"
                                   value="<?php echo htmlspecialchars($dados['cidade'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="">Selecione...</option>
                                <?php foreach ($estados as $uf): ?>
                                <option value="<?php echo $uf; ?>" <?php echo (isset($dados['estado']) && $dados['estado'] == $uf) ? 'selected' : ''; ?>>
                                    <?php echo $uf; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Atualizar Empresa
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
