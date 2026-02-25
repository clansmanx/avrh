-- phpMyAdmin SQL Dump
-- version 5.1.4
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 25/02/2026 às 07:46
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

--
-- Despejando dados para a tabela `advertencias`
--

INSERT INTO `advertencias` (`id`, `usuario_id`, `tipo`, `data_registro`, `motivo`, `arquivo_anexo`, `registrado_por`, `data_criacao`) VALUES
(1, 9, 'orientacao', '2026-02-24', 'fgttfydydrg', NULL, 1, '2026-02-24 20:27:34');

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
(21, 17, 10, 1, 15, 'concluida', '2026-02-24 16:19:06', '2026-02-24 16:19:59', '80.00', NULL, '2026-02-24 19:17:24'),
(22, 18, 9, 1, 16, 'pendente', NULL, NULL, NULL, NULL, '2026-02-24 19:29:06'),
(23, 18, 10, 1, 16, 'concluida', '2026-02-24 16:56:22', '2026-02-24 16:56:46', '90.00', NULL, '2026-02-24 19:29:06'),
(24, 19, 10, 1, 16, 'concluida', '2026-02-24 16:58:25', '2026-02-24 16:58:25', '100.00', NULL, '2026-02-24 19:58:01'),
(25, 20, 2, 9, 15, 'pendente', NULL, NULL, NULL, NULL, '2026-02-24 20:23:46');

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

--
-- Despejando dados para a tabela `calibracao`
--

INSERT INTO `calibracao` (`id`, `ciclo_id`, `avaliacao_id`, `usuario_calibrador_id`, `nota_original`, `nota_calibrada`, `justificativa`, `data_calibracao`) VALUES
(4, 18, 23, 1, '75.00', '90.00', 'ftjutfufthfghfgh', '2026-02-24 20:25:19');

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
(1, 'Diretor', 'Executivo', 'Diretor de área', 1, '2026-02-13 16:38:41'),
(2, 'Gerente', 'Gestão', 'Gerente de departamento', 1, '2026-02-13 16:38:41'),
(3, 'Coordenador', 'Gestão', 'Coordenador de equipe', 1, '2026-02-13 16:38:41'),
(4, 'Analista Senior', 'Sênior', 'Analista com experiência', 1, '2026-02-13 16:38:41'),
(5, 'Analista Pleno', 'Pleno', 'Analista em desenvolvimento', 1, '2026-02-13 16:38:41'),
(6, 'Analista Junior', 'Júnior', 'Analista em início de carreira', 1, '2026-02-13 16:38:41'),
(7, 'Estagiário', 'Estágio', 'Estudante em formação', 1, '2026-02-13 16:38:41'),
(8, 'Açogueiro Júnior', 'Júnior', 'Profissional de açougue em início de carreira', 1, '2026-02-20 12:23:01'),
(9, 'Açogueiro Pleno', 'Pleno', 'Profissional de açougue com experiência', 1, '2026-02-20 12:23:01'),
(10, 'Açogueiro Sênior', 'Sênior', 'Profissional de açougue especializado', 1, '2026-02-20 12:23:01'),
(11, 'Auxiliar de Açougue', 'Auxiliar', 'Auxilia nas atividades do açougue', 1, '2026-02-20 12:23:01'),
(12, 'Repositor', 'Operacional', 'Responsável por repor mercadorias', 1, '2026-02-20 12:23:01'),
(13, 'Estoquista', 'Operacional', 'Responsável pelo estoque', 1, '2026-02-20 12:23:01'),
(14, 'Conferente', 'Operacional', 'Confere mercadorias recebidas e expedidas', 1, '2026-02-20 12:23:01'),
(15, 'Analista de RH', 'Analista', 'Analista de recursos humanos', 1, '2026-02-20 12:23:01'),
(16, 'Assistente de RH', 'Assistente', 'Assistente de recursos humanos', 1, '2026-02-20 12:23:01'),
(17, 'Auxiliar Administrativo', 'Auxiliar', 'Auxilia nas atividades administrativas', 1, '2026-02-20 12:23:01');

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
(5, 12, 6, 1, '2026-02-20 12:23:01'),
(6, 13, 6, 1, '2026-02-20 12:23:01'),
(7, 14, 6, 1, '2026-02-20 12:23:01'),
(13, 2, 6, 1, '2026-02-20 12:23:01'),
(18, 3, 6, 1, '2026-02-20 12:23:01'),
(42, 6, 2, 1, '2026-02-20 12:41:12'),
(43, 5, 2, 1, '2026-02-20 12:46:37'),
(44, 2, 2, 1, '2026-02-20 19:35:25');

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
(17, 'Teste Ciclo 2026', 'novo teste', '2026-02-24', '2026-03-26', '90', NULL, 15, 'finalizado', '{\"autoavaliacao_peso\":1,\"gestor_peso\":2,\"pares_peso\":1,\"subordinados_peso\":1,\"permite_comentarios\":true,\"anonimo\":false,\"obrigar_justificativa\":true}', '2026-02-24 19:16:12', '2026-02-24 19:58:07'),
(18, 'Teste Ciclo 2026 1', 'teste', '2026-02-24', '2026-03-26', '90', NULL, 16, 'em_andamento', '{\"autoavaliacao_peso\":1,\"gestor_peso\":2,\"pares_peso\":1,\"subordinados_peso\":1,\"permite_comentarios\":true,\"anonimo\":false,\"obrigar_justificativa\":true}', '2026-02-24 19:28:45', '2026-02-24 19:29:06'),
(19, 'Teste Ciclo 2026 2', '', '2026-02-24', '2026-03-26', '90', NULL, 16, 'em_andamento', '{\"autoavaliacao_peso\":1,\"gestor_peso\":2,\"pares_peso\":1,\"subordinados_peso\":1,\"permite_comentarios\":true,\"anonimo\":false,\"obrigar_justificativa\":false}', '2026-02-24 19:57:37', '2026-02-24 19:58:01'),
(20, 'gjgvjgv', 'fgjfgjv', '2026-02-24', '2026-03-26', '90', NULL, 15, 'em_andamento', '{\"autoavaliacao_peso\":1,\"gestor_peso\":2,\"pares_peso\":1,\"subordinados_peso\":1,\"permite_comentarios\":false,\"anonimo\":false,\"obrigar_justificativa\":false}', '2026-02-24 20:20:57', '2026-02-24 20:23:46');

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
(34, 17, 1, 'avaliador', 'pendente', NULL),
(35, 17, 10, 'avaliado', 'pendente', NULL),
(36, 18, 1, 'avaliador', 'pendente', NULL),
(37, 18, 10, 'avaliado', 'pendente', NULL),
(38, 18, 9, 'avaliado', 'pendente', NULL),
(39, 19, 1, 'avaliador', 'pendente', NULL),
(40, 19, 10, 'avaliado', 'pendente', NULL),
(41, 20, 9, 'avaliador', 'pendente', NULL),
(42, 20, 2, 'avaliado', 'pendente', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `competencias`
--

CREATE TABLE `competencias` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo` enum('tecnica','comportamental') DEFAULT 'comportamental',
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `competencias`
--

INSERT INTO `competencias` (`id`, `nome`, `descricao`, `tipo`, `ativo`, `data_criacao`) VALUES
(75, 'Apresentação Pessoal', 'Cuidados com aparência e uniforme', 'comportamental', 1, '2026-02-18 13:37:53'),
(76, 'Atendimento ao Cliente', 'Qualidade no atendimento', 'comportamental', 1, '2026-02-18 13:37:53'),
(77, 'Cultura Organizacional', 'Conhecimento da empresa', 'comportamental', 1, '2026-02-18 13:37:53'),
(78, 'Disciplina', 'Respeito a hierarquia e regras', 'comportamental', 1, '2026-02-18 13:37:53'),
(79, 'Engajamento', 'Desejo de crescimento', 'comportamental', 1, '2026-02-18 13:37:53'),
(80, 'Trabalho em Equipe', 'Relacionamento com colegas', 'comportamental', 1, '2026-02-18 13:37:53'),
(81, 'Liderança', 'Capacidade de auxiliar novos', 'comportamental', 1, '2026-02-18 13:37:53'),
(82, 'Proatividade', 'Iniciativa própria', 'comportamental', 1, '2026-02-18 13:37:53'),
(83, 'Produtividade', 'Qualidade e quantidade de trabalho', 'comportamental', 1, '2026-02-18 13:37:53'),
(84, 'Responsabilidade', 'Zelo com materiais e instalações', 'comportamental', 1, '2026-02-18 13:37:53'),
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
(1, NULL, 'Recursos Humanos', 'Departamento responsável pela gestão de pessoas', '2026-02-13 16:38:40', 1),
(2, 6, 'Tecnologia', 'Departamento de tecnologia e inovação', '2026-02-13 16:38:40', 1),
(6, NULL, 'Mercearia', 'Setor de alimentos não perecíveis', '2026-02-20 12:23:01', 1),
(7, NULL, 'Hortifrúti', 'Setor de frutas, legumes e verduras', '2026-02-20 12:23:01', 1),
(8, NULL, 'Padaria', 'Setor de pães e confeitaria', '2026-02-20 12:23:01', 1),
(14, NULL, 'Açougue', 'Setor de carnes e frios', '2026-02-20 19:23:47', 1),
(15, 6, 'Administração', 'Diretoria e gerência', '2026-02-20 19:24:18', 1),
(16, 6, 'Comercial', 'Departamento de vendas e relacionamento com clientes', '2026-02-20 19:24:42', 1),
(17, 6, 'Financeiro', 'Departamento de finanças e controladoria', '2026-02-20 19:24:57', 1),
(18, NULL, 'Loja', 'Operador de loja, repositores, fiscais', '2026-02-20 19:32:02', 1);

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
(1, '001-Matriz', 'matriz', '06.316.466/0001-87', '3ª Avenida (esquina com 3144), 1789 \r\nCEP: 88330-102', 'Balneário Camboriú', 'SC', '(47) 3367-4800', 1, '2026-02-20 17:55:55'),
(2, '002-Brava', 'filial', '06.316.466/0003-49', 'Av. Osvaldo Reis, 3585 \r\nPraia Brava\r\nCEP: 88306-773', 'Itajaí', 'SC', '(47) 3367-4800', 1, '2026-02-20 17:55:55'),
(3, '004-Emporio', 'filial', '06.316.466/0004-20', 'Rua Delfim Mario de Pádua Peixoto, 500 \r\nPraia Brava \r\nCEP: 88306-806', 'Itajaí', 'SC', '(47) 3367-4800', 1, '2026-02-20 17:55:55'),
(4, '099-CD', 'filial', '06.316.466/0002-68', 'Rua Antônio Dias de Oliveira, 177 \r\nNova Esperança\r\nCEP: 88336-305', 'Balneário Camboriú', 'SC', '(47) 3311-1506', 1, '2026-02-20 17:55:55'),
(5, '005-Centro', 'filial', '06.316.466/0005-00', '3ª Avenida (esquina com 904), 500 \r\nCentro\r\nCEP: 88330-088', 'Balneário Camboriú', 'SC', '(47) 3367-4800', 1, '2026-02-20 17:55:55'),
(6, 'MOTIVA', 'matriz', '37.376.940/0001-90', 'Rua Manoel de Borba Gato, 164\r\nNova Esperança', 'Balneário Camboriú', 'SC', '(47) 3311-1506', 1, '2026-02-20 18:27:29');

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

--
-- Despejando dados para a tabela `pdi`
--

INSERT INTO `pdi` (`id`, `colaborador_id`, `ciclo_id`, `gestor_responsavel_id`, `titulo`, `data_criacao`, `data_revisao`, `data_conclusao`, `status`, `observacoes_gerais`, `progresso_geral`, `created_at`, `updated_at`) VALUES
(13, 9, NULL, 1, 'Plano teste', '2026-02-24', '2026-05-24', NULL, 'ativo', 'zdgbcghgvhjgv', 50, '2026-02-24 19:50:07', '2026-02-24 20:26:31');

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

--
-- Despejando dados para a tabela `pdi_checklists`
--

INSERT INTO `pdi_checklists` (`id`, `tipo`, `item_id`, `titulo`, `descricao`, `ordem`, `data_prevista`, `created_at`) VALUES
(13, 'meta', 8, 'wewweresrer', 'sefserwer', 0, '2026-02-25', '2026-02-24 19:51:10'),
(14, 'meta', 8, 'werwerwerwerwerwerwerwerwe', 'werwerwerwes', 2, '2026-02-27', '2026-02-24 19:51:21');

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

--
-- Despejando dados para a tabela `pdi_checklist_conclusoes`
--

INSERT INTO `pdi_checklist_conclusoes` (`id`, `checklist_id`, `usuario_id`, `concluido`, `data_conclusao`, `observacoes`) VALUES
(13, 13, 1, 1, '2026-02-24 19:51:43', NULL),
(14, 14, 1, 1, '2026-02-24 20:26:31', NULL);

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

--
-- Despejando dados para a tabela `pdi_historico`
--

INSERT INTO `pdi_historico` (`id`, `pdi_id`, `usuario_id`, `acao`, `descricao`, `data_acao`) VALUES
(15, 13, 1, 'criou', 'PDI criado para o colaborador', '2026-02-24 19:50:07');

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

--
-- Despejando dados para a tabela `pdi_metas`
--

INSERT INTO `pdi_metas` (`id`, `pdi_id`, `competencia_id`, `titulo`, `descricao`, `criterio_sucesso`, `data_prazo`, `peso`, `prioridade`, `progresso`, `status`, `observacoes`, `ordem`, `progresso_calculado`) VALUES
(8, 13, NULL, '1', '1', '1', '2026-05-24', 1, 'media', 100, 'pendente', '', 0, 50);

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
(42, 16, 78, 'O ponto está alinhado com a cultura da empresa?', 'sim_nao', 5, 7, 1);

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
(124, 21, 24, NULL, 1, '2026-02-24 19:19:06'),
(125, 21, 25, NULL, 0, '2026-02-24 19:19:06'),
(126, 21, 26, NULL, 1, '2026-02-24 19:19:06'),
(127, 21, 27, NULL, 0, '2026-02-24 19:19:06'),
(128, 21, 28, NULL, 1, '2026-02-24 19:19:06'),
(129, 21, 29, NULL, 1, '2026-02-24 19:19:06'),
(130, 21, 30, NULL, 1, '2026-02-24 19:19:06'),
(131, 21, 31, NULL, 1, '2026-02-24 19:19:06'),
(132, 21, 32, NULL, 1, '2026-02-24 19:19:06'),
(133, 21, 33, NULL, 1, '2026-02-24 19:19:06'),
(134, 21, 34, NULL, 1, '2026-02-24 19:19:06'),
(135, 21, 35, NULL, 1, '2026-02-24 19:19:06'),
(136, 23, 36, NULL, 0, '2026-02-24 19:56:22'),
(137, 23, 37, NULL, 1, '2026-02-24 19:56:22'),
(138, 23, 38, NULL, 0, '2026-02-24 19:56:22'),
(139, 23, 39, NULL, 1, '2026-02-24 19:56:22'),
(140, 23, 40, NULL, 1, '2026-02-24 19:56:22'),
(141, 23, 41, NULL, 1, '2026-02-24 19:56:22'),
(142, 23, 42, NULL, 1, '2026-02-24 19:56:22'),
(143, 24, 36, NULL, 1, '2026-02-24 19:58:25'),
(144, 24, 37, NULL, 1, '2026-02-24 19:58:25'),
(145, 24, 38, NULL, 1, '2026-02-24 19:58:25'),
(146, 24, 39, NULL, 1, '2026-02-24 19:58:25'),
(147, 24, 40, NULL, 1, '2026-02-24 19:58:25'),
(148, 24, 41, NULL, 1, '2026-02-24 19:58:25'),
(149, 24, 42, NULL, 1, '2026-02-24 19:58:25');

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
(1, NULL, 'Administrador', 'admin@sistema.com', '4999201400777', '$2y$10$va9sxzKtiByEQvSAWtr/aO3Y1LE0UZ1b28lMvh49MUuZv9lg/E8aG', 1, 1, NULL, 'admin', 1, NULL, NULL, '2026-02-13 16:38:41', '2026-02-18 18:18:38'),
(2, 1, 'João Silva Souza', 'joao@empresa.com', '49992014007', '$2y$10$va9sxzKtiByEQvSAWtr/aO3Y1LE0UZ1b28lMvh49MUuZv9lg/E8aG', 6, 2, 1, 'gestor', 1, NULL, '2026-02-16', '2026-02-13 16:38:41', '2026-02-20 18:57:36'),
(3, NULL, 'Maria Santos', 'maria@empresa.com', '', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 1, NULL, 'gestor', 1, NULL, NULL, '2026-02-13 16:38:41', '2026-02-16 13:41:55'),
(4, NULL, 'Pedro Oliveira', 'pedro@empresa.com', '', '$2y$10$va9sxzKtiByEQvSAWtr/aO3Y1LE0UZ1b28lMvh49MUuZv9lg/E8aG', 2, 2, 2, 'colaborador', 1, NULL, '2026-02-13', '2026-02-13 16:38:41', '2026-02-18 17:48:26'),
(7, NULL, 'Raul Santos', 'raul@empresa.com', '', '$2y$10$n5VONxTbP0EgBmLBX7gQdO5e2RcScoxvxseYh2jnoJmR7s60ZsFZq', 6, 2, 2, 'colaborador', 1, NULL, '2026-02-10', '2026-02-16 18:53:57', '2026-02-20 13:58:53'),
(8, NULL, 'Luiz De Angelina', 'luiz@deangelina.com.br', '', '$2y$10$uRQlsdEY9bIwdJigr.ja3.xowK6jwAzb2yrS.Oj/JnoMi8Q/oMJBa', 6, 2, 2, 'colaborador', 1, NULL, '2026-01-10', '2026-02-20 12:03:40', '2026-02-20 12:03:40'),
(9, 6, 'Deirudi Ecco', 'rudi.net@gmail.com', '49992014007', '$2y$10$mvNx0p2GmEASnscm1pMyBe8.h.hBdVOZ4o22BApIwJNy5hupyIqkC', 2, 2, 1, 'colaborador', 1, '6998656603a97_20260220104510.png', '2026-02-20', '2026-02-20 13:32:32', '2026-02-20 20:29:32'),
(10, 2, 'Fernando Alonso', 'alonso@sistema.com', '', '$2y$10$rK.IKHJK4uJzmON7leILwukomfIs/4wq2foMigIWLvEk1AHZc8YcS', 14, 6, 1, 'colaborador', 1, '699df87f6e475_20260224161407.png', '2026-02-24', '2026-02-24 19:14:07', '2026-02-24 19:14:37');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `calibracao`
--
ALTER TABLE `calibracao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `cargos`
--
ALTER TABLE `cargos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `cargo_departamento`
--
ALTER TABLE `cargo_departamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de tabela `ciclos_avaliacao`
--
ALTER TABLE `ciclos_avaliacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `ciclo_participantes`
--
ALTER TABLE `ciclo_participantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de tabela `competencias`
--
ALTER TABLE `competencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT de tabela `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de tabela `respostas`
--
ALTER TABLE `respostas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
