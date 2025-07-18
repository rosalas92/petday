ALTER TABLE `usuarios`
ADD COLUMN `reset_token` VARCHAR(255) NULL AFTER `is_verified`,
ADD COLUMN `reset_token_expires_at` DATETIME NULL AFTER `reset_token`;