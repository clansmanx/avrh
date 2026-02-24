<?php
// modules/pdi/marcar_item.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();
$auth = new Auth();
$functions = new Functions();

if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$item_id = $data['item_id'] ?? 0;
$tipo = $data['tipo'] ?? 'meta';
$marcado = $data['marcado'] ?? 0;

if (!$item_id) {
    echo json_encode(['success' => false, 'error' => 'ID do item não fornecido']);
    exit;
}

try {
    $conn->beginTransaction();
    
    if ($marcado) {
        // Marcar como concluído
        $query = "INSERT INTO pdi_checklist_conclusoes (checklist_id, usuario_id) 
                  VALUES (:checklist_id, :usuario_id)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':checklist_id', $item_id);
        $stmt->bindParam(':usuario_id', $_SESSION['user_id']);
        $stmt->execute();
    } else {
        // Desmarcar
        $query = "DELETE FROM pdi_checklist_conclusoes WHERE checklist_id = :checklist_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':checklist_id', $item_id);
        $stmt->execute();
    }
    
    // Descobrir o ID do PDI para recalcular progresso
    if ($tipo == 'meta') {
        $query = "SELECT pdi_id FROM pdi_metas WHERE id = (SELECT item_id FROM pdi_checklists WHERE id = :item_id)";
    } else {
        $query = "SELECT pdi_id FROM pdi_acoes WHERE id = (SELECT item_id FROM pdi_checklists WHERE id = :item_id)";
    }
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':item_id', $item_id);
    $stmt->execute();
    $pdi_info = $stmt->fetch();
    
    if ($pdi_info) {
        $pdi_id = $pdi_info['pdi_id'];
        
        // Recalcular progresso geral
        $functions->calcularProgressoGeralPDI($pdi_id);
    }
    
    $conn->commit();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
