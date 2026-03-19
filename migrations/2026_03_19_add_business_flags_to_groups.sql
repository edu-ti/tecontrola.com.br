ALTER TABLE `groups`
ADD COLUMN group_type ENUM('pessoal','empresa') NOT NULL DEFAULT 'pessoal',
ADD COLUMN show_financial_projection TINYINT(1) NOT NULL DEFAULT 0;
