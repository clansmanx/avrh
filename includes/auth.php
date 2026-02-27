<?php
// includes/auth.php
require_once dirname(__DIR__) . '/config/database.php';

class Auth {
    private $conn;
    private $user = null;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        // Verificar se usuário está logado
        if (isset($_SESSION['user_id'])) {
            $this->getUser($_SESSION['user_id']);
        }
    }

    public function login($email, $senha) {
        try {
            $query = "SELECT u.*, c.nome as cargo_nome, d.nome as departamento_nome 
                     FROM usuarios u 
                     LEFT JOIN cargos c ON u.cargo_id = c.id 
                     LEFT JOIN departamentos d ON u.departamento_id = d.id 
                     WHERE u.email = :email AND u.ativo = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                if (password_verify($senha, $user['senha'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_nome'] = $user['nome'];
                    $_SESSION['user_tipo'] = $user['tipo'];
                    
                    $this->user = $user;
                    
                    return ['success' => true, 'user' => $user];
                }
            }
            
            return ['success' => false, 'message' => 'Email ou senha inválidos'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro no sistema: ' . $e->getMessage()];
        }
    }

    public function logout() {
        session_destroy();
        $this->user = null;
        return true;
    }

    public function getUser($user_id = null) {
        if ($user_id) {
            $query = "SELECT u.*, c.nome as cargo_nome, d.nome as departamento_nome 
                     FROM usuarios u 
                     LEFT JOIN cargos c ON u.cargo_id = c.id 
                     LEFT JOIN departamentos d ON u.departamento_id = d.id 
                     WHERE u.id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $this->user = $stmt->fetch();
            }
        }
        
        return $this->user;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && $this->user !== null;
    }

    public function hasPermission($required_tipo) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        if ($this->user['tipo'] === 'admin') {
            return true;
        }
        
        if (is_array($required_tipo)) {
            return in_array($this->user['tipo'], $required_tipo);
        }
        
        return $this->user['tipo'] === $required_tipo;
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . SITE_URL . '/login.php');
            exit;
        }
    }

    public function requirePermission($required_tipo) {
        $this->requireLogin();
        
        if (!$this->hasPermission($required_tipo)) {
            header('Location: ' . SITE_URL . '/index.php?error=permission_denied');
            exit;
        }
    }

    public function getUserType() {
        return $this->user ? $this->user['tipo'] : null;
    }

    public function getUserId() {
        return $this->user ? $this->user['id'] : null;
    }
}

// Inicializar autenticação
$auth = new Auth();
?>
