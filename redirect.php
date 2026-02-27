<?php
// redirect.php
<?php
session_start();

$url = $_GET['url'] ?? 'index.php';
$mensagem = $_GET['msg'] ?? '';
$tipo = $_GET['tipo'] ?? 'success';

if ($mensagem) {
    $_SESSION[$tipo] = $mensagem;
}

// Se headers não foram enviados, tenta redirect normal
if (!headers_sent()) {
    header('Location: ' . $url);
    exit;
}

// Fallback: HTML com JavaScript e Meta refresh
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="2;url=<?php echo htmlspecialchars($url); ?>">
    <title>Redirecionando...</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .redirect-card {
            max-width: 400px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card redirect-card">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </div>
                        <h4 class="mb-3"><?php echo $mensagem ?: 'Redirecionando...'; ?></h4>
                        <p class="text-muted mb-4">Você será redirecionado em instantes.</p>
                        <a href="<?php echo htmlspecialchars($url); ?>" class="btn btn-primary">
                            Clique aqui se não for redirecionado automaticamente
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        setTimeout(function() {
            window.location.href = "<?php echo htmlspecialchars($url); ?>";
        }, 2000);
    </script>
</body>
</html>
<?php
exit;
?>
