<?php
// config/email.php
// Configurações de email para o sistema

// Configuração SMTP (use os dados do seu provedor de email)
define('SMTP_HOST', 'smtp.gmail.com'); // ou smtp.office365.com, smtp.mail.yahoo.com
define('SMTP_PORT', 587);
define('SMTP_USER', 'seuemail@gmail.com'); // seu email
define('SMTP_PASS', 'suasenha'); // sua senha ou app password
define('SMTP_FROM', 'naoresponda@avaliacao.com');
define('SMTP_FROM_NAME', 'Sistema de Avaliação');

// Se não tiver SMTP, pode usar a função mail() do PHP
define('USE_SMTP', true); // false para usar mail() simples
?>
