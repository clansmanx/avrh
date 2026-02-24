<?php
// modules/relatorios/dashboard_executivo.php
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

// ===========================================
// FILTRO POR EMPRESA
// ===========================================
$filtro_empresa = $_GET['empresa'] ?? 'todas';

$empresas = $conn->query("SELECT * FROM empresas WHERE ativo = 1 ORDER BY tipo, nome")->fetchAll();

// ===========================================
// MÉTRICAS GLOBAIS (COM FILTRO)
// ===========================================
$where_empresa = "";
$params = [];

if ($filtro_empresa != 'todas') {
    $where_empresa = " AND u.empresa_id = :empresa_id";
    $params[':empresa_id'] = $filtro_empresa;
}

// Total de colaboradores
$query = "SELECT COUNT(*) as total FROM usuarios u WHERE u.ativo = 1 AND u.tipo = 'colaborador' $where_empresa";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total_colaboradores = $stmt->fetch()['total'];

// Média geral
$query = "SELECT AVG(a.nota_final) as media 
          FROM avaliacoes a
          JOIN usuarios u ON a.avaliado_id = u.id
          WHERE a.status = 'concluida' $where_empresa";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$media_geral = $stmt->fetch()['media'] ?? 0;

// Total de avaliações
$query = "SELECT COUNT(*) as total 
          FROM avaliacoes a
          JOIN usuarios u ON a.avaliado_id = u.id
          WHERE a.status = 'concluida' $where_empresa";
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total_avaliacoes = $stmt->fetch()['total'];

// ===========================================
// COMPARATIVO MATRIZ x FILIAIS
// ===========================================
$comparativo = $conn->query("
    SELECT 
        e.id,
        e.nome,
        e.tipo,
        COUNT(DISTINCT u.id) as colaboradores,
        AVG(a.nota_final) as media,
        COUNT(a.id) as avaliacoes
    FROM empresas e
    LEFT JOIN usuarios u ON u.empresa_id = e.id AND u.ativo = 1
    LEFT JOIN avaliacoes a ON a.avaliado_id = u.id AND a.status = 'concluida'
    WHERE e.ativo = 1
    GROUP BY e.id
    ORDER BY e.tipo, e.nome
")->fetchAll();

require_once '../../includes/header.php';
?>

<!-- Filtro por Empresa -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Visualizar dados da empresa:</label>
                        <select class="form-select" name="empresa" onchange="this.form.submit()">
                            <option value="todas">Todas as empresas</option>
                            <?php foreach ($empresas as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>" 
                                <?php echo $filtro_empresa == $emp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['nome']); ?> (<?php echo ucfirst($emp['tipo']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Cards de KPI -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6 class="card-title">Colaboradores</h6>
                <h2><?php echo $total_colaboradores; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6 class="card-title">Média Geral</h6>
                <h2><?php echo number_format($media_geral, 1); ?>%</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h6 class="card-title">Avaliações</h6>
                <h2><?php echo $total_avaliacoes; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Comparativo Matriz x Filiais -->
<div class="card mb-4">
    <div class="card-header">
        <h5>Comparativo Matriz vs Filiais</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Tipo</th>
                        <th>Colaboradores</th>
                        <th>Avaliações</th>
                        <th>Média</th>
                        <th>Desempenho</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comparativo as $emp): ?>
                    <tr class="<?php echo $emp['tipo'] == 'matriz' ? 'table-primary' : ''; ?>">
                        <td><strong><?php echo htmlspecialchars($emp['nome']); ?></strong></td>
                        <td>
                            <span class="badge bg-<?php echo $emp['tipo'] == 'matriz' ? 'primary' : 'secondary'; ?>">
                                <?php echo ucfirst($emp['tipo']); ?>
                            </span>
                        </td>
                        <td><?php echo $emp['colaboradores']; ?></td>
                        <td><?php echo $emp['avaliacoes']; ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $emp['media'] >= 80 ? 'success' : 
                                    ($emp['media'] >= 60 ? 'warning' : 'danger'); 
                            ?>">
                                <?php echo number_format($emp['media'], 1); ?>%
                            </span>
                        </td>
                        <td style="width: 200px;">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-<?php 
                                    echo $emp['media'] >= 80 ? 'success' : 
                                        ($emp['media'] >= 60 ? 'warning' : 'danger'); 
                                ?>" style="width: <?php echo $emp['media']; ?>%"></div>
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
