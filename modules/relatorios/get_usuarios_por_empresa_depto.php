<?php
// modules/relatorios/get_usuarios_por_empresa_depto.php
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$empresa_id = $_GET['empresa_id'] ?? 0;
$departamento_id = $_GET['departamento_id'] ?? 0;

if (!$empresa_id || !$departamento_id) {
    echo json_encode([]);
    exit;
}

$query = "SELECT u.id, u.nome, u.email
          FROM usuarios u
          WHERE u.ativo = 1 
            AND u.empresa_id = :empresa_id 
            AND u.departamento_id = :departamento_id
          ORDER BY u.nome";

$stmt = $conn->prepare($query);
$stmt->bindParam(':empresa_id', $empresa_id);
$stmt->bindParam(':departamento_id', $departamento_id);
$stmt->execute();

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($usuarios);
?>
