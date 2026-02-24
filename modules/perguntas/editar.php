<?php
// modules/perguntas/editar.php
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

$id = $_GET['id'] ?? 0;
$errors = [];

// Buscar dados da pergunta
$query = "SELECT * FROM perguntas WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$pergunta = $stmt->fetch();

if (!$pergunta) {
    $_SESSION['error'] = "Pergunta não encontrada";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Buscar formulários
$query_form = "SELECT * FROM formularios WHERE ativo = 1 ORDER BY nome";
$stmt_form = $conn->query($query_form);
$formularios = $stmt_form->fetchAll();

// Buscar competências
$query_comp = "SELECT * FROM competencias WHERE ativo = 1 ORDER BY nome";
$stmt_comp = $conn->query($query_comp);
$competencias = $stmt_comp->fetchAll();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $formulario_id = $_POST['formulario_id'] ?? '';
        $competencia_id = !empty($_POST['competencia_id']) ? $_POST['competencia_id'] : null;
        $texto = trim($_POST['texto'] ?? '');
        $tipo_resposta = $_POST['tipo_resposta'] ?? 'escala_1_5';
        $peso = intval($_POST['peso'] ?? 1);
        $ordem = intval($_POST['ordem'] ?? 0);
        $obrigatorio = isset($_POST['obrigatorio']) ? 1 : 0;
        
        if (empty($formulario_id)) $errors[] = "Formulário é obrigatório";
        if (empty($texto)) $errors[] = "Texto da pergunta é obrigatório";
        
        if (empty($errors)) {
            $query = "UPDATE perguntas SET 
                      formulario_id = :formulario_id,
                      competencia_id = :competencia_id,
                      texto = :texto,
                      tipo_resposta = :tipo_resposta,
                      peso = :peso,
                      ordem = :ordem,
                      obrigatorio = :obrigatorio
                      WHERE id = :id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':formulario_id', $formulario_id);
            $stmt->bindParam(':competencia_id', $competencia_id);
            $stmt->bindParam(':texto', $texto);
            $stmt->bindParam(':tipo_resposta', $tipo_resposta);
            $stmt->bindParam(':peso', $peso);
            $stmt->bindParam(':ordem', $ordem);
            $stmt->bindParam(':obrigatorio', $obrigatorio);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Pergunta atualizada com sucesso!";
                ob_end_clean();
                header('Location: index.php');
                exit;
            } else {
                $errors[] = "Erro ao atualizar pergunta";
            }
        }
    } catch (Exception $e) {
        $errors[] = "Erro: " . $e->getMessage();
    }
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Editar Pergunta</h2>
            <div>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Pergunta *</label>
                                <textarea class="form-control" name="texto" rows="4" required><?php echo htmlspecialchars($_POST['texto'] ?? $pergunta['texto']); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Formulário *</label>
                                    <select class="form-select" name="formulario_id" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($formularios as $f): ?>
                                        <option value="<?php echo $f['id']; ?>"
                                            <?php echo ($_POST['formulario_id'] ?? $pergunta['formulario_id']) == $f['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($f['nome']); ?> (<?php echo $f['tipo']; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Competência</label>
                                    <select class="form-select" name="competencia_id">
                                        <option value="">Nenhuma</option>
                                        <?php foreach ($competencias as $c): ?>
                                        <option value="<?php echo $c['id']; ?>"
                                            <?php echo ($_POST['competencia_id'] ?? $pergunta['competencia_id']) == $c['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['nome']); ?> (<?php echo $c['tipo']; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5>Configurações</h5>
                                </div>


                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Tipo de Resposta</label>
                                        <select class="form-select" name="tipo_resposta">
                                            <option value="escala_1_5" <?php echo ($_POST['tipo_resposta'] ?? $pergunta['tipo_resposta']) == 'escala_1_5' ? 'selected' : ''; ?>>Escala 1 a 5</option>
                                            <option value="texto" <?php echo ($_POST['tipo_resposta'] ?? $pergunta['tipo_resposta']) == 'texto' ? 'selected' : ''; ?>>Texto Livre</option>
                                            <option value="nota" <?php echo ($_POST['tipo_resposta'] ?? $pergunta['tipo_resposta']) == 'nota' ? 'selected' : ''; ?>>Nota (0-10)</option>
					    <option value="sim_nao" <?php echo ($tipo_resposta ?? '') == 'sim_nao' ? 'selected' : ''; ?>>Sim/Não (0 ou 1) </option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Peso</label>
                                        <input type="number" class="form-control" name="peso" 
                                               value="<?php echo $_POST['peso'] ?? $pergunta['peso']; ?>" min="0" max="10">
                                        <small class="text-muted">Influencia no cálculo da nota final</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Ordem</label>
                                        <input type="number" class="form-control" name="ordem" 
                                               value="<?php echo $_POST['ordem'] ?? $pergunta['ordem']; ?>" min="0">
                                        <small class="text-muted">Ordem de exibição no formulário</small>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" name="obrigatorio" 
                                               id="obrigatorio" <?php echo (isset($_POST['obrigatorio']) || $pergunta['obrigatorio']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="obrigatorio">
                                            Pergunta Obrigatória
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Informações adicionais -->
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5>Informações</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>ID:</strong> #<?php echo $pergunta['id']; ?></p>
                                    <p><strong>Criada em:</strong> <?php echo date('d/m/Y H:i', strtotime($pergunta['data_criacao'] ?? 'now')); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Atualizar Pergunta
                        </button>
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
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
