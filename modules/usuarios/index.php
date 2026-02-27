<?php
// modules/usuarios/index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require_once '../../includes/header.php';

$conn = (new Database())->getConnection();
$user_tipo = $auth->getUserType();
$user_id = $auth->getUserId();

// ===========================================
// FILTROS
// ===========================================
$filtro_nome = $_GET['filtro'] ?? '';
$filtro_departamento = $_GET['departamento'] ?? '';
$filtro_empresa = $_GET['empresa'] ?? '';

// Buscar empresas para o filtro
$query_empresas = "SELECT id, nome FROM empresas WHERE ativo = 1 ORDER BY nome";
$stmt_empresas = $conn->query($query_empresas);
$empresas = $stmt_empresas->fetchAll();

// Buscar departamentos para filtro
$query_deptos = "SELECT * FROM departamentos ORDER BY nome";
$stmt_deptos = $conn->query($query_deptos);
$departamentos = $stmt_deptos->fetchAll();

// Processar inativação (apenas admin)
if (isset($_GET['inativar']) && $auth->hasPermission('admin')) {
    $id = $_GET['inativar'];
    
    if ($id != $auth->getUserId()) {
        $query = "UPDATE usuarios SET ativo = 0, data_atualizacao = NOW() WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $_SESSION['success'] = "Usuário inativado com sucesso!";
    } else {
        $_SESSION['error'] = "Você não pode inativar seu próprio usuário!";
    }
    
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Processar reativação (apenas admin)
if (isset($_GET['reativar']) && $auth->hasPermission('admin')) {
    $id = $_GET['reativar'];
    
    $query = "UPDATE usuarios SET ativo = 1, data_atualizacao = NOW() WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $_SESSION['success'] = "Usuário reativado com sucesso!";
    
    ob_end_clean();
    header('Location: index.php?tab=inativos');
    exit;
}

// Processar exclusão permanente (apenas admin)
if (isset($_GET['excluir_permanente']) && $auth->hasPermission('admin')) {
    $id = $_GET['excluir_permanente'];
    
    if ($id != $auth->getUserId()) {
        $query = "SELECT COUNT(*) as total FROM avaliacoes WHERE avaliado_id = :id OR avaliador_id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $total = $stmt->fetch()['total'];
        
        if ($total > 0) {
            $_SESSION['error'] = "Não é possível excluir usuário com avaliações associadas. Inative-o primeiro.";
        } else {
            $query = "DELETE FROM usuarios WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $_SESSION['success'] = "Usuário excluído permanentemente!";
        }
    }
    
    ob_end_clean();
    header('Location: index.php?tab=inativos');
    exit;
}

// Determinar qual aba está ativa
$tab = $_GET['tab'] ?? 'ativos';

// ===========================================
// CONSTRUIR QUERY COM FILTROS E PERMISSÕES
// ===========================================
function buildUserQuery($ativo, $filtro_nome, $filtro_departamento, $filtro_empresa, $user_tipo, $user_id) {
    $where = "WHERE u.ativo = $ativo";
    
    // 🔥 GESTOR: só vê sua própria equipe (quem tem ele como gestor)
    if ($user_tipo == 'gestor') {
        $where .= " AND u.gestor_id = $user_id";
    }
    
    // Filtro por nome/email
    if (!empty($filtro_nome)) {
        $where .= " AND (u.nome LIKE '%$filtro_nome%' OR u.email LIKE '%$filtro_nome%')";
    }
    
    // Filtro por departamento
    if (!empty($filtro_departamento)) {
        $where .= " AND u.departamento_id = $filtro_departamento";
    }
    
    // Filtro por empresa
    if (!empty($filtro_empresa)) {
        $where .= " AND u.empresa_id = $filtro_empresa";
    }
    
    return $where;
}

$where_ativos = buildUserQuery(1, $filtro_nome, $filtro_departamento, $filtro_empresa, $user_tipo, $user_id);
$where_inativos = buildUserQuery(0, $filtro_nome, $filtro_departamento, $filtro_empresa, $user_tipo, $user_id);

// Buscar usuários ATIVOS
$query_ativos = "SELECT u.*, c.nome as cargo_nome, d.nome as departamento_nome,
                        e.nome as empresa_nome, e.tipo as empresa_tipo,
                        g.nome as gestor_nome
                 FROM usuarios u
                 LEFT JOIN cargos c ON u.cargo_id = c.id
                 LEFT JOIN departamentos d ON u.departamento_id = d.id
                 LEFT JOIN empresas e ON u.empresa_id = e.id
                 LEFT JOIN usuarios g ON u.gestor_id = g.id
                 $where_ativos
                 ORDER BY u.nome ASC";

$stmt_ativos = $conn->prepare($query_ativos);
$stmt_ativos->execute();
$usuarios_ativos = $stmt_ativos->fetchAll();

// Buscar usuários INATIVOS
$query_inativos = "SELECT u.*, c.nome as cargo_nome, d.nome as departamento_nome,
                          e.nome as empresa_nome, e.tipo as empresa_tipo,
                          g.nome as gestor_nome
                   FROM usuarios u
                   LEFT JOIN cargos c ON u.cargo_id = c.id
                   LEFT JOIN departamentos d ON u.departamento_id = d.id
                   LEFT JOIN empresas e ON u.empresa_id = e.id
                   LEFT JOIN usuarios g ON u.gestor_id = g.id
                   $where_inativos
                   ORDER BY u.nome ASC";

$stmt_inativos = $conn->prepare($query_inativos);
$stmt_inativos->execute();
$usuarios_inativos = $stmt_inativos->fetchAll();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Usuários</h2>
            <?php if ($auth->hasPermission(['admin', 'rh'])): ?>
            <a href="adicionar.php" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Novo Usuário
            </a>
            <?php endif; ?>
        </div>

        <!-- Informação para gestor -->
        <?php if ($user_tipo == 'gestor'): ?>
        <div class="alert alert-info mb-3">
            <i class="bi bi-info-circle"></i> Você está vendo apenas os colaboradores da sua equipe.
        </div>
        <?php endif; ?>

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

        <!-- FILTROS AVANÇADOS -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="filtro" 
                               placeholder="Buscar por nome ou email..." 
                               value="<?php echo htmlspecialchars($filtro_nome); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="empresa">
                            <option value="">Todas as empresas</option>
                            <?php foreach ($empresas as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>" 
                                <?php echo $filtro_empresa == $emp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="departamento">
                            <option value="">Todos os departamentos</option>
                            <?php foreach ($departamentos as $depto): ?>
                            <option value="<?php echo $depto['id']; ?>" 
                                <?php echo $filtro_departamento == $depto['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($depto['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Abas -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $tab == 'ativos' ? 'active' : ''; ?>" 
                   href="?tab=ativos<?php echo !empty($filtro_nome) ? '&filtro=' . urlencode($filtro_nome) : ''; ?><?php echo !empty($filtro_empresa) ? '&empresa=' . $filtro_empresa : ''; ?><?php echo !empty($filtro_departamento) ? '&departamento=' . $filtro_departamento : ''; ?>">
                    <i class="bi bi-person-check"></i> Usuários Ativos 
                    <span class="badge bg-success"><?php echo count($usuarios_ativos); ?></span>
                </a>
            </li>
            <?php if ($auth->hasPermission(['admin', 'rh'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab == 'inativos' ? 'active' : ''; ?>" 
                   href="?tab=inativos<?php echo !empty($filtro_nome) ? '&filtro=' . urlencode($filtro_nome) : ''; ?><?php echo !empty($filtro_empresa) ? '&empresa=' . $filtro_empresa : ''; ?><?php echo !empty($filtro_departamento) ? '&departamento=' . $filtro_departamento : ''; ?>">
                    <i class="bi bi-person-x"></i> Usuários Inativos 
                    <span class="badge bg-secondary"><?php echo count($usuarios_inativos); ?></span>
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Aba de ATIVOS -->
        <?php if ($tab == 'ativos'): ?>
        <div class="card">
            <div class="card-body">
                <?php if (empty($usuarios_ativos)): ?>
                <p class="text-muted text-center py-4">
                    <i class="bi bi-people fs-1 d-block mb-3"></i>
                    Nenhum usuário ativo encontrado.
                </p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Contato</th>
                                <th>Empresa</th>
                                <th>Cargo/Departamento</th>
                                <th>Gestor</th>
                                <th>Tipo</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios_ativos as $usuario): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($usuario['foto_perfil']): ?>
                                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $usuario['foto_perfil']; ?>" 
                                             class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">
                                        <?php else: ?>
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" 
                                             style="width: 40px; height: 40px;">
                                            <i class="bi bi-person-fill text-secondary"></i>
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                                            <?php if ($usuario['id'] == $auth->getUserId()): ?>
                                            <span class="badge bg-info">Você</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($usuario['email']); ?><br>
                                    <?php if (!empty($usuario['telefone'])): ?>
                                    <small><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($usuario['telefone']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($usuario['empresa_nome']): ?>
                                    <span class="badge bg-<?php echo $usuario['empresa_tipo'] == 'matriz' ? 'primary' : 'secondary'; ?>">
                                        <i class="bi bi-building"></i> <?php echo htmlspecialchars($usuario['empresa_nome']); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-light text-muted">Não vinculado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($usuario['cargo_nome'] ?? 'Sem cargo'); ?></span><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($usuario['departamento_nome'] ?? 'Sem departamento'); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($usuario['gestor_nome'] ?? '-'); ?></td>
                                <td>
                                    <?php
                                    $badge_class = [
                                        'admin' => 'bg-danger',
                                        'rh' => 'bg-warning',
                                        'gestor' => 'bg-info',
                                        'colaborador' => 'bg-secondary'
                                    ][$usuario['tipo']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($usuario['tipo']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="visualizar.php?id=<?php echo $usuario['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Visualizar">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($auth->hasPermission(['admin', 'rh']) || $usuario['id'] == $auth->getUserId()): ?>
                                        <a href="editar.php?id=<?php echo $usuario['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($auth->hasPermission('admin') && $usuario['id'] != $auth->getUserId()): ?>
                                        <a href="?inativar=<?php echo $usuario['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                                           class="btn btn-sm btn-danger" title="Inativar"
                                           onclick="return confirm('Tem certeza que deseja INATIVAR este usuário? Ele poderá ser reativado depois.')">
                                            <i class="bi bi-person-x"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Resumo -->
                <div class="mt-3 text-muted">
                    <small>Mostrando <?php echo count($usuarios_ativos); ?> usuário(s) ativos</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Aba de INATIVOS (apenas admin/rh) -->
        <?php if ($tab == 'inativos' && $auth->hasPermission(['admin', 'rh'])): ?>
        <div class="card">
            <div class="card-body">
                <?php if (empty($usuarios_inativos)): ?>
                <p class="text-muted text-center py-4">
                    <i class="bi bi-people fs-1 d-block mb-3"></i>
                    Nenhum usuário inativo encontrado.
                </p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Email</th>
                                <th>Empresa</th>
                                <th>Última Atualização</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios_inativos as $usuario): ?>
                            <tr class="text-muted">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person-x me-2"></i>
                                        <?php echo htmlspecialchars($usuario['nome']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <?php if ($usuario['empresa_nome']): ?>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($usuario['empresa_nome']); ?>
                                    </span>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $functions->formatDate($usuario['data_atualizacao'], 'd/m/Y H:i'); ?></td>
                                <td>
                                    <?php if ($auth->hasPermission('admin')): ?>
                                    <a href="?reativar=<?php echo $usuario['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                                       class="btn btn-sm btn-success" title="Reativar"
                                       onclick="return confirm('Reativar este usuário?')">
                                        <i class="bi bi-person-check"></i> Reativar
                                    </a>
                                    <a href="?excluir_permanente=<?php echo $usuario['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                                       class="btn btn-sm btn-danger" title="Excluir Permanentemente"
                                       onclick="return confirm('EXCLUIR PERMANENTEMENTE? Esta ação não pode ser desfeita.')">
                                        <i class="bi bi-trash"></i> Excluir
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
        <?php endif; ?>
    </div>
</div>

<?php 
require_once '../../includes/footer.php';
ob_end_flush(); 
?>
