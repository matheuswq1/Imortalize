-- Tabela para mensagens do formulário "Fale conosco" — Imortalize
-- Execute uma vez no MySQL (phpMyAdmin ou linha de comando).

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  mensagem TEXT NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  lido TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY contact_messages_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
