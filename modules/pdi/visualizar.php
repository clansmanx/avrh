<?php
// modules/pdi/visualizar.php
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

$pdi_id = $_GET['id'] ?? 0;
$user_id = $auth->getUserId();

// Buscar PDI
$query = "SELECT p.*, 
                 u_colab.nome as colaborador_nome,
                 u_colab.email as colaborador_email,
                 u_colab.foto_perfil as colaborador_foto,
                 u_colab.cargo_id,
                 c.nome as cargo_colaborador,
                 d.nome as departamento_colaborador,
                 u_gestor.nome as gestor_nome,
                 u_gestor.id as gestor_id
          FROM pdi p
          JOIN usuarios u_colab ON p.colaborador_id = u_colab.id
          JOIN usuarios u_gestor ON p.gestor_responsavel_id = u_gestor.id
          LEFT JOIN cargos c ON u_colab.cargo_id = c.id
          LEFT JOIN departamentos d ON u_colab.departamento_id = d.id
          WHERE p.id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $pdi_id);
$stmt->execute();
$pdi = $stmt->fetch();

if (!$pdi) {
    $_SESSION['error'] = "PDI não encontrado";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Verificar permissão
$pode_editar = in_array($auth->getUserType(), ['admin', 'rh']) || $user_id == $pdi['gestor_id'];
$pode_ver = $pode_editar || $user_id == $pdi['colaborador_id'];

if (!$pode_ver) {
    $_SESSION['error'] = "Você não tem permissão para ver este PDI";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Buscar competências do PDI com tipo de pergunta
$query_comp = "SELECT pc.*, c.nome, c.tipo,
                      (SELECT tipo_resposta FROM perguntas WHERE competencia_id = c.id LIMIT 1) as tipo_pergunta
               FROM pdi_competencias pc
               JOIN competencias c ON pc.competencia_id = c.id
               WHERE pc.pdi_id = :pdi_id
               ORDER BY pc.prioridade DESC, c.nome";
$stmt_comp = $conn->prepare($query_comp);
$stmt_comp->bindParam(':pdi_id', $pdi_id);
$stmt_comp->execute();
$competencias = $stmt_comp->fetchAll();

// Buscar metas do PDI com progresso calculado
$query_metas = "SELECT m.*, c.nome as competencia_nome,
                       (SELECT COUNT(*) FROM pdi_checklists WHERE tipo = 'meta' AND item_id = m.id) as total_checklists,
                       (SELECT COUNT(*) FROM pdi_checklists pc 
                        LEFT JOIN pdi_checklist_conclusoes cc ON pc.id = cc.checklist_id 
                        WHERE pc.tipo = 'meta' AND pc.item_id = m.id) as checklists_concluidos
                FROM pdi_metas m
                LEFT JOIN pdi_competencias pc ON m.competencia_id = pc.id
                LEFT JOIN competencias c ON pc.competencia_id = c.id
                WHERE m.pdi_id = :pdi_id
                ORDER BY m.data_prazo ASC";
$stmt_metas = $conn->prepare($query_metas);
$stmt_metas->bindParam(':pdi_id', $pdi_id);
$stmt_metas->execute();
$metas = $stmt_metas->fetchAll();

// Buscar ações do PDI com progresso calculado
$query_acoes = "SELECT a.*, m.titulo as meta_titulo,
                       (SELECT COUNT(*) FROM pdi_checklists WHERE tipo = 'acao' AND item_id = a.id) as total_checklists,
                       (SELECT COUNT(*) FROM pdi_checklists pc 
                        LEFT JOIN pdi_checklist_conclusoes cc ON pc.id = cc.checklist_id 
                        WHERE pc.tipo = 'acao' AND pc.item_id = a.id) as checklists_concluidos
                FROM pdi_acoes a
                LEFT JOIN pdi_metas m ON a.meta_id = m.id
                WHERE a.pdi_id = :pdi_id
                ORDER BY a.data_inicio ASC";
$stmt_acoes = $conn->prepare($query_acoes);
$stmt_acoes->bindParam(':pdi_id', $pdi_id);
$stmt_acoes->execute();
$acoes = $stmt_acoes->fetchAll();

// Buscar acompanhamentos
$query_acomp = "SELECT a.*, u.nome as responsavel_nome
                FROM pdi_acompanhamentos a
                JOIN usuarios u ON a.responsavel_id = u.id
                WHERE a.pdi_id = :pdi_id
                ORDER BY a.data_acompanhamento DESC";
$stmt_acomp = $conn->prepare($query_acomp);
$stmt_acomp->bindParam(':pdi_id', $pdi_id);
$stmt_acomp->execute();
$acompanhamentos = $stmt_acomp->fetchAll();

// Buscar histórico
$query_hist = "SELECT h.*, u.nome as usuario_nome
               FROM pdi_historico h
               JOIN usuarios u ON h.usuario_id = u.id
               WHERE h.pdi_id = :pdi_id
               ORDER BY h.data_acao DESC";
$stmt_hist = $conn->prepare($query_hist);
$stmt_hist->bindParam(':pdi_id', $pdi_id);
$stmt_hist->execute();
$historico = $stmt_hist->fetchAll();

// ===========================================
// CALCULAR PROGRESSO GERAL REAL
// ===========================================
$progresso_geral = 0;
$total_metas = count($metas);
$total_acoes = count($acoes);
$soma_progresso = 0;

if ($total_metas > 0 || $total_acoes > 0) {
    // Calcular progresso das metas
    foreach ($metas as $meta) {
        $stmt_check = $conn->prepare("SELECT 
                                            COUNT(*) as total,
                                            SUM(CASE WHEN cc.id IS NOT NULL THEN 1 ELSE 0 END) as concluidos
                                        FROM pdi_checklists pc
                                        LEFT JOIN pdi_checklist_conclusoes cc ON pc.id = cc.checklist_id
                                        WHERE pc.tipo = 'meta' AND pc.item_id = ?");
        $stmt_check->execute([$meta['id']]);
        $check = $stmt_check->fetch();
        
        if ($check['total'] > 0) {
            $soma_progresso += round(($check['concluidos'] / $check['total']) * 100);
        } else {
            $soma_progresso += $meta['progresso'];
        }
    }
    
    // Calcular progresso das ações
    foreach ($acoes as $acao) {
        $stmt_check = $conn->prepare("SELECT 
                                            COUNT(*) as total,
                                            SUM(CASE WHEN cc.id IS NOT NULL THEN 1 ELSE 0 END) as concluidos
                                        FROM pdi_checklists pc
                                        LEFT JOIN pdi_checklist_conclusoes cc ON pc.id = cc.checklist_id
                                        WHERE pc.tipo = 'acao' AND pc.item_id = ?");
        $stmt_check->execute([$acao['id']]);
        $check = $stmt_check->fetch();
        
        if ($check['total'] > 0) {
            $soma_progresso += round(($check['concluidos'] / $check['total']) * 100);
        } else {
            $soma_progresso += $acao['progresso'];
        }
    }
    
    $progresso_geral = round($soma_progresso / ($total_metas + $total_acoes));
}

require_once '../../includes/header.php';
?>

<style>
/* =========================================== */
/* ESTILOS CORRIGIDOS - TUDO VISÍVEL           */
/* =========================================== */

/* Títulos dos cards - BRANCOS */
.card-header.bg-primary,
.card-header.bg-primary *,
.card-header.bg-success,
.card-header.bg-success *,
.card-header.bg-info,
.card-header.bg-info *,
.card-header.bg-warning,
.card-header.bg-warning *,
.card-header.bg-secondary,
.card-header.bg-secondary * {
    color: white !important;
}

/* Textos gerais */
.text-dark {
    color: #212529 !important;
}
.text-muted {
    color: #6c757d !important;
}

/* Barra de progresso - SEMPRE VISÍVEL */
.progress {
    background-color: #e9ecef !important;
    height: 20px !important;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}
.progress-bar {
    color: white !important;
    font-weight: bold;
    text-shadow: 1px 1px 1px rgba(0,0,0,0.3);
    line-height: 20px;
}
.progress-bar.bg-success {
    background-color: #28a745 !important;
}
.progress-bar.bg-primary {
    background-color: #007bff !important;
}
.progress-bar.bg-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

/* Badges */
.badge {
    font-size: 0.85rem;
    padding: 0.5em 1em;
    font-weight: 500;
}

/* Checklist */
.form-check-input {
    cursor: pointer;
    margin-top: 0.3rem;
}
.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}
.form-check-label {
    cursor: pointer;
    font-size: 0.95rem;
}
.text-decoration-line-through {
    text-decoration: line-through;
    color: #6c757d !important;
}

/* Botões */
.btn-primary, .btn-success, .btn-info, .btn-warning {
    color: white !important;
}
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold" style="color: #4e73df;"><i class="bi bi-diagram-3 me-2"></i><?php echo htmlspecialchars($pdi['titulo']); ?></h2>
            <div>
                <?php if ($pode_editar): ?>
                <a href="editar.php?id=<?php echo $pdi_id; ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil"></i> Editar
                </a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <!-- Alertas -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- =========================================== -->
        <!-- CABEÇALHO DO PDI - VERSÃO MELHORADA        -->
        <!-- =========================================== -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <!-- Foto maior e mais bonita -->
                        <div class="text-center">
                            <?php if ($pdi['colaborador_foto']): ?>
                            <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $pdi['colaborador_foto']; ?>" 
                                 class="rounded-circle img-fluid shadow-sm border border-3 border-white"
                                 style="width: 100px; height: 100px; object-fit: cover;">
                            <?php else: ?>
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm border border-3 border-white"
                                 style="width: 100px; height: 100px;">
                                <i class="bi bi-person-circle fs-1 text-secondary"></i>
                            </div>
                            <?php endif; ?>
                            <h5 class="mt-2 mb-0 fw-bold"><?php echo htmlspecialchars($pdi['colaborador_nome']); ?></h5>
                            <p class="text-muted small"><?php echo htmlspecialchars($pdi['cargo_colaborador'] ?? 'Sem cargo'); ?> | <?php echo htmlspecialchars($pdi['departamento_colaborador'] ?? 'Sem depto'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-sm-6 mb-2">
                                <small class="text-muted d-block">Criação</small>
                                <strong class="text-dark"><?php echo date('d/m/Y', strtotime($pdi['data_criacao'])); ?></strong>
                            </div>
                            <div class="col-sm-6 mb-2">
                                <small class="text-muted d-block">Revisão</small>
                                <strong class="text-dark"><?php echo isset($pdi['data_revisao']) ? date('d/m/Y', strtotime($pdi['data_revisao'])) : 'Não definida'; ?></strong>
                            </div>
                            <div class="col-sm-6 mb-2">
                                <small class="text-muted d-block">Gestor</small>
                                <strong class="text-dark"><?php echo htmlspecialchars($pdi['gestor_nome']); ?></strong>
                            </div>
                            <div class="col-sm-6 mb-2">
                                <small class="text-muted d-block">Status</small>
                                <?php
                                $status_class = [
                                    'ativo' => 'primary',
                                    'em_andamento' => 'warning',
                                    'concluido' => 'success',
                                    'cancelado' => 'danger'
                                ][$pdi['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $status_class; ?> text-white"><?php echo ucfirst($pdi['status']); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <!-- Gráfico Circular Melhorado -->
                        <div class="text-center">
                            <div class="progress-circle" style="width: 120px; height: 120px; margin: 0 auto; position: relative;">
                                <svg viewBox="0 0 120 120" style="transform: rotate(-90deg); width: 120px; height: 120px;">
                                    <circle cx="60" cy="60" r="54" fill="none" stroke="#e9ecef" stroke-width="8" stroke-linecap="round"/>
                                    <circle cx="60" cy="60" r="54" fill="none" stroke="#4e73df" stroke-width="8" stroke-linecap="round"
                                            stroke-dasharray="339.292" 
                                            stroke-dashoffset="<?php echo 339.292 - (339.292 * $progresso_geral / 100); ?>" 
                                            style="transition: stroke-dashoffset 0.8s ease-in-out;"/>
                                </svg>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                    <span style="font-size: 1.8rem; font-weight: bold; color: #4e73df;"><?php echo $progresso_geral; ?>%</span>
                                </div>
                            </div>
                            <p class="text-muted mt-2 small">Progresso Geral</p>
                        </div>
                    </div>
                </div>
                <?php if (isset($pdi['observacoes_gerais']) && !empty($pdi['observacoes_gerais'])): ?>
                <div class="mt-3 p-3 bg-light rounded">
                    <i class="bi bi-chat-text me-1 text-muted"></i> <span class="text-dark"><?php echo nl2br(htmlspecialchars($pdi['observacoes_gerais'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- =========================================== -->
        <!-- COMPETÊNCIAS A DESENVOLVER                  -->
        <!-- =========================================== -->
        <?php if (!empty($competencias)): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Competências a Desenvolver</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning py-2 mb-3">
                    <i class="bi bi-info-circle me-2"></i> 
                    <strong class="text-dark">SIM/NÃO:</strong> 
                    <span class="badge bg-success ms-2">✅ SIM</span>
                    <span class="badge bg-danger ms-2">❌ NÃO</span>
                    <span class="badge bg-warning text-dark ms-2">⚠️ Parcial</span>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th class="text-dark">Competência</th>
                                <th class="text-dark">Tipo</th>
                                <th class="text-dark">Resultado</th>
                                <th class="text-dark">Meta</th>
                                <th class="text-dark">Prioridade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($competencias as $c): 
                                $nivel_atual = floatval($c['nivel_atual']);
                                
                                if ($c['tipo_pergunta'] == 'sim_nao') {
                                    if ($nivel_atual >= 100) {
                                        $resultado = '<span class="badge bg-success">✅ SIM</span>';
                                    } elseif ($nivel_atual <= 0) {
                                        $resultado = '<span class="badge bg-danger">❌ NÃO</span>';
                                    } else {
                                        $total = round(100 / $nivel_atual);
                                        $qtd = round(($nivel_atual / 100) * $total);
                                        $resultado = '<span class="badge bg-warning text-dark">⚠️ ' . $qtd . '/' . $total . ' SIM</span>';
                                    }
                                    $meta = '<span class="badge bg-primary">✅ SIM</span>';
                                } else {
                                    $resultado = '<span class="badge bg-info">' . number_format($nivel_atual, 1) . '%</span>';
                                    $meta = '<span class="badge bg-primary">' . number_format($c['nivel_desejado'], 1) . '%</span>';
                                }
                            ?>
                            <tr>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($c['nome']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo $c['tipo_pergunta'] == 'sim_nao' ? 'SIM/NÃO' : 'Escala'; ?></span></td>
                                <td><?php echo $resultado; ?></td>
                                <td><?php echo $meta; ?></td>
                                <td>
                                    <?php if ($c['prioridade'] == 'alta'): ?>
                                        <span class="badge bg-danger">Alta</span>
                                    <?php elseif ($c['prioridade'] == 'media'): ?>
                                        <span class="badge bg-warning text-dark">Média</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Baixa</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- =========================================== -->
        <!-- METAS SMART - COM PROGRESSO REAL            -->
        <!-- =========================================== -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-bullseye me-2"></i>Metas SMART</h5>
                    <?php if ($pode_editar): ?>
                    <a href="adicionar_meta.php?pdi_id=<?php echo $pdi_id; ?>" class="btn btn-light btn-sm">
                        <i class="bi bi-plus"></i> Nova Meta
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($metas)): ?>
                    <p class="text-muted">Nenhuma meta cadastrada.</p>
                <?php else: ?>
                    <?php foreach ($metas as $meta): 
                        // ===========================================
                        // PROGRESSO REAL - CONSULTA DIRETA NO BANCO
                        // ===========================================
                        $stmt_check_count = $conn->prepare("SELECT 
                                                                COUNT(*) as total,
                                                                SUM(CASE WHEN cc.id IS NOT NULL THEN 1 ELSE 0 END) as concluidos
                                                            FROM pdi_checklists pc
                                                            LEFT JOIN pdi_checklist_conclusoes cc ON pc.id = cc.checklist_id
                                                            WHERE pc.tipo = 'meta' AND pc.item_id = ?");
                        $stmt_check_count->execute([$meta['id']]);
                        $check_data = $stmt_check_count->fetch();
                        
                        if ($check_data['total'] > 0) {
                            // Tem checklist - progresso baseado nos itens marcados
                            $progresso = round(($check_data['concluidos'] / $check_data['total']) * 100);
                            
                            // Atualizar no banco se necessário
                            if ($progresso != $meta['progresso']) {
                                $update_prog = $conn->prepare("UPDATE pdi_metas SET progresso = ? WHERE id = ?");
                                $update_prog->execute([$progresso, $meta['id']]);
                            }
                        } else {
                            // Não tem checklist - usa progresso manual
                            $progresso = $meta['progresso'];
                        }
                        
                        // STATUS BASEADO NO PROGRESSO REAL
                        if ($progresso >= 100) {
                            $status_badge = '<span class="badge bg-success">Concluída</span>';
                        } elseif ($progresso > 0) {
                            $status_badge = '<span class="badge bg-warning text-dark">Em Andamento</span>';
                        } else {
                            $status_badge = '<span class="badge bg-secondary">Pendente</span>';
                        }
                        
                        // Buscar itens do checklist para exibir
                        $stmt_check = $conn->prepare("SELECT * FROM pdi_checklists WHERE tipo='meta' AND item_id=? ORDER BY ordem");
                        $stmt_check->execute([$meta['id']]);
                        $checklist_itens = $stmt_check->fetchAll();
                    ?>
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($meta['titulo']); ?></h6>
                                <?php echo $status_badge; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-dark mb-2"><?php echo nl2br(htmlspecialchars($meta['descricao'])); ?></p>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Critério de sucesso</small>
                                    <span class="text-dark"><?php echo htmlspecialchars($meta['criterio_sucesso']); ?></span>
                                </div>
                                <?php if ($meta['competencia_nome']): ?>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Competência</small>
                                    <span class="badge bg-info text-dark"><?php echo $meta['competencia_nome']; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Prazo</small>
                                    <span class="text-dark"><?php echo date('d/m/Y', strtotime($meta['data_prazo'])); ?></span>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Progresso</small>
                                    <span class="fw-bold text-dark"><?php echo $progresso; ?>%</span>
                                </div>
                            </div>
                            
                            <!-- Barra de progresso com valor REAL -->
                            <div class="progress mb-3">
                                <div class="progress-bar bg-<?php echo $progresso >= 100 ? 'success' : 'primary'; ?>" 
                                     style="width: <?php echo $progresso; ?>%;">
                                    <?php echo $progresso; ?>%
                                </div>
                            </div>
                            
                            <!-- Checklist -->
                            <?php if (!empty($checklist_itens)): ?>
                                <div class="mt-3">
                                    <h6 class="text-dark mb-2">Checklist:</h6>
                                    <?php foreach ($checklist_itens as $item): 
                                        $feito = $conn->query("SELECT id FROM pdi_checklist_conclusoes WHERE checklist_id={$item['id']}")->rowCount() > 0;
                                    ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" <?php echo $feito ? 'checked' : ''; ?> 
                                               onchange="marcarItem(<?php echo $item['id']; ?>, 'meta', <?php echo $meta['id']; ?>, this.checked)">
                                        <label class="form-check-label <?php echo $feito ? 'text-decoration-line-through text-muted' : 'text-dark'; ?>">
                                            <?php echo htmlspecialchars($item['titulo']); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <a href="checklists.php?tipo=meta&id=<?php echo $meta['id']; ?>&pdi_id=<?php echo $pdi_id; ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil"></i> Gerenciar checklist completo
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- =========================================== -->
        <!-- AÇÕES DE DESENVOLVIMENTO                    -->
        <!-- =========================================== -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Ações de Desenvolvimento</h5>
                    <?php if ($pode_editar): ?>
                    <a href="adicionar_acao.php?pdi_id=<?php echo $pdi_id; ?>" class="btn btn-light btn-sm">
                        <i class="bi bi-plus"></i> Nova Ação
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($acoes)): ?>
                <p class="text-muted">Nenhuma ação cadastrada.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th class="text-dark">Ação</th>
                                <th class="text-dark">Tipo</th>
                                <th class="text-dark">Período</th>
                                <th class="text-dark">Progresso</th>
                                <th class="text-dark">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($acoes as $acao): 
                                $status_class = [
                                    'pendente' => 'secondary',
                                    'em_andamento' => 'warning',
                                    'concluida' => 'success',
                                    'cancelada' => 'danger'
                                ][$acao['status']] ?? 'secondary';
                                
                                $stmt_check_acao = $conn->prepare("SELECT 
                                                                        COUNT(*) as total,
                                                                        SUM(CASE WHEN cc.id IS NOT NULL THEN 1 ELSE 0 END) as concluidos
                                                                    FROM pdi_checklists pc
                                                                    LEFT JOIN pdi_checklist_conclusoes cc ON pc.id = cc.checklist_id
                                                                    WHERE pc.tipo = 'acao' AND pc.item_id = ?");
                                $stmt_check_acao->execute([$acao['id']]);
                                $check_acao = $stmt_check_acao->fetch();
                                
                                if ($check_acao['total'] > 0) {
                                    $progresso_acao = round(($check_acao['concluidos'] / $check_acao['total']) * 100);
                                } else {
                                    $progresso_acao = $acao['progresso'];
                                }
                            ?>
                            <tr>
                                <td>
                                    <strong class="text-dark"><?php echo htmlspecialchars($acao['titulo']); ?></strong><br>
                                    <small class="text-muted"><?php echo nl2br(htmlspecialchars($acao['descricao'])); ?></small>
                                </td>
                                <td><span class="badge bg-info text-white"><?php echo ucfirst($acao['tipo']); ?></span></td>
                                <td class="text-dark">
                                    <?php if ($acao['data_inicio']): ?>
                                        <?php echo date('d/m/Y', strtotime($acao['data_inicio'])); ?>
                                        <?php if ($acao['data_fim']): ?> até <?php echo date('d/m/Y', strtotime($acao['data_fim'])); ?><?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo $progresso_acao; ?>%;">
                                            <?php echo $progresso_acao; ?>%
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-<?php echo $status_class; ?> text-white"><?php echo ucfirst($acao['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- =========================================== -->
        <!-- ACOMPANHAMENTOS                             -->
        <!-- =========================================== -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-info text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Acompanhamentos</h5>
                    <?php if ($pode_editar): ?>
                    <a href="acompanhamentos.php?pdi_id=<?php echo $pdi_id; ?>" class="btn btn-light btn-sm">
                        <i class="bi bi-plus"></i> Novo Acompanhamento
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($acompanhamentos)): ?>
                <p class="text-muted">Nenhum acompanhamento registrado.</p>
                <?php else: ?>
                <div class="list-group">
                    <?php foreach ($acompanhamentos as $acomp): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <strong class="text-dark"><?php echo date('d/m/Y', strtotime($acomp['data_acompanhamento'])); ?></strong>
                            <span class="badge bg-info text-white"><?php echo $acomp['progresso_geral']; ?>%</span>
                        </div>
                        <p class="mt-2 text-dark"><?php echo nl2br(htmlspecialchars($acomp['topicos_discutidos'])); ?></p>
                        <?php if (!empty($acomp['dificuldades_encontradas'])): ?>
                        <p class="text-danger mb-1"><small><i class="bi bi-exclamation-triangle"></i> <?php echo nl2br(htmlspecialchars($acomp['dificuldades_encontradas'])); ?></small></p>
                        <?php endif; ?>
                        <small class="text-muted">por <?php echo htmlspecialchars($acomp['responsavel_nome']); ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- =========================================== -->
        <!-- HISTÓRICO                                   -->
        <!-- =========================================== -->
        <?php if (!empty($historico)): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-archive me-2"></i>Histórico de Alterações</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php foreach ($historico as $h): ?>
                    <div class="list-group-item">
                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($h['data_acao'])); ?></small><br>
                        <strong class="text-dark"><?php echo htmlspecialchars($h['usuario_nome']); ?></strong> - <span class="text-dark"><?php echo htmlspecialchars($h['descricao']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Script para marcar itens via AJAX -->
<script>
function marcarItem(itemId, tipo, metaId, marcado) {
    fetch('<?php echo SITE_URL; ?>/modules/pdi/marcar_item.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ item_id: itemId, tipo: tipo, marcado: marcado ? 1 : 0 })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao comunicar com o servidor');
    });
}
</script>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
