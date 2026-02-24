<?php
// modules/relatorios/dashboard_analytics.php
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

// ===========================================
// FILTROS
// ===========================================
$filtro_empresa = $_GET['empresa'] ?? 'todas';
$filtro_periodo = $_GET['periodo'] ?? '6meses';

// Buscar empresas
$empresas = $conn->query("SELECT * FROM empresas WHERE ativo = 1 ORDER BY nome")->fetchAll();

// ===========================================
// MÉDIAS POR EMPRESA
// ===========================================
$query_medias = "
    SELECT 
        e.id,
        e.nome,
        e.tipo,
        COUNT(DISTINCT u.id) as total_colaboradores,
        AVG(a.nota_final) as media_geral,
        COUNT(a.id) as total_avaliacoes,
        SUM(CASE WHEN a.nota_final >= 80 THEN 1 ELSE 0 END) as acima_80,
        SUM(CASE WHEN a.nota_final BETWEEN 60 AND 79 THEN 1 ELSE 0 END) as entre_60_79,
        SUM(CASE WHEN a.nota_final < 60 THEN 1 ELSE 0 END) as abaixo_60
    FROM empresas e
    LEFT JOIN usuarios u ON u.empresa_id = e.id AND u.ativo = 1
    LEFT JOIN avaliacoes a ON a.avaliado_id = u.id AND a.status = 'concluida'
    WHERE e.ativo = 1
    GROUP BY e.id
    ORDER BY media_geral DESC
";
$stmt_medias = $conn->query($query_medias);
$medias_empresas = $stmt_medias->fetchAll();

// ===========================================
// RANKING POR EMPRESA
// ===========================================
$query_ranking = "
    SELECT 
        u.nome as colaborador,
        e.nome as empresa,
        AVG(a.nota_final) as media,
        COUNT(a.id) as avaliacoes
    FROM usuarios u
    JOIN empresas e ON u.empresa_id = e.id
    JOIN avaliacoes a ON a.avaliado_id = u.id
    WHERE a.status = 'concluida' AND u.ativo = 1
    GROUP BY u.id
    HAVING avaliacoes >= 1
    ORDER BY media DESC
    LIMIT 20
";
$stmt_ranking = $conn->query($query_ranking);
$ranking_geral = $stmt_ranking->fetchAll();

require_once '../../includes/header.php';
?>

<!-- Filtros -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Empresa</label>
                        <select class="form-select" name="empresa">
                            <option value="todas">Todas as empresas</option>
                            <?php foreach ($empresas as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>" 
                                <?php echo $filtro_empresa == $emp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Período</label>
                        <select class="form-select" name="periodo">
                            <option value="3meses" <?php echo $filtro_periodo == '3meses' ? 'selected' : ''; ?>>Últimos 3 meses</option>
                            <option value="6meses" <?php echo $filtro_periodo == '6meses' ? 'selected' : ''; ?>>Últimos 6 meses</option>
                            <option value="12meses" <?php echo $filtro_periodo == '12meses' ? 'selected' : ''; ?>>Último ano</option>
                            <option value="todos" <?php echo $filtro_periodo == 'todos' ? 'selected' : ''; ?>>Todo período</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i> Aplicar Filtros
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Cards por Empresa -->
<div class="row mb-4">
    <?php foreach ($medias_empresas as $emp): ?>
    <div class="col-md-4 mb-3">
        <div class="card h-100 <?php echo $emp['tipo'] == 'matriz' ? 'border-primary' : ''; ?>">
            <div class="card-header <?php echo $emp['tipo'] == 'matriz' ? 'bg-primary text-white' : 'bg-secondary text-white'; ?>">
                <div class="d-flex justify-content-between">
                    <h5 class="mb-0"><?php echo htmlspecialchars($emp['nome']); ?></h5>
                    <span class="badge bg-light text-dark"><?php echo ucfirst($emp['tipo']); ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="row text-center mb-3">
                    <div class="col-6">
                        <h3><?php echo $emp['total_colaboradores']; ?></h3>
                        <small class="text-muted">Colaboradores</small>
                    </div>
                    <div class="col-6">
                        <h3><?php echo $emp['total_avaliacoes']; ?></h3>
                        <small class="text-muted">Avaliações</small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Média Geral</span>
                        <span class="fw-bold"><?php echo number_format($emp['media_geral'], 1); ?>%</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-<?php 
                            echo $emp['media_geral'] >= 80 ? 'success' : 
                                ($emp['media_geral'] >= 60 ? 'warning' : 'danger'); 
                        ?>" style="width: <?php echo $emp['media_geral']; ?>%"></div>
                    </div>
                </div>
                
                <div class="small">
                    <div class="d-flex justify-content-between mb-1">
                        <span>✅ Acima 80%</span>
                        <span class="text-success"><?php echo $emp['acima_80']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>⚠️ Entre 60-79%</span>
                        <span class="text-warning"><?php echo $emp['entre_60_79']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>❌ Abaixo 60%</span>
                        <span class="text-danger"><?php echo $emp['abaixo_60']; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Ranking Geral -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">🏆 Ranking Geral de Colaboradores</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Colaborador</th>
                        <th>Empresa</th>
                        <th>Avaliações</th>
                        <th>Média</th>
                        <th>Desempenho</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ranking_geral as $index => $r): ?>
                    <tr>
                        <td>
                            <?php if ($index == 0): ?>
                            <span class="badge bg-warning">🥇</span>
                            <?php elseif ($index == 1): ?>
                            <span class="badge bg-secondary">🥈</span>
                            <?php elseif ($index == 2): ?>
                            <span class="badge bg-danger">🥉</span>
                            <?php else: ?>
                            <?php echo $index + 1; ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($r['colaborador']); ?></td>
                        <td><?php echo htmlspecialchars($r['empresa']); ?></td>
                        <td><?php echo $r['avaliacoes']; ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $r['media'] >= 80 ? 'success' : 
                                    ($r['media'] >= 60 ? 'warning' : 'danger'); 
                            ?>">
                                <?php echo number_format($r['media'], 1); ?>%
                            </span>
                        </td>
                        <td style="width: 150px;">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-<?php 
                                    echo $r['media'] >= 80 ? 'success' : 
                                        ($r['media'] >= 60 ? 'warning' : 'danger'); 
                                ?>" style="width: <?php echo $r['media']; ?>%"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
