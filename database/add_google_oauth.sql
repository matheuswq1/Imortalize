-- ============================================================
-- Google OAuth — coluna google_id e password_hash opcional
-- Execute UMA VEZ no MySQL (phpMyAdmin ou linha de comando):
--
--   mysql -u root -p imortalize < database/add_google_oauth.sql
--
-- Ou copie e cole o conteúdo no phpMyAdmin (SQL).
-- ============================================================

USE imortalize;

-- Identificador do usuário no Google (login sem senha)
ALTER TABLE users
  ADD COLUMN google_id VARCHAR(255) NULL UNIQUE
  COMMENT 'ID do usuário no Google OAuth'
  AFTER email;

-- Permite usuários que só entram via Google (sem senha)
ALTER TABLE users
  MODIFY COLUMN password_hash VARCHAR(255) NULL;
