<?php
// modules/usuarios/vinculos.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start(); // Iniciar buffer no início

require_once '../../includes/header.php';

$conn = (new Database())->getConnection();

// Processar vínculo (CORRIGIDO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vincular'])) {
    $cargo_id = $_POST['cargo_id'];
    $departamento_id = $_POST['departamento_id'];
    
    if (empty($cargo_id) || empty($departamento_id)) {
        $_SESSION['error'] = "Selecione cargo e departamento";
    } else {
        // Verificar se já existe
        $check = $conn->prepare("SELECT id FROM cargo_departamento WHERE cargo_id = :cargo_id AND departamento_id = :departamento_id");
        $check->bindParam(':cargo_id', $cargo_id);
        $check->bindParam(':departamento_id', $departamento_id);
        $check->execute();
        
        if ($check->rowCount() == 0) {
            $query = "INSERT INTO cargo_departamento (cargo_id, departamento_id) VALUES (:cargo_id, :departamento_id)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':cargo_id', $cargo_id);
            $stmt->bindParam(':departamento_id', $departamento_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Vínculo criado com sucesso!";
            } else {
                $_SESSION['error'] = "Erro ao criar vínculo";
            }
        } else {
            $_SESSION['error'] = "Este vínculo já existe!";
        }
    }
    
    ob_end_clean();
    header('Location: vinculos.php');
    exit;
}

// Remover vínculo
if (isset($_GET['remover'])) {
    $id = $_GET['remover'];
    $query = "DELETE FROM cargo_departamento WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $_SESSION['success'] = "Vínculo removido!";
    
    ob_end_clean();
    header('Location: vinculos.php');
    exit;
}

// Buscar dados
$cargos = $conn->query("SELECT * FROM cargos WHERE ativo = 1 ORDER BY nome")->fetchAll();
$departamentos = $conn->query("SELECT * FROM departamentos ORDER BY nome")->fetchAll();

// Buscar vínculos atuais
$vinculos = $conn->query("
    SELECT cd.*, c.nome as cargo, c.nivel, d.nome as departamento 
    FROM cargo_departamento cd
    JOIN cargos c ON cd.cargo_id = c.id
    JOIN departamentos d ON cd.departamento_id = d.id
    ORDER BY d.nome, c.nome
")->fetchAll();

// Agrupar por departamento
$vinculos_agrupados = [];
foreach ($vinculos as $v) {
    $vinculos_agrupados[$v['departamento']][] = $v;
}
?>

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-link"></i> Novo Vínculo</h5>
            </div>
            <div class="card-body">
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
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Departamento</label>
                        <select name="departamento_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($departamentos as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cargo</label>
                        <select name="cargo_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($cargos as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= htmlspecialchars($c['nome']) ?> 
                                <?= $c['nivel'] ? '(' . $c['nivel'] . ')' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="vincular" class="btn btn-success">
                        <i class="bi bi-link"></i> Vincular
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Dica -->
        <div class="card mt-3 bg-light">
            <div class="card-body">
                <h6><i class="bi bi-info-circle"></i> Como funciona?</h6>
                <p class="small mb-0">
                    Ao vincular um cargo a um departamento, esse cargo ficará disponível 
                    apenas para usuários desse departamento no momento do cadastro/edição.
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Vínculos por Departamento</h5>
            </div>
            <div class="card-body">
                <?php if (empty($vinculos_agrupados)): ?>
                <p class="text-muted text-center py-4">
                    <i class="bi bi-link-45deg fs-1 d-block mb-3"></i>
                    Nenhum vínculo cadastrado.
                </p>
                <?php else: ?>
                <div class="accordion" id="accordionVinculos">
                    <?php foreach ($vinculos_agrupados as $depto => $lista): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#collapse<?= md5($depto) ?>">
                                <strong><?= htmlspecialchars($depto) ?></strong>
                                <span class="badge bg-primary ms-2"><?= count($lista) ?> cargos</span>
                            </button>
                        </h2>
                        <div id="collapse<?= md5($depto) ?>" class="accordion-collapse collapse" 
                             data-bs-parent="#accordionVinculos">
                            <div class="accordion-body">
                                <ul class="list-group">
                                    <?php foreach ($lista as $v): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($v['cargo']) ?></strong>
                                            <?php if ($v['nivel']): ?>
                                            <span class="badge bg-secondary ms-2"><?= $v['nivel'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="?remover=<?= $v['id'] ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Remover este vínculo?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
