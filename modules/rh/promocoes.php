<?php
// modules/rh/promocoes.php
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

// Processar promoção via GET (quando vem do elegiveis.php)
if (isset($_GET['promover']) && isset($_GET['cargo_id'])) {
    $usuario_id = $_GET['promover'];
    $cargo_novo_id = $_GET['cargo_id'];
    
    // Buscar dados do colaborador
    $query = "SELECT u.*, c.nome as cargo_atual_nome 
              FROM usuarios u
              LEFT JOIN cargos c ON u.cargo_id = c.id
              WHERE u.id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        $_SESSION['error'] = "Colaborador não encontrado";
        ob_end_clean();
        header('Location: elegiveis.php');
        exit;
    }
    
    // Buscar dados do novo cargo
    $query = "SELECT * FROM cargos WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $cargo_novo_id);
    $stmt->execute();
    $cargo_novo = $stmt->fetch();
    
    if (!$cargo_novo) {
        $_SESSION['error'] = "Cargo não encontrado";
        ob_end_clean();
        header('Location: elegiveis.php');
        exit;
    }
    
    // Buscar médias das avaliações
    $query_medias = "SELECT 
                        AVG(CASE WHEN f.tipo = 'rotina' THEN a.nota_final END) as media_rotina,
                        AVG(CASE WHEN f.tipo = 'rh' THEN a.nota_final END) as media_rh
                     FROM avaliacoes a
                     JOIN formularios f ON a.formulario_id = f.id
                     WHERE a.avaliado_id = :usuario_id 
                       AND a.status = 'concluida'";
    $stmt_medias = $conn->prepare($query_medias);
    $stmt_medias->bindParam(':usuario_id', $usuario_id);
    $stmt_medias->execute();
    $medias = $stmt_medias->fetch();
    
    require_once '../../includes/header.php';
    ?>
    
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Confirmar Promoção</h2>
                <a href="elegiveis.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
    
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Detalhes da Promoção</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6>Colaborador</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($usuario['nome']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
                                    <p><strong>Cargo Atual:</strong> 
                                        <span class="badge bg-info"><?php echo htmlspecialchars($usuario['cargo_atual_nome'] ?? 'Não definido'); ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6>Nova Posição</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Novo Cargo:</strong> 
                                        <span class="badge bg-success"><?php echo htmlspecialchars($cargo_novo['nome']); ?></span>
                                    </p>
                                    <p><strong>Nível:</strong> <?php echo htmlspecialchars($cargo_novo['nivel'] ?? 'Não definido'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6>Médias das Avaliações</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Avaliação de Rotina:</strong></p>
                                    <div class="d-flex align-items-center">
                                        <span class="h2 me-3"><?php echo number_format($medias['media_rotina'] ?? 0, 1); ?>%</span>
                                        <div class="progress flex-grow-1" style="height: 20px;">
                                            <div class="progress-bar bg-<?php echo ($medias['media_rotina'] ?? 0) >= 80 ? 'success' : 'warning'; ?>" 
                                                 style="width: <?php echo min($medias['media_rotina'] ?? 0, 100); ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Avaliação de RH:</strong></p>
                                    <div class="d-flex align-items-center">
                                        <span class="h2 me-3"><?php echo number_format($medias['media_rh'] ?? 0, 1); ?>%</span>
                                        <div class="progress flex-grow-1" style="height: 20px;">
                                            <div class="progress-bar bg-<?php echo ($medias['media_rh'] ?? 0) >= 80 ? 'success' : 'warning'; ?>" 
                                                 style="width: <?php echo min($medias['media_rh'] ?? 0, 100); ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
    
                    <form action="processar_promocao.php" method="POST">
                        <input type="hidden" name="usuario_id" value="<?php echo $usuario_id; ?>">
                        <input type="hidden" name="cargo_novo_id" value="<?php echo $cargo_novo_id; ?>">
                        <input type="hidden" name="cargo_anterior_id" value="<?php echo $usuario['cargo_id']; ?>">
                        <input type="hidden" name="media_rotina" value="<?php echo $medias['media_rotina'] ?? 0; ?>">
                        <input type="hidden" name="media_rh" value="<?php echo $medias['media_rh'] ?? 0; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data da Promoção</label>
                                <input type="date" class="form-control" name="data_promocao" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Promoção</label>
                                <select class="form-select" name="tipo_promocao" required>
                                    <option value="cargo">Promoção de Cargo</option>
                                    <option value="nivel">Mudança de Nível</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="observacoes" rows="3"></textarea>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Atenção!</strong> Esta ação irá alterar permanentemente o cargo do colaborador.
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Confirmar Promoção
                            </button>
                            <a href="elegiveis.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    require_once '../../includes/footer.php';
    ob_end_flush();
    exit;
}

// Se não for promoção, mostrar histórico
// Buscar histórico de promoções
$query = "SELECT h.*, 
                 u.nome as colaborador,
                 ca.nome as cargo_anterior,
                 cn.nome as cargo_novo,
                 r.nome as aprovador
          FROM historico_promocoes h
          JOIN usuarios u ON h.usuario_id = u.id
          LEFT JOIN cargos ca ON h.cargo_anterior_id = ca.id
          JOIN cargos cn ON h.cargo_novo_id = cn.id
          JOIN usuarios r ON h.aprovado_por = r.id
          ORDER BY h.data_promocao DESC, h.data_criacao DESC";
$stmt = $conn->query($query);
$promocoes = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Histórico de Promoções</h2>
            <a href="elegiveis.php" class="btn btn-primary">
                <i class="bi bi-trophy"></i> Ver Elegíveis
            </a>
        </div>
        
        <?php if (empty($promocoes)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-arrow-up-circle fs-1 d-block mb-3"></i>
            <h4>Nenhuma promoção registrada</h4>
            <p>As promoções aprovadas aparecerão aqui.</p>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Colaborador</th>
                                <th>Cargo Anterior</th>
                                <th>Cargo Novo</th>
                                <th>Tipo</th>
                                <th>Médias</th>
                                <th>Aprovado por</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($promocoes as $prom): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($prom['data_promocao'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($prom['colaborador']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($prom['cargo_anterior'] ?? '-'); ?></td>
                                <td><span class="badge bg-success"><?php echo htmlspecialchars($prom['cargo_novo']); ?></span></td>
                                <td>
                                    <?php if ($prom['tipo_promocao'] == 'cargo'): ?>
                                    <span class="badge bg-primary">Promoção</span>
                                    <?php else: ?>
                                    <span class="badge bg-info">Mudança de Nível</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        Rotina: <?php echo number_format($prom['media_rotina'], 1); ?>%<br>
                                        RH: <?php echo number_format($prom['media_rh'], 1); ?>%
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($prom['aprovador']); ?></td>
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
