<?php
// includes/functions.php
require_once dirname(__DIR__) . '/config/database.php';

class Functions {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Formatar data
    public function formatDate($date, $format = 'd/m/Y') {
        if (!$date || $date == '0000-00-00') return '';
        $dateTime = new DateTime($date);
        return $dateTime->format($format);
    }

    // Calcular progresso
    public function calcularProgresso($avaliacoes) {
        $total = count($avaliacoes);
        if ($total == 0) return 0;
        
        $concluidas = 0;
        foreach ($avaliacoes as $avaliacao) {
            if ($avaliacao['status'] === 'concluida') {
                $concluidas++;
            }
        }
        
        return round(($concluidas / $total) * 100);
    }

    // Gerar notificação
    public function criarNotificacao($usuario_id, $tipo, $titulo, $mensagem, $link = null) {
        $query = "INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link) 
                  VALUES (:usuario_id, :tipo, :titulo, :mensagem, :link)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':titulo', $titulo);
        $stmt->bindParam(':mensagem', $mensagem);
        $stmt->bindParam(':link', $link);
        
        return $stmt->execute();
    }






////////////////////////////////////////////////////////////////////**
////////// * Formata média baseada no tipo de resposta ///////////////
/////////////////////////////////////////////////////////////////// */

public function formatarMedia($tipo, $valor) {
    if ($tipo == 'sim_nao') {
        // Converte para percentual (0-1 → 0-100%)
        return number_format($valor * 100, 1) . '%';
    } elseif ($tipo == 'escala_1_5') {
        // Mantém escala 0-5
        return number_format($valor, 1) . '/5';
    } elseif ($tipo == 'nota') {
        // Nota 0-10
        return number_format($valor, 1) . '/10';
    } else {
        return number_format($valor, 1);
    }
}

/**
 * Retorna a cor baseada no valor e tipo
 */
public function corPorMedia($tipo, $valor) {
    if ($tipo == 'sim_nao') {
        // Para percentual
        if ($valor >= 0.8) return 'success';      // ≥ 80% SIM
        if ($valor >= 0.6) return 'warning';      // 60-79% SIM
        return 'danger';                            // < 60% SIM
    } elseif ($tipo == 'escala_1_5') {
        // Para escala 1-5
        if ($valor >= 4) return 'success';
        if ($valor >= 3) return 'warning';
        return 'danger';
    } else {
        // Para outros (fallback)
        if ($valor >= 80) return 'success';
        if ($valor >= 60) return 'warning';
        return 'danger';
    }
}


///////////////////////////////////////////////////////////////////////////////////////**
/////////////////////// * Enviar notificação por email * ///////////////////////////////
///////////////////////////////////////////////////////////////////////////////////// */

public function enviarEmailNotificacao($usuario_id, $tipo, $dados = []) {
    require_once __DIR__ . '/Email.php';
    $email = new Email();
    
    // Buscar dados do usuário
    $query = "SELECT nome, email FROM usuarios WHERE id = :id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();
    $usuario = $stmt->fetch();
    
    if (!$usuario) return false;
    
    $assunto = '';
    $conteudo = '';
    $botao_texto = '';
    $botao_link = '';
    
    switch ($tipo) {
        case 'avaliacao_pendente':
            $assunto = 'Nova avaliação pendente';
            $conteudo = '<p>Olá <strong>' . $usuario['nome'] . '</strong>,</p>
                        <p>Você tem uma nova avaliação pendente no ciclo <strong>' . $dados['ciclo_nome'] . '</strong>.</p>
                        <p><strong>Avaliado:</strong> ' . $dados['avaliado_nome'] . '</p>
                        <p><strong>Prazo:</strong> ' . $dados['prazo'] . '</p>';
            $botao_texto = 'Responder Agora';
            $botao_link = SITE_URL . '/modules/avaliacoes/responder.php?id=' . $dados['avaliacao_id'];
            break;
            
        case 'avaliacao_concluida':
            $assunto = 'Avaliação concluída';
            $conteudo = '<p>Olá <strong>' . $usuario['nome'] . '</strong>,</p>
                        <p>Uma avaliação sobre você foi concluída no ciclo <strong>' . $dados['ciclo_nome'] . '</strong>.</p>
                        <p><strong>Avaliador:</strong> ' . $dados['avaliador_nome'] . '</p>
                        <p><strong>Nota:</strong> ' . $dados['nota'] . '%</p>';
            $botao_texto = 'Visualizar Resultado';
            $botao_link = SITE_URL . '/modules/avaliacoes/visualizar.php?id=' . $dados['avaliacao_id'];
            break;
            
        case 'prazo_proximo':
            $assunto = 'Prazo de avaliação próximo';
            $conteudo = '<p>Olá <strong>' . $usuario['nome'] . '</strong>,</p>
                        <p>O prazo para responder a avaliação de <strong>' . $dados['avaliado_nome'] . '</strong> vence em <strong>' . $dados['dias'] . ' dias</strong>.</p>
                        <p><strong>Ciclo:</strong> ' . $dados['ciclo_nome'] . '</p>
                        <p><strong>Data limite:</strong> ' . $dados['data_fim'] . '</p>';
            $botao_texto = 'Responder Agora';
            $botao_link = SITE_URL . '/modules/avaliacoes/responder.php?id=' . $dados['avaliacao_id'];
            break;
            
        case 'ciclo_ativado':
            $assunto = 'Novo ciclo de avaliação ativado';
            $conteudo = '<p>Olá <strong>' . $usuario['nome'] . '</strong>,</p>
                        <p>O ciclo <strong>' . $dados['ciclo_nome'] . '</strong> foi ativado.</p>
                        <p><strong>Período:</strong> ' . $dados['data_inicio'] . ' até ' . $dados['data_fim'] . '</p>
                        <p><strong>Total de avaliações:</strong> ' . $dados['total_avaliacoes'] . '</p>';
            $botao_texto = 'Ver Avaliações';
            $botao_link = SITE_URL . '/modules/avaliacoes/';
            break;
            
        case 'promocao':
            $assunto = 'Parabéns pela promoção!';
            $conteudo = '<p>Olá <strong>' . $usuario['nome'] . '</strong>,</p>
                        <p>Você foi promovido para o cargo de <strong>' . $dados['cargo_novo'] . '</strong>!</p>
                        <p><strong>Cargo anterior:</strong> ' . $dados['cargo_anterior'] . '</p>
                        <p><strong>Data da promoção:</strong> ' . $dados['data_promocao'] . '</p>
                        <p><strong>Aprovado por:</strong> ' . $dados['aprovador'] . '</p>';
            $botao_texto = 'Ver Perfil';
            $botao_link = SITE_URL . '/modules/usuarios/perfil.php';
            break;
    }
    
    if ($assunto && $conteudo) {
        $mensagem = $email->template($assunto, $conteudo, $botao_texto, $botao_link);
        return $email->enviar($usuario['email'], $assunto, $mensagem, $usuario['nome']);
    }
    
    return false;
}

/**
 * Criar notificação no sistema E enviar email
 */
public function criarNotificacaoCompleta($usuario_id, $tipo, $titulo, $mensagem, $link = null, $dados_email = []) {
    // Criar notificação no banco (já existe)
    $this->criarNotificacao($usuario_id, $tipo, $titulo, $mensagem, $link);
    
    // Enviar email
    $this->enviarEmailNotificacao($usuario_id, $tipo, $dados_email);
}






///////////////////////////////////////////////////////////////////////////**
///////////// * Calcula progresso de uma meta baseado nos checklists ///////
///////////////////////////////////////////////////////////////////////// */
public function calcularProgressoMeta($meta_id) {
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN c.id IS NOT NULL THEN 1 ELSE 0 END) as concluidos
              FROM pdi_checklists pc
              LEFT JOIN pdi_checklist_conclusoes c ON pc.id = c.checklist_id
              WHERE pc.tipo = 'meta' AND pc.item_id = :meta_id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':meta_id', $meta_id);
    $stmt->execute();
    $result = $stmt->fetch();
    
    $progresso = $result['total'] > 0 
        ? round(($result['concluidos'] / $result['total']) * 100) 
        : 0;
    
    // Atualizar na tabela
    $update = "UPDATE pdi_metas SET progresso_calculado = :progresso WHERE id = :id";
    $stmt_up = $this->conn->prepare($update);
    $stmt_up->bindParam(':progresso', $progresso);
    $stmt_up->bindParam(':id', $meta_id);
    $stmt_up->execute();
    
    return $progresso;
}

/**
 * Calcula progresso de uma ação baseado nos checklists
 */
public function calcularProgressoAcao($acao_id) {
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN c.id IS NOT NULL THEN 1 ELSE 0 END) as concluidos
              FROM pdi_checklists pc
              LEFT JOIN pdi_checklist_conclusoes c ON pc.id = c.checklist_id
              WHERE pc.tipo = 'acao' AND pc.item_id = :acao_id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':acao_id', $acao_id);
    $stmt->execute();
    $result = $stmt->fetch();
    
    $progresso = $result['total'] > 0 
        ? round(($result['concluidos'] / $result['total']) * 100) 
        : 0;
    
    // Atualizar na tabela
    $update = "UPDATE pdi_acoes SET progresso_calculado = :progresso WHERE id = :id";
    $stmt_up = $this->conn->prepare($update);
    $stmt_up->bindParam(':progresso', $progresso);
    $stmt_up->bindParam(':id', $acao_id);
    $stmt_up->execute();
    
    return $progresso;
}

/**
 * Calcula progresso geral do PDI
 */
public function calcularProgressoGeralPDI($pdi_id) {
    // Buscar todas as metas do PDI
    $query_metas = "SELECT id FROM pdi_metas WHERE pdi_id = :pdi_id";
    $stmt_metas = $this->conn->prepare($query_metas);
    $stmt_metas->bindParam(':pdi_id', $pdi_id);
    $stmt_metas->execute();
    $metas = $stmt_metas->fetchAll();
    
    $soma_progresso = 0;
    $total_itens = 0;
    
    foreach ($metas as $meta) {
        $progresso = $this->calcularProgressoMeta($meta['id']);
        $soma_progresso += $progresso;
        $total_itens++;
    }
    
    // Buscar ações do PDI
    $query_acoes = "SELECT id FROM pdi_acoes WHERE pdi_id = :pdi_id";
    $stmt_acoes = $this->conn->prepare($query_acoes);
    $stmt_acoes->bindParam(':pdi_id', $pdi_id);
    $stmt_acoes->execute();
    $acoes = $stmt_acoes->fetchAll();
    
    foreach ($acoes as $acao) {
        $progresso = $this->calcularProgressoAcao($acao['id']);
        $soma_progresso += $progresso;
        $total_itens++;
    }
    
    $progresso_geral = $total_itens > 0 ? round($soma_progresso / $total_itens) : 0;
    
    // Atualizar PDI
    $update = "UPDATE pdi SET progresso_geral = :progresso WHERE id = :id";
    $stmt_up = $this->conn->prepare($update);
    $stmt_up->bindParam(':progresso', $progresso_geral);
    $stmt_up->bindParam(':id', $pdi_id);
    $stmt_up->execute();
    
    return $progresso_geral;
}







    // Buscar notificações não lidas
    public function getNotificacoesNaoLidas($usuario_id) {
        if (!$usuario_id) return [];
        
        $query = "SELECT * FROM notificacoes 
                  WHERE usuario_id = :usuario_id AND lida = 0 
                  ORDER BY data_criacao DESC
                  LIMIT 10";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    // Marcar notificação como lida
    public function marcarNotificacaoLida($notificacao_id) {
        $query = "UPDATE notificacoes SET lida = 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $notificacao_id);
        return $stmt->execute();
    }

    // Calcular nota final da avaliação
    public function calcularNotaFinal($avaliacao_id) {
        $query = "SELECT r.*, p.peso 
                  FROM respostas r 
                  JOIN perguntas p ON r.pergunta_id = p.id 
                  WHERE r.avaliacao_id = :avaliacao_id AND p.tipo_resposta != 'texto'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':avaliacao_id', $avaliacao_id);
        $stmt->execute();
        
        $respostas = $stmt->fetchAll();
        
        if (empty($respostas)) return null;
        
        $soma = 0;
        $peso_total = 0;
        
        foreach ($respostas as $resposta) {
            $soma += $resposta['resposta_nota'] * $resposta['peso'];
            $peso_total += $resposta['peso'];
        }
        
        return $peso_total > 0 ? round($soma / $peso_total, 2) : null;
    }

    // Obter estatísticas do dashboard
    public function getEstatisticasDashboard($usuario_id, $tipo_usuario) {
        $estatisticas = [];
        
        if ($tipo_usuario === 'admin' || $tipo_usuario === 'rh') {
            // Estatísticas gerais
            $query = "SELECT 
                        COUNT(DISTINCT u.id) as total_usuarios,
                        (SELECT COUNT(*) FROM ciclos_avaliacao WHERE status = 'em_andamento') as total_ciclos,
                        (SELECT COUNT(*) FROM avaliacoes) as total_avaliacoes,
                        (SELECT COUNT(*) FROM avaliacoes WHERE status = 'concluida') as avaliacoes_concluidas
                      FROM usuarios u
                      WHERE u.ativo = 1";
            
            $stmt = $this->conn->query($query);
            $estatisticas['geral'] = $stmt->fetch();
            
        } elseif ($tipo_usuario === 'gestor') {
            // Estatísticas da equipe
            $query = "SELECT 
                        COUNT(DISTINCT u.id) as total_equipe,
                        (SELECT COUNT(*) FROM avaliacoes a WHERE a.avaliado_id IN 
                            (SELECT id FROM usuarios WHERE gestor_id = :gestor_id)) as total_avaliacoes,
                        (SELECT COUNT(*) FROM avaliacoes a WHERE a.avaliado_id IN 
                            (SELECT id FROM usuarios WHERE gestor_id = :gestor_id) AND a.status = 'pendente') as avaliacoes_pendentes,
                        (SELECT COUNT(*) FROM avaliacoes a WHERE a.avaliado_id IN 
                            (SELECT id FROM usuarios WHERE gestor_id = :gestor_id) AND a.status = 'concluida') as avaliacoes_concluidas
                      FROM usuarios u
                      WHERE u.gestor_id = :gestor_id AND u.ativo = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':gestor_id', $usuario_id);
            $stmt->execute();
            $estatisticas['equipe'] = $stmt->fetch();
        }
        
        // Avaliações pendentes do usuário
        $query = "SELECT COUNT(*) as pendentes 
                  FROM avaliacoes 
                  WHERE avaliador_id = :usuario_id AND status = 'pendente'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        $estatisticas['minhas_pendentes'] = $stmt->fetch()['pendentes'] ?? 0;
        
        return $estatisticas;
    }
}

$functions = new Functions();
?>
