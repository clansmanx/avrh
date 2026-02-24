<?php
// modules/avaliacoes/index.php
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

$user_id = $auth->getUserId();

// Buscar avaliações do usuário (como avaliador)
$query = "SELECT a.*, 
            u.nome as avaliado_nome,
            u.foto_perfil,
            u.cargo_id,
            c.nome as cargo_nome,
            ci.nome as ciclo_nome,
            ci.data_fim as prazo,
            f.nome as formulario_nome,
            DATEDIFF(ci.data_fim, CURDATE()) as dias_restantes
          FROM avaliacoes a
          JOIN usuarios u ON a.avaliado_id = u.id
          LEFT JOIN cargos c ON u.cargo_id = c.id
          JOIN ciclos_avaliacao ci ON a.ciclo_id = ci.id
          JOIN formularios f ON a.formulario_id = f.id
          WHERE a.avaliador_id = :avaliador_id
          ORDER BY 
            CASE a.status 
              WHEN 'pendente' THEN 1
              WHEN 'em_andamento' THEN 2
              ELSE 3
            END,
            ci.data_fim ASC";

$stmt = $conn->prepare($query);
$stmt->bindParam(':avaliador_id', $user_id);
$stmt->execute();
$avaliacoes = $stmt->fetchAll();

// Separar por status
$pendentes = array_filter($avaliacoes, function($a) { return $a['status'] == 'pendente'; });
$em_andamento = array_filter($avaliacoes, function($a) { return $a['status'] == 'em_andamento'; });
$concluidas = array_filter($avaliacoes, function($a) { return $a['status'] == 'concluida'; });

// Buscar avaliações onde o usuário é avaliado (recebidas)
$query_avaliado = "SELECT a.*, 
                    av.nome as avaliador_nome,
                    ci.nome as ciclo_nome,
                    f.nome as formulario_nome,
                    u_avaliado.nome as avaliado_nome
                  FROM avaliacoes a
                  JOIN usuarios av ON a.avaliador_id = av.id
                  JOIN usuarios u_avaliado ON a.avaliado_id = u_avaliado.id
                  JOIN ciclos_avaliacao ci ON a.ciclo_id = ci.id
                  JOIN formularios f ON a.formulario_id = f.id
                  WHERE a.avaliado_id = :avaliado_id 
                    AND a.avaliador_id != :avaliado_id2
                  ORDER BY a.data_conclusao DESC";

$stmt2 = $conn->prepare($query_avaliado);
$stmt2->bindParam(':avaliado_id', $user_id);
$stmt2->bindParam(':avaliado_id2', $user_id);
$stmt2->execute();
$avaliacoes_recebidas = $stmt2->fetchAll();

require_once '../../includes/header.php';
?>

<style>
/* Garantir que as abas funcionem corretamente */
.tab-pane {
    display: none;
}
.tab-pane.active {
    display: block;
}
</style>

<div class="row">
    <div class="col-12">
        <h2>Minhas Avaliações</h2>
        
        <!-- Abas -->
        <ul class="nav nav-tabs mb-4" id="avaliacoesTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pendentes-tab" data-bs-toggle="tab" data-bs-target="#pendentes" type="button" role="tab" aria-controls="pendentes" aria-selected="true">
                    <i class="bi bi-clock-history"></i> Pendentes
                    <span class="badge bg-danger ms-2"><?php echo count($pendentes); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="andamento-tab" data-bs-toggle="tab" data-bs-target="#andamento" type="button" role="tab" aria-controls="andamento" aria-selected="false">
                    <i class="bi bi-arrow-repeat"></i> Em Andamento
                    <span class="badge bg-warning ms-2"><?php echo count($em_andamento); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="concluidas-tab" data-bs-toggle="tab" data-bs-target="#concluidas" type="button" role="tab" aria-controls="concluidas" aria-selected="false">
                    <i class="bi bi-check-circle"></i> Concluídas
                    <span class="badge bg-success ms-2"><?php echo count($concluidas); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="recebidas-tab" data-bs-toggle="tab" data-bs-target="#recebidas" type="button" role="tab" aria-controls="recebidas" aria-selected="false">
                    <i class="bi bi-envelope"></i> Avaliações Recebidas
                    <span class="badge bg-info ms-2"><?php echo count($avaliacoes_recebidas); ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="avaliacoesTabContent">
            <!-- Pendentes -->
            <div class="tab-pane fade show active" id="pendentes" role="tabpanel" aria-labelledby="pendentes-tab">
                <?php if (empty($pendentes)): ?>
                <div class="alert alert-info text-center py-5">
                    <i class="bi bi-emoji-smile fs-1 d-block mb-3"></i>
                    <h4>Nenhuma avaliação pendente!</h4>
                    <p class="mb-0">Todas as suas avaliações estão em dia.</p>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($pendentes as $av): 
                        $dias_restantes = $av['dias_restantes'];
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-warning">
                            <div class="card-header bg-warning text-dark">
                                <i class="bi bi-exclamation-triangle"></i> Prazo: 
                                <?php if ($dias_restantes < 0): ?>
                                    <span class="text-danger">Atrasado <?php echo abs($dias_restantes); ?> dias</span>
                                <?php elseif ($dias_restantes == 0): ?>
                                    <span class="text-danger">Hoje</span>
                                <?php else: ?>
                                    <?php echo $dias_restantes; ?> dias restantes
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <?php if ($av['foto_perfil']): ?>
                                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $av['foto_perfil']; ?>" 
                                         class="rounded-circle me-3" width="50" height="50" style="object-fit: cover;">
                                    <?php else: ?>
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" 
                                         style="width: 50px; height: 50px;">
                                        <i class="bi bi-person-fill fs-2 text-secondary"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <h5 class="mb-0"><?php echo htmlspecialchars($av['avaliado_nome']); ?></h5>
                                        <small class="text-muted"><?php echo htmlspecialchars($av['cargo_nome'] ?? ''); ?></small>
                                    </div>
                                </div>
                                <p class="mb-2">
                                    <i class="bi bi-calendar3"></i> Ciclo: <?php echo htmlspecialchars($av['ciclo_nome']); ?>
                                </p>
                                <p class="mb-2">
                                    <i class="bi bi-file-text"></i> Formulário: <?php echo htmlspecialchars($av['formulario_nome']); ?>
                                </p>
                                <a href="responder.php?id=<?php echo $av['id']; ?>" class="btn btn-warning w-100 mt-3">
                                    <i class="bi bi-pencil"></i> Responder Agora
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Em Andamento -->
            <div class="tab-pane fade" id="andamento" role="tabpanel" aria-labelledby="andamento-tab">
                <?php if (empty($em_andamento)): ?>
                <div class="alert alert-secondary text-center py-5">
                    <i class="bi bi-arrow-repeat fs-1 d-block mb-3"></i>
                    <h4>Nenhuma avaliação em andamento</h4>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($em_andamento as $av): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-info">
                            <div class="card-header bg-info text-white">
                                <i class="bi bi-pencil-square"></i> Em Andamento
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <?php if ($av['foto_perfil']): ?>
                                    <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $av['foto_perfil']; ?>" 
                                         class="rounded-circle me-3" width="50" height="50">
                                    <?php else: ?>
                                    <i class="bi bi-person-circle fs-1 me-3"></i>
                                    <?php endif; ?>
                                    <div>
                                        <h5 class="mb-0"><?php echo htmlspecialchars($av['avaliado_nome']); ?></h5>
                                        <small><?php echo htmlspecialchars($av['ciclo_nome']); ?></small>
                                    </div>
                                </div>
                                
                                <div class="progress mb-3" style="height: 20px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" 
                                         style="width: 50%">50%</div>
                                </div>
                                
                                <a href="responder.php?id=<?php echo $av['id']; ?>" class="btn btn-info w-100">
                                    <i class="bi bi-arrow-repeat"></i> Continuar Avaliação
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Concluídas -->
            <div class="tab-pane fade" id="concluidas" role="tabpanel" aria-labelledby="concluidas-tab">
                <?php if (empty($concluidas)): ?>
                <div class="alert alert-secondary text-center py-5">
                    <i class="bi bi-check-circle fs-1 d-block mb-3"></i>
                    <h4>Nenhuma avaliação concluída</h4>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Avaliado</th>
                                <th>Ciclo</th>
                                <th>Formulário</th>
                                <th>Nota</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($concluidas as $av): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($av['foto_perfil']): ?>
                                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $av['foto_perfil']; ?>" 
                                             class="rounded-circle me-2" width="30" height="30" style="object-fit: cover;">
                                        <?php else: ?>
                                        <i class="bi bi-person-circle me-2"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($av['avaliado_nome']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($av['ciclo_nome']); ?></td>
                                <td><?php echo htmlspecialchars($av['formulario_nome']); ?></td>



<td>
    <?php if ($av['nota_final'] !== null): ?>
    <span class="badge bg-<?php 
        // Lógica NOVA para percentual (0-100%)
        if ($av['nota_final'] >= 80) {
            echo 'success'; // Verde para notas altas
        } elseif ($av['nota_final'] >= 60) {
            echo 'warning'; // Amarelo para notas médias
        } else {
            echo 'danger'; // Vermelho para notas baixas
        }
    ?> fs-6">
        <?php echo number_format($av['nota_final'], 2); ?>%
    </span>
    <?php else: ?>
    <span class="badge bg-secondary">-</span>
    <?php endif; ?>
</td>




                                <td><?php echo $av['data_conclusao'] ? date('d/m/Y', strtotime($av['data_conclusao'])) : '-'; ?></td>
                                <td>
                                    <a href="visualizar.php?id=<?php echo $av['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i> Ver
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>




<!-- Aba de Avaliações Recebidas -->
<div class="tab-pane fade" id="recebidas" role="tabpanel">
    <?php if (empty($avaliacoes_recebidas)): ?>
    <div class="alert alert-secondary text-center py-4">
        <i class="bi bi-envelope fs-1 d-block mb-3"></i>
        <h5>Nenhuma avaliação recebida</h5>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Avaliador</th>
                    <th>Ciclo</th>
                    <th>Formulário</th>
                    <th>Nota</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($avaliacoes_recebidas as $av): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($av['avaliador_nome']); ?></strong>
                        <?php if ($av['avaliador_id'] == $av['avaliado_id']): ?>
                        <span class="badge bg-info ms-1">Autoavaliação</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($av['ciclo_nome']); ?></td>
                    <td><?php echo htmlspecialchars($av['formulario_nome']); ?></td>
                    <td>
                        <?php if ($av['status'] == 'concluida'): ?>
                            <?php if ($av['nota_final'] !== null): ?>
                            <span class="badge bg-<?php 
                                // CORRIGIDO: percentual 0-100%
                                if ($av['nota_final'] >= 80) {
                                    echo 'success';
                                } elseif ($av['nota_final'] >= 60) {
                                    echo 'warning';
                                } else {
                                    echo 'danger';
                                }
                            ?> fs-6">
                                <?php echo number_format($av['nota_final'], 2); ?>%
                            </span>
                            <?php else: ?>
                            <span class="badge bg-secondary">-</span>
                            <?php endif; ?>
                        <?php else: ?>
                        <span class="badge bg-warning">Pendente</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $av['data_conclusao'] ? date('d/m/Y', strtotime($av['data_conclusao'])) : '-'; ?></td>
                    <td>
                        <?php if ($av['status'] == 'concluida'): ?>
                        <a href="visualizar.php?id=<?php echo $av['id']; ?>" class="btn btn-sm btn-info">
                            <i class="bi bi-eye"></i> Ver
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>






        </div>
    </div>
</div>

<script>
// Garantir que as abas funcionem corretamente
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar as abas do Bootstrap
    var triggerTabList = [].slice.call(document.querySelectorAll('#avaliacoesTab button'));
    triggerTabList.forEach(function(triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl);
        
        triggerEl.addEventListener('click', function(event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });
    
    // Verificar se há hash na URL para ativar a aba correta
    if (window.location.hash) {
        var tab = document.querySelector('button[data-bs-target="' + window.location.hash + '"]');
        if (tab) {
            var tabInstance = new bootstrap.Tab(tab);
            tabInstance.show();
        }
    }
});
</script>

<?php 
require_once '../../includes/footer.php';
ob_end_flush();
?>
