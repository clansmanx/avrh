<?php
// modules/relatorios/exportar.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

$database = new Database();
$conn = $database->getConnection();
$auth = new Auth();
$functions = new Functions();

$auth->requirePermission(['admin', 'rh', 'gestor']);

$tipo = $_GET['tipo'] ?? '';
$formato = $_GET['formato'] ?? 'pdf';

// Função para gerar CSV
function gerarCSV($dados, $nome_arquivo) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nome_arquivo . '.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para UTF-8
    
    if (!empty($dados)) {
        // Cabeçalhos
        fputcsv($output, array_keys($dados[0]), ';');
        
        // Dados
        foreach ($dados as $linha) {
            fputcsv($output, $linha, ';');
        }
    }
    
    fclose($output);
    exit;
}

// Função para gerar Excel (HTML table)
function gerarExcel($dados, $titulo, $nome_arquivo) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $nome_arquivo . '.xls"');
    
    echo '<html>';
    echo '<head><meta charset="UTF-8"></head>';
    echo '<body>';
    echo '<h2>' . $titulo . '</h2>';
    echo '<table border="1">';
    
    if (!empty($dados)) {
        // Cabeçalho
        echo '<tr>';
        foreach (array_keys($dados[0]) as $cabecalho) {
            echo '<th>' . $cabecalho . '</th>';
        }
        echo '</tr>';
        
        // Dados
        foreach ($dados as $linha) {
            echo '<tr>';
            foreach ($linha as $valor) {
                echo '<td>' . $valor . '</td>';
            }
            echo '</tr>';
        }
    }
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    exit;
}

// Função para gerar PDF simples (HTML)
function gerarPDF($html, $nome_arquivo) {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nome_arquivo . '.html"');
    
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<style>';
    echo 'body { font-family: Arial, sans-serif; margin: 20px; }';
    echo 'h1 { color: #4e73df; }';
    echo 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
    echo 'th { background-color: #4e73df; color: white; padding: 10px; text-align: left; }';
    echo 'td { border: 1px solid #ddd; padding: 8px; }';
    echo 'tr:nth-child(even) { background-color: #f2f2f2; }';
    echo '.badge-success { background-color: #1cc88a; color: white; padding: 3px 8px; border-radius: 10px; }';
    echo '.badge-warning { background-color: #f6c23e; color: white; padding: 3px 8px; border-radius: 10px; }';
    echo '.badge-danger { background-color: #e74a3b; color: white; padding: 3px 8px; border-radius: 10px; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo $html;
    echo '</body>';
    echo '</html>';
    exit;
}

switch ($tipo) {
    case 'colaborador':
        $usuario_id = $_GET['usuario_id'] ?? 0;
        $periodo = $_GET['periodo'] ?? 'todos';
        
        // Buscar dados do colaborador
        $query = "SELECT u.*, c.nome as cargo_nome, d.nome as departamento_nome
                  FROM usuarios u
                  LEFT JOIN cargos c ON u.cargo_id = c.id
                  LEFT JOIN departamentos d ON u.departamento_id = d.id
                  WHERE u.id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $usuario_id);
        $stmt->execute();
        $colaborador = $stmt->fetch();
        
        if (!$colaborador) {
            $_SESSION['error'] = "Colaborador não encontrado";
            ob_end_clean();
            header('Location: index.php');
            exit;
        }
        
        // Buscar avaliações
        $query_avaliacoes = "SELECT a.*, c.nome as ciclo_nome, c.data_inicio, c.data_fim,
                                     av.nome as avaliador_nome
                              FROM avaliacoes a
                              JOIN ciclos_avaliacao c ON a.ciclo_id = c.id
                              JOIN usuarios av ON a.avaliador_id = av.id
                              WHERE a.avaliado_id = :avaliado_id AND a.status = 'concluida'";
        
        if ($periodo == 'ultimo') {
            $query_avaliacoes .= " ORDER BY c.data_fim DESC LIMIT 1";
        } elseif ($periodo == 'ano') {
            $query_avaliacoes .= " AND c.data_inicio >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        } else {
            $query_avaliacoes .= " ORDER BY c.data_inicio DESC";
        }
        
        $stmt = $conn->prepare($query_avaliacoes);
        $stmt->bindParam(':avaliado_id', $usuario_id);
        $stmt->execute();
        $avaliacoes = $stmt->fetchAll();
        
        // Preparar dados para relatório
        $dados_relatorio = [];
        foreach ($avaliacoes as $av) {
            $dados_relatorio[] = [
                'Ciclo' => $av['ciclo_nome'],
                'Período' => $functions->formatDate($av['data_inicio']) . ' a ' . $functions->formatDate($av['data_fim']),
                'Avaliador' => $av['avaliador_nome'],
                'Nota Final' => number_format($av['nota_final'], 2),
                'Data Conclusão' => $functions->formatDate($av['data_conclusao'])
            ];
        }
        
        $nome_arquivo = 'relatorio_' . preg_replace('/[^a-zA-Z0-9]/', '_', $colaborador['nome']);
        
        if ($formato == 'csv') {
            gerarCSV($dados_relatorio, $nome_arquivo);
        } elseif ($formato == 'excel') {
            gerarExcel($dados_relatorio, 'Relatório de ' . $colaborador['nome'], $nome_arquivo);
        } else {
            // Gerar HTML para PDF
            $html = '<h1>Relatório de Desempenho</h1>';
            $html .= '<h2>' . $colaborador['nome'] . '</h2>';
            $html .= '<p><strong>Cargo:</strong> ' . $colaborador['cargo_nome'] . '</p>';
            $html .= '<p><strong>Departamento:</strong> ' . $colaborador['departamento_nome'] . '</p>';
            $html .= '<p><strong>Email:</strong> ' . $colaborador['email'] . '</p>';
            
            if (empty($avaliacoes)) {
                $html .= '<p>Nenhuma avaliação encontrada para este período.</p>';
            } else {
                $html .= '<table>';
                $html .= '<tr>';
                foreach (array_keys($dados_relatorio[0] ?? []) as $cabecalho) {
                    $html .= '<th>' . $cabecalho . '</th>';
                }
                $html .= '</tr>';
                
                foreach ($dados_relatorio as $linha) {
                    $html .= '<tr>';
                    foreach ($linha as $valor) {
                        $html .= '<td>' . $valor . '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</table>';
            }
            
            gerarPDF($html, $nome_arquivo);
        }
        break;
        
    case 'departamento':
        $departamento_id = $_GET['departamento_id'] ?? 0;
        $ciclo_id = $_GET['ciclo_id'] ?? null;
        
        // Buscar dados do departamento
        $query = "SELECT * FROM departamentos WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $departamento_id);
        $stmt->execute();
        $departamento = $stmt->fetch();
        
        if (!$departamento) {
            $_SESSION['error'] = "Departamento não encontrado";
            ob_end_clean();
            header('Location: index.php');
            exit;
        }
        
        // Buscar avaliações do departamento
        $query = "SELECT a.*, u.nome as colaborador_nome, u.email, c.nome as cargo_nome,
                         ci.nome as ciclo_nome
                  FROM avaliacoes a
                  JOIN usuarios u ON a.avaliado_id = u.id
                  LEFT JOIN cargos c ON u.cargo_id = c.id
                  JOIN ciclos_avaliacao ci ON a.ciclo_id = ci.id
                  WHERE u.departamento_id = :departamento_id AND a.status = 'concluida'";
        
        if ($ciclo_id) {
            $query .= " AND a.ciclo_id = :ciclo_id";
        }
        
        $query .= " ORDER BY a.nota_final DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':departamento_id', $departamento_id);
        if ($ciclo_id) {
            $stmt->bindParam(':ciclo_id', $ciclo_id);
        }
        $stmt->execute();
        $avaliacoes = $stmt->fetchAll();
        
        // Calcular estatísticas
        $total = count($avaliacoes);
        $soma_notas = array_sum(array_column($avaliacoes, 'nota_final'));
        $media = $total > 0 ? $soma_notas / $total : 0;
        
        // Preparar dados
        $dados_relatorio = [];
        foreach ($avaliacoes as $av) {
            $dados_relatorio[] = [
                'Colaborador' => $av['colaborador_nome'],
                'Cargo' => $av['cargo_nome'] ?? 'Não definido',
                'Ciclo' => $av['ciclo_nome'],
                'Nota' => number_format($av['nota_final'], 2),
                'Email' => $av['email']
            ];
        }
        
        // Adicionar linha de resumo
        $dados_relatorio[] = [
            'Colaborador' => 'MÉDIA DO DEPARTAMENTO',
            'Cargo' => '',
            'Ciclo' => '',
            'Nota' => number_format($media, 2),
            'Email' => ''
        ];
        
        $nome_arquivo = 'relatorio_' . preg_replace('/[^a-zA-Z0-9]/', '_', $departamento['nome']);
        
        if ($formato == 'csv') {
            gerarCSV($dados_relatorio, $nome_arquivo);
        } elseif ($formato == 'excel') {
            gerarExcel($dados_relatorio, 'Relatório do Departamento: ' . $departamento['nome'], $nome_arquivo);
        } else {
            // Gerar HTML para PDF
            $html = '<h1>Relatório por Departamento</h1>';
            $html .= '<h2>' . $departamento['nome'] . '</h2>';
            
            if (empty($avaliacoes)) {
                $html .= '<p>Nenhuma avaliação encontrada para este departamento.</p>';
            } else {
                $html .= '<p><strong>Total de Avaliações:</strong> ' . $total . '</p>';
                $html .= '<p><strong>Média do Departamento:</strong> ' . number_format($media, 2) . '</p>';
                
                $html .= '<table>';
                $html .= '<tr>';
                foreach (array_keys($dados_relatorio[0] ?? []) as $cabecalho) {
                    $html .= '<th>' . $cabecalho . '</th>';
                }
                $html .= '</tr>';
                
                foreach ($dados_relatorio as $linha) {
                    $html .= '<tr>';
                    foreach ($linha as $valor) {
                        $html .= '<td>' . $valor . '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</table>';
            }
            
            gerarPDF($html, $nome_arquivo);
        }
        break;
        
    case 'ciclo':
        $ciclo_id = $_GET['ciclo_id'] ?? 0;
        $agrupar = $_GET['agrupar'] ?? 'departamento';
        
        // Buscar dados do ciclo
        $query = "SELECT * FROM ciclos_avaliacao WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $ciclo_id);
        $stmt->execute();
        $ciclo = $stmt->fetch();
        
        if (!$ciclo) {
            $_SESSION['error'] = "Ciclo não encontrado";
            ob_end_clean();
            header('Location: index.php');
            exit;
        }
        
        // Buscar avaliações do ciclo
        $query = "SELECT a.*, u.nome as colaborador_nome, u.email,
                         c.nome as cargo_nome, d.nome as departamento_nome,
                         g.nome as gestor_nome
                  FROM avaliacoes a
                  JOIN usuarios u ON a.avaliado_id = u.id
                  LEFT JOIN cargos c ON u.cargo_id = c.id
                  LEFT JOIN departamentos d ON u.departamento_id = d.id
                  LEFT JOIN usuarios g ON u.gestor_id = g.id
                  WHERE a.ciclo_id = :ciclo_id AND a.status = 'concluida'
                  ORDER BY a.nota_final DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':ciclo_id', $ciclo_id);
        $stmt->execute();
        $avaliacoes = $stmt->fetchAll();
        
        // Agrupar dados
        $dados_agrupados = [];
        foreach ($avaliacoes as $av) {
            $chave = $agrupar == 'departamento' ? $av['departamento_nome'] : 
                    ($agrupar == 'cargo' ? $av['cargo_nome'] : $av['gestor_nome']);
            
            $chave = $chave ?: 'Não definido';
            
            if (!isset($dados_agrupados[$chave])) {
                $dados_agrupados[$chave] = [
                    'total' => 0,
                    'soma' => 0,
                    'colaboradores' => []
                ];
            }
            
            $dados_agrupados[$chave]['total']++;
            $dados_agrupados[$chave]['soma'] += $av['nota_final'];
            $dados_agrupados[$chave]['colaboradores'][] = $av;
        }
        
        // Preparar dados para relatório
        $dados_relatorio = [];
        
        foreach ($dados_agrupados as $grupo_nome => $grupo) {
            $media_grupo = $grupo['soma'] / $grupo['total'];
            
            foreach ($grupo['colaboradores'] as $colab) {
                $dados_relatorio[] = [
                    'Grupo' => $grupo_nome,
                    'Colaborador' => $colab['colaborador_nome'],
                    'Cargo' => $colab['cargo_nome'] ?? '-',
                    'Departamento' => $colab['departamento_nome'] ?? '-',
                    'Nota' => number_format($colab['nota_final'], 2)
                ];
            }
            
            $dados_relatorio[] = [
                'Grupo' => 'MÉDIA DO GRUPO',
                'Colaborador' => $grupo_nome,
                'Cargo' => '',
                'Departamento' => '',
                'Nota' => number_format($media_grupo, 2)
            ];
            
            $dados_relatorio[] = [
                'Grupo' => '---',
                'Colaborador' => '---',
                'Cargo' => '---',
                'Departamento' => '---',
                'Nota' => '---'
            ];
        }
        
        $nome_arquivo = 'relatorio_ciclo_' . preg_replace('/[^a-zA-Z0-9]/', '_', $ciclo['nome']);
        
        if ($formato == 'csv') {
            gerarCSV($dados_relatorio, $nome_arquivo);
        } elseif ($formato == 'excel') {
            gerarExcel($dados_relatorio, 'Relatório do Ciclo: ' . $ciclo['nome'], $nome_arquivo);
        } else {
            // Gerar HTML para PDF
            $html = '<h1>Relatório do Ciclo</h1>';
            $html .= '<h2>' . $ciclo['nome'] . '</h2>';
            $html .= '<p><strong>Período:</strong> ' . $functions->formatDate($ciclo['data_inicio']) . ' a ' . $functions->formatDate($ciclo['data_fim']) . '</p>';
            $html .= '<p><strong>Tipo:</strong> ' . $ciclo['tipo'] . '°</p>';
            
            if (empty($avaliacoes)) {
                $html .= '<p>Nenhuma avaliação concluída neste ciclo.</p>';
            } else {
                $html .= '<table>';
                $html .= '<tr>';
                $html .= '<th>Grupo</th>';
                $html .= '<th>Colaborador</th>';
                $html .= '<th>Cargo</th>';
                $html .= '<th>Departamento</th>';
                $html .= '<th>Nota</th>';
                $html .= '</tr>';
                
                foreach ($dados_relatorio as $linha) {
                    $html .= '<tr>';
                    $html .= '<td>' . $linha['Grupo'] . '</td>';
                    $html .= '<td>' . $linha['Colaborador'] . '</td>';
                    $html .= '<td>' . $linha['Cargo'] . '</td>';
                    $html .= '<td>' . $linha['Departamento'] . '</td>';
                    $html .= '<td>' . $linha['Nota'] . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</table>';
            }
            
            gerarPDF($html, $nome_arquivo);
        }
        break;
        
    default:
        $_SESSION['error'] = "Tipo de relatório inválido";
        ob_end_clean();
        header('Location: index.php');
        exit;
}

ob_end_flush();
?>
