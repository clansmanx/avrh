<?php
// modules/perguntas/adicionar.php
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

// Buscar formulários (apenas os ativos)
$query_form = "SELECT id, nome, tipo FROM formularios WHERE ativo = 1 ORDER BY nome";
$stmt_form = $conn->query($query_form);
$formularios = $stmt_form->fetchAll();

// Buscar competências ativas
$query_comp = "SELECT id, nome, tipo FROM competencias WHERE ativo = 1 ORDER BY nome";
$stmt_comp = $conn->query($query_comp);
$competencias = $stmt_comp->fetchAll();

$errors = [];

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
            $query = "INSERT INTO perguntas (formulario_id, competencia_id, texto, tipo_resposta, peso, ordem, obrigatorio) 
                      VALUES (:formulario_id, :competencia_id, :texto, :tipo_resposta, :peso, :ordem, :obrigatorio)";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':formulario_id', $formulario_id);
            $stmt->bindParam(':competencia_id', $competencia_id);
            $stmt->bindParam(':texto', $texto);
            $stmt->bindParam(':tipo_resposta', $tipo_resposta);
            $stmt->bindParam(':peso', $peso);
            $stmt->bindParam(':ordem', $ordem);
            $stmt->bindParam(':obrigatorio', $obrigatorio);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Pergunta cadastrada com sucesso!";
                ob_end_clean();
                header('Location: index.php');
                exit;
            } else {
                $errors[] = "Erro ao cadastrar pergunta";
            }
        }
    } catch (Exception $e) {
        $errors[] = "Erro: " . $e->getMessage();
    }
}

require_once '../../includes/header.php';
?>

<style>
.info-card {
    transition: all 0.3s;
    border-left: 4px solid transparent;
}
.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.info-card.autoavaliacao { border-left-color: #4e73df; }
.info-card.gestor { border-left-color: #1cc88a; }
.info-card.['360'] { border-left-color: #36b9cc; }
.info-card.rotina { border-left-color: #f6c23e; }
.info-card.rh { border-left-color: #e74a3b; }
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Nova Pergunta</h2>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
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

        <div class="row">
            <!-- Formulário principal -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <!-- Pergunta -->
                            <div class="mb-3">
                                <label class="form-label">Pergunta *</label>
                                <textarea class="form-control" name="texto" rows="3" required><?php echo htmlspecialchars($_POST['texto'] ?? ''); ?></textarea>
                                <small class="text-muted">Digite a pergunta que será exibida no formulário</small>
                            </div>
                            
                            <div class="row">
                                <!-- Formulário -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Formulário *</label>
                                    <select class="form-select" name="formulario_id" id="formulario_id" required>
                                        <option value="">-- Selecione um formulário --</option>
                                        <?php foreach ($formularios as $form): ?>
                                        <option value="<?php echo $form['id']; ?>" data-tipo="<?php echo $form['tipo']; ?>"
                                            <?php echo ($_POST['formulario_id'] ?? '') == $form['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($form['nome']); ?> (<?php echo $form['tipo']; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Competência -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Competência</label>
                                    <select class="form-select" name="competencia_id">
                                        <option value="">-- Nenhuma competência --</option>
                                        <?php foreach ($competencias as $comp): ?>
                                        <option value="<?php echo $comp['id']; ?>"
                                            <?php echo ($_POST['competencia_id'] ?? '') == $comp['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($comp['nome']); ?> (<?php echo $comp['tipo']; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Tipo de Resposta -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tipo de Resposta *</label>
                                    <select class="form-select" name="tipo_resposta" required>
                                        <option value="escala_1_5" <?php echo ($_POST['tipo_resposta'] ?? '') == 'escala_1_5' ? 'selected' : ''; ?>>
                                            ⭐ Escala 1 a 5
                                        </option>
                                        <option value="sim_nao" <?php echo ($_POST['tipo_resposta'] ?? '') == 'sim_nao' ? 'selected' : ''; ?>>
                                            ✅ Sim/Não (0 ou 1)
                                        </option>
                                        <option value="texto" <?php echo ($_POST['tipo_resposta'] ?? '') == 'texto' ? 'selected' : ''; ?>>
                                            📝 Texto Livre
                                        </option>
                                        <option value="nota" <?php echo ($_POST['tipo_resposta'] ?? '') == 'nota' ? 'selected' : ''; ?>>
                                            🔢 Nota (0-10)
                                        </option>
                                    </select>
                                </div>
                                
                                <!-- Peso -->
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Peso</label>
                                    <input type="number" class="form-control" name="peso" 
                                           value="<?php echo $_POST['peso'] ?? 1; ?>" min="0" max="10" step="1">
                                </div>
                                
                                <!-- Ordem -->
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Ordem</label>
                                    <input type="number" class="form-control" name="ordem" 
                                           value="<?php echo $_POST['ordem'] ?? 0; ?>" min="0">
                                </div>
                            </div>
                            
                            <!-- Obrigatório -->
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="obrigatorio" 
                                       id="obrigatorio" <?php echo isset($_POST['obrigatorio']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="obrigatorio">
                                    Pergunta Obrigatória
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Salvar Pergunta
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Cards informativos -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Guia Rápido</h5>
                    </div>
                    <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                        <p class="text-muted small">Selecione um formulário para ver detalhes:</p>
                        
                        <!-- Card 1: Autoavaliação 180° -->
                        <div class="card info-card autoavaliacao mb-3" id="card-autoavaliacao" style="display: none;">
                            <div class="card-body p-3">
                                <h6 class="card-title text-primary">
                                    <i class="bi bi-person-circle"></i> Autoavaliação 180°
                                    <small class="text-muted">(autoavaliacao)</small>
                                </h6>
                                <ul class="small mb-0 ps-3">
                                    <li class="mb-1"><strong>👤 Quem responde:</strong> O próprio colaborador</li>
                                    <li class="mb-1"><strong>🎯 Finalidade:</strong> Colaborador avalia seu próprio desempenho</li>
                                    <li class="mb-1"><strong>📅 Quando usar:</strong> Ciclos 180° e 360°</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Card 2: Avaliação do Gestor 180° -->
                        <div class="card info-card gestor mb-3" id="card-gestor" style="display: none;">
                            <div class="card-body p-3">
                                <h6 class="card-title text-success">
                                    <i class="bi bi-person-badge"></i> Avaliação do Gestor 180°
                                    <small class="text-muted">(gestor)</small>
                                </h6>
                                <ul class="small mb-0 ps-3">
                                    <li class="mb-1"><strong>👤 Quem responde:</strong> Gestor imediato</li>
                                    <li class="mb-1"><strong>🎯 Finalidade:</strong> Gestor avalia o colaborador</li>
                                    <li class="mb-1"><strong>📅 Quando usar:</strong> Ciclos 180° e 360°</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Card 3: Avaliação 360° Completa -->
                        <div class="card info-card 360 mb-3" id="card-360" style="display: none;">
                            <div class="card-body p-3">
                                <h6 class="card-title text-info">
                                    <i class="bi bi-people"></i> Avaliação 360° Completa
                                    <small class="text-muted">(360)</small>
                                </h6>
                                <ul class="small mb-0 ps-3">
                                    <li class="mb-1"><strong>👥 Quem responde:</strong> Todos (gestor, pares, subordinados, autoavaliação)</li>
                                    <li class="mb-1"><strong>🎯 Finalidade:</strong> Visão completa e multidisciplinar</li>
                                    <li class="mb-1"><strong>📅 Quando usar:</strong> Avaliações complexas</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Card 4: Avaliação de Rotina -->
                        <div class="card info-card rotina mb-3" id="card-rotina" style="display: none;">
                            <div class="card-body p-3">
                                <h6 class="card-title text-warning">
                                    <i class="bi bi-clock-history"></i> Avaliação de Rotina ⭐
                                    <small class="text-muted">(rotina)</small>
                                </h6>
                                <ul class="small mb-0 ps-3">
                                    <li class="mb-1"><strong>👤 Quem responde:</strong> Gestor imediato</li>
                                    <li class="mb-1"><strong>🎯 Finalidade:</strong> Avaliar rotina, comportamento e produtividade</li>
                                    <li class="mb-1"><strong>📅 Quando usar:</strong> Avaliações periódicas</li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Card 5: Avaliação de RH -->
                        <div class="card info-card rh mb-3" id="card-rh" style="display: none;">
                            <div class="card-body p-3">
                                <h6 class="card-title text-danger">
                                    <i class="bi bi-building"></i> Avaliação de RH ⭐
                                    <small class="text-muted">(rh)</small>
                                </h6>
                                <ul class="small mb-0 ps-3">
                                    <li class="mb-1"><strong>👤 Quem responde:</strong> Departamento de RH</li>
                                    <li class="mb-1"><strong>🎯 Finalidade:</strong> Avaliar conformidade, disciplina, faltas, advertências</li>
                                    <li class="mb-1"><strong>📅 Quando usar:</strong> Promoções, desligamentos, avaliações formais</li>
                                </ul>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Dicas de Tipo de Resposta -->
                        <h6 class="mt-3"><i class="bi bi-chat-dots"></i> Tipos de Resposta:</h6>
                        <div class="small">
                            <p class="mb-1"><span class="badge bg-primary">⭐</span> <strong>Escala 1-5:</strong> Para avaliações quantitativas</p>
                            <p class="mb-1"><span class="badge bg-success">✅</span> <strong>Sim/Não:</strong> Para perguntas objetivas (vale 0 ou 1)</p>
                            <p class="mb-1"><span class="badge bg-info">📝</span> <strong>Texto Livre:</strong> Para feedbacks qualitativos</p>
                            <p class="mb-1"><span class="badge bg-warning">🔢</span> <strong>Nota 0-10:</strong> Para avaliações numéricas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Mostrar card informativo baseado no formulário selecionado
document.getElementById('formulario_id').addEventListener('change', function() {
    // Esconder todos os cards
    document.querySelectorAll('[id^="card-"]').forEach(card => {
        card.style.display = 'none';
    });
    
    // Mostrar o card correspondente
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
        const tipo = selectedOption.getAttribute('data-tipo');
        const cardId = 'card-' + tipo;
        const card = document.getElementById(cardId);
        if (card) {
            card.style.display = 'block';
        }
    }
});

// Se já houver um formulário selecionado (ex: em caso de erro), mostrar o card
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('formulario_id');
    if (select.value) {
        const tipo = select.options[select.selectedIndex].getAttribute('data-tipo');
        const card = document.getElementById('card-' + tipo);
        if (card) {
            card.style.display = 'block';
        }
    }
});
</script>

<?php 
require_once '../../includes/footer.php';
ob_end_flush();
?>
