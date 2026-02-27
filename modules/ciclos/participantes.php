<?php
// modules/ciclos/participantes.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require_once '../../includes/header.php';
$auth->requirePermission(['admin', 'rh']);

$conn = (new Database())->getConnection();

$ciclo_id = $_GET['id'] ?? 0;

// Buscar dados do ciclo
$query = "SELECT * FROM ciclos_avaliacao WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $ciclo_id);
$stmt->execute();
$ciclo = $stmt->fetch();

if (!$ciclo) {
    $_SESSION['error'] = "Ciclo não encontrado";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Processar adição de participante
if (isset($_GET['add']) && $auth->hasPermission(['admin', 'rh'])) {
    $usuario_id = $_GET['add'];
    $tipo = $_GET['tipo'] ?? 'avaliado';
    
    // Verificar se já existe antes de inserir
    $check = "SELECT id FROM ciclo_participantes WHERE ciclo_id = :ciclo_id AND usuario_id = :usuario_id";
    $stmt_check = $conn->prepare($check);
    $stmt_check->bindParam(':ciclo_id', $ciclo_id);
    $stmt_check->bindParam(':usuario_id', $usuario_id);
    $stmt_check->execute();
    
    if (!$stmt_check->fetch()) {
        // Só insere se não existir
        $query = "INSERT INTO ciclo_participantes (ciclo_id, usuario_id, tipo_participacao) 
                  VALUES (:ciclo_id, :usuario_id, :tipo)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':ciclo_id', $ciclo_id);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->execute();
    }
    
    ob_end_clean();
    header('Location: participantes.php?id=' . $ciclo_id . '&empresa=' . ($_GET['empresa'] ?? '') . '&depto=' . ($_GET['depto'] ?? ''));
    exit;
}

// Processar remoção de participante
if (isset($_GET['remove']) && $auth->hasPermission(['admin', 'rh'])) {
    $participante_id = $_GET['remove'];
    
    $query = "DELETE FROM ciclo_participantes WHERE id = :id AND ciclo_id = :ciclo_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $participante_id);
    $stmt->bindParam(':ciclo_id', $ciclo_id);
    $stmt->execute();
    
    ob_end_clean();
    header('Location: participantes.php?id=' . $ciclo_id . '&empresa=' . ($_GET['empresa'] ?? '') . '&depto=' . ($_GET['depto'] ?? ''));
    exit;
}

// ===========================================
// FILTROS POR EMPRESA E DEPARTAMENTO
// ===========================================
$filtro_empresa = $_GET['empresa'] ?? '';
$filtro_departamento = $_GET['depto'] ?? '';

// Buscar empresas para o filtro
$empresas = $conn->query("SELECT * FROM empresas WHERE ativo = 1 ORDER BY nome")->fetchAll();

// Buscar departamentos (para o filtro)
$query_deptos = "SELECT * FROM departamentos";
if (!empty($filtro_empresa)) {
    // Se tem empresa selecionada, mostra deptos da empresa OU globais
    $query_deptos .= " WHERE empresa_id = :empresa_id OR empresa_id IS NULL";
}
$query_deptos .= " ORDER BY nome";

$stmt_deptos = $conn->prepare($query_deptos);
if (!empty($filtro_empresa)) {
    $stmt_deptos->bindParam(':empresa_id', $filtro_empresa);
}
$stmt_deptos->execute();
$departamentos = $stmt_deptos->fetchAll();

// Buscar participantes do ciclo (REMOVI a coluna funcao)
$query_participantes = "SELECT cp.*, u.nome, u.email, u.tipo as funcao, c.nome as cargo_nome, d.nome as departamento_nome,
                               e.nome as empresa_nome
                        FROM ciclo_participantes cp
                        JOIN usuarios u ON cp.usuario_id = u.id
                        LEFT JOIN cargos c ON u.cargo_id = c.id
                        LEFT JOIN departamentos d ON u.departamento_id = d.id
                        LEFT JOIN empresas e ON u.empresa_id = e.id
                        WHERE cp.ciclo_id = :ciclo_id
                        ORDER BY cp.tipo_participacao, u.nome";
$stmt_participantes = $conn->prepare($query_participantes);
$stmt_participantes->bindParam(':ciclo_id', $ciclo_id);
$stmt_participantes->execute();
$participantes = $stmt_participantes->fetchAll();

// Buscar usuários disponíveis (COM FILTROS E EXCLUINDO JÁ ADICIONADOS)
$query_disponiveis = "SELECT u.id, u.nome, u.email, u.tipo as funcao, d.nome as departamento_nome, e.nome as empresa_nome
                      FROM usuarios u
                      LEFT JOIN departamentos d ON u.departamento_id = d.id
                      LEFT JOIN empresas e ON u.empresa_id = e.id
                      WHERE u.ativo = 1";

// Subquery para excluir quem já está no ciclo
$query_disponiveis .= " AND u.id NOT IN (
    SELECT usuario_id FROM ciclo_participantes WHERE ciclo_id = :ciclo_id
)";

$params = [':ciclo_id' => $ciclo_id];

if (!empty($filtro_empresa)) {
    $query_disponiveis .= " AND u.empresa_id = :empresa";
    $params[':empresa'] = $filtro_empresa;
}

if (!empty($filtro_departamento)) {
    $query_disponiveis .= " AND u.departamento_id = :depto";
    $params[':depto'] = $filtro_departamento;
}

$query_disponiveis .= " ORDER BY u.nome";

$stmt_disponiveis = $conn->prepare($query_disponiveis);
foreach ($params as $key => $value) {
    $stmt_disponiveis->bindValue($key, $value);
}
$stmt_disponiveis->execute();
$usuarios_disponiveis = $stmt_disponiveis->fetchAll();

require_once '../../includes/header.php';
?>

<style>
/* Estilo para a badge pequena abaixo do nome */
.badge-pequena {
    display: inline-block;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    background-color: #e9ecef;
    color: #495057;
    margin-top: 2px;
    font-weight: normal;
}

/* Badge para a função */
.badge-funcao {
    display: inline-block;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: 600;
    margin-right: 4px;
    background-color: #6c757d;
    color: white;
}

/* Cores diferentes para cada função */
.badge-funcao.admin { background-color: #dc3545; }
.badge-funcao.rh { background-color: #fd7e14; }
.badge-funcao.gestor { background-color: #0d6efd; }
.badge-funcao.colaborador { background-color: #198754; }

/* Ajuste na célula do nome para melhor visualização */
.nome-com-badge {
    display: flex;
    flex-direction: column;
    line-height: 1.3;
}

.nome-principal {
    font-weight: 600;
    margin-bottom: 2px;
}

.linha-badges {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
}
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Participantes do Ciclo: <?php echo htmlspecialchars($ciclo['nome']); ?></h2>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- 🔥 FILTROS POR EMPRESA E DEPARTAMENTO -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="id" value="<?php echo $ciclo_id; ?>">
                    
                    <div class="col-md-4">
                        <label class="form-label">Empresa</label>
                        <select class="form-select" name="empresa" onchange="this.form.submit()">
                            <option value="">Todas as empresas</option>
                            <?php foreach ($empresas as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>" 
                                <?php echo $filtro_empresa == $emp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Departamento</label>
                        <select class="form-select" name="depto" onchange="this.form.submit()">
                            <option value="">Todos os departamentos</option>
                            <?php foreach ($departamentos as $depto): ?>
                            <option value="<?php echo $depto['id']; ?>" 
                                <?php echo $filtro_departamento == $depto['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($depto['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 d-flex align-items-end">
                        <a href="participantes.php?id=<?php echo $ciclo_id; ?>" class="btn btn-secondary w-100">
                            <i class="bi bi-eraser"></i> Limpar filtros
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <!-- Lista de Participantes Atuais -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Participantes Atuais</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($participantes)): ?>
                        <p class="text-muted text-center py-4">
                            <i class="bi bi-people fs-1 d-block mb-3"></i>
                            Nenhum participante adicionado ainda.
                        </p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Empresa</th>
                                        <th>Depto</th>
                                        <th>Tipo</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($participantes as $part): ?>
                                    <tr>
                                        <td>
                                            <!-- Nome com badge da função e empresa/depto -->
                                            <div class="nome-com-badge">
                                                <span class="nome-principal"><?php echo htmlspecialchars($part['nome']); ?></span>
                                                <div class="linha-badges">
                                                    <?php 
                                                    // Badge da função - usando if/else em vez de match
                                                    $funcao = isset($part['funcao']) ? $part['funcao'] : 'colaborador';
                                                    $classe_funcao = 'colaborador';
                                                    if ($funcao == 'admin') $classe_funcao = 'admin';
                                                    elseif ($funcao == 'rh') $classe_funcao = 'rh';
                                                    elseif ($funcao == 'gestor') $classe_funcao = 'gestor';
                                                    ?>
                                                    <span class="badge-funcao <?php echo $classe_funcao; ?>">
                                                        <?php echo ucfirst($funcao); ?>
                                                    </span>
                                                    
                                                    <!-- Badge com empresa/departamento -->
                                                    <span class="badge-pequena">
                                                        <?php 
                                                        $empresa = isset($part['empresa_nome']) && $part['empresa_nome'] ? $part['empresa_nome'] : 'Sem empresa';
                                                        $departamento = isset($part['departamento_nome']) && $part['departamento_nome'] ? $part['departamento_nome'] : 'Sem depto';
                                                        echo htmlspecialchars($empresa . ' / ' . $departamento);
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($part['empresa_nome'])): ?>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($part['empresa_nome']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($part['departamento_nome'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $part['tipo_participacao'] == 'avaliado' ? 'primary' : 'success'; ?>">
                                                <?php echo ucfirst($part['tipo_participacao']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?id=<?php echo $ciclo_id; ?>&remove=<?php echo $part['id']; ?>&empresa=<?php echo $filtro_empresa; ?>&depto=<?php echo $filtro_departamento; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Remover este participante?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
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

            <!-- Adicionar Participantes -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Adicionar Participantes</h5>
                    </div>
                    <div class="card-body">
                        <form class="mb-3">
                            <input type="hidden" name="id" value="<?php echo $ciclo_id; ?>">
                            <input type="hidden" name="empresa" value="<?php echo $filtro_empresa; ?>">
                            <input type="hidden" name="depto" value="<?php echo $filtro_departamento; ?>">
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchUser" 
                                       placeholder="Buscar usuário...">
                                <button class="btn btn-outline-secondary" type="button" onclick="filterUsers()">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>

                        <div class="list-group" id="userList" style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($usuarios_disponiveis)): ?>
                            <div class="alert alert-info">
                                Nenhum usuário encontrado com os filtros selecionados.
                            </div>
                            <?php else: ?>
                                <?php foreach ($usuarios_disponiveis as $user): ?>
                                <div class="list-group-item user-item" data-name="<?php echo strtolower($user['nome']); ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <!-- Nome com badge da função e empresa/depto -->
                                            <div class="nome-com-badge">
                                                <span class="nome-principal"><?php echo htmlspecialchars($user['nome']); ?></span>
                                                <div class="linha-badges">
                                                    <?php 
                                                    $funcao = isset($user['funcao']) ? $user['funcao'] : 'colaborador';
                                                    $classe_funcao = 'colaborador';
                                                    if ($funcao == 'admin') $classe_funcao = 'admin';
                                                    elseif ($funcao == 'rh') $classe_funcao = 'rh';
                                                    elseif ($funcao == 'gestor') $classe_funcao = 'gestor';
                                                    ?>
                                                    <span class="badge-funcao <?php echo $classe_funcao; ?>">
                                                        <?php echo ucfirst($funcao); ?>
                                                    </span>
                                                    
                                                    <span class="badge-pequena">
                                                        <?php 
                                                        $empresa = isset($user['empresa_nome']) && $user['empresa_nome'] ? $user['empresa_nome'] : 'Sem empresa';
                                                        $departamento = isset($user['departamento_nome']) && $user['departamento_nome'] ? $user['departamento_nome'] : 'Sem depto';
                                                        echo htmlspecialchars($empresa . ' / ' . $departamento);
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="btn-group">
                                            <a href="?id=<?php echo $ciclo_id; ?>&add=<?php echo $user['id']; ?>&tipo=avaliado&empresa=<?php echo $filtro_empresa; ?>&depto=<?php echo $filtro_departamento; ?>" 
                                               class="btn btn-sm btn-primary" title="Adicionar como Avaliado">
                                                <i class="bi bi-person-plus"></i>
                                            </a>
                                            <a href="?id=<?php echo $ciclo_id; ?>&add=<?php echo $user['id']; ?>&tipo=avaliador&empresa=<?php echo $filtro_empresa; ?>&depto=<?php echo $filtro_departamento; ?>" 
                                               class="btn btn-sm btn-success" title="Adicionar como Avaliador">
                                                <i class="bi bi-person-check"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                Mostrando <?php echo count($usuarios_disponiveis); ?> usuário(s)
                                <?php if ($filtro_empresa): ?>da empresa selecionada<?php endif; ?>
                                <?php if ($filtro_departamento): ?>e do departamento selecionado<?php endif; ?>.
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Informações do Ciclo -->
                <div class="card bg-light">
                    <div class="card-header">
                        <h5 class="mb-0">Informações do Ciclo</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Período:</strong><br>
                           <?php echo $functions->formatDate($ciclo['data_inicio']); ?> até<br>
                           <?php echo $functions->formatDate($ciclo['data_fim']); ?>
                        </p>
                        <p><strong>Tipo:</strong> <?php echo $ciclo['tipo']; ?>°</p>
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?php 
                                echo $ciclo['status'] == 'planejado' ? 'secondary' : 
                                    ($ciclo['status'] == 'em_andamento' ? 'success' : 
                                    ($ciclo['status'] == 'finalizado' ? 'info' : 'danger')); 
                            ?>">
                                <?php echo ucfirst($ciclo['status']); ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterUsers() {
    const searchTerm = document.getElementById('searchUser').value.toLowerCase();
    const users = document.querySelectorAll('.user-item');
    
    users.forEach(user => {
        const name = user.dataset.name;
        if (name.includes(searchTerm)) {
            user.style.display = 'block';
        } else {
            user.style.display = 'none';
        }
    });
}

document.getElementById('searchUser').addEventListener('keyup', filterUsers);
</script>

<?php 
require_once '../../includes/footer.php';
ob_end_flush(); 
?>
