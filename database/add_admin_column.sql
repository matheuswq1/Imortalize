-- ============================================================
-- Adiciona coluna is_admin na tabela users — Imortalize
-- Execute UMA VEZ no MySQL (phpMyAdmin ou linha de comando):
--
--   mysql -u root -p imortalize < database/add_admin_column.sql
--
-- Ou copie e cole o conteúdo no phpMyAdmin (SQL).
-- ============================================================

USE imortalize;

-- Adiciona coluna is_admin (0 = não admin, 1 = admin)
-- Se a coluna já existir, ignore este erro e rode só os UPDATEs abaixo.
ALTER TABLE users
  ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0
  COMMENT '1 = acesso ao painel admin';

-- Garante que o usuário de teste NÃO é admin (padrão seguro)
UPDATE users SET is_admin = 0 WHERE email = 'teste@imortalize.com' LIMIT 1;

-- Para dar acesso admin a um usuário, execute (troque o e-mail):
-- UPDATE users SET is_admin = 1 WHERE email = 'admin@imortalize.com' LIMIT 1;
