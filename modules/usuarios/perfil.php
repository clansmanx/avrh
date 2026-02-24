<?php
// modules/usuarios/perfil.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar buffer de saída
ob_start();

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Inicializar objetos
$database = new Database();
$conn = $database->getConnection();
$auth = new Auth();
$functions = new Functions();

// Verificar se usuário está logado
$auth->requireLogin();

$id = $auth->getUserId();

// Buscar dados do usuário
$query = "SELECT u.*, c.nome as cargo_nome, d.nome as departamento_nome,
                 g.nome as gestor_nome
          FROM usuarios u
          LEFT JOIN cargos c ON u.cargo_id = c.id
          LEFT JOIN departamentos d ON u.departamento_id = d.id
          LEFT JOIN usuarios g ON u.gestor_id = g.id
          WHERE u.id = :id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$usuario = $stmt->fetch();

// Buscar histórico de avaliações
$query_avaliacoes = "SELECT a.*, c.nome as ciclo_nome, c.data_fim,
                            av.nome as avaliador_nome,
                            f.nome as formulario_nome
                     FROM avaliacoes a
                     JOIN ciclos_avaliacao c ON a.ciclo_id = c.id
                     JOIN usuarios av ON a.avaliador_id = av.id
                     JOIN formularios f ON a.formulario_id = f.id
                     WHERE a.avaliado_id = :avaliado_id
                     ORDER BY c.data_fim DESC
                     LIMIT 10";

$stmt = $conn->prepare($query_avaliacoes);
$stmt->bindParam(':avaliado_id', $id);
$stmt->execute();
$avaliacoes = $stmt->fetchAll();

// Buscar estatísticas
$query_stats = "SELECT 
                  COUNT(*) as total_avaliacoes,
                  AVG(nota_final) as media_geral,
                  MAX(nota_final) as melhor_nota,
                  MIN(nota_final) as pior_nota
                FROM avaliacoes 
                WHERE avaliado_id = :avaliado_id AND status = 'concluida'";

$stmt = $conn->prepare($query_stats);
$stmt->bindParam(':avaliado_id', $id);
$stmt->execute();
$estatisticas = $stmt->fetch();

// SÓ AGORA INCLUIMOS O HEADER
require_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Meu Perfil</h2>
            <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Editar Perfil
            </a>
        </div>

        <div class="row">
            <div class="col-md-4">
                <!-- Card do perfil -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <?php if ($usuario['foto_perfil']): ?>
                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $usuario['foto_perfil']; ?>" 
                             class="rounded-circle img-fluid mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" 
                             style="width: 150px; height: 150px;">
                            <i class="bi bi-person-fill" style="font-size: 5rem;"></i>
                        </div>
                        <?php endif; ?>
                        
                        <h4><?php echo htmlspecialchars($usuario['nome']); ?></h4>
                        <p class="text-muted">
                            <?php echo htmlspecialchars($usuario['cargo_nome'] ?? 'Cargo não definido'); ?>
                        </p>
                        
                        <div class="mt-3">
                            <?php
                            $badge_class = [
                                'admin' => 'bg-danger',
                                'rh' => 'bg-warning',
                                'gestor' => 'bg-info',
                                'colaborador' => 'bg-secondary'
                            ][$usuario['tipo']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?php echo $badge_class; ?> p-2">
                                <?php echo ucfirst($usuario['tipo']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Informações de contato -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Informações de Contato</h5>
                    </div>
                    <div class="card-body">
                        <p><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($usuario['email']); ?></p>
                        <?php if (!empty($usuario['telefone'])): ?>
                        <p><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($usuario['telefone']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Minhas estatísticas -->
                <div class="card">
                    <div class="card-header">
                        <h5>Minhas Estatísticas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <h3><?php echo $estatisticas['total_avaliacoes'] ?? 0; ?></h3>
                                <small class="text-muted">Avaliações</small>
                            </div>
                            <div class="col-6 mb-3">
                                <h3><?php echo number_format($estatisticas['media_geral'] ?? 0, 1); ?></h3>
                                <small class="text-muted">Média Geral</small>
                            </div>
                            <div class="col-6">
                                <h3 class="text-success"><?php echo number_format($estatisticas['melhor_nota'] ?? 0, 1); ?></h3>
                                <small class="text-muted">Melhor Nota</small>
                            </div>
                            <div class="col-6">
                                <h3 class="text-danger"><?php echo number_format($estatisticas['pior_nota'] ?? 0, 1); ?></h3>
                                <small class="text-muted">Pior Nota</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Informações profissionais -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Informações Profissionais</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Departamento:</strong> 
                                    <?php echo htmlspecialchars($usuario['departamento_nome'] ?? 'Não definido'); ?>
                                </p>
                                <p><strong>Cargo:</strong> 
                                    <?php echo htmlspecialchars($usuario['cargo_nome'] ?? 'Não definido'); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Gestor:</strong> 
                                    <?php echo htmlspecialchars($usuario['gestor_nome'] ?? 'Não definido'); ?>
                                </p>
                                <p><strong>Data de Contratação:</strong> 
                                    <?php echo $functions->formatDate($usuario['data_contratacao']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Minhas últimas avaliações -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Minhas Últimas Avaliações</h5>
                        <a href="../avaliacoes/" class="btn btn-sm btn-primary">Ver Todas</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($avaliacoes)): ?>
                        <p class="text-muted text-center py-4">
                            <i class="bi bi-clipboard-x fs-1 d-block mb-3"></i>
                            Nenhuma avaliação encontrada.
                        </p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Ciclo</th>
                                        <th>Avaliador</th>
                                        <th>Data</th>
                                        <th>Nota</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($avaliacoes as $av): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($av['ciclo_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($av['avaliador_nome']); ?></td>
                                        <td><?php echo $functions->formatDate($av['data_conclusao']); ?></td>
                                        <td>
                                            <?php if ($av['nota_final']): ?>
                                            <span class="badge bg-<?php 
                                                echo $av['nota_final'] >= 4 ? 'success' : 
                                                    ($av['nota_final'] >= 3 ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo number_format($av['nota_final'], 2); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="badge bg-warning">Pendente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="../avaliacoes/visualizar.php?id=<?php echo $av['id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Gráfico de evolução -->
                        <?php if (count($avaliacoes) > 1): ?>
                        <canvas id="minhaEvolucaoChart" class="mt-4" height="100"></canvas>
                        
                        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                        <script>
                        const ctx = document.getElementById('minhaEvolucaoChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: <?php echo json_encode(array_column(array_reverse($avaliacoes), 'ciclo_nome')); ?>,
                                datasets: [{
                                    label: 'Minha Evolução',
                                    data: <?php echo json_encode(array_column(array_reverse($avaliacoes), 'nota_final')); ?>,
                                    borderColor: 'rgb(75, 192, 192)',
                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: 5,
                                        ticks: {
                                            stepSize: 0.5
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                }
                            }
                        });
                        </script>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Minhas competências -->
                <div class="card">
                    <div class="card-header">
                        <h5>Minhas Competências em Destaque</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = "SELECT c.nome, AVG(r.resposta_nota) as media,
                                         COUNT(*) as total_avaliacoes
                                  FROM respostas r
                                  JOIN perguntas p ON r.pergunta_id = p.id
                                  JOIN competencias c ON p.competencia_id = c.id
                                  JOIN avaliacoes a ON r.avaliacao_id = a.id
                                  WHERE a.avaliado_id = :avaliado_id 
                                    AND r.resposta_nota IS NOT NULL
                                  GROUP BY c.id
                                  ORDER BY media DESC
                                  LIMIT 5";
                        
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':avaliado_id', $id);
                        $stmt->execute();
                        $competencias = $stmt->fetchAll();
                        ?>
                        
                        <?php if (empty($competencias)): ?>
                        <p class="text-muted text-center py-3">
                            Nenhuma competência avaliada ainda
                        </p>
                        <?php else: ?>
                            <?php foreach ($competencias as $comp): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>
                                        <strong><?php echo htmlspecialchars($comp['nome']); ?></strong>
                                        <small class="text-muted">(<?php echo $comp['total_avaliacoes']; ?>x)</small>
                                    </span>
                                    <span class="text-primary"><?php echo number_format($comp['media'], 1); ?>/5</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: <?php echo ($comp['media'] / 5) * 100; ?>%">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    Baseado em <?php echo array_sum(array_column($competencias, 'total_avaliacoes')); ?> avaliações
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once '../../includes/footer.php';
ob_end_flush(); 
?>
