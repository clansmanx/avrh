<?php
// modules/ciclos/editar.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar buffer de saída
ob_start();

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Inicializar objetos
$database = new Database();
$conn = $database->getConnection();
$auth = new Auth();
$functions = new Functions();

// Verificar permissão
$auth->requirePermission(['admin', 'rh']);

$id = $_GET['id'] ?? 0;
$errors = [];

// Buscar dados do ciclo
$query = "SELECT * FROM ciclos_avaliacao WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$ciclo = $stmt->fetch();

if (!$ciclo) {
    $_SESSION['error'] = "Ciclo não encontrado";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Verificar se pode editar (apenas se estiver planejado)
if ($ciclo['status'] != 'planejado') {
    $_SESSION['error'] = "Apenas ciclos em planejamento podem ser editados";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Decodificar configurações
$config = json_decode($ciclo['configuracao'], true);

// Buscar formulários disponíveis
$query_form = "SELECT * FROM formularios WHERE ativo = 1 ORDER BY nome";
$stmt_form = $conn->query($query_form);
$formularios = $stmt_form->fetchAll();

// PROCESSAR FORMULÁRIO ANTES DO HEADER
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $data_inicio = $_POST['data_inicio'] ?? '';
        $data_fim = $_POST['data_fim'] ?? '';
        $tipo = $_POST['tipo'] ?? '180';
        $formulario_id = $_POST['formulario_id'] ?? null;
        
        // Configurações em JSON
        $configuracao = json_encode([
            'autoavaliacao_peso' => intval($_POST['autoavaliacao_peso'] ?? 1),
            'gestor_peso' => intval($_POST['gestor_peso'] ?? 2),
            'pares_peso' => intval($_POST['pares_peso'] ?? 1),
            'subordinados_peso' => intval($_POST['subordinados_peso'] ?? 1),
            'permite_comentarios' => isset($_POST['permite_comentarios']) ? true : false,
            'anonimo' => isset($_POST['anonimo']) ? true : false,
            'obrigar_justificativa' => isset($_POST['obrigar_justificativa']) ? true : false
        ]);
        
        // Validar campos obrigatórios
        if (empty($nome)) $errors[] = "Nome do ciclo é obrigatório";
        if (empty($data_inicio)) $errors[] = "Data de início é obrigatória";
        if (empty($data_fim)) $errors[] = "Data de fim é obrigatória";
        
        // Validar datas
        if (!empty($data_inicio) && !empty($data_fim)) {
            if (strtotime($data_fim) < strtotime($data_inicio)) {
                $errors[] = "Data de fim não pode ser menor que data de início";
            }
        }
        
        if (empty($errors)) {
            $query = "UPDATE ciclos_avaliacao SET 
                      nome = :nome, 
                      descricao = :descricao, 
                      data_inicio = :data_inicio, 
                      data_fim = :data_fim, 
                      tipo = :tipo,
                      formulario_id = :formulario_id,
                      configuracao = :configuracao
                      WHERE id = :id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':data_inicio', $data_inicio);
            $stmt->bindParam(':data_fim', $data_fim);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':formulario_id', $formulario_id);
            $stmt->bindParam(':configuracao', $configuracao);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Ciclo atualizado com sucesso!";
                ob_end_clean();
                header('Location: index.php');
                exit;
            } else {
                $errors[] = "Erro ao atualizar ciclo";
            }
        }
    } catch (Exception $e) {
        $errors[] = "Erro: " . $e->getMessage();
    }
}

// SÓ AGORA INCLUIMOS O HEADER
require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Editar Ciclo de Avaliação</h2>
            <div>
                <a href="visualizar.php?id=<?php echo $id; ?>" class="btn btn-info">
                    <i class="bi bi-eye"></i> Visualizar
                </a>
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
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Dados principais -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5>Informações Básicas</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="nome" class="form-label">Nome do Ciclo *</label>
                                        <input type="text" class="form-control" id="nome" name="nome" 
                                               value="<?php echo htmlspecialchars($_POST['nome'] ?? $ciclo['nome']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="descricao" class="form-label">Descrição</label>
                                        <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($_POST['descricao'] ?? $ciclo['descricao']); ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="data_inicio" class="form-label">Data de Início *</label>
                                            <input type="date" class="form-control" id="data_inicio" name="data_inicio" 
                                                   value="<?php echo $_POST['data_inicio'] ?? $ciclo['data_inicio']; ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="data_fim" class="form-label">Data de Término *</label>
                                            <input type="date" class="form-control" id="data_fim" name="data_fim" 
                                                   value="<?php echo $_POST['data_fim'] ?? $ciclo['data_fim']; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="tipo" class="form-label">Tipo de Avaliação *</label>
                                            <select class="form-select" id="tipo" name="tipo" required>
                                                <option value="90" <?php echo ($_POST['tipo'] ?? $ciclo['tipo']) == '90' ? 'selected' : ''; ?>>90° (Apenas Gestor)</option>
                                                <option value="180" <?php echo ($_POST['tipo'] ?? $ciclo['tipo']) == '180' ? 'selected' : ''; ?>>180° (Autoavaliação + Gestor)</option>
                                                <option value="360" <?php echo ($_POST['tipo'] ?? $ciclo['tipo']) == '360' ? 'selected' : ''; ?>>360° (Completa)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="formulario_id" class="form-label">Formulário Padrão</label>
                                            <select class="form-select" id="formulario_id" name="formulario_id">
                                                <option value="">Selecione...</option>
                                                <?php foreach ($formularios as $form): ?>
                                                <option value="<?php echo $form['id']; ?>"
                                                    <?php echo ($_POST['formulario_id'] ?? $ciclo['formulario_id']) == $form['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($form['nome']); ?> (<?php echo $form['tipo']; ?>)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Configurações de Pesos -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5>Configurações de Pesos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="autoavaliacao_peso" class="form-label">Peso da Autoavaliação</label>
                                        <input type="number" class="form-control" id="autoavaliacao_peso" 
                                               name="autoavaliacao_peso" value="<?php echo $_POST['autoavaliacao_peso'] ?? ($config['autoavaliacao_peso'] ?? 1); ?>" min="0" max="10">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="gestor_peso" class="form-label">Peso da Avaliação do Gestor</label>
                                        <input type="number" class="form-control" id="gestor_peso" 
                                               name="gestor_peso" value="<?php echo $_POST['gestor_peso'] ?? ($config['gestor_peso'] ?? 2); ?>" min="0" max="10">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="pares_peso" class="form-label">Peso da Avaliação de Pares</label>
                                        <input type="number" class="form-control" id="pares_peso" 
                                               name="pares_peso" value="<?php echo $_POST['pares_peso'] ?? ($config['pares_peso'] ?? 1); ?>" min="0" max="10">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="subordinados_peso" class="form-label">Peso da Avaliação de Subordinados</label>
                                        <input type="number" class="form-control" id="subordinados_peso" 
                                               name="subordinados_peso" value="<?php echo $_POST['subordinados_peso'] ?? ($config['subordinados_peso'] ?? 1); ?>" min="0" max="10">
                                    </div>
                                </div>
                            </div>

                            <!-- Opções Adicionais -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5>Opções Adicionais</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="permite_comentarios" 
                                               name="permite_comentarios" <?php echo (isset($_POST['permite_comentarios']) || ($config['permite_comentarios'] ?? false)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="permite_comentarios">
                                            Permitir comentários nas avaliações
                                        </label>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="anonimo" 
                                               name="anonimo" <?php echo (isset($_POST['anonimo']) || ($config['anonimo'] ?? false)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="anonimo">
                                            Avaliações anônimas
                                        </label>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="obrigar_justificativa" 
                                               name="obrigar_justificativa" <?php echo (isset($_POST['obrigar_justificativa']) || ($config['obrigar_justificativa'] ?? false)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="obrigar_justificativa">
                                            Obrigar justificativa para notas baixas
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Atual -->
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5>Status do Ciclo</h5>
                                </div>
                                <div class="card-body">
                                    <p>
                                        <strong>Status:</strong> 
                                        <span class="badge bg-<?php 
                                            echo $ciclo['status'] == 'planejado' ? 'secondary' : 
                                                ($ciclo['status'] == 'em_andamento' ? 'success' : 
                                                ($ciclo['status'] == 'finalizado' ? 'info' : 'danger')); 
                                        ?>">
                                            <?php echo ucfirst($ciclo['status']); ?>
                                        </span>
                                    </p>
                                    <p><strong>Criado em:</strong> <?php echo date('d/m/Y H:i', strtotime($ciclo['data_criacao'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Atualizar Ciclo
                        </button>
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Validação de datas
document.getElementById('data_inicio').addEventListener('change', function() {
    document.getElementById('data_fim').min = this.value;
});
</script>

<?php 
require_once '../../includes/footer.php';
ob_end_flush(); 
?>
