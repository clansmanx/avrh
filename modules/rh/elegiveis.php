<?php
// modules/rh/elegiveis.php
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

// Buscar todos os colaboradores ativos com info de promoções recentes
$query = "SELECT u.*, 
                 c.nome as cargo_atual,
                 c.nivel as nivel_atual,
                 d.nome as departamento,
                 (SELECT COUNT(*) FROM historico_promocoes WHERE usuario_id = u.id AND data_promocao > DATE_SUB(NOW(), INTERVAL 6 MONTH)) as promocoes_recentes
          FROM usuarios u
          LEFT JOIN cargos c ON u.cargo_id = c.id
          LEFT JOIN departamentos d ON u.departamento_id = d.id
          WHERE u.ativo = 1 AND u.tipo = 'colaborador'
          ORDER BY u.nome";

$stmt = $conn->query($query);
$colaboradores = $stmt->fetchAll();

// Buscar todos os cargos disponíveis com seus níveis
$query_cargos = "SELECT * FROM cargos ORDER BY 
                 FIELD(nivel, 'Estágio', 'Júnior', 'Pleno', 'Sênior', 'Coordenador', 'Gerente', 'Diretor'), 
                 nome";
$stmt_cargos = $conn->query($query_cargos);
$cargos = $stmt_cargos->fetchAll();

// Mapeamento de níveis para ordenação
$ordem_niveis = [
    'Estágio' => 1,
    'Júnior' => 2,
    'Pleno' => 3,
    'Sênior' => 4,
    'Coordenador' => 5,
    'Gerente' => 6,
    'Diretor' => 7,
    '' => 0
];

$dados_colaboradores = [];

foreach ($colaboradores as $colab) {
    // Buscar a última avaliação de rotina
    $query_rotina = "SELECT nota_final, data_conclusao 
                     FROM avaliacoes a
                     JOIN formularios f ON a.formulario_id = f.id
                     WHERE a.avaliado_id = :id 
                       AND f.tipo = 'rotina'
                       AND a.status = 'concluida'
                     ORDER BY a.data_conclusao DESC
                     LIMIT 1";
    $stmt_rotina = $conn->prepare($query_rotina);
    $stmt_rotina->bindParam(':id', $colab['id']);
    $stmt_rotina->execute();
    $rotina = $stmt_rotina->fetch();
    
    // Buscar a última avaliação de RH
    $query_rh = "SELECT nota_final, data_conclusao 
                 FROM avaliacoes a
                 JOIN formularios f ON a.formulario_id = f.id
                 WHERE a.avaliado_id = :id 
                   AND f.tipo = 'rh'
                   AND a.status = 'concluida'
                 ORDER BY a.data_conclusao DESC
                 LIMIT 1";
    $stmt_rh = $conn->prepare($query_rh);
    $stmt_rh->bindParam(':id', $colab['id']);
    $stmt_rh->execute();
    $rh = $stmt_rh->fetch();
    
    $media_rotina = $rotina ? $rotina['nota_final'] : 0;
    $media_rh = $rh ? $rh['nota_final'] : 0;
    
    // Verificar elegibilidade para cargo (ambas >= 80 e sem promoção recente)
    $elegivel_cargo = ($media_rotina >= 80 && $media_rh >= 80 && $colab['promocoes_recentes'] == 0);
    
    $dados_colaboradores[] = [
        'id' => $colab['id'],
        'nome' => $colab['nome'],
        'cargo_atual' => $colab['cargo_atual'] ?? 'Sem cargo',
        'nivel_atual' => $colab['nivel_atual'] ?? '',
        'departamento' => $colab['departamento'] ?? 'Sem depto',
        'media_rotina' => $media_rotina,
        'media_rh' => $media_rh,
        'qtd_rotina' => $rotina ? 1 : 0,
        'qtd_rh' => $rh ? 1 : 0,
        'elegivel_cargo' => $elegivel_cargo,
        'promocoes_recentes' => $colab['promocoes_recentes']
    ];
}

// Contar elegíveis
$elegiveis_cargo = count(array_filter($dados_colaboradores, function($c) { return $c['elegivel_cargo']; }));

require_once '../../includes/header.php';
?>

<style>
.dropdown-menu {
    max-height: 300px;
    overflow-y: auto;
    min-width: 280px;
}

.dropdown-item {
    white-space: normal;
    word-wrap: break-word;
    padding: 10px 15px;
    border-bottom: 1px solid #f0f0f0;
}

.dropdown-item:last-child {
    border-bottom: none;
}

.dropdown-item small {
    display: block;
    color: #6c757d;
    font-size: 0.8em;
}

.table-responsive {
    overflow: visible !important;
}

.card-body {
    overflow: visible !important;
}
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Colaboradores Elegíveis para Promoção</h2>
            <a href="promocoes.php" class="btn btn-secondary">
                <i class="bi bi-clock-history"></i> Histórico
            </a>
        </div>

        <!-- Cards de resumo -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h6 class="card-title">Elegíveis para Cargo</h6>
                        <h3><?php echo $elegiveis_cargo; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h6 class="card-title">Total Avaliado</h6>
                        <h3><?php echo count($dados_colaboradores); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h6 class="card-title">Promovidos (6 meses)</h6>
                        <h3><?php echo array_sum(array_column($dados_colaboradores, 'promocoes_recentes')); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de colaboradores -->
        <div class="card">
            <div class="card-header">
                <h5>Todos os Colaboradores</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Colaborador</th>
                                <th>Cargo/Depto</th>
                                <th>Avaliações</th>
                                <th>Média Rotina</th>
                                <th>Média RH</th>
                                <th>Elegibilidade</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dados_colaboradores as $colab): 
                                $elegivel = $colab['elegivel_cargo'];
                            ?>
                            <tr class="<?php echo $elegivel ? 'table-success' : ''; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($colab['nome']); ?></strong>
                                    <?php if ($colab['promocoes_recentes'] > 0): ?>
                                    <span class="badge bg-warning ms-1">Promovido</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($colab['cargo_atual']); ?>
                                    <?php if ($colab['nivel_atual']): ?>
                                    <br><small><?php echo $colab['nivel_atual']; ?></small>
                                    <?php endif; ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($colab['departamento']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info">R: <?php echo $colab['qtd_rotina']; ?></span>
                                    <span class="badge bg-secondary">RH: <?php echo $colab['qtd_rh']; ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2"><?php echo number_format($colab['media_rotina'], 1); ?>%</span>
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-<?php echo $colab['media_rotina'] >= 80 ? 'success' : 'warning'; ?>" 
                                                 style="width: <?php echo min($colab['media_rotina'], 100); ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2"><?php echo number_format($colab['media_rh'], 1); ?>%</span>
                                        <div class="progress flex-grow-1" style="height: 8px;">
                                            <div class="progress-bar bg-<?php echo $colab['media_rh'] >= 80 ? 'success' : 'warning'; ?>" 
                                                 style="width: <?php echo min($colab['media_rh'], 100); ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($colab['elegivel_cargo']): ?>
                                    <span class="badge bg-success">Elegível</span>
                                    <?php elseif ($colab['promocoes_recentes'] > 0): ?>
                                    <span class="badge bg-warning">Promovido</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Não elegível</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($colab['elegivel_cargo']): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Promover
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php 
                                            $nivel_atual_num = $ordem_niveis[$colab['nivel_atual']] ?? 0;
                                            
                                            foreach ($cargos as $cargo): 
                                                // Pular o cargo atual
                                                if ($cargo['nome'] == $colab['cargo_atual']) continue;
                                                
                                                // Verificar se é um cargo superior
                                                $nivel_cargo_num = $ordem_niveis[$cargo['nivel'] ?? ''] ?? 0;
                                                
                                                // Se tiver níveis definidos, só mostrar superiores
                                                if ($nivel_atual_num > 0 && $nivel_cargo_num > 0) {
                                                    if ($nivel_cargo_num <= $nivel_atual_num) continue;
                                                }
                                            ?>
                                            <li>
                                                <a class="dropdown-item" href="promocoes.php?promover=<?php echo $colab['id']; ?>&cargo_id=<?php echo $cargo['id']; ?>">
                                                    <strong><?php echo htmlspecialchars($cargo['nome']); ?></strong>
                                                    <?php if ($cargo['nivel']): ?>
                                                    <br><small class="text-muted">Nível: <?php echo $cargo['nivel']; ?></small>
                                                    <?php endif; ?>
                                                </a>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
