<?php
// modules/relatorios/matriz_9box.php
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
// PARÂMETROS DE FILTRO
// ===========================================
$ciclo_id = $_GET['ciclo_id'] ?? 0;
$departamento_id = $_GET['departamento_id'] ?? 0;
$empresa_id = $_GET['empresa_id'] ?? 0;

// Buscar ciclos finalizados
$ciclos = $conn->query("SELECT id, nome FROM ciclos_avaliacao WHERE status = 'finalizado' ORDER BY data_fim DESC")->fetchAll();
$departamentos = $conn->query("SELECT * FROM departamentos ORDER BY nome")->fetchAll();
$empresas = $conn->query("SELECT * FROM empresas WHERE ativo = 1 ORDER BY nome")->fetchAll();

// ===========================================
// LÓGICA DE CÁLCULO DA 9-BOX
// ===========================================
// EIXO X: Desempenho (0-100%) baseado nas avaliações de rotina + RH
// EIXO Y: Potencial (calculado com base em critérios objetivos)

function calcularPotencial($conn, $usuario_id, $avaliacoes) {
    // Critérios para calcular potencial:
    // - Média das últimas 3 avaliações (tendência de crescimento)
    // - Quantidade de feedbacks positivos
    // - Tempo de empresa vs. progressão
    // - Participação em treinamentos
    
    $potencial = 50; // Base médio
    
    // Buscar histórico de avaliações
    $query = "SELECT nota_final, data_conclusao FROM avaliacoes 
              WHERE avaliado_id = ? AND status = 'concluida' 
              ORDER BY data_conclusao DESC LIMIT 3";
    $stmt = $conn->prepare($query);
    $stmt->execute([$usuario_id]);
    $historico = $stmt->fetchAll();
    
    if (count($historico) >= 2) {
        // Verificar tendência de crescimento
        $primeira = end($historico)['nota_final'];
        $ultima = $historico[0]['nota_final'];
        
        if ($ultima > $primeira + 10) {
            $potencial += 25; // Crescimento significativo
        } elseif ($ultima > $primeira) {
            $potencial += 10; // Crescimento moderado
        }
    }
    
    // Buscar feedbacks e reconhecimentos
    $query_feedbacks = "SELECT COUNT(*) FROM pdi_checklist_conclusoes cc
                       JOIN pdi_checklists pc ON cc.checklist_id = pc.id
                       JOIN pdi_metas m ON pc.item_id = m.id
                       JOIN pdi p ON m.pdi_id = p.id
                       WHERE p.colaborador_id = ?";
    $stmt = $conn->prepare($query_feedbacks);
    $stmt->execute([$usuario_id]);
    $feedbacks = $stmt->fetchColumn();
    
    if ($feedbacks > 5) {
        $potencial += 15; // Proativo em desenvolvimento
    }
    
    return min(100, max(0, $potencial));
}

// Buscar dados para a matriz
$where = "WHERE a.status = 'concluida'";
$params = [];

if ($ciclo_id) {
    $where .= " AND a.ciclo_id = ?";
    $params[] = $ciclo_id;
}

if ($departamento_id) {
    $where .= " AND u.departamento_id = ?";
    $params[] = $departamento_id;
}

if ($empresa_id) {
    $where .= " AND u.empresa_id = ?";
    $params[] = $empresa_id;
}

$query = "SELECT 
            u.id,
            u.nome,
            u.foto_perfil,
            d.nome as departamento,
            e.nome as empresa,
            AVG(a.nota_final) as media_desempenho,
            COUNT(a.id) as total_avaliacoes
          FROM usuarios u
          JOIN avaliacoes a ON u.id = a.avaliado_id
          LEFT JOIN departamentos d ON u.departamento_id = d.id
          LEFT JOIN empresas e ON u.empresa_id = e.id
          $where
          GROUP BY u.id
          HAVING total_avaliacoes >= 1
          ORDER BY u.nome";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$colaboradores = $stmt->fetchAll();

// Processar dados para a matriz
$dados_matriz = [];
foreach ($colaboradores as $c) {
    $desempenho = round($c['media_desempenho']);
    $potencial = calcularPotencial($conn, $c['id'], []);
    
    // Determinar quadrantes (0-100 dividido em 3 partes)
    $quadrante_desempenho = $desempenho < 60 ? 0 : ($desempenho < 80 ? 1 : 2);
    $quadrante_potencial = $potencial < 60 ? 0 : ($potencial < 80 ? 1 : 2);
    
    $dados_matriz[] = [
        'id' => $c['id'],
        'nome' => $c['nome'],
        'foto' => $c['foto_perfil'],
        'departamento' => $c['departamento'],
        'empresa' => $c['empresa'],
        'desempenho' => $desempenho,
        'potencial' => $potencial,
        'qx' => $quadrante_desempenho,
        'qy' => $quadrante_potencial,
        'quadrante' => ($quadrante_potencial * 3) + $quadrante_desempenho + 1
    ];
}

// Organizar por quadrante
$quadrantes = array_fill(1, 9, []);
foreach ($dados_matriz as $d) {
    $quadrantes[$d['quadrante']][] = $d;
}

// Definições dos quadrantes
$descricao_quadrantes = [
    1 => ['nome' => 'Baixo Potencial / Baixo Desempenho', 'cor' => '#dc3545', 'plano' => 'Avaliar desligamento ou realocação'],
    2 => ['nome' => 'Baixo Potencial / Médio Desempenho', 'cor' => '#fd7e14', 'plano' => 'Plano de desenvolvimento focado'],
    3 => ['nome' => 'Baixo Potencial / Alto Desempenho', 'cor' => '#ffc107', 'plano' => 'Especialista técnico, manter engajado'],
    4 => ['nome' => 'Médio Potencial / Baixo Desempenho', 'cor' => '#fd7e14', 'plano' => 'Identificar causas do baixo desempenho'],
    5 => ['nome' => 'Médio Potencial / Médio Desempenho', 'cor' => '#17a2b8', 'plano' => 'Core team, desenvolver gradualmente'],
    6 => ['nome' => 'Médio Potencial / Alto Desempenho', 'cor' => '#28a745', 'plano' => 'Preparar para promoção'],
    7 => ['nome' => 'Alto Potencial / Baixo Desempenho', 'cor' => '#fd7e14', 'plano' => 'Mentoria intensiva, avaliar fit cultural'],
    8 => ['nome' => 'Alto Potencial / Médio Desempenho', 'cor' => '#28a745', 'plano' => 'Plano de aceleração de carreira'],
    9 => ['nome' => 'Alto Potencial / Alto Desempenho', 'cor' => '#006400', 'plano' => 'Sucessão imediata, high potential']
];

require_once '../../includes/header.php';
?>

<style>
/* ======================================== */
/* MATRIZ 9-BOX STYLES                      */
/* ======================================== */
.matrix-container {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    border: 1px solid #e9ecef;
}

.matrix-title {
    font-size: 1.8rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 20px;
}

.matrix-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    grid-template-rows: repeat(3, 1fr);
    gap: 15px;
    min-height: 600px;
    background: #f8f9fc;
    padding: 20px;
    border-radius: 20px;
    border: 2px solid #e9ecef;
    margin-bottom: 30px;
}

.quadrante {
    background: white;
    border-radius: 16px;
    padding: 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    border: 2px solid;
    transition: all 0.2s;
    min-height: 180px;
    display: flex;
    flex-direction: column;
}

.quadrante:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

.quadrante-header {
    font-size: 0.85rem;
    font-weight: 600;
    color: white;
    padding: 8px 12px;
    border-radius: 30px;
    margin-bottom: 12px;
    text-align: center;
    background-color: inherit;
}

.quadrante-count {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1;
    margin-bottom: 5px;
}

.quadrante-list {
    max-height: 300px;
    overflow-y: auto;
    margin-top: 10px;
}

.colaborador-item {
    display: flex;
    align-items: center;
    padding: 8px;
    border-radius: 8px;
    transition: all 0.2s;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}

.colaborador-item:hover {
    background-color: #f8f9fa;
}

.colaborador-foto {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    margin-right: 10px;
    object-fit: cover;
    background-color: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
}

.colaborador-info {
    flex: 1;
}

.colaborador-nome {
    font-weight: 600;
    font-size: 0.9rem;
    color: #2c3e50;
}

.colaborador-metricas {
    display: flex;
    gap: 10px;
    margin-top: 2px;
    font-size: 0.75rem;
}

.metrica {
    background: #f8f9fc;
    padding: 2px 8px;
    border-radius: 20px;
}

/* Eixos */
.eixo-label {
    position: absolute;
    font-weight: 600;
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.eixo-y {
    left: -40px;
    top: 50%;
    transform: rotate(-90deg) translateX(-50%);
    transform-origin: left;
}

.eixo-x {
    bottom: -30px;
    right: 50%;
    transform: translateX(50%);
}

/* Planos de ação */
.planos-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    border: 1px solid #e9ecef;
    margin-top: 30px;
}

.planos-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-top: 20px;
}

.plano-item {
    padding: 15px;
    border-radius: 12px;
    color: white;
    font-size: 0.9rem;
}

.plano-item strong {
    display: block;
    font-size: 1rem;
    margin-bottom: 5px;
}
</style>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i> Matriz 9-Box Estratégica</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" onclick="exportarMatriz()">
                    <i class="bi bi-download"></i> Exportar
                </button>
            </div>
        </div>
        <p class="text-muted">Análise de Desempenho vs. Potencial para tomada de decisões estratégicas</p>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Ciclo de Avaliação</label>
                <select class="form-select" name="ciclo_id">
                    <option value="">Último ciclo</option>
                    <?php foreach ($ciclos as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $ciclo_id == $c['id'] ? 'selected' : ''; ?>>
                        <?php echo $c['nome']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Empresa</label>
                <select class="form-select" name="empresa_id">
                    <option value="">Todas</option>
                    <?php foreach ($empresas as $e): ?>
                    <option value="<?php echo $e['id']; ?>" <?php echo $empresa_id == $e['id'] ? 'selected' : ''; ?>>
                        <?php echo $e['nome']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Departamento</label>
                <select class="form-select" name="departamento_id">
                    <option value="">Todos</option>
                    <?php foreach ($departamentos as $d): ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo $departamento_id == $d['id'] ? 'selected' : ''; ?>>
                        <?php echo $d['nome']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Gerar Matriz
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Matriz 9-Box -->
<div class="matrix-container">
    <div class="position-relative" style="padding: 20px 40px;">
        <div class="eixo-label eixo-y">POTENCIAL ↑</div>
        <div class="eixo-label eixo-x">DESEMPENHO →</div>
        
        <div class="matrix-grid">
            <?php for ($y = 2; $y >= 0; $y--): ?>
                <?php for ($x = 0; $x <= 2; $x++): 
                    $quadrante_num = ($y * 3) + $x + 1;
                    $q = $descricao_quadrantes[$quadrante_num];
                ?>
                <div class="quadrante" style="border-color: <?php echo $q['cor']; ?>">
                    <div class="quadrante-header" style="background-color: <?php echo $q['cor']; ?>">
                        Q<?php echo $quadrante_num; ?>
                    </div>
                    <div class="quadrante-count"><?php echo count($quadrantes[$quadrante_num]); ?></div>
                    <div class="quadrante-list">
                        <?php foreach ($quadrantes[$quadrante_num] as $col): ?>
                        <div class="colaborador-item" onclick="verDetalhes(<?php echo $col['id']; ?>)">
                            <div class="colaborador-foto">
                                <?php if ($col['foto']): ?>
                                <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $col['foto']; ?>" 
                                     style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                <i class="bi bi-person"></i>
                                <?php endif; ?>
                            </div>
                            <div class="colaborador-info">
                                <div class="colaborador-nome"><?php echo $col['nome']; ?></div>
                                <div class="colaborador-metricas">
                                    <span class="metrica">D: <?php echo $col['desempenho']; ?>%</span>
                                    <span class="metrica">P: <?php echo $col['potencial']; ?>%</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endfor; ?>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Planos de Ação por Quadrante -->
<div class="planos-card">
    <h5 class="mb-3"><i class="bi bi-clipboard-check me-2 text-success"></i> Planos de Ação Recomendados</h5>
    <div class="planos-grid">
        <?php for ($i = 1; $i <= 9; $i++): 
            $q = $descricao_quadrantes[$i];
        ?>
        <div class="plano-item" style="background-color: <?php echo $q['cor']; ?>">
            <strong>Q<?php echo $i; ?>: <?php echo $q['nome']; ?></strong>
            <p style="margin-top: 8px; opacity: 0.9; font-size: 0.85rem;"><?php echo $q['plano']; ?></p>
        </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Modal de Detalhes -->
<div class="modal fade" id="modalColaborador" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent">
                Carregando...
            </div>
        </div>
    </div>
</div>

<script>
function verDetalhes(id) {
    fetch('get_colaborador_9box.php?id=' + id)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalColaborador')).show();
        });
}

function exportarMatriz() {
    window.location.href = 'exportar_matriz.php?' + window.location.search.slice(1);
}
</script>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
