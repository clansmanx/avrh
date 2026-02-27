<?php
// modules/usuarios/get_cargos_por_depto.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';

$depto_id = $_GET['depto_id'] ?? 0;

if (!$depto_id) {
    echo json_encode([]);
    exit;
}

try {
    $conn = (new Database())->getConnection();
    
    // 🔥 Cargos são globais (não dependem de empresa)
    $query = "SELECT c.* FROM cargos c
              JOIN cargo_departamento cd ON c.id = cd.cargo_id
              WHERE cd.departamento_id = :depto_id AND c.ativo = 1
              ORDER BY c.nome";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':depto_id', $depto_id);
    $stmt->execute();
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
?>
