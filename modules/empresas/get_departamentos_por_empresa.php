<?php
// modules/empresas/get_departamentos_por_empresa.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';

header('Content-Type: application/json');

$empresa_id = $_GET['empresa_id'] ?? 0;

if (!$empresa_id) {
    echo json_encode([]);
    exit;
}

try {
    $conn = (new Database())->getConnection();
    
    // 🔥 NOVA LÓGICA: Descobrir tipo da empresa
    $stmt_emp = $conn->prepare("SELECT tipo FROM empresas WHERE id = :id");
    $stmt_emp->bindParam(':id', $empresa_id);
    $stmt_emp->execute();
    $empresa = $stmt_emp->fetch();
    
    if ($empresa && $empresa['tipo'] == 'matriz') {
        // MATRIZ: vê TODOS os departamentos (inclusive os exclusivos)
        $query = "SELECT id, nome FROM departamentos ORDER BY nome";
        $stmt = $conn->query($query);
    } else {
        // FILIAL: vê apenas departamentos COMUNS (empresa_id IS NULL)
        $query = "SELECT id, nome FROM departamentos WHERE empresa_id IS NULL OR empresa_id = :empresa_id ORDER BY nome";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':empresa_id', $empresa_id);
        $stmt->execute();
    }
    
    $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($departamentos);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
?>
