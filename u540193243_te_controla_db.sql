-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 18/03/2026 às 17:51
-- Versão do servidor: 11.8.3-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u540193243_te_controla_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `cards`
--

CREATE TABLE `cards` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `due_day` int(2) NOT NULL,
  `closing_day` int(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cards`
--

INSERT INTO `cards` (`id`, `group_id`, `name`, `due_day`, `closing_day`) VALUES
(1, 1, 'Nubank Edu', 11, 4),
(2, 1, 'Nubank PJ', 11, 4),
(3, 1, 'Hiper Mãe', 8, 1),
(4, 1, 'Hiper Edu', 8, 1),
(5, 1, 'Mercado Pago', 14, 9),
(6, 1, 'BV-EDU', 11, 6);

-- --------------------------------------------------------

--
-- Estrutura para tabela `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categories`
--

INSERT INTO `categories` (`id`, `group_id`, `name`) VALUES
(1, 1, 'Igreja'),
(2, 1, 'Veículo'),
(3, 1, 'Emprestimo'),
(4, 1, 'Energia'),
(5, 1, 'Água'),
(6, 1, 'D.A.S'),
(7, 1, 'Financiamento'),
(8, 1, 'Plano de Saúde'),
(9, 3, 'Aluguel');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fixed_expenses`
--

CREATE TABLE `fixed_expenses` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_day` int(2) NOT NULL,
  `responsible` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `fixed_expenses`
--

INSERT INTO `fixed_expenses` (`id`, `group_id`, `description`, `amount`, `due_day`, `responsible`, `category_id`) VALUES
(2, 3, 'Pagamento da primeira parcela do escrotio virtual', 125.00, 26, 'Gustavo', 9);

-- --------------------------------------------------------

--
-- Estrutura para tabela `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `groups`
--

INSERT INTO `groups` (`id`, `name`, `created_at`) VALUES
(1, 'Eduardo&Priscilla', '2025-11-10 17:11:28'),
(2, 'Família de Hugo', '2025-11-17 00:31:00'),
(3, 'GC Repretações e Serviços', '2026-02-27 00:47:07');

-- --------------------------------------------------------

--
-- Estrutura para tabela `income`
--

CREATE TABLE `income` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `income_date` date NOT NULL,
  `income_type` varchar(50) NOT NULL,
  `responsible` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `income`
--

INSERT INTO `income` (`id`, `group_id`, `description`, `amount`, `income_date`, `income_type`, `responsible`) VALUES
(2, 1, 'Slário de Eduardo', 2290.00, '2025-11-10', 'SALARIO', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `purchased_by` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `purchase_date` date NOT NULL,
  `installments` int(11) NOT NULL DEFAULT 1,
  `initial_installment` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `purchases`
--

INSERT INTO `purchases` (`id`, `group_id`, `card_id`, `description`, `notes`, `purchased_by`, `category_id`, `amount`, `purchase_date`, `installments`, `initial_installment`) VALUES
(1, 1, 4, 'nubank-grupo-cabral', 'Emprestimo para pastora e Samuel', 'pastora e Samuel', 3, 2353.32, '2025-11-08', 3, 1),
(2, 1, 4, 'Empréstimo pastora/samuel', NULL, 'Pastora/Samuel', 3, 319.00, '2025-11-14', 2, 1),
(3, 1, 6, 'EMPRESTIMO IGREJA', NULL, 'SAMUEL E PASTORA', 3, 636.88, '2025-12-10', 2, 1),
(4, 1, 4, 'Empréstimo pastora e samuel', NULL, 'Pastora e samuel ', 3, 374.00, '2025-12-12', 1, 1),
(5, 1, 4, 'Empréstimo da pastora e samuel ', NULL, 'Pastora e samuel ', 3, 207.00, '2026-01-20', 1, 1),
(6, 1, 4, 'emprestimo igreja', NULL, 'emprestimo para samuel e pastora', 3, 1273.76, '2026-02-08', 2, 1),
(7, 1, 4, 'emprestimo pastora e samuel', NULL, 'PASTORA E SAMUEL', 3, 361.16, '2026-02-11', 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `registration_tokens`
--

CREATE TABLE `registration_tokens` (
  `id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `group_id` int(11) DEFAULT NULL COMMENT 'Se nulo, cria um novo grupo. Se preenchido, adiciona o usuário a este grupo.',
  `is_used` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Marca se o token já foi utilizado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `registration_tokens`
--

INSERT INTO `registration_tokens` (`id`, `token`, `group_id`, `is_used`) VALUES
(1, 'ADMIN-174ba57c291fadc3acbff072ab95b6d2', 1, 1),
(2, 'MEMBER-0d1af076ff3e998a2ba4ab26206a17f1', 1, 1),
(3, 'ADMIN-b9f4ca1a2eeba94e66b438cd31fb83ba', 2, 0),
(4, 'ADMIN-2541c2ac4bc8a98730da3d6b895a9f13', 3, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `role` enum('admin','membro') NOT NULL DEFAULT 'membro' COMMENT 'Define se o utilizador é admin do grupo ou membro',
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `group_id`, `role`, `email`, `password`, `name`) VALUES
(1, 1, 'admin', 'educs85.ti@gmail.com', '$2y$10$ZQSuoOZZ62bO0.Fx7z4rI.Mzjxr5aEvNj6jesgmsPb.nqvaF6tABm', 'Eduardo Cabral'),
(2, 1, 'membro', 'priscilla.alinecilla@gmail.com', '$2y$10$P0UhVX3w72rLrDs.wLpcBOTPk3tW9Zfh6ubVH6T.vuJVIrBO.Nodq', 'Priscilla Cabral'),
(3, 3, 'admin', 'eduardo@gcpe.com.br', '$2y$10$QcfAiUVnzqQCrba3dtoifuAJr9PhDD6dWwdOsGxcb/2f3OzK/bf6q', 'Eduardo Cabral');

-- --------------------------------------------------------

--
-- Estrutura para tabela `variable_expenses`
--

CREATE TABLE `variable_expenses` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `purchase_date` date NOT NULL,
  `installments` int(11) NOT NULL DEFAULT 1,
  `initial_installment` int(11) NOT NULL DEFAULT 1,
  `responsible` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `cards`
--
ALTER TABLE `cards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`);

--
-- Índices de tabela `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`);

--
-- Índices de tabela `fixed_expenses`
--
ALTER TABLE `fixed_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `fk_fixed_category` (`category_id`);

--
-- Índices de tabela `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `income`
--
ALTER TABLE `income`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`);

--
-- Índices de tabela `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `card_id` (`card_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `fk_purchase_category` (`category_id`);

--
-- Índices de tabela `registration_tokens`
--
ALTER TABLE `registration_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `group_id` (`group_id`);

--
-- Índices de tabela `variable_expenses`
--
ALTER TABLE `variable_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `fk_variable_category` (`category_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `cards`
--
ALTER TABLE `cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `fixed_expenses`
--
ALTER TABLE `fixed_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `income`
--
ALTER TABLE `income`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `registration_tokens`
--
ALTER TABLE `registration_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `variable_expenses`
--
ALTER TABLE `variable_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `cards`
--
ALTER TABLE `cards`
  ADD CONSTRAINT `cards_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `fixed_expenses`
--
ALTER TABLE `fixed_expenses`
  ADD CONSTRAINT `fixed_expenses_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fixed_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `income`
--
ALTER TABLE `income`
  ADD CONSTRAINT `income_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `fk_purchase_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`card_id`) REFERENCES `cards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `variable_expenses`
--
ALTER TABLE `variable_expenses`
  ADD CONSTRAINT `fk_variable_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `variable_expenses_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
