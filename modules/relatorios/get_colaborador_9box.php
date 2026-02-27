<?php
// modules/relatorios/get_colaborador_9box.php
require_once '../../config/database.php';

$id = $_GET['id'] ?? 0;
$conn = (new Database())->getConnection();

// Buscar dados completos
$query = "SELECT u.*, c.nome as cargo, d.nome as departamento, e.nome as empresa,
                 (SELECT AVG(nota_final) FROM avaliacoes WHERE avaliado_id = u.id) as media_geral,
                 (SELECT COUNT(*) FROM avaliacoes WHERE avaliado_id = u.id AND status = 'concluida') as total_avals
          FROM usuarios u
          LEFT JOIN cargos c ON u.cargo_id = c.id
          LEFT JOIN departamentos d ON u.departamento_id = d.id
          LEFT JOIN empresas e ON u.empresa_id = e.id
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$id]);
$user = $stmt->fetch();

// Últimas avaliações
$avals = $conn->prepare("SELECT a.*, f.nome as formulario, ci.nome as ciclo
                        FROM avaliacoes a
                        JOIN formularios f ON a.formulario_id = f.id
                        JOIN ciclos_avaliacao ci ON a.ciclo_id = ci.id
                        WHERE a.avaliado_id = ? AND a.status = 'concluida'
                        ORDER BY a.data_conclusao DESC
                        LIMIT 5");
$avals->execute([$id]);
$avaliacoes = $avals->fetchAll();
?>
<div class="row">
    <div class="col-md-4 text-center">
        <?php if ($user['foto_perfil']): ?>
        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo $user['foto_perfil']; ?>" 
             class="rounded-circle img-fluid mb-3" width="120">
        <?php else: ?>
        <i class="bi bi-person-circle fs-1"></i>
        <?php endif; ?>
        <h5><?php echo $user['nome']; ?></h5>
        <p class="text-muted"><?php echo $user['cargo'] ?? 'Sem cargo'; ?></p>
    </div>
    <div class="col-md-8">
        <table class="table table-sm">
            <tr><th>Departamento:</th><td><?php echo $user['departamento'] ?? '-'; ?></td></tr>
            <tr><th>Empresa:</th><td><?php echo $user['empresa'] ?? '-'; ?></td></tr>
            <tr><th>Data Contratação:</th><td><?php echo date('d/m/Y', strtotime($user['data_contratacao'])); ?></td></tr>
            <tr><th>Média Geral:</th><td><?php echo number_format($user['media_geral'], 1); ?>%</td></tr>
        </table>
        
        <h6 class="mt-3">Últimas Avaliações</h6>
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Ciclo</th>
                    <th>Nota</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($avaliacoes as $a): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($a['data_conclusao'])); ?></td>
                    <td><?php echo $a['ciclo']; ?></td>
                    <td><?php echo $a['nota_final']; ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
