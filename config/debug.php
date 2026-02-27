<?php
// config/debug.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log de erros
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Função para debug
function debug_log($msg) {
    error_log(date('Y-m-d H:i:s') . " - " . $msg . PHP_EOL, 3, __DIR__ . '/../logs/debug.log');
}
?>
