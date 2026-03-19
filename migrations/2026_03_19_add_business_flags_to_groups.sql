-- Migration: Adicionar campos de assinatura SaaS na tabela groups
-- Data: 2026-03-19

ALTER TABLE `groups`
  ADD COLUMN IF NOT EXISTS `subscription_status` ENUM('trial', 'active', 'overdue', 'blocked') NOT NULL DEFAULT 'trial',
  ADD COLUMN IF NOT EXISTS `subscription_plan` VARCHAR(50) DEFAULT 'basico',
  ADD COLUMN IF NOT EXISTS `subscription_price` DECIMAL(10,2) DEFAULT 29.90,
  ADD COLUMN IF NOT EXISTS `next_due_date` DATE NULL,
  ADD COLUMN IF NOT EXISTS `trial_ends_at` DATE NULL,
  ADD COLUMN IF NOT EXISTS `asaas_customer_id` VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS `asaas_subscription_id` VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS `payment_method` ENUM('pix', 'credit_card', 'boleto') DEFAULT 'pix',
  ADD COLUMN IF NOT EXISTS `blocked_at` DATETIME NULL;

-- Tabela de histórico de pagamentos da assinatura
CREATE TABLE IF NOT EXISTS `subscription_payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT NOT NULL,
  `asaas_payment_id` VARCHAR(100) NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'confirmed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `payment_method` VARCHAR(50) NULL,
  `due_date` DATE NULL,
  `paid_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
