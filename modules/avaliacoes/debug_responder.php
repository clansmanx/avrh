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

// ==========================================
// DEBUG
// ==========================================
echo "<!-- DEBUG: Avaliação ID = $avaliacao_id -->";
echo "<!-- DEBUG: User ID = $user_id -->";

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
            f.nome as formulario_nome
          FROM avaliacoes a
          JOIN usuarios u ON a.avaliado_id = u.id
          LEFT JOIN cargos c ON u.cargo_id = c.id
          JOIN usuarios av ON a.avaliador_id = av.id
          JOIN ciclos_avaliacao ci ON a.ciclo_id = ci.id
          JOIN formularios f ON a.formulario_id = f.id
          WHERE a.id = :id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $avaliacao_id);
$stmt->execute();

echo "<!-- DEBUG: Query executada, linhas = " . $stmt->rowCount() . " -->";

$avaliacao = $stmt->fetch();

if (!$avaliacao) {
    echo "<!-- DEBUG: Avaliação não encontrada -->";
    $_SESSION['error'] = "Avaliação não encontrada";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

echo "<!-- DEBUG: Avaliação encontrada: " . $avaliacao['avaliado_nome'] . " -->";
echo "<!-- DEBUG: Avaliador ID = " . $avaliacao['avaliador_id'] . " -->";

if ($avaliacao['avaliador_id'] != $user_id) {
    echo "<!-- DEBUG: Permissão negada! Avaliador ID difere do User ID -->";
    $_SESSION['error'] = "Você não tem permissão para responder esta avaliação";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

echo "<!-- DEBUG: Permissão OK -->";

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

echo "<!-- DEBUG: Perguntas encontradas = " . count($perguntas) . " -->";

if (empty($perguntas)) {
    echo "<!-- DEBUG: Nenhuma pergunta encontrada -->";
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

echo "<!-- DEBUG: Respostas salvas = " . count($respostas_salvas) . " -->";

require_once '../../includes/header.php';
?>

<!-- DEBUG VISÍVEL (remova depois) -->
<div class="alert alert-info">
    <strong>DEBUG:</strong><br>
    Avaliação ID: <?php echo $avaliacao_id; ?><br>
    User ID: <?php echo $user_id; ?><br>
    Avaliador ID: <?php echo $avaliacao['avaliador_id']; ?><br>
    Avaliado: <?php echo $avaliacao['avaliado_nome']; ?><br>
    Formulário ID: <?php echo $avaliacao['formulario_id']; ?><br>
    Total Perguntas: <?php echo count($perguntas); ?><br>
    Respostas Salvas: <?php echo count($respostas_salvas); ?>
</div>

<!-- RESTO DO CÓDIGO DO FORMULÁRIO (manter igual) -->
<style>
/* ======================================== */
/* BARRA DE PROGRESSO FIXA NO TOPO          */
/* ======================================== */
.progresso-fixo {
    position: sticky;
    top: 0;
    z-index: 1000;
    background-color: #f8f9fc;
    padding: 15px 0;
    margin-bottom: 20px;
    border-bottom: 1px solid #e3e6f0;
    box-shadow: 0 4px 6px rgba(0,0,0,0.02);
}

/* ======================================== */
/* BOTÕES SIM/NÃO MAIS COMPACTOS            */
/* ======================================== */
.sim-nao-container {
    display: flex;
    gap: 10px;
    max-width: 300px;
}

.sim-nao-container .btn {
    flex: 1;
    padding: 8px 15px !important;
    font-size: 0.9rem !important;
    border-radius: 50px !important;
}

.sim-nao-container .btn-success {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
}

.sim-nao-container .btn-success:hover {
    background-color: #218838 !important;
    border-color: #1e7e34 !important;
}

.sim-nao-container .btn-danger {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
}

.sim-nao-container .btn-danger:hover {
    background-color: #c82333 !important;
    border-color: #bd2130 !important;
}

/* ======================================== */
/* BOTÕES ESCALA 1-5 MAIS COMPACTOS         */
/* ======================================== */
.escala-container {
    display: flex;
    gap: 5px;
    max-width: 400px;
    flex-wrap: wrap;
}

.escala-container .btn {
    flex: 1;
    min-width: 50px;
    padding: 8px 5px !important;
    font-size: 0.9rem !important;
    border-radius: 8px !important;
}

/* ======================================== */
/* CARD DA PERGUNTA MAIS LIMPO              */
/* ======================================== */
.pergunta-card {
    margin-bottom: 15px;
    border: 1px solid #e3e6f0 !important;
    border-radius: 12px !important;
    transition: all 0.2s;
}

.pergunta-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border-color: #cbd5e0 !important;
}

.pergunta-card .card-body {
    padding: 15px 20px !important;
}

.pergunta-card .card-title {
    font-size: 1rem;
    font-weight: 600;
    color: #2d3748;
}

/* ======================================== */
/* BOTÕES DE AÇÃO FIXOS NO FINAL            */
/* ======================================== */
.acoes-fixas {
    position: sticky;
    bottom: 0;
    z-index: 1000;
    background-color: #f8f9fc;
    padding: 20px 0;
    margin-top: 30px;
    border-top: 1px solid #e3e6f0;
    box-shadow: 0 -4px 6px rgba(0,0,0,0.02);
}

/* ======================================== */
/* RESPONSIVIDADE                           */
/* ======================================== */
@media (max-width: 768px) {
    .sim-nao-container {
        max-width: 100%;
    }
    .escala-container {
        max-width: 100%;
    }
    .escala-container .btn {
        min-width: 40px;
    }
}
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Avaliação: <?php echo htmlspecialchars($avaliacao['avaliado_nome']); ?></h2>
            <div>
                <span class="badge bg-info me-2"><?php echo htmlspecialchars($avaliacao['formulario_nome']); ?></span>
                <span class="badge bg-secondary"><?php echo htmlspecialchars($avaliacao['ciclo_nome']); ?></span>
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
            <!-- BARRA DE PROGRESSO FIXA NO TOPO -->
            <div class="progresso-fixo">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                 role="progressbar" id="formProgress" style="width: 0%">
                                0% Concluído
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="text-muted" id="progresso-texto">0 de <?php echo count($perguntas); ?> respondidas</span>
                    </div>
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
                        <div class="escala-container">
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
                        


<!-- =========================================== -->
<!-- BOTÕES SIM/NÃO - CORRIGIDO                  -->
<!-- =========================================== -->
<?php elseif ($pergunta['tipo_resposta'] == 'sim_nao'): ?>
<div class="sim-nao-container">
    <input type="radio" class="btn-check" 
           name="respostas[<?php echo $pergunta['id']; ?>]" 
           id="sim_<?php echo $pergunta['id']; ?>" 
           value="1"
           <?php echo ($resposta_atual && $resposta_atual['resposta_nota'] == 1) ? 'checked' : ''; ?>
           <?php echo $pergunta['obrigatorio'] ? 'required' : ''; ?>>
    <label class="btn <?php echo ($resposta_atual && $resposta_atual['resposta_nota'] == 1) ? 'btn-success' : 'btn-outline-success'; ?>" 
           for="sim_<?php echo $pergunta['id']; ?>">
        <i class="bi bi-check-circle"></i> SIM
    </label>
    
    <input type="radio" class="btn-check" 
           name="respostas[<?php echo $pergunta['id']; ?>]" 
           id="nao_<?php echo $pergunta['id']; ?>" 
           value="0"
           <?php echo ($resposta_atual && $resposta_atual['resposta_nota'] == 0) ? 'checked' : ''; ?>
           <?php echo $pergunta['obrigatorio'] ? 'required' : ''; ?>>
    <label class="btn <?php echo ($resposta_atual && $resposta_atual['resposta_nota'] == 0) ? 'btn-danger' : 'btn-outline-danger'; ?>" 
           for="nao_<?php echo $pergunta['id']; ?>">
        <i class="bi bi-x-circle"></i> NÃO
    </label>
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
                               style="max-width: 200px;"
                               <?php echo $pergunta['obrigatorio'] ? 'required' : ''; ?>>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>







            <!-- BOTÕES DE AÇÃO FIXOS -->
            <div class="acoes-fixas">
                <div class="d-flex justify-content-between">
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
            } else if (input.value && input.value.trim() !== '') {
                respondida = true;
            }
        });
        
        if (respondida) perguntasRespondidas++;
    });
    
    const percentual = totalPerguntas > 0 ? Math.round((perguntasRespondidas / totalPerguntas) * 100) : 0;
    const progressBar = document.getElementById('formProgress');
    const progressoTexto = document.getElementById('progresso-texto');
    
    progressBar.style.width = percentual + '%';
    progressBar.textContent = percentual + '% Concluído';
    progressoTexto.textContent = perguntasRespondidas + ' de ' + totalPerguntas + ' respondidas';
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input, textarea').forEach(input => {
        input.addEventListener('change', updateProgress);
        input.addEventListener('keyup', updateProgress);
    });
    
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
            } else if (input.value && input.value.trim() !== '') {
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
