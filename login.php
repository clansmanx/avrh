<?php
// login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/auth.php';

// Se já estiver logado, redirecionar para dashboard
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    $result = $auth->login($email, $senha);
    
    if ($result['success']) {
        header('Location: index.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login · <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #6c757d;
            --success: #2ecc71;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --light: #f8f9fc;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Background animado */
        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }
        
        .bg-animation .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            animation: float 20s infinite ease-in-out;
        }
        
        .shape-1 {
            width: 400px;
            height: 400px;
            top: -100px;
            right: -100px;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 300px;
            height: 300px;
            bottom: -50px;
            left: -50px;
            animation-delay: 5s;
        }
        
        .shape-3 {
            width: 200px;
            height: 200px;
            bottom: 50%;
            right: 20%;
            animation-delay: 10s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            33% { transform: translateY(-30px) rotate(120deg); }
            66% { transform: translateY(20px) rotate(240deg); }
        }
        
        /* Card de login */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 450px;
            margin: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.6s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Logo e título */
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }
        
        .logo-icon i {
            font-size: 40px;
            color: white;
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .login-header p {
            color: var(--secondary);
            font-size: 14px;
            font-weight: 400;
        }
        
        /* Formulário */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            font-size: 14px;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .input-group {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e9ecef;
            transition: all 0.3s;
            background: white;
        }
        
        .input-group:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
        }
        
        .input-group-text {
            background: white;
            border: none;
            color: var(--secondary);
            padding: 12px 15px;
        }
        
        .form-control {
            border: none;
            padding: 12px 15px;
            font-size: 14px;
            background: white;
        }
        
        .form-control:focus {
            box-shadow: none;
            outline: none;
        }
        
        /* Botão de login */
        .btn-login {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            font-size: 16px;
            color: white;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login i {
            margin-right: 8px;
            transition: all 0.3s;
        }
        
        .btn-login:hover i {
            transform: translateX(5px);
        }
        
        /* Links */
        .login-links {
            text-align: center;
            margin-top: 25px;
        }
        
        .login-links a {
            color: var(--secondary);
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .login-links a:hover {
            color: var(--primary);
        }
        
        .divider {
            margin: 0 10px;
            color: #e9ecef;
        }
        
        /* Alerta de erro */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            animation: shake 0.5s;
            font-size: 14px;
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            color: #b91c1c;
            border-left: 4px solid #ef4444;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        
        .alert i {
            margin-right: 8px;
            font-size: 18px;
        }
        
        /* Info de teste */
        .test-info {
            background: rgba(0, 0, 0, 0.03);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            font-size: 12px;
            color: var(--secondary);
            border: 1px dashed #e9ecef;
        }
        
        .test-info strong {
            color: var(--dark);
            display: block;
            margin-bottom: 8px;
        }
        
        .test-info span {
            background: rgba(67, 97, 238, 0.1);
            padding: 4px 8px;
            border-radius: 6px;
            font-family: monospace;
        }
        
        /* Responsividade */
        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px;
            }
            
            .logo-icon {
                width: 60px;
                height: 60px;
            }
            
            .logo-icon i {
                font-size: 30px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
        }
        
        /* Loading state */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }
        
        .btn-loading i {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Background animado -->
    <div class="bg-animation">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    
    <!-- Container de login -->
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="logo-icon">
                    <i class="bi bi-bar-chart-fill"></i>
                </div>
                <h1><?php echo SITE_NAME; ?></h1>
                <p>Faça login para acessar o sistema</p>
            </div>
            
            <!-- Mensagem de erro -->
            <?php if ($error): ?>
            <div class="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <!-- Formulário -->
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label class="form-label">
                        <i class="bi bi-envelope me-1"></i> Email
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="admin@sistema.com" placeholder="seu@email.com" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="bi bi-lock me-1"></i> Senha
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="senha" name="senha" 
                               value="123456" placeholder="••••••••" required>
                        <button class="btn btn-outline-secondary" type="button" 
                                onclick="togglePassword('senha', 'eyeIcon')" style="border: none; background: white;">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Opções adicionais -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember">
                        <label class="form-check-label small" for="remember">
                            Lembrar-me
                        </label>
                    </div>
                    <a href="#" class="small text-decoration-none" style="color: var(--primary);">
                        Esqueceu a senha?
                    </a>
                </div>
                
                <!-- Botão de login -->
                <button type="submit" class="btn-login" id="btnLogin">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Entrar no Sistema</span>
                </button>
            </form>
            
            <!-- Links -->
            <div class="login-links">
                <a href="#">Precisa de ajuda?</a>
                <span class="divider">|</span>
                <a href="#">Suporte</a>
            </div>
            
            <!-- Info de teste -->
            <div class="test-info">
                <strong><i class="bi bi-info-circle me-1"></i> Acesso de teste:</strong>
                <span>Admin - admin@sistema.com</span> / <span>admin123</span><br>
		<span>Gestor - jean.todt@ferrari.com</span> / <span>123456</span><br>
		<span>Colab - rubens.barrichello@ferrari.com</span> / <span>123456</span>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-3">
            <small class="text-white-50">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos os direitos reservados.
            </small>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }
        
        // Loading state no botão
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('btnLogin');
            btn.classList.add('btn-loading');
            btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Entrando...';
        });
        
        // Autocomplete off para campos de senha (melhor segurança)
        document.getElementById('senha').autocomplete = 'off';
        
        // Animação de entrada nos campos
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.closest('.input-group').style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.closest('.input-group').style.transform = 'scale(1)';
            });
        });
        
        // Efeito de ripple no botão
        document.querySelector('.btn-login').addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            this.appendChild(ripple);
            
            const x = e.clientX - e.target.offsetLeft;
            const y = e.clientY - e.target.offsetTop;
            
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    </script>
    
    <style>
        /* Efeito ripple personalizado */
        .btn-login {
            position: relative;
            overflow: hidden;
        }
        
        .ripple {
            position: absolute;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        /* Melhorias no checkbox */
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        /* Texto do footer */
        .text-white-50 {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        
        /* Hover nos links */
        .login-links a:hover {
            text-decoration: underline !important;
        }
    </style>
</body>
</html>
