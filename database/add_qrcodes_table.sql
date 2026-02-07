-- Tabela qrcodes para painel ADM — Imortalize
-- Execute um bloco por vez no phpMyAdmin se preferir.

-- 1) Criar tabela
CREATE TABLE IF NOT EXISTS qrcodes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  memorial_id INT UNSIGNED DEFAULT NULL COMMENT 'NULL = disponível no estoque',
  unique_id VARCHAR(36) NOT NULL,
  type ENUM('url','texto','wifi','vcard') NOT NULL DEFAULT 'url',
  content JSON DEFAULT NULL COMMENT 'ex: {"url": "https://..."}',
  scans INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('disponivel','usado') NOT NULL DEFAULT 'disponivel',
  imagem_path VARCHAR(500) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY qrcodes_unique_id (unique_id),
  KEY qrcodes_memorial_id (memorial_id),
  KEY qrcodes_status (status),
  CONSTRAINT qrcodes_memorial_foreign FOREIGN KEY (memorial_id) REFERENCES memorials (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
