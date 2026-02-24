<?php
// modules/ciclos/index.php
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

$auth->requirePermission(['admin', 'rh', 'gestor']);

// Processar FINALIZAÇÃO de ciclo
if (isset($_GET['finalizar']) && $auth->hasPermission(['admin', 'rh'])) {
    $id = $_GET['finalizar'];
 
    try {
        // Verificar se o ciclo existe e está em andamento
        $query = "SELECT * FROM ciclos_avaliacao WHERE id = :id AND status = 'em_andamento'";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $ciclo = $stmt->fetch();
        
        if (!$ciclo) {
            throw new Exception("Ciclo não encontrado ou não está em andamento");
        }
        
        // Atualizar status do ciclo para finalizado
        $query = "UPDATE ciclos_avaliacao SET status = 'finalizado' WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Ciclo finalizado com sucesso!";
        } else {
            throw new Exception("Erro ao finalizar ciclo");
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro: " . $e->getMessage();
    }
    
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Processar exclusão (cancelar ciclo)
if (isset($_GET['delete']) && $auth->hasPermission('admin')) {
    $id = $_GET['delete'];
    
    $query = "UPDATE ciclos_avaliacao SET status = 'cancelado' WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Ciclo cancelado com sucesso!";
    } else {
        $_SESSION['error'] = "Erro ao cancelar ciclo.";
    }
    
    ob_end_clean();
    header('Location: index.php');
    exit;
}


// ===========================================
// PROCESSAR ATIVAÇÃO DE CICLO (FUNCIONA PARA TODOS)
// ===========================================
if (isset($_GET['ativar']) && $auth->hasPermission(['admin', 'rh'])) {
    $id = $_GET['ativar'];
    
    // Iniciar transação
    $conn->beginTransaction();
    
    try {
        // Buscar ciclo
        $query = "SELECT c.*, f.id as formulario_selecionado_id, f.tipo as formulario_selecionado_tipo
                  FROM ciclos_avaliacao c
                  LEFT JOIN formularios f ON c.formulario_id = f.id
                  WHERE c.id = :id AND c.status = 'planejado'";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $ciclo = $stmt->fetch();
        
        if (!$ciclo) {
            throw new Exception("Ciclo não encontrado ou já está ativo");
        }
        
        // Buscar participantes do ciclo
        $query_part = "SELECT cp.*, u.id as usuario_id, u.nome
                      FROM ciclo_participantes cp
                      JOIN usuarios u ON cp.usuario_id = u.id
                      WHERE cp.ciclo_id = :ciclo_id";
        $stmt_part = $conn->prepare($query_part);
        $stmt_part->bindParam(':ciclo_id', $id);
        $stmt_part->execute();
        $participantes = $stmt_part->fetchAll();
        
        // Separar avaliados e avaliadores
        $avaliados = [];
        $avaliadores = [];
        
        foreach ($participantes as $p) {
            if ($p['tipo_participacao'] == 'avaliado') {
                $avaliados[] = $p['usuario_id'];
            } else {
                $avaliadores[] = $p['usuario_id'];
            }
        }
        
        // Buscar TODOS os formulários disponíveis por tipo
        $query_form = "SELECT id, tipo FROM formularios WHERE ativo = 1";
        $stmt_form = $conn->query($query_form);
        $formularios_por_tipo = [];
        while ($f = $stmt_form->fetch()) {
            $formularios_por_tipo[$f['tipo']] = $f['id'];
        }
        
        $avaliacoes_geradas = 0;
        
        // ===========================================
        // LÓGICA SIMPLIFICADA: Para cada avaliado e cada avaliador, cria uma avaliação
        // usando o formulário apropriado
        // ===========================================
        
        foreach ($avaliados as $avaliado_id) {
            foreach ($avaliadores as $avaliador_id) {
                
                // Determinar qual formulário usar
                $formulario_id = null;
                
                // PRIORIDADE 1: Se o ciclo tem um formulário específico, usa ele
                if ($ciclo['formulario_selecionado_id']) {
                    $formulario_id = $ciclo['formulario_selecionado_id'];
                } 
                // PRIORIDADE 2: Se o avaliador é do RH ou Admin, tenta usar formulário de RH
                else {
                    // Descobrir o tipo do avaliador
                    $query_tipo = "SELECT tipo FROM usuarios WHERE id = :id";
                    $stmt_tipo = $conn->prepare($query_tipo);
                    $stmt_tipo->bindParam(':id', $avaliador_id);
                    $stmt_tipo->execute();
                    $avaliador_tipo = $stmt_tipo->fetchColumn();
                    
                    if (in_array($avaliador_tipo, ['admin', 'rh'])) {
                        // É admin ou rh - tenta usar formulário de RH
                        $formulario_id = $formularios_por_tipo['rh'] ?? null;
                    }
                    
                    // Se não achou formulário de RH, tenta de gestor
                    if (!$formulario_id) {
                        $formulario_id = $formularios_por_tipo['gestor'] ?? null;
                    }
                    
                    // Último recurso: qualquer formulário disponível
                    if (!$formulario_id && !empty($formularios_por_tipo)) {
                        $formulario_id = reset($formularios_por_tipo); // pega o primeiro
                    }
                }
                
                // Só cria se tiver um formulário válido
                if ($formulario_id) {
                    // Verificar se já não existe esta avaliação
                    $query_check = "SELECT id FROM avaliacoes 
                                   WHERE ciclo_id = :ciclo_id 
                                   AND avaliado_id = :avaliado_id 
                                   AND avaliador_id = :avaliador_id";
                    $stmt_check = $conn->prepare($query_check);
                    $stmt_check->bindParam(':ciclo_id', $id);
                    $stmt_check->bindParam(':avaliado_id', $avaliado_id);
                    $stmt_check->bindParam(':avaliador_id', $avaliador_id);
                    $stmt_check->execute();
                    
                    if ($stmt_check->rowCount() == 0) {
                        $query = "INSERT INTO avaliacoes (ciclo_id, avaliado_id, avaliador_id, formulario_id, status) 
                                  VALUES (:ciclo_id, :avaliado_id, :avaliador_id, :formulario_id, 'pendente')";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':ciclo_id', $id);
                        $stmt->bindParam(':avaliado_id', $avaliado_id);
                        $stmt->bindParam(':avaliador_id', $avaliador_id);
                        $stmt->bindParam(':formulario_id', $formulario_id);
                        $stmt->execute();
                        $avaliacoes_geradas++;
                    }
                }
            }
        }
        
        // Se não gerou nenhuma avaliação, criar pelo menos uma para cada avaliado com o primeiro avaliador
        if ($avaliacoes_geradas == 0 && !empty($avaliados) && !empty($avaliadores)) {
            foreach ($avaliados as $avaliado_id) {
                // Pega o primeiro avaliador da lista
                $avaliador_id = $avaliadores[0];
                
                // Pega qualquer formulário disponível
                $formulario_id = null;
                if ($ciclo['formulario_selecionado_id']) {
                    $formulario_id = $ciclo['formulario_selecionado_id'];
                } elseif (!empty($formularios_por_tipo)) {
                    $formulario_id = reset($formularios_por_tipo);
                }
                
                if ($formulario_id) {
                    $query = "INSERT INTO avaliacoes (ciclo_id, avaliado_id, avaliador_id, formulario_id, status) 
                              VALUES (:ciclo_id, :avaliado_id, :avaliador_id, :formulario_id, 'pendente')";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':ciclo_id', $id);
                    $stmt->bindParam(':avaliado_id', $avaliado_id);
                    $stmt->bindParam(':avaliador_id', $avaliador_id);
                    $stmt->bindParam(':formulario_id', $formulario_id);
                    $stmt->execute();
                    $avaliacoes_geradas++;
                }
            }
        }
        
        // Atualizar status do ciclo para em_andamento
        $query = "UPDATE ciclos_avaliacao SET status = 'em_andamento' WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $conn->commit();
        
        $_SESSION['success'] = "Ciclo ativado com sucesso! $avaliacoes_geradas avaliações foram geradas.";
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Erro ao ativar ciclo: " . $e->getMessage();
    }
    
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// ===========================================
// FIM PROCESSAR ATIVAÇÃO DE CICLO 
// ===========================================


// Buscar ciclos
$query = "SELECT c.*, 
            (SELECT COUNT(*) FROM ciclo_participantes WHERE ciclo_id = c.id AND tipo_participacao = 'avaliado') as total_avaliados,
            (SELECT COUNT(*) FROM avaliacoes WHERE ciclo_id = c.id) as total_avaliacoes,
            (SELECT COUNT(*) FROM avaliacoes WHERE ciclo_id = c.id AND status = 'concluida') as concluidas
          FROM ciclos_avaliacao c
          ORDER BY c.data_inicio DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$ciclos = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Ciclos de Avaliação</h2>
            <?php if ($auth->hasPermission(['admin', 'rh'])): ?>
            <a href="adicionar.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Novo Ciclo
            </a>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (empty($ciclos)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
            <h4>Nenhum ciclo encontrado</h4>
            <p class="mb-0">Clique em "Novo Ciclo" para começar.</p>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Período</th>
                                <th>Tipo</th>
                                <th>Participantes</th>
                                <th>Progresso</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ciclos as $ciclo): 
                                $progresso = $ciclo['total_avaliacoes'] > 0 
                                    ? round(($ciclo['concluidas'] / $ciclo['total_avaliacoes']) * 100) 
                                    : 0;
                                
                                $status_class = [
                                    'planejado' => 'secondary',
                                    'em_andamento' => 'success',
                                    'finalizado' => 'info',
                                    'cancelado' => 'danger'
                                ][$ciclo['status']] ?? 'secondary';
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($ciclo['nome']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($ciclo['descricao'] ?? '', 0, 50)); ?>...</small>
                                </td>
                                <td>
                                    <?php echo $functions->formatDate($ciclo['data_inicio']); ?><br>
                                    <small>até <?php echo $functions->formatDate($ciclo['data_fim']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $ciclo['tipo']; ?>°
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $ciclo['total_avaliados']; ?> avaliados</span>
                                </td>
                                <td style="min-width: 150px;">
                                    <div class="progress mb-2" style="height: 8px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo $progresso; ?>%"
                                             aria-valuenow="<?php echo $progresso; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small><?php echo $ciclo['concluidas']; ?>/<?php echo $ciclo['total_avaliacoes']; ?> concluídas</small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $ciclo['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="visualizar.php?id=<?php echo $ciclo['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Visualizar">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php if ($auth->hasPermission(['admin', 'rh'])): ?>
                                            <?php if ($ciclo['status'] == 'planejado'): ?>
                                            <a href="editar.php?id=<?php echo $ciclo['id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="participantes.php?id=<?php echo $ciclo['id']; ?>" 
                                               class="btn btn-sm btn-success" title="Gerenciar Participantes">
                                                <i class="bi bi-people"></i>
                                            </a>
                                            <a href="?ativar=<?php echo $ciclo['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Ativar Ciclo"
                                               onclick="return confirm('Ativar este ciclo? As avaliações serão geradas automaticamente.')">
                                                <i class="bi bi-play-fill"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($ciclo['status'] == 'em_andamento'): ?>
                                            <a href="?finalizar=<?php echo $ciclo['id']; ?>" 
                                               class="btn btn-sm btn-success" title="Finalizar Ciclo"
                                               onclick="return confirm('Finalizar este ciclo? Após finalizado, não será possível realizar novas avaliações.')">
                                                <i class="bi bi-check-lg"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($auth->hasPermission('admin') && $ciclo['status'] != 'cancelado' && $ciclo['status'] != 'finalizado'): ?>
                                            <a href="?delete=<?php echo $ciclo['id']; ?>" 
                                               class="btn btn-sm btn-danger" title="Cancelar Ciclo"
                                               onclick="return confirm('Tem certeza que deseja cancelar este ciclo?')">
                                                <i class="bi bi-x-circle"></i>
                                            </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php if ($ciclo['status'] == 'finalizado'): ?>
                                        <a href="<?php echo SITE_URL; ?>/modules/relatorios/ciclo.php?id=<?php echo $ciclo['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Relatório">
                                            <i class="bi bi-graph-up"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php 
require_once '../../includes/footer.php';
ob_end_flush();
?>
