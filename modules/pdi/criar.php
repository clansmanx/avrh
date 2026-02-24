<?php
// modules/pdi/criar.php
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

$user_id = $auth->getUserId();

// Buscar colaboradores para o select
$query_colab = "SELECT u.id, u.nome, c.nome as cargo 
                FROM usuarios u
                LEFT JOIN cargos c ON u.cargo_id = c.id
                WHERE u.ativo = 1 AND u.tipo = 'colaborador'
                ORDER BY u.nome";
$stmt_colab = $conn->query($query_colab);
$colaboradores = $stmt_colab->fetchAll();

// Buscar ciclos finalizados para vincular (opcional)
$query_ciclos = "SELECT id, nome FROM ciclos_avaliacao WHERE status = 'finalizado' ORDER BY data_fim DESC";
$stmt_ciclos = $conn->query($query_ciclos);
$ciclos = $stmt_ciclos->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $colaborador_id = $_POST['colaborador_id'];
        $titulo = trim($_POST['titulo']);
        $ciclo_id = !empty($_POST['ciclo_id']) ? $_POST['ciclo_id'] : null;
        $data_revisao = !empty($_POST['data_revisao']) ? $_POST['data_revisao'] : null;
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        if (empty($colaborador_id)) throw new Exception("Selecione um colaborador");
        if (empty($titulo)) throw new Exception("Título é obrigatório");
        
        $conn->beginTransaction();
        
        $query = "INSERT INTO pdi (colaborador_id, gestor_responsavel_id, titulo, ciclo_id, data_criacao, data_revisao, observacoes_gerais) 
                  VALUES (:colaborador_id, :gestor_id, :titulo, :ciclo_id, CURDATE(), :data_revisao, :observacoes)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':colaborador_id', $colaborador_id);
        $stmt->bindParam(':gestor_id', $user_id);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':ciclo_id', $ciclo_id);
        $stmt->bindParam(':data_revisao', $data_revisao);
        $stmt->bindParam(':observacoes', $observacoes);
        $stmt->execute();
        
        $pdi_id = $conn->lastInsertId();
        
        // Se veio de um ciclo, analisar avaliações
        if ($ciclo_id) {
            $query_avals = "SELECT a.*, p.texto, p.competencia_id, r.resposta_nota, p.tipo_resposta
                           FROM avaliacoes a
                           JOIN respostas r ON a.id = r.avaliacao_id
                           JOIN perguntas p ON r.pergunta_id = p.id
                           WHERE a.avaliado_id = :colaborador_id 
                             AND a.ciclo_id = :ciclo_id
                             AND r.resposta_nota IS NOT NULL";
            $stmt_avals = $conn->prepare($query_avals);
            $stmt_avals->bindParam(':colaborador_id', $colaborador_id);
            $stmt_avals->bindParam(':ciclo_id', $ciclo_id);
            $stmt_avals->execute();
            $avaliacoes = $stmt_avals->fetchAll();
            
            $competencias_notas = [];
            foreach ($avaliacoes as $a) {
                if ($a['competencia_id']) {
                    if (!isset($competencias_notas[$a['competencia_id']])) {
                        $competencias_notas[$a['competencia_id']] = [
                            'total' => 0,
                            'soma' => 0,
                            'tipo' => $a['tipo_resposta']
                        ];
                    }
                    $competencias_notas[$a['competencia_id']]['soma'] += $a['resposta_nota'];
                    $competencias_notas[$a['competencia_id']]['total']++;
                }
            }
            
            foreach ($competencias_notas as $comp_id => $dados) {
                $media = $dados['total'] > 0 ? ($dados['soma'] / $dados['total']) : 0;
                
                if ($dados['tipo'] == 'sim_nao') {
                    $media_percent = $media * 100;
                    $baixo_desempenho = $media_percent < 100;
                    $nivel_desejado = 100;
                } else {
                    $media_percent = $media * 20;
                    $baixo_desempenho = $media_percent < 80;
                    $nivel_desejado = 80;
                }
                
                if ($baixo_desempenho) {
                    $query_ins = "INSERT INTO pdi_competencias (pdi_id, competencia_id, nivel_atual, nivel_desejado, prioridade) 
                                  VALUES (:pdi_id, :competencia_id, :nivel_atual, :nivel_desejado, 'alta')";
                    $stmt_ins = $conn->prepare($query_ins);
                    $stmt_ins->bindParam(':pdi_id', $pdi_id);
                    $stmt_ins->bindParam(':competencia_id', $comp_id);
                    $stmt_ins->bindParam(':nivel_atual', $media_percent);
                    $stmt_ins->bindParam(':nivel_desejado', $nivel_desejado);
                    $stmt_ins->execute();
                }
            }
        }
        
        $query_hist = "INSERT INTO pdi_historico (pdi_id, usuario_id, acao, descricao) 
                       VALUES (:pdi_id, :usuario_id, 'criou', 'PDI criado para o colaborador')";
        $stmt_hist = $conn->prepare($query_hist);
        $stmt_hist->bindParam(':pdi_id', $pdi_id);
        $stmt_hist->bindParam(':usuario_id', $user_id);
        $stmt_hist->execute();
        
        $conn->commit();
        
        $_SESSION['success'] = "PDI criado com sucesso!";
        ob_end_clean();
        header("Location: visualizar.php?id=$pdi_id");
        exit;
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}

require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-plus-circle"></i> Novo PDI</h2>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informações Básicas</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="colaborador_id" class="form-label fw-bold">Colaborador *</label>
                            <select class="form-select" name="colaborador_id" id="colaborador_id" required>
                                <option value="">Selecione um colaborador...</option>
                                <?php foreach ($colaboradores as $c): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo htmlspecialchars($c['nome']); ?> (<?php echo htmlspecialchars($c['cargo']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="titulo" class="form-label fw-bold">Título do PDI *</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                   placeholder="Ex: Plano de Desenvolvimento 2026" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="ciclo_id" class="form-label fw-bold">Vincular a um Ciclo (opcional)</label>
                            <select class="form-select" name="ciclo_id" id="ciclo_id">
                                <option value="">Nenhum</option>
                                <?php foreach ($ciclos as $ciclo): ?>
                                <option value="<?php echo $ciclo['id']; ?>">
                                    <?php echo htmlspecialchars($ciclo['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Se selecionado, o PDI será baseado nas avaliações deste ciclo</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="data_revisao" class="form-label fw-bold">Data de Revisão (opcional)</label>
                            <input type="date" class="form-control" id="data_revisao" name="data_revisao" 
                                   value="<?php echo date('Y-m-d', strtotime('+3 months')); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label fw-bold">Observações Gerais</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="4" placeholder="Observações iniciais sobre o PDI..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Dica:</strong> Se vincular a um ciclo, o sistema identificará automaticamente 
                        as competências com baixo desempenho para incluir no PDI.
                        <br>
                        <span class="text-warning">⚡ Para perguntas SIM/NÃO, o nível desejado será 100% (todas respostas "SIM").</span>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Criar PDI
                        </button>
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
ob_end_flush();
?>
