<?php
// includes/Email.php
require_once __DIR__ . '/../config/email.php';

class Email {
    private $conn;
    
    public function __construct() {
        // Se precisar de conexão com banco para templates, etc
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Envia email usando SMTP
     */
    private function enviarSMTP($para, $assunto, $mensagem, $para_nome = '') {
        require_once 'PHPMailer/PHPMailer.php';
        require_once 'PHPMailer/SMTP.php';
        require_once 'PHPMailer/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Configurações do servidor
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            
            // Remetente
            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            
            // Destinatário
            $mail->addAddress($para, $para_nome);
            
            // Conteúdo
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body    = $mensagem;
            $mail->AltBody = strip_tags($mensagem);
            
            $mail->send();
            return ['success' => true, 'message' => 'Email enviado com sucesso'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erro ao enviar email: {$mail->ErrorInfo}"];
        }
    }
    
    /**
     * Envia email usando mail() simples
     */
    private function enviarMailSimples($para, $assunto, $mensagem, $para_nome = '') {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">" . "\r\n";
        
        if (mail($para, $assunto, $mensagem, $headers)) {
            return ['success' => true, 'message' => 'Email enviado com sucesso'];
        } else {
            return ['success' => false, 'message' => 'Erro ao enviar email'];
        }
    }
    
    /**
     * Envia email (decide qual método usar)
     */
    public function enviar($para, $assunto, $mensagem, $para_nome = '') {
        if (USE_SMTP && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return $this->enviarSMTP($para, $assunto, $mensagem, $para_nome);
        } else {
            return $this->enviarMailSimples($para, $assunto, $mensagem, $para_nome);
        }
    }
    
    /**
     * Template de email padrão
     */
    public function template($titulo, $conteudo, $botao_texto = '', $botao_link = '') {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4e73df; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f8f9fc; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #858796; }
                .button { display: inline-block; padding: 10px 20px; background-color: #4e73df; 
                          color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>' . SITE_NAME . '</h2>
                </div>
                <div class="content">
                    <h3>' . $titulo . '</h3>
                    ' . $conteudo . '
                    ';
        
        if ($botao_texto && $botao_link) {
            $html .= '<p style="text-align: center;">
                        <a href="' . $botao_link . '" class="button">' . $botao_texto . '</a>
                      </p>';
        }
        
        $html .= '
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' ' . SITE_NAME . '. Todos os direitos reservados.</p>
                    <p>Este é um email automático, por favor não responda.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}
