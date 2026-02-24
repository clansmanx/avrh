<?php
// modules/ciclos/visualizar.php
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

$id = $_GET['id'] ?? 0;

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

// Decodificar configurações
$config = json_decode($ciclo['configuracao'], true);

// Buscar participantes do ciclo com STATUS
$query_participantes = "SELECT cp.*, u.nome, u.email, c.nome as cargo_nome, d.nome as departamento_nome,
                               (SELECT COUNT(*) FROM avaliacoes 
                                WHERE ciclo_id = cp.ciclo_id 
                                AND avaliador_id = cp.usuario_id 
                                AND status = 'concluida') as avaliacoes_como_avaliador,
                               (SELECT COUNT(*) FROM avaliacoes 
                                WHERE ciclo_id = cp.ciclo_id 
                                AND avaliado_id = cp.usuario_id 
                                AND status = 'concluida') as avaliacoes_como_avaliado
                        FROM ciclo_participantes cp
                        JOIN usuarios u ON cp.usuario_id = u.id
                        LEFT JOIN cargos c ON u.cargo_id = c.id
                        LEFT JOIN departamentos d ON u.departamento_id = d.id
                        WHERE cp.ciclo_id = :ciclo_id
                        ORDER BY cp.tipo_participacao, u.nome";

$stmt = $conn->prepare($query_participantes);
$stmt->bindParam(':ciclo_id', $id);
$stmt->execute();
$participantes = $stmt->fetchAll();

// Estatísticas básicas
$total_avaliados = 0;
$total_avaliadores = 0;

foreach ($participantes as $p) {
    if ($p['tipo_participacao'] == 'avaliado') {
        $total_avaliados++;
    } else {
        $total_avaliadores++;
    }
}

// Buscar estatísticas de avaliações
$query_stats = "SELECT 
                  COUNT(*) as total_avaliacoes,
                  SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) as concluidas
                FROM avaliacoes
                WHERE ciclo_id = :ciclo_id";

$stmt_stats = $conn->prepare($query_stats);
$stmt_stats->bindParam(':ciclo_id', $id);
$stmt_stats->execute();
$stats = $stmt_stats->fetch();

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Detalhes do Ciclo: <?php echo htmlspecialchars($ciclo['nome']); ?></h2>
            <div>
                <?php if ($auth->hasPermission(['admin', 'rh']) && $ciclo['status'] == 'planejado'): ?>
                <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Editar
                </a>
                <a href="participantes.php?id=<?php echo $id; ?>" class="btn btn-success">
                    <i class="bi bi-people"></i> Participantes
                </a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <!-- Cards de estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h6 class="card-title">Total Avaliados</h6>
                        <h2><?php echo $total_avaliados; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h6 class="card-title">Total Avaliadores</h6>
                        <h2><?php echo $total_avaliadores; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h6 class="card-title">Total Avaliações</h6>
                        <h2><?php echo $stats['total_avaliacoes'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h6 class="card-title">Concluídas</h6>
                        <h2><?php echo $stats['concluidas'] ?? 0; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <!-- Informações do Ciclo -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Informações do Ciclo</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th style="width: 150px;">Nome:</th>
                                <td><?php echo htmlspecialchars($ciclo['nome']); ?></td>
                            </tr>
                            <tr>
                                <th>Descrição:</th>
                                <td><?php echo nl2br(htmlspecialchars($ciclo['descricao'] ?? '')); ?></td>
                            </tr>
                            <tr>
                                <th>Período:</th>
                                <td><?php echo $functions->formatDate($ciclo['data_inicio']); ?> até <?php echo $functions->formatDate($ciclo['data_fim']); ?></td>
                            </tr>
                            <tr>
                                <th>Tipo:</th>
                                <td><span class="badge bg-info"><?php echo $ciclo['tipo']; ?>°</span></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $ciclo['status'] == 'planejado' ? 'secondary' : 
                                            ($ciclo['status'] == 'em_andamento' ? 'success' : 
                                            ($ciclo['status'] == 'finalizado' ? 'info' : 'danger')); 
                                    ?>">
                                        <?php echo ucfirst($ciclo['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <!-- Configurações -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Configurações do Ciclo</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th>Peso Autoavaliação:</th>
                                <td><?php echo $config['autoavaliacao_peso'] ?? 1; ?></td>
                            </tr>
                            <tr>
                                <th>Peso Gestor:</th>
                                <td><?php echo $config['gestor_peso'] ?? 2; ?></td>
                            </tr>
                            <tr>
                                <th>Peso Pares:</th>
                                <td><?php echo $config['pares_peso'] ?? 1; ?></td>
                            </tr>
                            <tr>
                                <th>Peso Subordinados:</th>
                                <td><?php echo $config['subordinados_peso'] ?? 1; ?></td>
                            </tr>
                            <tr>
                                <th>Comentários:</th>
                                <td><?php echo ($config['permite_comentarios'] ?? false) ? 'Permitidos' : 'Não permitidos'; ?></td>
                            </tr>
                            <tr>
                                <th>Anônimo:</th>
                                <td><?php echo ($config['anonimo'] ?? false) ? 'Sim' : 'Não'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Participantes com STATUS -->
        <div class="card">
            <div class="card-header">
                <h5>Participantes</h5>
            </div>
            <div class="card-body">
                <?php if (empty($participantes)): ?>
                <p class="text-muted text-center py-4">
                    <i class="bi bi-people fs-1 d-block mb-3"></i>
                    Nenhum participante cadastrado neste ciclo.
                </p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Departamento</th>
                                <th>Cargo</th>
                                <th>Tipo</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participantes as $part): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($part['nome']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($part['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($part['departamento_nome'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($part['cargo_nome'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($part['tipo_participacao'] == 'avaliado'): ?>
                                    <span class="badge bg-primary">
                                        <i class="bi bi-person"></i> Avaliado
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-person-check"></i> Avaliador
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    // Determinar status baseado nas avaliações
                                    if ($part['tipo_participacao'] == 'avaliado') {
                                        // É avaliado - verificar se já foi avaliado (recebeu avaliações)
                                        if ($part['avaliacoes_como_avaliado'] > 0) {
                                            echo '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Concluído</span>';
                                        } else {
                                            echo '<span class="badge bg-warning"><i class="bi bi-clock"></i> Pendente</span>';
                                        }
                                    } else {
                                        // É avaliador - verificar se já avaliou alguém
                                        if ($part['avaliacoes_como_avaliador'] > 0) {
                                            echo '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Concluído</span>';
                                        } else {
                                            echo '<span class="badge bg-warning"><i class="bi bi-clock"></i> Pendente</span>';
                                        }
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Resumo com contadores de status -->
                <div class="mt-3 p-3 bg-light rounded">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Total de Participantes:</strong> <?php echo count($participantes); ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Avaliados:</strong> <?php echo $total_avaliados; ?> | 
                            <strong>Avaliadores:</strong> <?php echo $total_avaliadores; ?>
                        </div>
                        <div class="col-md-4">
                            <?php
                            $concluidos = 0;
                            foreach ($participantes as $p) {
                                if ($p['tipo_participacao'] == 'avaliado') {
                                    if ($p['avaliacoes_como_avaliado'] > 0) $concluidos++;
                                } else {
                                    if ($p['avaliacoes_como_avaliador'] > 0) $concluidos++;
                                }
                            }
                            ?>
                            <strong>Status:</strong> 
                            <span class="badge bg-success"><?php echo $concluidos; ?> Concluído</span>
                            <span class="badge bg-warning"><?php echo count($participantes) - $concluidos; ?> Pendente</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
require_once '../../includes/footer.php';
ob_end_flush();
?>
