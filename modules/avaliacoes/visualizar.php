<?php
// modules/avaliacoes/visualizar.php
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
$avaliacao = $stmt->fetch();

if (!$avaliacao) {
    $_SESSION['error'] = "Avaliação não encontrada";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Verificar permissão
if ($avaliacao['avaliado_id'] != $user_id && 
    $avaliacao['avaliador_id'] != $user_id && 
    !$auth->hasPermission(['admin', 'rh'])) {
    $_SESSION['error'] = "Você não tem permissão para visualizar esta avaliação";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Buscar respostas
$query_respostas = "SELECT r.*, p.texto as pergunta_texto, p.tipo_resposta,
                           c.nome as competencia_nome
                    FROM respostas r
                    JOIN perguntas p ON r.pergunta_id = p.id
                    LEFT JOIN competencias c ON p.competencia_id = c.id
                    WHERE r.avaliacao_id = :avaliacao_id
                    ORDER BY p.ordem ASC";

$stmt = $conn->prepare($query_respostas);
$stmt->bindParam(':avaliacao_id', $avaliacao_id);
$stmt->execute();
$respostas = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Resultado da Avaliação</h2>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Cabeçalho da avaliação -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6>Avaliado</h6>
                        <div class="d-flex align-items-center">
                            <?php if ($avaliacao['foto_perfil']): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $avaliacao['foto_perfil']; ?>" 
                                 class="rounded-circle me-2" width="40" height="40">
                            <?php else: ?>
                            <i class="bi bi-person-circle fs-2 me-2"></i>
                            <?php endif; ?>
                            <div>
                                <strong><?php echo htmlspecialchars($avaliacao['avaliado_nome']); ?></strong><br>
                                <small><?php echo htmlspecialchars($avaliacao['cargo_nome'] ?? ''); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h6>Avaliador</h6>
                        <p class="mb-0"><?php echo htmlspecialchars($avaliacao['avaliador_nome']); ?></p>
                        <small class="text-muted"><?php echo htmlspecialchars($avaliacao['formulario_nome']); ?></small>
                    </div>
                    <div class="col-md-4">
                        <h6>Ciclo</h6>
                        <p class="mb-0"><?php echo htmlspecialchars($avaliacao['ciclo_nome']); ?></p>
                        <small class="text-muted">
                            Concluída em: <?php echo $functions->formatDate($avaliacao['data_conclusao'], 'd/m/Y H:i'); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nota final -->
        <?php if ($avaliacao['nota_final']): ?>
        <div class="card mb-4 bg-light">
            <div class="card-body text-center">
                <h3>Nota Final</h3>
                <div class="display-1 text-primary fw-bold">
                    <?php echo number_format($avaliacao['nota_final'], 2); ?>%
                </div>
                <div class="progress mt-3" style="height: 20px;">
                    <div class="progress-bar bg-<?php 
                        echo $avaliacao['nota_final'] >= 80 ? 'success' : 
                            ($avaliacao['nota_final'] >= 60 ? 'warning' : 'danger'); 
                    ?>" role="progressbar" 
                         style="width: <?php echo $avaliacao['nota_final']; ?>%">
                        <?php echo number_format($avaliacao['nota_final'], 2); ?>%
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Respostas -->
        <div class="card">
            <div class="card-header">
                <h5>Detalhes das Respostas</h5>
            </div>
            <div class="card-body">
                <?php if (empty($respostas)): ?>
                <p class="text-muted text-center py-4">
                    <i class="bi bi-chat-square-text fs-1 d-block mb-3"></i>
                    Nenhuma resposta encontrada para esta avaliação.
                </p>
                <?php else: ?>
                    <?php foreach ($respostas as $resposta): ?>
                    <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex justify-content-between mb-2">
                            <strong><?php echo htmlspecialchars($resposta['pergunta_texto']); ?></strong>
                            <?php if ($resposta['competencia_nome']): ?>
                            <span class="badge bg-info"><?php echo htmlspecialchars($resposta['competencia_nome']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($resposta['resposta_nota'] !== null): ?>
                            <?php if ($resposta['tipo_resposta'] == 'sim_nao'): ?>
                                <!-- Para SIM/NÃO, mostrar texto ao invés de estrelas -->
                                <div class="mt-2">
                                    <?php if ($resposta['resposta_nota'] == 1): ?>
                                    <span class="badge bg-success p-2">
                                        <i class="bi bi-check-circle"></i> SIM
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-danger p-2">
                                        <i class="bi bi-x-circle"></i> NÃO
                                    </span>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($resposta['tipo_resposta'] == 'escala_1_5'): ?>
                                <!-- Para escala 1-5, mostrar estrelas -->
                                <div class="d-flex align-items-center">
                                    <span class="me-3">Nota:</span>
                                    <div class="rating-display">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?php echo $i <= $resposta['resposta_nota'] ? '-fill' : ''; ?> text-warning fs-5"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="ms-3 badge bg-primary"><?php echo $resposta['resposta_nota']; ?>/5</span>
                                </div>
                            <?php elseif ($resposta['tipo_resposta'] == 'nota'): ?>
                                <!-- Para nota 0-10 -->
                                <div class="d-flex align-items-center">
                                    <span class="me-3">Nota:</span>
                                    <span class="badge bg-primary fs-6"><?php echo $resposta['resposta_nota']; ?>/10</span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($resposta['resposta_texto']): ?>
                        <div class="mt-2 p-3 bg-light rounded">
                            <i class="bi bi-chat-quote"></i>
                            <?php echo nl2br(htmlspecialchars($resposta['resposta_texto'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
require_once '../../includes/footer.php';
ob_end_flush();
?>
