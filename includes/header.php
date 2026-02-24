<?php
// includes/header.php
// NÃO PODE TER NENHUM ESPAÇO OU ECHO ANTES DO DOCTYPE

// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require dos arquivos necessários
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Verificar se usuário está logado
if (!$auth->isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

$user = $auth->getUser();
$notificacoes = $functions->getNotificacoesNaoLidas($user['id'] ?? 0);

// Definir módulo atual
$current_page = basename($_SERVER['PHP_SELF']);
$current_module = basename(dirname($_SERVER['PHP_SELF']));
$current_file = basename($_SERVER['PHP_SELF']);
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/index.php">
                <i class="bi bi-bar-chart-fill"></i> <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- DASHBOARD -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" 
                           href="<?php echo SITE_URL; ?>/index.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    
                    <!-- USUÁRIOS - com submenu para RH/Admin -->
                    <?php if ($auth->hasPermission(['admin', 'rh', 'gestor'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($current_module == 'usuarios') ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-people"></i> Usuários
                        </a>
                        <ul class="dropdown-menu">
                            <!-- Lista de usuários (todos veem) -->
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/usuarios/">
                                    <i class="bi bi-list-ul"></i> Lista de Usuários
                                </a>
                            </li>
                            
                            <!-- Estrutura Organizacional (apenas Admin e RH) -->
                            <?php if ($auth->hasPermission(['admin', 'rh'])): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">📋 Estrutura Organizacional</h6></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/usuarios/departamentos.php">
                                    <i class="bi bi-building"></i> Departamentos
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/usuarios/cargos.php">
                                    <i class="bi bi-person-badge"></i> Cargos
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/usuarios/vinculos.php">
                                    <i class="bi bi-link"></i> Vincular Cargos
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- CICLOS -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_module == 'ciclos') ? 'active' : ''; ?>" 
                           href="<?php echo SITE_URL; ?>/modules/ciclos/">
                            <i class="bi bi-calendar-check"></i> Ciclos
                        </a>
                    </li>
                    
                    <!-- AVALIAÇÕES -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_module == 'avaliacoes') ? 'active' : ''; ?>" 
                           href="<?php echo SITE_URL; ?>/modules/avaliacoes/">
                            <i class="bi bi-pencil-square"></i> Avaliações
                        </a>
                    </li>
                    
                    <!-- RELATÓRIOS (submenu) -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($current_module == 'relatorios') ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-graph-up"></i> Relatórios
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/relatorios/dashboard_executivo.php">
                                    <i class="bi bi-bar-chart-steps"></i> Dashboard Executivo
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/relatorios/dashboard_gestor.php">
                                    <i class="bi bi-people"></i> Dashboard Gestor
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/relatorios/dashboard_individual.php">
                                    <i class="bi bi-person"></i> Meu Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/relatorios/dashboard_analytics.php">
                                    <i class="bi bi-pie-chart"></i> Analytics de Respostas
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/relatorios/index.php">
                                    <i class="bi bi-file-text"></i> Relatórios Exportáveis
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- CALIBRAÇÃO -->
                    <?php if ($auth->hasPermission(['admin', 'rh'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_module == 'calibracao') ? 'active' : ''; ?>" 
                           href="<?php echo SITE_URL; ?>/modules/calibracao/">
                            <i class="bi bi-sliders2"></i> Calibração
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- PERGUNTAS -->
                    <?php if ($auth->hasPermission(['admin', 'rh'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_module == 'perguntas') ? 'active' : ''; ?>" 
                           href="<?php echo SITE_URL; ?>/modules/perguntas/">
                            <i class="bi bi-question-circle"></i> Perguntas
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- MÓDULO RH COMPLETO (com PDI e EMPRESAS) -->
                    <?php if ($auth->hasPermission(['admin', 'rh'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($current_module == 'rh' || $current_module == 'pdi' || $current_module == 'empresas') ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-building"></i> RH
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width: 320px; max-height: none; overflow-y: visible;">
                            <!-- Cabeçalho -->
                            <li class="px-3 py-2 bg-light bg-opacity-25 rounded-3 mb-2">
                                <span class="fw-bold text-primary"><i class="bi bi-building"></i> RECURSOS HUMANOS</span>
                            </li>
                            
                            <!-- ESTRUTURA (NOVO) -->
                            <li><h6 class="dropdown-header text-uppercase small fw-bold text-secondary px-3 mt-2">🏢 ESTRUTURA</h6></li>
                            <li>
                                <a class="dropdown-item rounded-3 mb-1 <?php echo ($current_module == 'empresas') ? 'active bg-primary text-white' : ''; ?>" 
                                   href="<?php echo SITE_URL; ?>/modules/empresas/">
                                    <i class="bi bi-building me-2"></i> Empresas (Matriz/Filiais)
                                </a>
                            </li>
                            
                            <!-- Gestão de Pessoas -->
                            <li><h6 class="dropdown-header text-uppercase small fw-bold text-secondary px-3 mt-2">👥 GESTÃO DE PESSOAS</h6></li>
                            <li>
                                <a class="dropdown-item rounded-3 mb-1 <?php echo ($current_file == 'advertencias.php') ? 'active bg-primary text-white' : ''; ?>" 
                                   href="<?php echo SITE_URL; ?>/modules/rh/advertencias.php">
                                    <i class="bi bi-exclamation-triangle me-2 <?php echo ($current_file == 'advertencias.php') ? 'text-white' : 'text-danger'; ?>"></i> 
                                    Advertências
                                    <span class="badge bg-danger bg-opacity-10 text-danger ms-2">⚠️</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-3 mb-1 <?php echo ($current_file == 'faltas.php') ? 'active bg-primary text-white' : ''; ?>" 
                                   href="<?php echo SITE_URL; ?>/modules/rh/faltas.php">
                                    <i class="bi bi-calendar-x me-2 <?php echo ($current_file == 'faltas.php') ? 'text-white' : 'text-warning'; ?>"></i> 
                                    Faltas
                                    <span class="badge bg-warning bg-opacity-10 text-warning ms-2">📅</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-3 mb-1 <?php echo ($current_file == 'integracao.php') ? 'active bg-primary text-white' : ''; ?>" 
                                   href="<?php echo SITE_URL; ?>/modules/rh/integracao.php">
                                    <i class="bi bi-person-badge me-2 <?php echo ($current_file == 'integracao.php') ? 'text-white' : 'text-info'; ?>"></i> 
                                    Integração
                                    <span class="badge bg-info bg-opacity-10 text-info ms-2">🆕</span>
                                </a>
                            </li>
                            
                            <li><hr class="dropdown-divider"></li>
                            
                            <!-- Carreira e Promoções -->
                            <li><h6 class="dropdown-header text-uppercase small fw-bold text-secondary px-3 mt-2">📈 CARREIRA</h6></li>
                            <li>
                                <a class="dropdown-item rounded-3 mb-1 <?php echo ($current_file == 'promocoes.php') ? 'active bg-primary text-white' : ''; ?>" 
                                   href="<?php echo SITE_URL; ?>/modules/rh/promocoes.php">
                                    <i class="bi bi-arrow-up-circle me-2 <?php echo ($current_file == 'promocoes.php') ? 'text-white' : 'text-success'; ?>"></i> 
                                    Promoções
                                    <span class="badge bg-success bg-opacity-10 text-success ms-2">📊</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-3 mb-1 <?php echo ($current_file == 'elegiveis.php') ? 'active bg-primary text-white' : ''; ?>" 
                                   href="<?php echo SITE_URL; ?>/modules/rh/elegiveis.php">
                                    <i class="bi bi-trophy me-2 <?php echo ($current_file == 'elegiveis.php') ? 'text-white' : 'text-warning'; ?>"></i> 
                                    Elegíveis
                                    <span class="badge bg-warning bg-opacity-10 text-warning ms-2">🏆</span>
                                </a>
                            </li>
                            
                            <li><hr class="dropdown-divider"></li>
                            
                            <!-- Desenvolvimento (PDI) -->
                            <li><h6 class="dropdown-header text-uppercase small fw-bold text-success px-3 mt-2">🎯 DESENVOLVIMENTO</h6></li>
                            <li>
                                <a class="dropdown-item rounded-3 mb-1 <?php echo ($current_module == 'pdi' && $current_file == 'index.php') ? 'active bg-primary text-white' : ''; ?>" 
                                   href="<?php echo SITE_URL; ?>/modules/pdi/index.php">
                                    <i class="bi bi-diagram-3 me-2 <?php echo ($current_module == 'pdi' && $current_file == 'index.php') ? 'text-white' : 'text-primary'; ?>"></i> 
                                    Dashboard PDI
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-3 mb-1 <?php echo ($current_module == 'pdi' && $current_file == 'listar.php') ? 'active bg-primary text-white' : ''; ?>" 
                                   href="<?php echo SITE_URL; ?>/modules/pdi/listar.php">
                                    <i class="bi bi-list-task me-2 <?php echo ($current_module == 'pdi' && $current_file == 'listar.php') ? 'text-white' : 'text-info'; ?>"></i> 
                                    Todos os PDIs
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-3 mb-1 <?php echo ($current_module == 'pdi' && $current_file == 'criar.php') ? 'active bg-primary text-white' : ''; ?>" 
                                   href="<?php echo SITE_URL; ?>/modules/pdi/criar.php">
                                    <i class="bi bi-plus-circle me-2 <?php echo ($current_module == 'pdi' && $current_file == 'criar.php') ? 'text-white' : 'text-success'; ?>"></i> 
                                    Novo PDI
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-3 mb-1" href="#" data-bs-toggle="modal" data-bs-target="#modalPdiInfo">
                                    <i class="bi bi-question-circle me-2 text-secondary"></i> 
                                    Sobre PDI
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- GESTORES também veem PDI (limitado) -->
                    <?php if ($auth->hasPermission(['gestor']) && !$auth->hasPermission(['admin', 'rh'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo ($current_module == 'pdi') ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-diagram-3"></i> PDI
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/pdi/index.php">
                                    <i class="bi bi-diagram-3"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/pdi/listar.php?meus=true">
                                    <i class="bi bi-people"></i> Minha Equipe
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/pdi/criar.php">
                                    <i class="bi bi-plus-circle"></i> Novo PDI
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Menu do usuário (lado direito) -->
                <ul class="navbar-nav">
                    <!-- Notificações -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell"></i>
                            <?php if (count($notificacoes) > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo count($notificacoes); ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                            <li><h6 class="dropdown-header">Notificações</h6></li>
                            <?php if (empty($notificacoes)): ?>
                            <li><span class="dropdown-item-text text-muted">Nenhuma notificação</span></li>
                            <?php else: ?>
                                <?php foreach (array_slice($notificacoes, 0, 5) as $notif): ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo $notif['link'] ?: '#'; ?>">
                                        <strong><?php echo $notif['titulo']; ?></strong><br>
                                        <small class="text-muted"><?php echo $notif['mensagem']; ?></small>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                                <?php if (count($notificacoes) > 5): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="#">Ver todas</a></li>
                                <?php endif; ?>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <!-- Usuário - Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?php echo explode(' ', $user['nome'] ?? 'Usuário')[0]; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/usuarios/perfil.php">
                                    <i class="bi bi-person"></i> Meu Perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/modules/usuarios/editar.php?id=<?php echo $user['id']; ?>">
                                    <i class="bi bi-pencil"></i> Editar Perfil
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Sair
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-4">
        <div class="container-fluid">
