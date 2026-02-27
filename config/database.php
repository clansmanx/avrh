<?php
// config/database.php
class Database {
    private $host = "localhost";
    private $db_name = "avaliacao_desempenho";
    private $username = "root";
    private $password = "PCM@st3r";
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            die("Erro na conexão: " . $e->getMessage());
        }

        return $this->conn;
    }
}

// Configurações da aplicação
define('SITE_NAME', 'Sistema de Avaliação de Desempenho');
define('SITE_URL', 'http://192.168.99.192/avrh');
define('TIMEZONE', 'America/Sao_Paulo');
date_default_timezone_set(TIMEZONE);


// ============================================
// 🚀 CONFIGURAÇÕES PARA IA
// ============================================


// OPÇÃO 1: DeepSeek API (Recomendado)
//define('AI_API_URL', 'https://api.deepseek.com/v1/chat/completions');
//define('AI_API_KEY', ''); // <-- COLOQUE SUA CHAVE AQUI
//define('AI_MODEL', 'deepseek-chat');

// OPÇÃO 2: OpenRouter (gratuito)
 define('AI_API_URL', 'https://openrouter.ai/api/v1/chat/completions');
 define('AI_API_KEY', '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'); // <-- SUA CHAVE
 define('AI_MODEL', 'openrouter/aurora-alpha:free');

// OPÇÃO 3: Self-hosted
// define('AI_API_URL', 'http://localhost:8000/v1/chat/completions');
// define('AI_API_KEY', 'not-needed');
// define('AI_MODEL', 'deepseek-chat');

// Configurações adicionais
define('AI_TEMPERATURE', 0.3);
define('AI_MAX_TOKENS', 2000);

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
