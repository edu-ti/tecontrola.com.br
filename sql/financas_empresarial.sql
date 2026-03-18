-- ============================================================
-- TeControla - Módulo Finanças Empresarial
-- Execute este script no banco u540193243_te_controla_db
-- ============================================================

-- Tabela de Metas Financeiras
CREATE TABLE IF NOT EXISTS `financial_goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `target_amount` decimal(10,2) NOT NULL,
  `current_amount` decimal(10,2) DEFAULT 0.00,
  `deadline` date DEFAULT NULL,
  `type` enum('economia','investimento','reserva','outro') NOT NULL DEFAULT 'outro',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  CONSTRAINT `financial_goals_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Projeções Manuais (override das projeções automáticas)
CREATE TABLE IF NOT EXISTS `financial_projections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `month` int(2) NOT NULL COMMENT 'Mês (1-12)',
  `year` int(4) NOT NULL,
  `projected_income` decimal(10,2) DEFAULT 0.00,
  `projected_expense` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_month_year` (`group_id`,`month`,`year`),
  CONSTRAINT `financial_projections_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice extra para performance nas consultas de projeção
ALTER TABLE `income`
  ADD INDEX IF NOT EXISTS `idx_income_date` (`income_date`);

ALTER TABLE `fixed_expenses`
  ADD INDEX IF NOT EXISTS `idx_fixed_group` (`group_id`);

ALTER TABLE `variable_expenses`
  ADD INDEX IF NOT EXISTS `idx_variable_date` (`purchase_date`);
