<?php
// modules/usuarios/editar.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require_once '../../includes/header.php';

$conn = (new Database())->getConnection();

$id = $_GET['id'] ?? 0;

// Buscar dados do usuário
$query = "SELECT * FROM usuarios WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$usuario = $stmt->fetch();

if (!$usuario) {
    $_SESSION['error'] = "Usuário não encontrado";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Verificar permissão
if ($usuario['id'] != $auth->getUserId() && !$auth->hasPermission(['admin', 'rh'])) {
    $_SESSION['error'] = "Você não tem permissão para editar este usuário";
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Buscar empresas ativas
$query_empresas = "SELECT * FROM empresas WHERE ativo = 1 ORDER BY tipo, nome";
$stmt_empresas = $conn->query($query_empresas);
$empresas = $stmt_empresas->fetchAll();

// Buscar gestores (exceto o próprio usuário)
$query_gestores = "SELECT id, nome FROM usuarios WHERE tipo IN ('admin', 'rh', 'gestor') AND ativo = 1 AND id != :id ORDER BY nome";
$stmt_gestores = $conn->prepare($query_gestores);
$stmt_gestores->bindParam(':id', $id);
$stmt_gestores->execute();
$gestores = $stmt_gestores->fetchAll();

$erros = [];
$dados = $usuario; // Inicializa com os dados do banco

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dados = $_POST;
        
        // Validar formulário
        $regras = [
            'nome' => [
                'required' => true,
                'tipo' => 'nome',
                'max_length' => 100
            ],
            'email' => [
                'required' => true,
                'max_length' => 100
            ],
            'telefone' => [
                'telefone' => true
            ]
        ];
        
        $erros = $functions->validarFormulario($_POST, $regras);
        
        // Validar email com filtro específico
        if (!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $erros['email'][] = "Email inválido";
        }
        
        // Verificar se email já existe (exceto o atual)
        $query = "SELECT id FROM usuarios WHERE email = :email AND id != :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $erros['email'][] = "Este email já está cadastrado para outro usuário";
        }
        
        // Validar senha se foi fornecida
        if (!empty($_POST['senha']) && strlen($_POST['senha']) < 6) {
            $erros['senha'][] = "A senha deve ter no mínimo 6 caracteres";
        }
        
        // Upload da foto
        $foto_perfil = $usuario['foto_perfil'];
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $extensao = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
            $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($extensao, $extensoes_permitidas)) {
                if ($_FILES['foto_perfil']['size'] > 2 * 1024 * 1024) {
                    $erros['foto'][] = "Arquivo muito grande. Máximo 2MB";
                } else {
                    $upload_dir = '../../uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $nome_arquivo = uniqid() . '_' . date('YmdHis') . '.' . $extensao;
                    $caminho_destino = $upload_dir . $nome_arquivo;
                    
                    if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $caminho_destino)) {
                        // Apagar foto antiga
                        if ($usuario['foto_perfil'] && file_exists($upload_dir . $usuario['foto_perfil'])) {
                            unlink($upload_dir . $usuario['foto_perfil']);
                        }
                        $foto_perfil = $nome_arquivo;
                    } else {
                        $erros['foto'][] = "Erro ao fazer upload da foto";
                    }
                }
            } else {
                $erros['foto'][] = "Tipo de arquivo não permitido. Use JPG, PNG ou GIF";
            }
        }
        
        if (empty($erros)) {
            // Formatar dados
            $nome = $functions->validarEFormatarInput($_POST['nome'], 'nome');
            $email = trim($_POST['email']);
            $empresa_id = !empty($_POST['empresa_id']) ? $_POST['empresa_id'] : null;
            $departamento_id = !empty($_POST['departamento_id']) ? $_POST['departamento_id'] : null;
            $cargo_id = !empty($_POST['cargo_id']) ? $_POST['cargo_id'] : null;
            $gestor_id = !empty($_POST['gestor_id']) ? $_POST['gestor_id'] : null;
            $tipo = $_POST['tipo'] ?? 'colaborador';
            $telefone = $functions->validarEFormatarInput($_POST['telefone'] ?? '', 'telefone');
            $data_contratacao = !empty($_POST['data_contratacao']) ? $_POST['data_contratacao'] : null;
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            
            if (!empty($_POST['senha'])) {
                $senha_hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                $query = "UPDATE usuarios SET 
                          nome = :nome, 
                          email = :email, 
                          senha = :senha,
                          empresa_id = :empresa_id,
                          departamento_id = :departamento_id,
                          cargo_id = :cargo_id,
                          gestor_id = :gestor_id,
                          telefone = :telefone,
                          tipo = :tipo,
                          data_contratacao = :data_contratacao,
                          foto_perfil = :foto_perfil,
                          ativo = :ativo,
                          data_atualizacao = NOW()
                          WHERE id = :id";
            } else {
                $query = "UPDATE usuarios SET 
                          nome = :nome, 
                          email = :email, 
                          empresa_id = :empresa_id,
                          departamento_id = :departamento_id,
                          cargo_id = :cargo_id,
                          gestor_id = :gestor_id,
                          telefone = :telefone,
                          tipo = :tipo,
                          data_contratacao = :data_contratacao,
                          foto_perfil = :foto_perfil,
                          ativo = :ativo,
                          data_atualizacao = NOW()
                          WHERE id = :id";
            }
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':email', $email);
            if (!empty($_POST['senha'])) {
                $stmt->bindParam(':senha', $senha_hash);
            }
            $stmt->bindParam(':empresa_id', $empresa_id);
            $stmt->bindParam(':departamento_id', $departamento_id);
            $stmt->bindParam(':cargo_id', $cargo_id);
            $stmt->bindParam(':gestor_id', $gestor_id);
            $stmt->bindParam(':telefone', $telefone);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':data_contratacao', $data_contratacao);
            $stmt->bindParam(':foto_perfil', $foto_perfil);
            $stmt->bindParam(':ativo', $ativo);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Usuário atualizado com sucesso!";
                ob_end_clean();
                header('Location: index.php');
                exit;
            } else {
                $erros['geral'][] = "Erro ao atualizar usuário";
            }
        }
    } catch (Exception $e) {
        $erros['geral'][] = "Erro: " . $e->getMessage();
    }
}

require_once '../../includes/header.php';
?>

<!-- Incluir máscaras JS -->
<?php echo $functions->mascaraJS(); ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-pencil"></i> Editar Usuário</h2>
            <div>
                <a href="visualizar.php?id=<?php echo $id; ?>" class="btn btn-info">
                    <i class="bi bi-eye"></i> Visualizar
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <?php if (!empty($erros['geral'])): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($erros['geral'] as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
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
                                            <input type="text" class="form-control <?php echo isset($erros['nome']) ? 'is-invalid' : ''; ?>" 
                                                   id="nome" name="nome" 
                                                   onkeyup="apenasLetras(this); formatarNomeInput(this)"
                                                   maxlength="100"
                                                   value="<?php echo htmlspecialchars($dados['nome'] ?? ''); ?>" required>
                                            <?php if (isset($erros['nome'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo implode(', ', $erros['nome']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control <?php echo isset($erros['email']) ? 'is-invalid' : ''; ?>" 
                                                   id="email" name="email" 
                                                   maxlength="100"
                                                   value="<?php echo htmlspecialchars($dados['email'] ?? ''); ?>" required>
                                            <?php if (isset($erros['email'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo implode(', ', $erros['email']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="senha" class="form-label">Nova Senha</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control <?php echo isset($erros['senha']) ? 'is-invalid' : ''; ?>" 
                                                       id="senha" name="senha" 
                                                       placeholder="Deixe em branco para manter">
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('senha', 'eyeIcon')">
                                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                                </button>
                                            </div>
                                            <?php if (isset($erros['senha'])): ?>
                                                <div class="invalid-feedback d-block">
                                                    <?php echo implode(', ', $erros['senha']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <small class="text-muted">Preencha apenas se quiser alterar (mínimo 6 caracteres)</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="telefone" class="form-label">Telefone</label>
                                            <input type="text" class="form-control <?php echo isset($erros['telefone']) ? 'is-invalid' : ''; ?>" 
                                                   id="telefone" name="telefone" 
                                                   onkeyup="mascaraTelefone(this)"
                                                   maxlength="15"
                                                   value="<?php echo htmlspecialchars($dados['telefone'] ?? ''); ?>"
                                                   placeholder="(11) 99999-9999">
                                            <?php if (isset($erros['telefone'])): ?>
                                                <div class="invalid-feedback">
                                                    <?php echo implode(', ', $erros['telefone']); ?>
                                                </div>
                                            <?php endif; ?>
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
                                                    <?php echo ($dados['empresa_id'] ?? '') == $emp['id'] ? 'selected' : ''; ?>>
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
                                                    <?php echo ($dados['gestor_id'] ?? '') == $gestor['id'] ? 'selected' : ''; ?>>
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
                                                   value="<?php echo $dados['data_contratacao'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Permissões e Status -->
                            <div class="card mb-3">
                                <div class="card-header bg-warning">
                                    <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Configurações</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="tipo" class="form-label">Tipo de Usuário *</label>
                                            <select class="form-select" id="tipo" name="tipo" required>
                                                <option value="colaborador" <?php echo ($dados['tipo'] ?? '') == 'colaborador' ? 'selected' : ''; ?>>Colaborador</option>
                                                <option value="gestor" <?php echo ($dados['tipo'] ?? '') == 'gestor' ? 'selected' : ''; ?>>Gestor</option>
                                                <option value="rh" <?php echo ($dados['tipo'] ?? '') == 'rh' ? 'selected' : ''; ?>>RH</option>
                                                <option value="admin" <?php echo ($dados['tipo'] ?? '') == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check mt-4">
                                                <input type="checkbox" class="form-check-input" id="ativo" name="ativo" value="1"
                                                       <?php echo ($dados['ativo'] ?? '') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="ativo">Usuário Ativo</label>
                                            </div>
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
                                    <?php if (isset($erros['foto'])): ?>
                                        <div class="alert alert-danger py-2">
                                            <?php echo implode('<br>', $erros['foto']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <img id="previewFoto" src="<?php 
                                            echo $usuario['foto_perfil'] 
                                                ? SITE_URL . '/uploads/' . $usuario['foto_perfil']
                                                : SITE_URL . '/assets/img/default-avatar.png'; 
                                        ?>" class="rounded-circle img-fluid mb-3 border border-3" 
                                           style="width: 150px; height: 150px; object-fit: cover;">
                                    </div>
                                    <input type="file" class="form-control" id="foto_perfil" 
                                           name="foto_perfil" accept="image/jpeg,image/png,image/gif">
                                    <small class="text-muted">Deixe em branco para manter a foto atual</small>
                                </div>
                            </div>

                            <!-- Informações do Sistema -->
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informações do Sistema</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>ID:</strong> #<?php echo $usuario['id']; ?></p>
                                    <p><strong>Data de Cadastro:</strong> <?php echo date('d/m/Y H:i', strtotime($usuario['data_criacao'])); ?></p>
                                    <p><strong>Última Atualização:</strong> <?php echo date('d/m/Y H:i', strtotime($usuario['data_atualizacao'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save"></i> Atualizar Usuário
                        </button>
                        <a href="visualizar.php?id=<?php echo $id; ?>" class="btn btn-secondary btn-lg">Cancelar</a>
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
                            
                            // Selecionar o departamento atual se for o caso
                            <?php if (isset($usuario['departamento_id'])): ?>
                            if (depto.id == <?php echo $usuario['departamento_id']; ?>) {
                                option.selected = true;
                            }
                            <?php endif; ?>
                            
                            deptoSelect.appendChild(option);
                        });
                        
                        // Se tiver departamento selecionado, carregar cargos
                        if (deptoSelect.value) {
                            deptoSelect.dispatchEvent(new Event('change'));
                        }
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
                            
                            // Selecionar o cargo atual se for o caso
                            <?php if (isset($usuario['cargo_id'])): ?>
                            if (cargo.id == <?php echo $usuario['cargo_id']; ?>) {
                                option.selected = true;
                            }
                            <?php endif; ?>
                            
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

    // 3. Disparar change inicial para carregar departamentos (se já tiver empresa)
    <?php if (!empty($usuario['empresa_id'])): ?>
    setTimeout(function() {
        empresaSelect.value = '<?php echo $usuario['empresa_id']; ?>';
        empresaSelect.dispatchEvent(new Event('change'));
    }, 500);
    <?php endif; ?>

    // 4. Se veio de POST (erro no formulário), manter os valores
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
