<?php
// modules/avaliacoes/responder.php
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

$auth->requireLogin();

$avaliacao_id = $_GET['id'] ?? 0;
$user_id = $auth->getUserId();

// Buscar avaliação
$query = "SELECT a.*, 
            u.nome as avaliado_nome,
            u.email as avaliado_email,
            u.foto_perfil,
            u.cargo_id,
            c.nome as cargo_nome,
            av.nome as avaliador_nome,
            ci.nome as ciclo_nome,
            ci.data_fim as prazo,
            f.id as formulario_id,
            f.nome as formulario_nome,
            f.tipo as formulario_tipo
          FROM avaliacoes a
          JOIN usuarios u ON a.avaliado_id = u.id
          LEFT JOIN cargos c ON u.cargo_id = c.id
          JOIN usuarios av ON a.avaliador_id = av.id
          JOIN ciclos_avaliacao ci ON a.ciclo_id = ci.id
          JOIN formularios f ON a.formulario_id = f.id
          WHERE a.id = :id AND a.avaliador_id = :avaliador_id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $avaliacao_id);
$stmt->bindParam(':avaliador_id', $user_id);
$stmt->execute();

$avaliacao = $stmt->fetch();

if (!$avaliacao) {
    $_SESSION['error'] = "Avaliação não encontrada ou você não tem permissão para respondê-la";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Buscar perguntas do formulário
$query_perguntas = "SELECT p.*, c.nome as competencia_nome
                    FROM perguntas p
                    LEFT JOIN competencias c ON p.competencia_id = c.id
                    WHERE p.formulario_id = :formulario_id
                    ORDER BY p.ordem ASC";

$stmt = $conn->prepare($query_perguntas);
$stmt->bindParam(':formulario_id', $avaliacao['formulario_id']);
$stmt->execute();
$perguntas = $stmt->fetchAll();

if (empty($perguntas)) {
    $_SESSION['error'] = "Este formulário não possui perguntas cadastradas. Entre em contato com o RH.";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Buscar respostas já salvas
$query_respostas = "SELECT * FROM respostas WHERE avaliacao_id = :avaliacao_id";
$stmt = $conn->prepare($query_respostas);
$stmt->bindParam(':avaliacao_id', $avaliacao_id);
$stmt->execute();
$respostas_salvas = [];
while ($row = $stmt->fetch()) {
    $respostas_salvas[$row['pergunta_id']] = $row;
}

// Processar submissão
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        // Atualizar status da avaliação
        $status = $_POST['action'] === 'save' ? 'em_andamento' : 'concluida';
        
        $query = "UPDATE avaliacoes SET 
                  status = :status, 
                  data_inicio = COALESCE(data_inicio, NOW())";
        
        if ($status === 'concluida') {
            $query .= ", data_conclusao = NOW()";
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $avaliacao_id);
        $stmt->execute();
        
        // Salvar respostas
        foreach ($_POST['respostas'] as $pergunta_id => $resposta) {
            $texto = null;
            $nota = null;
            
            // Determinar se é texto ou nota
            if (is_numeric($resposta)) {
                $nota = floatval($resposta);
            } else {
                $texto = trim($resposta);
            }
            
            if (isset($respostas_salvas[$pergunta_id])) {
                // Atualizar resposta existente
                $query = "UPDATE respostas SET 
                          resposta_texto = :texto,
                          resposta_nota = :nota
                          WHERE id = :id";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $respostas_salvas[$pergunta_id]['id']);
            } else {
                // Inserir nova resposta
                $query = "INSERT INTO respostas (avaliacao_id, pergunta_id, resposta_texto, resposta_nota) 
                          VALUES (:avaliacao_id, :pergunta_id, :texto, :nota)";
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':avaliacao_id', $avaliacao_id);
                $stmt->bindParam(':pergunta_id', $pergunta_id);
            }
            
            $stmt->bindParam(':texto', $texto);
            $stmt->bindParam(':nota', $nota);
            $stmt->execute();
        }
        
        // Calcular nota final se concluída (agora em percentual)
        if ($status === 'concluida') {
            // Buscar todas as respostas com nota e seus pesos
            $query_notas = "SELECT r.resposta_nota, p.peso, p.tipo_resposta
                            FROM respostas r
                            JOIN perguntas p ON r.pergunta_id = p.id
                            WHERE r.avaliacao_id = :avaliacao_id 
                              AND r.resposta_nota IS NOT NULL";
            $stmt_notas = $conn->prepare($query_notas);
            $stmt_notas->bindParam(':avaliacao_id', $avaliacao_id);
            $stmt_notas->execute();
            $respostas_notas = $stmt_notas->fetchAll();
            
            $soma = 0;
            $peso_total = 0;
            
            foreach ($respostas_notas as $resp) {
                $soma += $resp['resposta_nota'] * $resp['peso'];
                $peso_total += $resp['peso'];
            }
            
            // Calcular percentual (0-100%)
            // Para SIM/NÃO, a nota máxima é 1, então o percentual é (soma / peso_total) * 100
            $percentual = $peso_total > 0 ? round(($soma / $peso_total) * 100, 2) : 0;
            
            // Atualizar avaliação com a nota percentual
            $query = "UPDATE avaliacoes SET nota_final = :nota WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nota', $percentual);
            $stmt->bindParam(':id', $avaliacao_id);
            $stmt->execute();
        }
        
        $conn->commit();
        
        $_SESSION['success'] = $status === 'concluida' 
            ? "Avaliação concluída com sucesso! (Nota: {$percentual}%)" 
            : "Progresso salvo com sucesso!";
        
        ob_end_clean();
        
        if ($status === 'concluida') {
            header('Location: visualizar.php?id=' . $avaliacao_id);
        } else {
            header('Location: responder.php?id=' . $avaliacao_id);
        }
        exit;
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Erro ao salvar avaliação: " . $e->getMessage();
    }
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Avaliação: <?php echo htmlspecialchars($avaliacao['avaliado_nome']); ?></h2>
            <div>
                <span class="badge bg-info me-2"><?php echo htmlspecialchars($avaliacao['formulario_nome']); ?></span>
                <span class="badge bg-secondary"><?php echo htmlspecialchars($avaliacao['ciclo_nome']); ?></span>
                <a href="index.php" class="btn btn-sm btn-secondary ms-2">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Card do avaliado -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <?php if ($avaliacao['foto_perfil']): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $avaliacao['foto_perfil']; ?>" 
                                 class="rounded-circle me-3" width="60" height="60" style="object-fit: cover;">
                            <?php else: ?>
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 60px; height: 60px;">
                                <i class="bi bi-person-fill fs-1 text-secondary"></i>
                            </div>
                            <?php endif; ?>
                            <div>
                                <h4 class="mb-0"><?php echo htmlspecialchars($avaliacao['avaliado_nome']); ?></h4>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($avaliacao['cargo_nome'] ?? 'Sem cargo'); ?></p>
                                <small><?php echo htmlspecialchars($avaliacao['avaliado_email']); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-end">
                            <p class="mb-1"><strong>Prazo:</strong> 
                                <?php echo $functions->formatDate($avaliacao['prazo']); ?>
                            </p>
                            <p class="mb-0"><strong>Status:</strong>
                                <span class="badge bg-<?php 
                                    echo $avaliacao['status'] == 'pendente' ? 'warning' : 
                                        ($avaliacao['status'] == 'em_andamento' ? 'info' : 'success'); 
                                ?>">
                                    <?php echo ucfirst($avaliacao['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulário de avaliação -->
        <form method="POST" action="" id="formAvaliacao">
            <div class="progress mb-4" style="height: 30px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                     role="progressbar" id="formProgress" style="width: 0%">
                    0% Concluído
                </div>
            </div>

            <?php foreach ($perguntas as $index => $pergunta): 
                $resposta_atual = $respostas_salvas[$pergunta['id']] ?? null;
                $tem_resposta = $resposta_atual && ($resposta_atual['resposta_nota'] !== null || !empty($resposta_atual['resposta_texto']));
            ?>
            <div class="card mb-3 pergunta-card" data-obrigatorio="<?php echo $pergunta['obrigatorio']; ?>" data-respondida="<?php echo $tem_resposta ? '1' : '0'; ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title">
                            <?php echo ($index + 1); ?>. <?php echo htmlspecialchars($pergunta['texto']); ?>
                            <?php if ($pergunta['obrigatorio']): ?>
                                <span class="text-danger">*</span>
                            <?php endif; ?>
                            <span class="badge bg-secondary ms-2">Peso: <?php echo $pergunta['peso']; ?></span>
                        </h5>
                        <?php if ($pergunta['competencia_nome']): ?>
                        <span class="badge bg-light text-dark">
                            <?php echo htmlspecialchars($pergunta['competencia_nome']); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($pergunta['tipo_resposta'] == 'escala_1_5'): ?>
                        <div class="rating-scale">
                            <div class="btn-group w-100" role="group">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" class="btn-check" 
                                       name="respostas[<?php echo $pergunta['id']; ?>]" 
                                       id="nota_<?php echo $pergunta['id']; ?>_<?php echo $i; ?>" 
                                       value="<?php echo $i; ?>"
                                       <?php echo ($resposta_atual && $resposta_atual['resposta_nota'] == $i) ? 'checked' : ''; ?>
                                       <?php echo $pergunta['obrigatorio'] ? 'required' : ''; ?>>
                                <label class="btn btn-outline-primary" 
                                       for="nota_<?php echo $pergunta['id']; ?>_<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </label>
                                <?php endfor; ?>
                            </div>
                            <div class="d-flex justify-content-between mt-2 text-muted small">
                                <span>Muito Ruim</span>
                                <span>Ruim</span>
                                <span>Regular</span>
                                <span>Bom</span>
                                <span>Excelente</span>
                            </div>
                        </div>
                        
                    <?php elseif ($pergunta['tipo_resposta'] == 'sim_nao'): ?>
                        <div class="sim-nao-group">
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" 
                                       name="respostas[<?php echo $pergunta['id']; ?>]" 
                                       id="sim_<?php echo $pergunta['id']; ?>" 
                                       value="1"
                                       <?php echo ($resposta_atual && $resposta_atual['resposta_nota'] == 1) ? 'checked' : ''; ?>
                                       <?php echo $pergunta['obrigatorio'] ? 'required' : ''; ?>>
                                <label class="btn btn-outline-success" for="sim_<?php echo $pergunta['id']; ?>">
                                    <i class="bi bi-check-circle"></i> SIM
                                </label>
                                
                                <input type="radio" class="btn-check" 
                                       name="respostas[<?php echo $pergunta['id']; ?>]" 
                                       id="nao_<?php echo $pergunta['id']; ?>" 
                                       value="0"
                                       <?php echo ($resposta_atual && $resposta_atual['resposta_nota'] == 0) ? 'checked' : ''; ?>
                                       <?php echo $pergunta['obrigatorio'] ? 'required' : ''; ?>>
                                <label class="btn btn-outline-danger" for="nao_<?php echo $pergunta['id']; ?>">
                                    <i class="bi bi-x-circle"></i> NÃO
                                </label>
                            </div>
                        </div>
                        
                    <?php elseif ($pergunta['tipo_resposta'] == 'texto'): ?>
                        <textarea class="form-control" 
                                  name="respostas[<?php echo $pergunta['id']; ?>]" 
                                  rows="4"
                                  placeholder="Digite seu feedback aqui..."
                                  <?php echo $pergunta['obrigatorio'] ? 'required' : ''; ?>><?php 
                                    echo htmlspecialchars($resposta_atual ? $resposta_atual['resposta_texto'] : ''); 
                                ?></textarea>
                                
                    <?php elseif ($pergunta['tipo_resposta'] == 'nota'): ?>
                        <input type="number" class="form-control" 
                               name="respostas[<?php echo $pergunta['id']; ?>]" 
                               value="<?php echo $resposta_atual ? $resposta_atual['resposta_nota'] : ''; ?>"
                               min="0" max="10" step="0.1"
                               placeholder="Digite uma nota de 0 a 10"
                               <?php echo $pergunta['obrigatorio'] ? 'required' : ''; ?>>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="d-flex justify-content-between mt-4 mb-4">
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancelar
                </a>
                <div>
                    <button type="submit" name="action" value="save" class="btn btn-info me-2">
                        <i class="bi bi-save"></i> Salvar Rascunho
                    </button>
                    <button type="submit" name="action" value="complete" class="btn btn-success"
                            onclick="return confirm('Tem certeza que deseja finalizar esta avaliação? Após finalizada, não será possível alterar as respostas.')">
                        <i class="bi bi-check-circle"></i> Finalizar Avaliação
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Atualizar barra de progresso
function updateProgress() {
    const totalPerguntas = document.querySelectorAll('.pergunta-card').length;
    let perguntasRespondidas = 0;
    
    document.querySelectorAll('.pergunta-card').forEach(card => {
        const inputs = card.querySelectorAll('input[type="radio"]:checked, textarea, input[type="number"]');
        let respondida = false;
        
        inputs.forEach(input => {
            if (input.type === 'radio' && input.checked) {
                respondida = true;
            } else if (input.type === 'textarea' && input.value && input.value.trim() !== '') {
                respondida = true;
            } else if (input.type === 'number' && input.value !== '') {
                respondida = true;
            }
        });
        
        if (respondida) perguntasRespondidas++;
    });
    
    const percentual = totalPerguntas > 0 ? Math.round((perguntasRespondidas / totalPerguntas) * 100) : 0;
    const progressBar = document.getElementById('formProgress');
    
    progressBar.style.width = percentual + '%';
    progressBar.textContent = percentual + '% Concluído';
}

document.addEventListener('DOMContentLoaded', function() {
    // Adicionar listeners para atualizar progresso
    document.querySelectorAll('input, textarea').forEach(input => {
        input.addEventListener('change', updateProgress);
        input.addEventListener('keyup', updateProgress);
    });
    
    // Atualizar progresso inicial
    updateProgress();
});

// Validar antes de finalizar
document.querySelector('button[value="complete"]').addEventListener('click', function(e) {
    const obrigatorias = document.querySelectorAll('.pergunta-card[data-obrigatorio="1"]');
    let todasRespondidas = true;
    
    obrigatorias.forEach(card => {
        const inputs = card.querySelectorAll('input[type="radio"]:checked, textarea, input[type="number"]');
        let respondida = false;
        
        inputs.forEach(input => {
            if (input.type === 'radio' && input.checked) {
                respondida = true;
            } else if (input.type === 'textarea' && input.value && input.value.trim() !== '') {
                respondida = true;
            } else if (input.type === 'number' && input.value !== '') {
                respondida = true;
            }
        });
        
        if (!respondida) {
            todasRespondidas = false;
            card.classList.add('border-danger');
        } else {
            card.classList.remove('border-danger');
        }
    });
    
    if (!todasRespondidas) {
        e.preventDefault();
        alert('Por favor, responda todas as perguntas obrigatórias antes de finalizar.');
    }
});
</script>

<?php 
require_once '../../includes/footer.php';
ob_end_flush();
?>
