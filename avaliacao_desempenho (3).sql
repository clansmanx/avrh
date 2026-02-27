-- phpMyAdmin SQL Dump
-- version 5.1.4
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 27/02/2026 às 15:54
-- Versão do servidor: 10.11.6-MariaDB-0+deb12u1
-- Versão do PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `avaliacao_desempenho`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `advertencias`
--

CREATE TABLE `advertencias` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('orientacao','advertencia') DEFAULT 'orientacao',
  `data_registro` date NOT NULL,
  `motivo` text NOT NULL,
  `arquivo_anexo` varchar(255) DEFAULT NULL,
  `registrado_por` int(11) NOT NULL,
  `data_criacao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `avaliacoes`
--

CREATE TABLE `avaliacoes` (
  `id` int(11) NOT NULL,
  `ciclo_id` int(11) NOT NULL,
  `avaliado_id` int(11) NOT NULL,
  `avaliador_id` int(11) NOT NULL,
  `formulario_id` int(11) NOT NULL,
  `status` enum('pendente','em_andamento','concluida') DEFAULT 'pendente',
  `data_inicio` datetime DEFAULT NULL,
  `data_conclusao` datetime DEFAULT NULL,
  `nota_final` decimal(5,2) DEFAULT NULL,
  `feedback_geral` text DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `avaliacoes`
--

INSERT INTO `avaliacoes` (`id`, `ciclo_id`, `avaliado_id`, `avaliador_id`, `formulario_id`, `status`, `data_inicio`, `data_conclusao`, `nota_final`, `feedback_geral`, `data_criacao`) VALUES
(176, 50, 11, 13, 1, 'pendente', NULL, NULL, NULL, NULL, '2026-02-26 19:08:40'),
(177, 50, 12, 13, 1, 'concluida', '2026-02-26 16:09:14', '2026-02-26 16:09:14', '300.00', NULL, '2026-02-26 19:08:40');

-- --------------------------------------------------------

--
-- Estrutura para tabela `calibracao`
--

CREATE TABLE `calibracao` (
  `id` int(11) NOT NULL,
  `ciclo_id` int(11) NOT NULL,
  `avaliacao_id` int(11) NOT NULL,
  `usuario_calibrador_id` int(11) NOT NULL,
  `nota_original` decimal(5,2) DEFAULT NULL,
  `nota_calibrada` decimal(5,2) DEFAULT NULL,
  `justificativa` text DEFAULT NULL,
  `data_calibracao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cargos`
--

CREATE TABLE `cargos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `nivel` varchar(50) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cargos`
--

INSERT INTO `cargos` (`id`, `nome`, `nivel`, `descricao`, `ativo`, `data_criacao`) VALUES
(56, 'Piloto Titular', 'Sênior', 'Piloto principal da equipe', 1, '2026-02-25 11:41:52'),
(57, 'Piloto Reserva', 'Pleno', 'Piloto que substitui em caso de necessidade', 1, '2026-02-25 11:41:52'),
(58, 'Piloto de Desenvolvimento', 'Júnior', 'Piloto que desenvolve o carro no simulador', 1, '2026-02-25 11:41:52'),
(59, 'Chefe de Equipe', 'Executivo', 'Líder máximo da equipe', 1, '2026-02-25 11:41:52'),
(60, 'Diretor Técnico', 'Executivo', 'Responsável por toda a engenharia', 1, '2026-02-25 11:41:52'),
(61, 'Engenheiro Chefe de Corrida', 'Sênior', 'Engenheiro responsável pelo piloto', 1, '2026-02-25 11:41:52'),
(62, 'Engenheiro de Performance', 'Sênior', 'Otimização do desempenho do carro', 1, '2026-02-25 11:41:52'),
(63, 'Estrategista Chefe', 'Sênior', 'Responsável pela estratégia de corrida', 1, '2026-02-25 11:41:52'),
(64, 'Analista de Dados Sênior', 'Sênior', 'Análise avançada de telemetria', 1, '2026-02-25 11:41:52'),
(65, 'Mecânico Chefe', 'Pleno', 'Líder da equipe de mecânicos', 1, '2026-02-25 11:41:52'),
(66, 'Troca de Pneus Dianteiro', 'Operacional', 'Mecânico especializado', 1, '2026-02-25 11:41:52'),
(67, 'Troca de Pneus Traseiro', 'Operacional', 'Mecânico especializado', 1, '2026-02-25 11:41:52'),
(68, 'Operador de Macaco', 'Operacional', 'Opera o macaco hidráulico', 1, '2026-02-25 11:41:52'),
(69, 'Coordenador de Logística', 'Pleno', 'Gerencia transporte de equipamentos', 1, '2026-02-25 11:41:52'),
(70, 'Chefe de Marketing', 'Sênior', 'Gestão de patrocínios e marca', 1, '2026-02-25 11:41:52'),
(71, 'Assessor de Imprensa', 'Pleno', 'Relações com a mídia', 1, '2026-02-25 11:41:52'),
(72, 'Nutricionista Esportivo', 'Pleno', 'Nutrição dos pilotos e equipe', 1, '2026-02-25 11:41:52'),
(73, 'Preparador Físico', 'Pleno', 'Condicionamento físico dos pilotos', 1, '2026-02-25 11:41:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cargo_departamento`
--

CREATE TABLE `cargo_departamento` (
  `id` int(11) NOT NULL,
  `cargo_id` int(11) NOT NULL,
  `departamento_id` int(11) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cargo_departamento`
--

INSERT INTO `cargo_departamento` (`id`, `cargo_id`, `departamento_id`, `ativo`, `data_criacao`) VALUES
(45, 65, 41, 1, '2026-02-25 11:42:06'),
(46, 66, 41, 1, '2026-02-25 11:42:06'),
(47, 67, 41, 1, '2026-02-25 11:42:06'),
(48, 68, 41, 1, '2026-02-25 11:42:06'),
(49, 60, 32, 1, '2026-02-25 11:42:06'),
(50, 61, 32, 1, '2026-02-25 11:42:06'),
(51, 62, 32, 1, '2026-02-25 11:42:06'),
(52, 59, 31, 1, '2026-02-25 11:42:06'),
(53, 63, 34, 1, '2026-02-25 11:42:06'),
(54, 64, 35, 1, '2026-02-25 11:42:06'),
(55, 57, 31, 1, '2026-02-25 13:29:52'),
(56, 56, 31, 1, '2026-02-25 13:29:59');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ciclos_avaliacao`
--

CREATE TABLE `ciclos_avaliacao` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `tipo` enum('90','180','360') DEFAULT '180',
  `empresa_id` int(11) DEFAULT NULL,
  `formulario_id` int(11) DEFAULT NULL,
  `status` enum('planejado','em_andamento','finalizado','cancelado') DEFAULT 'planejado',
  `configuracao` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuracao`)),
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `ciclos_avaliacao`
--

INSERT INTO `ciclos_avaliacao` (`id`, `nome`, `descricao`, `data_inicio`, `data_fim`, `tipo`, `empresa_id`, `formulario_id`, `status`, `configuracao`, `data_criacao`, `data_atualizacao`) VALUES
(50, 'Temporada 2000', 'Avaliação temporada 2000', '2000-02-01', '2000-10-31', '180', NULL, 1, 'em_andamento', '{\"autoavaliacao_peso\":1,\"gestor_peso\":2,\"pares_peso\":1,\"subordinados_peso\":1,\"permite_comentarios\":false,\"anonimo\":false,\"obrigar_justificativa\":false}', '2026-02-26 19:08:04', '2026-02-26 19:08:40');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ciclo_participantes`
--

CREATE TABLE `ciclo_participantes` (
  `id` int(11) NOT NULL,
  `ciclo_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_participacao` enum('avaliado','avaliador') DEFAULT 'avaliado',
  `status` enum('pendente','em_andamento','concluido') DEFAULT 'pendente',
  `data_conclusao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `ciclo_participantes`
--

INSERT INTO `ciclo_participantes` (`id`, `ciclo_id`, `usuario_id`, `tipo_participacao`, `status`, `data_conclusao`) VALUES
(465, 50, 13, 'avaliador', 'pendente', NULL),
(466, 50, 11, 'avaliado', 'pendente', NULL),
(467, 50, 12, 'avaliado', 'pendente', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `competencias`
--

CREATE TABLE `competencias` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo` enum('comportamental','tecnica','organizacional') DEFAULT 'comportamental',
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `competencias`
--

INSERT INTO `competencias` (`id`, `nome`, `descricao`, `tipo`, `ativo`, `data_criacao`) VALUES
(75, 'Apresentação Pessoal', 'Cuidados com aparência e uniforme', 'comportamental', 1, '2026-02-18 13:37:53'),
(76, 'Atendimento ao Cliente', 'Qualidade no atendimento', 'organizacional', 1, '2026-02-18 13:37:53'),
(77, 'Cultura Organizacional', 'Conhecimento da empresa', 'organizacional', 1, '2026-02-18 13:37:53'),
(78, 'Disciplina', 'Respeito a hierarquia e regras', 'comportamental', 1, '2026-02-18 13:37:53'),
(79, 'Engajamento', 'Desejo de crescimento', 'comportamental', 1, '2026-02-18 13:37:53'),
(80, 'Trabalho em Equipe', 'Relacionamento com colegas', 'comportamental', 1, '2026-02-18 13:37:53'),
(81, 'Liderança', 'Capacidade de auxiliar novos', 'comportamental', 1, '2026-02-18 13:37:53'),
(82, 'Proatividade', 'Iniciativa própria', 'comportamental', 1, '2026-02-18 13:37:53'),
(83, 'Produtividade', 'Qualidade e quantidade de trabalho', 'tecnica', 1, '2026-02-18 13:37:53'),
(84, 'Responsabilidade', 'Zelo com materiais e instalações', 'organizacional', 1, '2026-02-18 13:37:53'),
(85, 'Assiduidade', 'Frequência e pontualidade', 'comportamental', 1, '2026-02-18 13:37:53'),
(86, 'Conduta Profissional', 'Comportamento ético', 'comportamental', 1, '2026-02-18 13:37:53');

-- --------------------------------------------------------

--
-- Estrutura para tabela `departamentos`
--

CREATE TABLE `departamentos` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `departamentos`
--

INSERT INTO `departamentos` (`id`, `empresa_id`, `nome`, `descricao`, `data_criacao`, `ativo`) VALUES
(31, NULL, 'Pista e Corrida', 'Equipe que acompanha os carros nos GPs', '2026-02-25 11:41:31', 1),
(32, NULL, 'Engenharia de Chassi', 'Desenvolvimento do chassi e aerodinâmica', '2026-02-25 11:41:31', 1),
(33, NULL, 'Engenharia de Powertrain', 'Desenvolvimento do motor e câmbio', '2026-02-25 11:41:31', 1),
(34, NULL, 'Estratégia de Corrida', 'Planejamento estratégico durante as provas', '2026-02-25 11:41:31', 1),
(35, NULL, 'Telemetria e Dados', 'Análise de dados dos carros', '2026-02-25 11:41:31', 1),
(36, NULL, 'Logística e Transporte', 'Transporte dos equipamentos entre GPs', '2026-02-25 11:41:31', 1),
(37, NULL, 'Marketing e Patrocínios', 'Gestão de patrocinadores e marca', '2026-02-25 11:41:31', 1),
(38, NULL, 'Recursos Humanos', 'Gestão de pessoas da equipe', '2026-02-25 11:41:31', 1),
(39, NULL, 'Financeiro', 'Gestão financeira da equipe', '2026-02-25 11:41:31', 1),
(40, NULL, 'Comunicação e Mídia', 'Relações com a imprensa', '2026-02-25 11:41:31', 1),
(41, NULL, 'Pit Stop Crew', 'Equipe de troca de pneus e reparos rápidos', '2026-02-25 11:41:31', 1),
(42, NULL, 'Simulador', 'Operação do simulador de pilotagem', '2026-02-25 11:41:31', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `empresas`
--

CREATE TABLE `empresas` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('matriz','filial') DEFAULT 'filial',
  `cnpj` varchar(18) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `empresas`
--

INSERT INTO `empresas` (`id`, `nome`, `tipo`, `cnpj`, `endereco`, `cidade`, `estado`, `telefone`, `ativo`, `data_criacao`) VALUES
(17, 'Scuderia Ferrari', 'matriz', '01.234.567/0001-90', NULL, 'Maranello', 'IT', NULL, 1, '2026-02-25 11:37:31'),
(18, 'Red Bull Racing', 'matriz', '02.345.678/0001-01', NULL, 'Milton Keynes', 'UK', NULL, 1, '2026-02-25 11:37:31'),
(19, 'Mercedes-AMG Petronas', 'matriz', '03.456.789/0001-12', NULL, 'Brackley', 'UK', NULL, 1, '2026-02-25 11:37:31'),
(20, 'McLaren F1 Team', 'filial', '04.567.890/0001-23', NULL, 'Woking', 'UK', NULL, 1, '2026-02-25 11:37:31'),
(23, 'Williams Racing', 'filial', '07.890.123/0001-56', NULL, 'Grove', 'UK', NULL, 1, '2026-02-25 11:37:31'),
(25, 'Alfa Romeo Racing', 'filial', '09.012.345/0001-78', NULL, 'Hinwil', 'CH', NULL, 1, '2026-02-25 11:37:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `faltas`
--

CREATE TABLE `faltas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_falta` date NOT NULL,
  `justificada` tinyint(1) DEFAULT 0,
  `justificativa` text DEFAULT NULL,
  `registrado_por` int(11) NOT NULL,
  `data_criacao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `formularios`
--

CREATE TABLE `formularios` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo` enum('autoavaliacao','gestor','pares','subordinados','360','rotina','rh') DEFAULT 'autoavaliacao',
  `peso` int(11) DEFAULT 1,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `formularios`
--

INSERT INTO `formularios` (`id`, `nome`, `descricao`, `tipo`, `peso`, `ativo`, `data_criacao`) VALUES
(1, 'Autoavaliação 180°', 'Formulário de autoavaliação para ciclo 180°', 'autoavaliacao', 1, 1, '2026-02-13 16:38:41'),
(2, 'Avaliação do Gestor 180°', 'Formulário de avaliação do gestor', 'gestor', 2, 1, '2026-02-13 16:38:41'),
(3, 'Avaliação 360° Completa', 'Avaliação com múltiplas perspectivas', '360', 1, 1, '2026-02-13 16:38:41'),
(15, 'Avaliação de Rotina', 'Avaliação de rotina e comportamento do colaborador', 'rotina', 1, 1, '2026-02-18 13:09:36'),
(16, 'Avaliação de RH', 'Avaliação de conformidade e disciplina pelo RH', 'rh', 1, 1, '2026-02-18 13:09:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_promocoes`
--

CREATE TABLE `historico_promocoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `cargo_anterior_id` int(11) DEFAULT NULL,
  `cargo_novo_id` int(11) NOT NULL,
  `data_promocao` date NOT NULL,
  `tipo_promocao` enum('cargo','nivel') DEFAULT 'cargo',
  `media_rotina` decimal(5,2) DEFAULT NULL,
  `media_rh` decimal(5,2) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `aprovado_por` int(11) NOT NULL,
  `data_criacao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `integracao`
--

CREATE TABLE `integracao` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_integracao` date NOT NULL,
  `conhece_missao` tinyint(1) DEFAULT 0,
  `conhece_visao` tinyint(1) DEFAULT 0,
  `conhece_valores` tinyint(1) DEFAULT 0,
  `assinou_termo` tinyint(1) DEFAULT 0,
  `observacoes` text DEFAULT NULL,
  `realizado_por` int(11) NOT NULL,
  `data_criacao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('avaliacao_pendente','prazo_proximo','avaliacao_concluida','calibracao','sistema') DEFAULT 'avaliacao_pendente',
  `titulo` varchar(200) NOT NULL,
  `mensagem` text DEFAULT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pdi`
--

CREATE TABLE `pdi` (
  `id` int(11) NOT NULL,
  `colaborador_id` int(11) NOT NULL,
  `ciclo_id` int(11) DEFAULT NULL,
  `gestor_responsavel_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `data_criacao` date NOT NULL,
  `data_revisao` date DEFAULT NULL,
  `data_conclusao` date DEFAULT NULL,
  `status` enum('ativo','em_andamento','concluido','cancelado') DEFAULT 'ativo',
  `observacoes_gerais` text DEFAULT NULL,
  `progresso_geral` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pdi_acoes`
--

CREATE TABLE `pdi_acoes` (
  `id` int(11) NOT NULL,
  `pdi_id` int(11) NOT NULL,
  `meta_id` int(11) DEFAULT NULL,
  `tipo` enum('treinamento','mentoria','projeto','leitura','feedback','job_rotation','workshop','outro') DEFAULT 'treinamento',
  `titulo` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `recurso_necessario` text DEFAULT NULL,
  `custo_estimado` decimal(10,2) DEFAULT NULL,
  `progresso` int(11) DEFAULT 0,
  `status` enum('pendente','em_andamento','concluida','cancelada') DEFAULT 'pendente',
  `evidencia` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `progresso_calculado` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pdi_acompanhamentos`
--

CREATE TABLE `pdi_acompanhamentos` (
  `id` int(11) NOT NULL,
  `pdi_id` int(11) NOT NULL,
  `data_acompanhamento` date NOT NULL,
  `responsavel_id` int(11) NOT NULL,
  `progresso_geral` int(11) DEFAULT NULL,
  `topicos_discutidos` text DEFAULT NULL,
  `dificuldades_encontradas` text DEFAULT NULL,
  `proximos_passos` text DEFAULT NULL,
  `nova_data_revisao` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pdi_checklists`
--

CREATE TABLE `pdi_checklists` (
  `id` int(11) NOT NULL,
  `tipo` enum('meta','acao') NOT NULL,
  `item_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `data_prevista` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pdi_checklist_conclusoes`
--

CREATE TABLE `pdi_checklist_conclusoes` (
  `id` int(11) NOT NULL,
  `checklist_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `concluido` tinyint(1) DEFAULT 1,
  `data_conclusao` timestamp NULL DEFAULT current_timestamp(),
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pdi_competencias`
--

CREATE TABLE `pdi_competencias` (
  `id` int(11) NOT NULL,
  `pdi_id` int(11) NOT NULL,
  `competencia_id` int(11) NOT NULL,
  `nivel_atual` decimal(5,2) DEFAULT NULL,
  `nivel_desejado` decimal(5,2) DEFAULT NULL,
  `prioridade` enum('baixa','media','alta') DEFAULT 'media',
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pdi_historico`
--

CREATE TABLE `pdi_historico` (
  `id` int(11) NOT NULL,
  `pdi_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `acao` varchar(50) NOT NULL,
  `descricao` text NOT NULL,
  `data_acao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pdi_metas`
--

CREATE TABLE `pdi_metas` (
  `id` int(11) NOT NULL,
  `pdi_id` int(11) NOT NULL,
  `competencia_id` int(11) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  `criterio_sucesso` text NOT NULL,
  `data_prazo` date NOT NULL,
  `peso` int(11) DEFAULT 1,
  `prioridade` enum('baixa','media','alta') DEFAULT 'media',
  `progresso` int(11) DEFAULT 0,
  `status` enum('pendente','em_andamento','concluida','cancelada') DEFAULT 'pendente',
  `observacoes` text DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `progresso_calculado` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `perguntas`
--

CREATE TABLE `perguntas` (
  `id` int(11) NOT NULL,
  `formulario_id` int(11) NOT NULL,
  `competencia_id` int(11) DEFAULT NULL,
  `texto` text NOT NULL,
  `tipo_resposta` enum('escala_1_5','texto','nota','sim_nao') DEFAULT 'escala_1_5',
  `peso` int(11) DEFAULT 1,
  `ordem` int(11) DEFAULT 0,
  `obrigatorio` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `perguntas`
--

INSERT INTO `perguntas` (`id`, `formulario_id`, `competencia_id`, `texto`, `tipo_resposta`, `peso`, `ordem`, `obrigatorio`) VALUES
(24, 15, 75, 'Cuida bem da sua aparência?', 'sim_nao', 5, 1, 1),
(25, 15, 76, 'Disponibiliza atenção total ao cliente quando requisitado?', 'sim_nao', 10, 2, 1),
(26, 15, 77, 'Conhece bem a história da empresa?', 'sim_nao', 10, 3, 1),
(27, 15, 78, 'É disciplinado com a hierarquia?', 'sim_nao', 10, 4, 1),
(28, 15, 78, 'Conhece e faz uso das regras da empresa?', 'sim_nao', 10, 5, 1),
(29, 15, 79, 'Demonstra desejo de crescer dentro da empresa?', 'sim_nao', 5, 6, 1),
(30, 15, 80, 'Tem um bom relacionamento com os colegas de equipe?', 'sim_nao', 5, 7, 1),
(31, 15, 81, 'Tem facilidade em auxiliar um colaborador novo?', 'sim_nao', 10, 8, 1),
(32, 15, 82, 'Buscou recurso próprio para crescer em sua área?', 'sim_nao', 5, 9, 1),
(33, 15, 83, 'Realiza tarefas de forma completa (Qualidade + Produtividade)?', 'sim_nao', 10, 10, 1),
(34, 15, 84, 'Tem zelo pelo material disponibilizado?', 'sim_nao', 10, 11, 1),
(35, 15, 84, 'Zela pelas instalações e contribui para a limpeza?', 'sim_nao', 10, 12, 1),
(36, 16, 75, 'Colaborador devidamente uniformizado e seguindo o padrão?', 'sim_nao', 5, 1, 1),
(37, 16, 85, 'Cumpriu integralmente a assiduidade, sem faltas injustificadas?', 'sim_nao', 30, 2, 1),
(38, 16, 76, 'Realizou atendimentos de excelência, sem gerar reclamações de clientes?', 'sim_nao', 20, 3, 1),
(39, 16, 86, 'Manteve conduta profissional exemplar, sem registro de advertências?', 'sim_nao', 10, 4, 1),
(40, 16, 86, 'Respeitou todas as normas disciplinares, sem registros de advertência?', 'sim_nao', 10, 5, 1),
(41, 16, 77, 'Alinhado com Missão, Visão e Valores (Manual do Colaborador)?', 'sim_nao', 20, 6, 1),
(42, 16, 78, 'O ponto está alinhado com a cultura da empresa?', 'sim_nao', 5, 7, 1),
(55, 1, 83, 'Como você avalia sua capacidade de cumprir prazos?', 'escala_1_5', 1, 1, 1),
(69, 1, 80, 'Como você avalia sua comunicação com a equipe?', 'escala_1_5', 1, 2, 1),
(70, 1, 82, 'Como você avalia sua proatividade no trabalho?\'', 'escala_1_5', 1, 3, 1),
(71, 1, 84, 'Como você avalia sua capacidade de resolver problemas?', 'escala_1_5', 1, 4, 1),
(72, 1, 80, 'Como você avalia seu trabalho em equipe?', 'escala_1_5', 1, 5, 1),
(73, 1, 83, 'Você cumpre os prazos estabelecidos?', 'escala_1_5', 1, 6, 1),
(74, 1, 86, 'Você busca ajuda quando necessário?', 'escala_1_5', 1, 7, 1),
(75, 1, 80, 'Você compartilha conhecimento com a equipe?', 'escala_1_5', 1, 8, 1),
(76, 1, 79, 'Você atinge as metas propostas?', 'escala_1_5', 1, 9, 1),
(77, 1, 79, 'Você se considera engajado com a empresa?', 'escala_1_5', 1, 10, 1),
(79, 3, 80, 'Como você avalia a colaboração do colega?', 'escala_1_5', 1, 1, 1),
(80, 3, 76, 'Como você avalia a comunicação do colega?', 'escala_1_5', 1, 2, 1),
(81, 3, 82, 'Como você avalia a disponibilidade para ajudar?', 'escala_1_5', 1, 3, 1),
(82, 3, 80, 'Como você avalia o conhecimento técnico?', 'escala_1_5', 1, 4, 1),
(83, 3, 77, 'Como você avalia a confiabilidade do colega?', 'escala_1_5', 1, 5, 1),
(84, 3, 80, 'Compartilha informações relevantes com a equipe?', 'sim_nao', 1, 6, 1),
(85, 3, 80, 'Contribui para um bom ambiente de trabalho?', 'sim_nao', 1, 7, 1),
(86, 3, 84, 'Cumpre com suas responsabilidades?', 'sim_nao', 1, 8, 1),
(87, 3, 77, 'Oferece ajuda quando percebe dificuldades?', 'escala_1_5', 1, 9, 1),
(88, 3, 86, 'É respeitoso com os colegas?', 'escala_1_5', 1, 10, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `respostas`
--

CREATE TABLE `respostas` (
  `id` int(11) NOT NULL,
  `avaliacao_id` int(11) NOT NULL,
  `pergunta_id` int(11) NOT NULL,
  `resposta_texto` text DEFAULT NULL,
  `resposta_nota` int(11) DEFAULT NULL,
  `data_resposta` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `respostas`
--

INSERT INTO `respostas` (`id`, `avaliacao_id`, `pergunta_id`, `resposta_texto`, `resposta_nota`, `data_resposta`) VALUES
(221, 177, 55, NULL, 2, '2026-02-26 19:09:14'),
(222, 177, 69, NULL, 4, '2026-02-26 19:09:14'),
(223, 177, 70, NULL, 2, '2026-02-26 19:09:14'),
(224, 177, 71, NULL, 4, '2026-02-26 19:09:14'),
(225, 177, 72, NULL, 2, '2026-02-26 19:09:14'),
(226, 177, 73, NULL, 3, '2026-02-26 19:09:14'),
(227, 177, 74, NULL, 2, '2026-02-26 19:09:14'),
(228, 177, 75, NULL, 4, '2026-02-26 19:09:14'),
(229, 177, 76, NULL, 4, '2026-02-26 19:09:14'),
(230, 177, 77, NULL, 3, '2026-02-26 19:09:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `cargo_id` int(11) DEFAULT NULL,
  `departamento_id` int(11) DEFAULT NULL,
  `gestor_id` int(11) DEFAULT NULL,
  `tipo` enum('admin','rh','gestor','colaborador') DEFAULT 'colaborador',
  `ativo` tinyint(1) DEFAULT 1,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `data_contratacao` date DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `empresa_id`, `nome`, `email`, `telefone`, `senha`, `cargo_id`, `departamento_id`, `gestor_id`, `tipo`, `ativo`, `foto_perfil`, `data_contratacao`, `data_criacao`, `data_atualizacao`) VALUES
(1, NULL, 'Administrador', 'admin@sistema.com', '4999201400777', '$2y$10$va9sxzKtiByEQvSAWtr/aO3Y1LE0UZ1b28lMvh49MUuZv9lg/E8aG', NULL, NULL, NULL, 'admin', 1, NULL, NULL, '2026-02-13 16:38:41', '2026-02-18 18:18:38'),
(9, 17, 'Felipe Massa', 'felipe.massa@ferrari.com', '', '$2y$10$mvNx0p2GmEASnscm1pMyBe8.h.hBdVOZ4o22BApIwJNy5hupyIqkC', 57, 31, 13, 'colaborador', 0, '6998656603a97_20260220104510.png', '2026-02-20', '2026-02-20 13:32:32', '2026-02-25 13:30:27'),
(11, 17, 'Michael Schumacher', 'michael.schumacher@ferrari.com', '+39 123456789', '$2y$10$DVGp46/oU4pOwlF0rqumbu5PKIAUlDkk0o8.rKHf8WOTxgA5rZ0uW', NULL, 31, 13, 'colaborador', 1, NULL, '1996-01-01', '2026-02-25 11:42:27', '2026-02-25 13:22:34'),
(12, 17, 'Rubens Barrichello', 'rubens.barrichello@ferrari.com', '39123456790', '$2y$10$eSu0rCUoCSy5tWnwYiciM.ywZqVzTCyh6jlUZrj5wA9C./kjQmrWu', 56, 31, 13, 'colaborador', 1, NULL, '2000-01-01', '2026-02-25 11:42:27', '2026-02-26 12:34:33'),
(13, 17, 'Jean Todt', 'jean.todt@ferrari.com', '39123456791', '$2y$10$jfjCTpkCY4hI4rk7xj2RPesd7T407xgdKVH1Sbo9rGx1LLDG05.X6', 59, 31, NULL, 'gestor', 1, NULL, '1993-01-01', '2026-02-25 11:42:27', '2026-02-26 12:34:48'),
(14, 17, 'Ross Brawn', 'ross.brawn@ferrari.com', '39123456792', '$2y$10$j9uUc3n8jXEU2IWnBtI5mOS0/WItAOzrGVvJC4en1kLmhCtbLea0.', 60, 32, NULL, 'gestor', 1, NULL, '1997-01-01', '2026-02-25 11:42:27', '2026-02-26 18:48:46'),
(15, 20, 'Ayrton Senna', 'ayrton.senna@mclaren.com', '44123456789', '$2y$10$p1/A5zTcSm7Gs6hahI.G9uVDX310KzDmNFWnQhvj1EBk.NjAGpDty', 56, 31, 17, 'colaborador', 1, NULL, '1988-01-01', '2026-02-25 11:42:27', '2026-02-25 19:18:09'),
(16, 20, 'Alain Prost', 'alain.prost@mclaren.com', '44123456790', '$2y$10$70XLUDp5jmGZ4aT3sZl9RO4tAj/.T4iX8a8tBIpwwy4ytgSIEND8u', 56, 31, 17, 'colaborador', 1, NULL, '1984-01-01', '2026-02-25 11:42:27', '2026-02-25 19:17:24'),
(17, 20, 'Ron Dennis', 'ron.dennis@mclaren.com', '+44 123456791', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 59, 31, NULL, 'gestor', 1, NULL, '1980-01-01', '2026-02-25 11:42:27', '2026-02-25 11:42:27'),
(18, 23, 'Nelson Piquet', 'nelson.piquet@williams.com', '+44 223456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 56, 31, 20, 'colaborador', 1, NULL, '1987-01-01', '2026-02-25 11:42:27', '2026-02-25 11:42:40'),
(19, 23, 'Nigel Mansell', 'nigel.mansell@williams.com', '+44 223456790', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 56, 31, 20, 'colaborador', 1, NULL, '1989-01-01', '2026-02-25 11:42:27', '2026-02-25 11:42:40'),
(20, 23, 'Frank Williams', 'frank.williams@williams.com', '+44 223456791', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 59, 31, NULL, 'gestor', 1, NULL, '1977-01-01', '2026-02-25 11:42:27', '2026-02-25 11:42:27'),
(21, 18, 'Sebastian Vettel', 'sebastian.vettel@redbull.com', '+44 323456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 56, 31, 23, 'colaborador', 1, NULL, '2009-01-01', '2026-02-25 11:42:27', '2026-02-25 11:42:40'),
(22, 18, 'Mark Webber', 'mark.webber@redbull.com', '+44 323456790', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 56, 31, 23, 'colaborador', 1, NULL, '2007-01-01', '2026-02-25 11:42:27', '2026-02-25 11:42:40'),
(23, 18, 'Christian Horner', 'christian.horner@redbull.com', '+44 323456791', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 59, 31, NULL, 'gestor', 1, NULL, '2005-01-01', '2026-02-25 11:42:27', '2026-02-25 11:42:27'),
(24, 19, 'Lewis Hamilton', 'lewis.hamilton@mercedes.com', '+44 423456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 56, 31, 26, 'colaborador', 1, NULL, '2013-01-01', '2026-02-25 11:42:27', '2026-02-25 11:42:40'),
(25, 19, 'Nico Rosberg', 'nico.rosberg@mercedes.com', '+44 423456790', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 56, 31, 26, 'colaborador', 1, NULL, '2010-01-01', '2026-02-25 11:42:27', '2026-02-25 11:42:40'),
(26, 19, 'Toto Wolff', 'toto.wolff@mercedes.com', '+44 423456791', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 59, 31, NULL, 'gestor', 1, NULL, '2013-01-01', '2026-02-25 11:42:27', '2026-02-25 11:42:27');

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_cargos_por_departamento`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_cargos_por_departamento` (
`departamento_id` int(11)
,`departamento_nome` varchar(100)
,`cargo_id` int(11)
,`cargo_nome` varchar(100)
,`nivel` varchar(50)
,`ativo` tinyint(1)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_pdi_resumo`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_pdi_resumo` (
`id` int(11)
,`titulo` varchar(255)
,`data_criacao` date
,`data_revisao` date
,`status` enum('ativo','em_andamento','concluido','cancelado')
,`progresso_geral` int(11)
,`colaborador_id` int(11)
,`colaborador_nome` varchar(150)
,`colaborador_foto` varchar(255)
,`gestor_nome` varchar(150)
,`cargo_colaborador` varchar(100)
,`departamento_colaborador` varchar(100)
,`total_metas` bigint(21)
,`metas_concluidas` bigint(21)
,`total_acoes` bigint(21)
,`acoes_concluidas` bigint(21)
);

-- --------------------------------------------------------

--
-- Estrutura para view `vw_cargos_por_departamento`
--
DROP TABLE IF EXISTS `vw_cargos_por_departamento`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_cargos_por_departamento`  AS SELECT `d`.`id` AS `departamento_id`, `d`.`nome` AS `departamento_nome`, `c`.`id` AS `cargo_id`, `c`.`nome` AS `cargo_nome`, `c`.`nivel` AS `nivel`, `cd`.`ativo` AS `ativo` FROM ((`departamentos` `d` join `cargo_departamento` `cd` on(`d`.`id` = `cd`.`departamento_id`)) join `cargos` `c` on(`cd`.`cargo_id` = `c`.`id`)) WHERE `cd`.`ativo` = 1 ORDER BY `d`.`nome` ASC, `c`.`nome` ASC  ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_pdi_resumo`
--
DROP TABLE IF EXISTS `vw_pdi_resumo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_pdi_resumo`  AS SELECT `p`.`id` AS `id`, `p`.`titulo` AS `titulo`, `p`.`data_criacao` AS `data_criacao`, `p`.`data_revisao` AS `data_revisao`, `p`.`status` AS `status`, `p`.`progresso_geral` AS `progresso_geral`, `u_colab`.`id` AS `colaborador_id`, `u_colab`.`nome` AS `colaborador_nome`, `u_colab`.`foto_perfil` AS `colaborador_foto`, `u_gestor`.`nome` AS `gestor_nome`, `c`.`nome` AS `cargo_colaborador`, `d`.`nome` AS `departamento_colaborador`, (select count(0) from `pdi_metas` where `pdi_metas`.`pdi_id` = `p`.`id`) AS `total_metas`, (select count(0) from `pdi_metas` where `pdi_metas`.`pdi_id` = `p`.`id` and `pdi_metas`.`status` = 'concluida') AS `metas_concluidas`, (select count(0) from `pdi_acoes` where `pdi_acoes`.`pdi_id` = `p`.`id`) AS `total_acoes`, (select count(0) from `pdi_acoes` where `pdi_acoes`.`pdi_id` = `p`.`id` and `pdi_acoes`.`status` = 'concluida') AS `acoes_concluidas` FROM ((((`pdi` `p` join `usuarios` `u_colab` on(`p`.`colaborador_id` = `u_colab`.`id`)) join `usuarios` `u_gestor` on(`p`.`gestor_responsavel_id` = `u_gestor`.`id`)) left join `cargos` `c` on(`u_colab`.`cargo_id` = `c`.`id`)) left join `departamentos` `d` on(`u_colab`.`departamento_id` = `d`.`id`))  ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `advertencias`
--
ALTER TABLE `advertencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `registrado_por` (`registrado_por`);

--
-- Índices de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_avaliacao` (`ciclo_id`,`avaliado_id`,`avaliador_id`),
  ADD KEY `avaliado_id` (`avaliado_id`),
  ADD KEY `avaliador_id` (`avaliador_id`),
  ADD KEY `formulario_id` (`formulario_id`),
  ADD KEY `idx_avaliacoes_status` (`status`);

--
-- Índices de tabela `calibracao`
--
ALTER TABLE `calibracao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ciclo_id` (`ciclo_id`),
  ADD KEY `avaliacao_id` (`avaliacao_id`),
  ADD KEY `usuario_calibrador_id` (`usuario_calibrador_id`);

--
-- Índices de tabela `cargos`
--
ALTER TABLE `cargos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `cargo_departamento`
--
ALTER TABLE `cargo_departamento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cargo_depto` (`cargo_id`,`departamento_id`),
  ADD KEY `departamento_id` (`departamento_id`);

--
-- Índices de tabela `ciclos_avaliacao`
--
ALTER TABLE `ciclos_avaliacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ciclos_status` (`status`),
  ADD KEY `formulario_id` (`formulario_id`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Índices de tabela `ciclo_participantes`
--
ALTER TABLE `ciclo_participantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_participante` (`ciclo_id`,`usuario_id`,`tipo_participacao`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `competencias`
--
ALTER TABLE `competencias`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Índices de tabela `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `faltas`
--
ALTER TABLE `faltas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `registrado_por` (`registrado_por`);

--
-- Índices de tabela `formularios`
--
ALTER TABLE `formularios`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `historico_promocoes`
--
ALTER TABLE `historico_promocoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `cargo_anterior_id` (`cargo_anterior_id`),
  ADD KEY `cargo_novo_id` (`cargo_novo_id`),
  ADD KEY `aprovado_por` (`aprovado_por`);

--
-- Índices de tabela `integracao`
--
ALTER TABLE `integracao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `realizado_por` (`realizado_por`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notificacoes_usuario_lida` (`usuario_id`,`lida`);

--
-- Índices de tabela `pdi`
--
ALTER TABLE `pdi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `colaborador_id` (`colaborador_id`),
  ADD KEY `ciclo_id` (`ciclo_id`),
  ADD KEY `gestor_responsavel_id` (`gestor_responsavel_id`);

--
-- Índices de tabela `pdi_acoes`
--
ALTER TABLE `pdi_acoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pdi_id` (`pdi_id`),
  ADD KEY `meta_id` (`meta_id`);

--
-- Índices de tabela `pdi_acompanhamentos`
--
ALTER TABLE `pdi_acompanhamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pdi_id` (`pdi_id`),
  ADD KEY `responsavel_id` (`responsavel_id`);

--
-- Índices de tabela `pdi_checklists`
--
ALTER TABLE `pdi_checklists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tipo_item` (`tipo`,`item_id`);

--
-- Índices de tabela `pdi_checklist_conclusoes`
--
ALTER TABLE `pdi_checklist_conclusoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_checklist` (`checklist_id`);

--
-- Índices de tabela `pdi_competencias`
--
ALTER TABLE `pdi_competencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pdi_id` (`pdi_id`),
  ADD KEY `competencia_id` (`competencia_id`);

--
-- Índices de tabela `pdi_historico`
--
ALTER TABLE `pdi_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pdi_id` (`pdi_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `pdi_metas`
--
ALTER TABLE `pdi_metas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pdi_id` (`pdi_id`),
  ADD KEY `competencia_id` (`competencia_id`);

--
-- Índices de tabela `perguntas`
--
ALTER TABLE `perguntas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `formulario_id` (`formulario_id`),
  ADD KEY `competencia_id` (`competencia_id`);

--
-- Índices de tabela `respostas`
--
ALTER TABLE `respostas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `avaliacao_id` (`avaliacao_id`),
  ADD KEY `pergunta_id` (`pergunta_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `cargo_id` (`cargo_id`),
  ADD KEY `departamento_id` (`departamento_id`),
  ADD KEY `idx_usuarios_email` (`email`),
  ADD KEY `idx_usuarios_gestor` (`gestor_id`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `advertencias`
--
ALTER TABLE `advertencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=178;

--
-- AUTO_INCREMENT de tabela `calibracao`
--
ALTER TABLE `calibracao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `cargos`
--
ALTER TABLE `cargos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT de tabela `cargo_departamento`
--
ALTER TABLE `cargo_departamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT de tabela `ciclos_avaliacao`
--
ALTER TABLE `ciclos_avaliacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de tabela `ciclo_participantes`
--
ALTER TABLE `ciclo_participantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=468;

--
-- AUTO_INCREMENT de tabela `competencias`
--
ALTER TABLE `competencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT de tabela `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de tabela `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `faltas`
--
ALTER TABLE `faltas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `formularios`
--
ALTER TABLE `formularios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `historico_promocoes`
--
ALTER TABLE `historico_promocoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `integracao`
--
ALTER TABLE `integracao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `pdi`
--
ALTER TABLE `pdi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `pdi_acoes`
--
ALTER TABLE `pdi_acoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `pdi_acompanhamentos`
--
ALTER TABLE `pdi_acompanhamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `pdi_checklists`
--
ALTER TABLE `pdi_checklists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `pdi_checklist_conclusoes`
--
ALTER TABLE `pdi_checklist_conclusoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `pdi_competencias`
--
ALTER TABLE `pdi_competencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT de tabela `pdi_historico`
--
ALTER TABLE `pdi_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `pdi_metas`
--
ALTER TABLE `pdi_metas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `perguntas`
--
ALTER TABLE `perguntas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT de tabela `respostas`
--
ALTER TABLE `respostas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=231;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `advertencias`
--
ALTER TABLE `advertencias`
  ADD CONSTRAINT `advertencias_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `advertencias_ibfk_2` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD CONSTRAINT `avaliacoes_ibfk_1` FOREIGN KEY (`ciclo_id`) REFERENCES `ciclos_avaliacao` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avaliacoes_ibfk_2` FOREIGN KEY (`avaliado_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avaliacoes_ibfk_3` FOREIGN KEY (`avaliador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `avaliacoes_ibfk_4` FOREIGN KEY (`formulario_id`) REFERENCES `formularios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `calibracao`
--
ALTER TABLE `calibracao`
  ADD CONSTRAINT `calibracao_ibfk_1` FOREIGN KEY (`ciclo_id`) REFERENCES `ciclos_avaliacao` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calibracao_ibfk_2` FOREIGN KEY (`avaliacao_id`) REFERENCES `avaliacoes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calibracao_ibfk_3` FOREIGN KEY (`usuario_calibrador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `cargo_departamento`
--
ALTER TABLE `cargo_departamento`
  ADD CONSTRAINT `cargo_departamento_ibfk_1` FOREIGN KEY (`cargo_id`) REFERENCES `cargos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cargo_departamento_ibfk_2` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `ciclos_avaliacao`
--
ALTER TABLE `ciclos_avaliacao`
  ADD CONSTRAINT `ciclos_avaliacao_ibfk_1` FOREIGN KEY (`formulario_id`) REFERENCES `formularios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ciclos_avaliacao_ibfk_2` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Restrições para tabelas `ciclo_participantes`
--
ALTER TABLE `ciclo_participantes`
  ADD CONSTRAINT `ciclo_participantes_ibfk_1` FOREIGN KEY (`ciclo_id`) REFERENCES `ciclos_avaliacao` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ciclo_participantes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `departamentos`
--
ALTER TABLE `departamentos`
  ADD CONSTRAINT `departamentos_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `faltas`
--
ALTER TABLE `faltas`
  ADD CONSTRAINT `faltas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `faltas_ibfk_2` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `historico_promocoes`
--
ALTER TABLE `historico_promocoes`
  ADD CONSTRAINT `historico_promocoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historico_promocoes_ibfk_2` FOREIGN KEY (`cargo_anterior_id`) REFERENCES `cargos` (`id`),
  ADD CONSTRAINT `historico_promocoes_ibfk_3` FOREIGN KEY (`cargo_novo_id`) REFERENCES `cargos` (`id`),
  ADD CONSTRAINT `historico_promocoes_ibfk_4` FOREIGN KEY (`aprovado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `integracao`
--
ALTER TABLE `integracao`
  ADD CONSTRAINT `integracao_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `integracao_ibfk_2` FOREIGN KEY (`realizado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pdi`
--
ALTER TABLE `pdi`
  ADD CONSTRAINT `pdi_ibfk_1` FOREIGN KEY (`colaborador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pdi_ibfk_2` FOREIGN KEY (`ciclo_id`) REFERENCES `ciclos_avaliacao` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pdi_ibfk_3` FOREIGN KEY (`gestor_responsavel_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `pdi_acoes`
--
ALTER TABLE `pdi_acoes`
  ADD CONSTRAINT `pdi_acoes_ibfk_1` FOREIGN KEY (`pdi_id`) REFERENCES `pdi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pdi_acoes_ibfk_2` FOREIGN KEY (`meta_id`) REFERENCES `pdi_metas` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `pdi_acompanhamentos`
--
ALTER TABLE `pdi_acompanhamentos`
  ADD CONSTRAINT `pdi_acompanhamentos_ibfk_1` FOREIGN KEY (`pdi_id`) REFERENCES `pdi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pdi_acompanhamentos_ibfk_2` FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `pdi_checklist_conclusoes`
--
ALTER TABLE `pdi_checklist_conclusoes`
  ADD CONSTRAINT `pdi_checklist_conclusoes_ibfk_1` FOREIGN KEY (`checklist_id`) REFERENCES `pdi_checklists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pdi_checklist_conclusoes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `pdi_competencias`
--
ALTER TABLE `pdi_competencias`
  ADD CONSTRAINT `pdi_competencias_ibfk_1` FOREIGN KEY (`pdi_id`) REFERENCES `pdi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pdi_competencias_ibfk_2` FOREIGN KEY (`competencia_id`) REFERENCES `competencias` (`id`);

--
-- Restrições para tabelas `pdi_historico`
--
ALTER TABLE `pdi_historico`
  ADD CONSTRAINT `pdi_historico_ibfk_1` FOREIGN KEY (`pdi_id`) REFERENCES `pdi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pdi_historico_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `pdi_metas`
--
ALTER TABLE `pdi_metas`
  ADD CONSTRAINT `pdi_metas_ibfk_1` FOREIGN KEY (`pdi_id`) REFERENCES `pdi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pdi_metas_ibfk_2` FOREIGN KEY (`competencia_id`) REFERENCES `pdi_competencias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `perguntas`
--
ALTER TABLE `perguntas`
  ADD CONSTRAINT `perguntas_ibfk_1` FOREIGN KEY (`formulario_id`) REFERENCES `formularios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `perguntas_ibfk_2` FOREIGN KEY (`competencia_id`) REFERENCES `competencias` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `respostas`
--
ALTER TABLE `respostas`
  ADD CONSTRAINT `respostas_ibfk_1` FOREIGN KEY (`avaliacao_id`) REFERENCES `avaliacoes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `respostas_ibfk_2` FOREIGN KEY (`pergunta_id`) REFERENCES `perguntas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`cargo_id`) REFERENCES `cargos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `usuarios_ibfk_3` FOREIGN KEY (`gestor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `usuarios_ibfk_4` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
