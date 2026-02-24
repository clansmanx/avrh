<?php
// modules/rh/processar_promocao.php
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    header('Location: elegiveis.php');
    exit;
}

$usuario_id = $_POST['usuario_id'];
$cargo_novo_id = $_POST['cargo_novo_id'];
$cargo_anterior_id = $_POST['cargo_anterior_id'] ?: null;
$data_promocao = $_POST['data_promocao'];
$tipo_promocao = $_POST['tipo_promocao'];
$media_rotina = $_POST['media_rotina'];
$media_rh = $_POST['media_rh'];
$observacoes = $_POST['observacoes'] ?? '';

try {
    $conn->beginTransaction();
    
    // Verificar se já foi promovido recentemente
    $query_check = "SELECT id FROM historico_promocoes 
                    WHERE usuario_id = :usuario_id 
                    AND data_promocao > DATE_SUB(NOW(), INTERVAL 6 MONTH)";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bindParam(':usuario_id', $usuario_id);
    $stmt_check->execute();
    
    if ($stmt_check->rowCount() > 0) {
        throw new Exception("Colaborador já foi promovido nos últimos 6 meses");
    }
    
    // Registrar no histórico
    $query = "INSERT INTO historico_promocoes 
              (usuario_id, cargo_anterior_id, cargo_novo_id, data_promocao, tipo_promocao, media_rotina, media_rh, observacoes, aprovado_por) 
              VALUES 
              (:usuario_id, :cargo_anterior_id, :cargo_novo_id, :data_promocao, :tipo_promocao, :media_rotina, :media_rh, :observacoes, :aprovado_por)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->bindParam(':cargo_anterior_id', $cargo_anterior_id);
    $stmt->bindParam(':cargo_novo_id', $cargo_novo_id);
    $stmt->bindParam(':data_promocao', $data_promocao);
    $stmt->bindParam(':tipo_promocao', $tipo_promocao);
    $stmt->bindParam(':media_rotina', $media_rotina);
    $stmt->bindParam(':media_rh', $media_rh);
    $stmt->bindParam(':observacoes', $observacoes);
    $stmt->bindParam(':aprovado_por', $_SESSION['user_id']);
    $stmt->execute();
    
    // Atualizar cargo do usuário
    $query = "UPDATE usuarios SET cargo_id = :cargo_novo_id WHERE id = :usuario_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':cargo_novo_id', $cargo_novo_id);
    $stmt->bindParam(':usuario_id', $usuario_id);
    $stmt->execute();
    
    // Criar notificação
    $functions->criarNotificacao(
        $usuario_id,
        'sistema',
        'Promoção Recebida',
        "Parabéns! Você foi promovido para " . $cargo_novo_id,
        SITE_URL . "/modules/usuarios/perfil.php"
    );
    
    $conn->commit();
    
    $_SESSION['success'] = "Promoção realizada com sucesso!";
    
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = "Erro ao processar promoção: " . $e->getMessage();
}

ob_end_clean();
header('Location: promocoes.php');
exit;
?>
