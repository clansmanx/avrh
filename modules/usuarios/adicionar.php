<?php
// modules/usuarios/adicionar.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require_once '../../includes/header.php';

$conn = (new Database())->getConnection();
$auth->requirePermission(['admin', 'rh']);

// Buscar empresas ativas
$query_empresas = "SELECT * FROM empresas WHERE ativo = 1 ORDER BY tipo, nome";
$stmt_empresas = $conn->query($query_empresas);
$empresas = $stmt_empresas->fetchAll();

// Buscar gestores
$query_gestores = "SELECT id, nome FROM usuarios WHERE tipo IN ('admin', 'rh', 'gestor') AND ativo = 1 ORDER BY nome";
$stmt_gestores = $conn->query($query_gestores);
$gestores = $stmt_gestores->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $empresa_id = !empty($_POST['empresa_id']) ? $_POST['empresa_id'] : null;
        $departamento_id = !empty($_POST['departamento_id']) ? $_POST['departamento_id'] : null;
        $cargo_id = !empty($_POST['cargo_id']) ? $_POST['cargo_id'] : null;
        $gestor_id = !empty($_POST['gestor_id']) ? $_POST['gestor_id'] : null;
        $tipo = $_POST['tipo'] ?? 'colaborador';
        $telefone = $_POST['telefone'] ?? '';
        $data_contratacao = !empty($_POST['data_contratacao']) ? $_POST['data_contratacao'] : null;
        
        // Validar campos obrigatórios
        if (empty($nome)) $errors[] = "Nome é obrigatório";
        if (empty($email)) $errors[] = "Email é obrigatório";
        if (empty($senha)) $errors[] = "Senha é obrigatória";
        
        // Validar email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email inválido";
        }
        
        // Validar tamanho da senha
        if (strlen($senha) < 6) {
            $errors[] = "A senha deve ter no mínimo 6 caracteres";
        }
        
        // Verificar se email já existe
        $query = "SELECT id FROM usuarios WHERE email = :email";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $errors[] = "Este email já está cadastrado";
        }
        
        // Upload da foto
        $foto_perfil = null;
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $extensao = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
            $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($extensao, $extensoes_permitidas)) {
                if ($_FILES['foto_perfil']['size'] > 2 * 1024 * 1024) {
                    $errors[] = "Arquivo muito grande. Máximo 2MB";
                } else {
                    $upload_dir = '../../uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $nome_arquivo = uniqid() . '_' . date('YmdHis') . '.' . $extensao;
                    $caminho_destino = $upload_dir . $nome_arquivo;
                    
                    if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $caminho_destino)) {
                        $foto_perfil = $nome_arquivo;
                    } else {
                        $errors[] = "Erro ao fazer upload da foto";
                    }
                }
            } else {
                $errors[] = "Tipo de arquivo não permitido. Use JPG, PNG ou GIF";
            }
        }
        
        if (empty($errors)) {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO usuarios (nome, email, senha, empresa_id, departamento_id, cargo_id, gestor_id, tipo, telefone, data_contratacao, foto_perfil, ativo) 
                      VALUES (:nome, :email, :senha, :empresa_id, :departamento_id, :cargo_id, :gestor_id, :tipo, :telefone, :data_contratacao, :foto_perfil, 1)";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':senha', $senha_hash);
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->bindParam(':departamento_id', $departamento_id);
            $stmt->bindParam(':cargo_id', $cargo_id);
            $stmt->bindParam(':gestor_id', $gestor_id);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':telefone', $telefone);
            $stmt->bindParam(':data_contratacao', $data_contratacao);
            $stmt->bindParam(':foto_perfil', $foto_perfil);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Usuário cadastrado com sucesso!";
                ob_end_clean();
                header('Location: index.php');
                exit;
            } else {
                $errors[] = "Erro ao cadastrar usuário";
            }
        }
    } catch (Exception $e) {
        $errors[] = "Erro: " . $e->getMessage();
    }
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-person-plus"></i> Novo Usuário</h2>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data" id="formUsuario">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Dados principais -->
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="bi bi-person"></i> Informações Pessoais</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nome" class="form-label">Nome Completo *</label>
                                            <input type="text" class="form-control" id="nome" name="nome" 
                                                   value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="senha" class="form-label">Senha *</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="senha" name="senha" required>
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('senha', 'eyeIcon')">
                                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Mínimo 6 caracteres</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="telefone" class="form-label">Telefone</label>
                                            <input type="text" class="form-control" id="telefone" name="telefone" 
                                                   value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>"
                                                   placeholder="(11) 99999-9999">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Vínculo Empresarial -->
                            <div class="card mb-3">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="bi bi-building"></i> Vínculo Empresarial</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="empresa_id" class="form-label">Empresa *</label>
                                            <select class="form-select" name="empresa_id" id="empresa_id" required>
                                                <option value="">Selecione a empresa...</option>
                                                <?php foreach ($empresas as $emp): ?>
                                                <option value="<?php echo $emp['id']; ?>" 
                                                    <?php echo ($_POST['empresa_id'] ?? '') == $emp['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($emp['nome']); ?> (<?php echo ucfirst($emp['tipo']); ?>)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="departamento_id" class="form-label">Departamento</label>
                                            <select class="form-select" name="departamento_id" id="departamento_id" disabled>
                                                <option value="">Primeiro selecione a empresa</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="cargo_id" class="form-label">Cargo</label>
                                            <select class="form-select" name="cargo_id" id="cargo_id" disabled>
                                                <option value="">Primeiro selecione o departamento</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="gestor_id" class="form-label">Gestor</label>
                                            <select class="form-select" id="gestor_id" name="gestor_id">
                                                <option value="">Selecione...</option>
                                                <?php foreach ($gestores as $gestor): ?>
                                                <option value="<?php echo $gestor['id']; ?>"
                                                    <?php echo ($_POST['gestor_id'] ?? '') == $gestor['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($gestor['nome']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="data_contratacao" class="form-label">Data de Contratação</label>
                                            <input type="date" class="form-control" id="data_contratacao" name="data_contratacao" 
                                                   value="<?php echo $_POST['data_contratacao'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Permissões -->
                            <div class="card mb-3">
                                <div class="card-header bg-warning">
                                    <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Permissões</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="tipo" class="form-label">Tipo de Usuário *</label>
                                            <select class="form-select" id="tipo" name="tipo" required>
                                                <option value="colaborador" <?php echo ($_POST['tipo'] ?? '') == 'colaborador' ? 'selected' : ''; ?>>Colaborador</option>
                                                <option value="gestor" <?php echo ($_POST['tipo'] ?? '') == 'gestor' ? 'selected' : ''; ?>>Gestor</option>
                                                <option value="rh" <?php echo ($_POST['tipo'] ?? '') == 'rh' ? 'selected' : ''; ?>>RH</option>
                                                <option value="admin" <?php echo ($_POST['tipo'] ?? '') == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                            </select>
                                            <small class="text-muted">
                                                <strong>Colaborador:</strong> Apenas visualiza suas avaliações<br>
                                                <strong>Gestor:</strong> Visualiza avaliações da equipe<br>
                                                <strong>RH:</strong> Gerencia ciclos e relatórios<br>
                                                <strong>Administrador:</strong> Acesso total ao sistema
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Foto -->
                            <div class="card mb-3">
                                <div class="card-header bg-secondary text-white">
                                    <h5 class="mb-0"><i class="bi bi-camera"></i> Foto do Perfil</h5>
                                </div>
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <img id="previewFoto" src="<?php echo SITE_URL; ?>/assets/img/default-avatar.png" 
                                             class="rounded-circle img-fluid mb-3 border border-3" 
                                             style="width: 150px; height: 150px; object-fit: cover;">
                                    </div>
                                    <input type="file" class="form-control" id="foto_perfil" 
                                           name="foto_perfil" accept="image/jpeg,image/png,image/gif">
                                    <small class="text-muted">Formatos: JPG, PNG, GIF. Tamanho máx: 2MB</small>
                                </div>
                            </div>

                            <!-- Dicas -->
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-lightbulb"></i> Dicas</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="mb-0">
                                        <li class="mb-2">🔴 Campos com * são obrigatórios</li>
                                        <li class="mb-2">🔑 A senha deve ter no mínimo 6 caracteres</li>
                                        <li class="mb-2">📧 O email será usado para login</li>
                                        <li class="mb-2">🏢 Selecione a empresa primeiro</li>
                                        <li class="mb-2">👔 Gestores podem ser atribuídos posteriormente</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Salvar Usuário
                        </button>
                        <a href="index.php" class="btn btn-secondary btn-lg">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// Preview da imagem
document.getElementById('foto_perfil')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewFoto').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});

// ===========================================
// CARREGAMENTO HIERÁRQUICO: EMPRESA → DEPARTAMENTO → CARGO
// ===========================================
document.addEventListener('DOMContentLoaded', function() {
    const empresaSelect = document.getElementById('empresa_id');
    const deptoSelect = document.getElementById('departamento_id');
    const cargoSelect = document.getElementById('cargo_id');

    if (!empresaSelect) {
        console.error('Select de empresa não encontrado!');
        return;
    }

    // 1. Quando empresa muda, carrega departamentos
    empresaSelect.addEventListener('change', function() {
        const empresaId = this.value;
        
        deptoSelect.innerHTML = '<option value="">Carregando...</option>';
        deptoSelect.disabled = true;
        cargoSelect.innerHTML = '<option value="">Primeiro selecione o departamento</option>';
        cargoSelect.disabled = true;
        
        if (empresaId) {
            fetch('<?php echo SITE_URL; ?>/modules/empresas/get_departamentos_por_empresa.php?empresa_id=' + empresaId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na resposta da rede');
                    }
                    return response.json();
                })
                .then(data => {
                    deptoSelect.innerHTML = '<option value="">Selecione um departamento...</option>';
                    
                    if (data.length === 0) {
                        deptoSelect.innerHTML = '<option value="">Nenhum departamento disponível</option>';
                    } else {
                        data.forEach(depto => {
                            const option = document.createElement('option');
                            option.value = depto.id;
                            option.textContent = depto.nome;
                            deptoSelect.appendChild(option);
                        });
                    }
                    
                    deptoSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Erro ao carregar departamentos:', error);
                    deptoSelect.innerHTML = '<option value="">Erro ao carregar departamentos</option>';
                });
        } else {
            deptoSelect.innerHTML = '<option value="">Primeiro selecione a empresa</option>';
            deptoSelect.disabled = true;
        }
    });

    // 2. Quando departamento muda, carrega cargos
    deptoSelect.addEventListener('change', function() {
        const deptoId = this.value;
        
        cargoSelect.innerHTML = '<option value="">Carregando...</option>';
        cargoSelect.disabled = true;
        
        if (deptoId) {
            fetch('<?php echo SITE_URL; ?>/modules/usuarios/get_cargos_por_depto.php?depto_id=' + deptoId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na resposta da rede');
                    }
                    return response.json();
                })
                .then(data => {
                    cargoSelect.innerHTML = '<option value="">Selecione um cargo...</option>';
                    
                    if (data.length === 0) {
                        cargoSelect.innerHTML = '<option value="">Nenhum cargo disponível</option>';
                    } else {
                        data.forEach(cargo => {
                            const option = document.createElement('option');
                            option.value = cargo.id;
                            option.textContent = cargo.nome + (cargo.nivel ? ' (' + cargo.nivel + ')' : '');
                            cargoSelect.appendChild(option);
                        });
                    }
                    
                    cargoSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Erro ao carregar cargos:', error);
                    cargoSelect.innerHTML = '<option value="">Erro ao carregar cargos</option>';
                });
        } else {
            cargoSelect.innerHTML = '<option value="">Primeiro selecione o departamento</option>';
            cargoSelect.disabled = true;
        }
    });

    // 3. Se veio de POST (erro no formulário), manter os valores selecionados
    <?php if (!empty($_POST['empresa_id'])): ?>
    setTimeout(function() {
        empresaSelect.value = '<?php echo $_POST['empresa_id']; ?>';
        empresaSelect.dispatchEvent(new Event('change'));
    }, 500);
    <?php endif; ?>
});
</script>

<?php 
require_once '../../includes/footer.php';
ob_end_flush(); 
?>
