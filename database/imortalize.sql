-- ============================================================
-- Imortalize — Banco de dados MySQL
-- Cria o banco e as tabelas necessárias, já populado com planos
-- e usuário de teste (senha: 123456).
-- ============================================================
-- Uso: mysql -u root -p < database/imortalize.sql
-- Ou no phpMyAdmin: importar este arquivo
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Criar banco
-- ------------------------------------------------------------
DROP DATABASE IF EXISTS imortalize;
CREATE DATABASE imortalize
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;
USE imortalize;

-- ------------------------------------------------------------
-- Tabela: users (usuários)
-- ------------------------------------------------------------
CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  telefone VARCHAR(20) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  remember_token VARCHAR(100) DEFAULT NULL,
  email_verified_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: plans (planos)
-- ------------------------------------------------------------
CREATE TABLE plans (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  descricao_curta VARCHAR(255) DEFAULT NULL,
  max_fotos INT UNSIGNED NOT NULL DEFAULT 0,
  tem_audio TINYINT(1) NOT NULL DEFAULT 0,
  tem_video TINYINT(1) NOT NULL DEFAULT 0,
  ordem INT UNSIGNED NOT NULL DEFAULT 0,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY plans_slug_unique (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: plan_benefits (benefícios de cada plano)
-- ------------------------------------------------------------
CREATE TABLE plan_benefits (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  plan_id INT UNSIGNED NOT NULL,
  descricao VARCHAR(255) NOT NULL,
  ordem INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY plan_benefits_plan_id_foreign (plan_id),
  CONSTRAINT plan_benefits_plan_id_foreign FOREIGN KEY (plan_id) REFERENCES plans (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: subscriptions (assinaturas — usuário x plano)
-- ------------------------------------------------------------
CREATE TABLE subscriptions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  plan_id INT UNSIGNED NOT NULL,
  status ENUM('ativo','cancelado','expirado','pendente') NOT NULL DEFAULT 'ativo',
  data_inicio DATE NOT NULL,
  data_vencimento DATE NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY subscriptions_user_id_foreign (user_id),
  KEY subscriptions_plan_id_foreign (plan_id),
  CONSTRAINT subscriptions_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT subscriptions_plan_id_foreign FOREIGN KEY (plan_id) REFERENCES plans (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: memorials (memoriais)
-- ------------------------------------------------------------
CREATE TABLE memorials (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  titulo VARCHAR(255) NOT NULL COMMENT 'Nome do ente querido ou título do memorial',
  slug VARCHAR(255) NOT NULL COMMENT 'URL amigável (ex: maria-silva)',
  biografia TEXT DEFAULT NULL,
  status ENUM('rascunho','publicado') NOT NULL DEFAULT 'rascunho',
  qr_code_path VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY memorials_slug_unique (slug),
  KEY memorials_user_id_foreign (user_id),
  CONSTRAINT memorials_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: memorial_media (fotos, vídeos, áudios do memorial)
-- ------------------------------------------------------------
CREATE TABLE memorial_media (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  memorial_id INT UNSIGNED NOT NULL,
  tipo ENUM('foto','video','audio','texto') NOT NULL DEFAULT 'foto',
  caminho VARCHAR(500) NOT NULL,
  legenda VARCHAR(500) DEFAULT NULL,
  ordem INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY memorial_media_memorial_id_foreign (memorial_id),
  CONSTRAINT memorial_media_memorial_id_foreign FOREIGN KEY (memorial_id) REFERENCES memorials (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: newsletter (e-mails da newsletter)
-- ------------------------------------------------------------
CREATE TABLE newsletter (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY newsletter_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DADOS INICIAIS
-- ============================================================

-- Planos (Essencial, Tributo, Legado)
INSERT INTO plans (id, nome, slug, descricao_curta, max_fotos, tem_audio, tem_video, ordem, ativo) VALUES
(1, 'Plano Essencial', 'essencial', '12 Fotos • Biografia • Layout perfil e capa', 12, 0, 0, 1, 1),
(2, 'Plano Tributo', 'tributo', '24 Fotos • Biografia • Áudio', 24, 1, 0, 2, 1),
(3, 'Plano Legado', 'legado', '50 Fotos • Vídeos • Áudio', 50, 1, 1, 3, 1);

-- Benefícios por plano
INSERT INTO plan_benefits (plan_id, descricao, ordem) VALUES
(1, '12 Fotos', 1),
(1, 'Biografia completa', 2),
(1, 'Layout perfil e capa', 3),
(1, 'QR Code em aço inox', 4),
(2, '24 Fotos', 1),
(2, 'Biografia completa', 2),
(2, 'Layout perfil e capa', 3),
(2, 'Áudio', 4),
(2, 'QR Code em aço inox', 5),
(3, '50 Fotos', 1),
(3, 'Vídeos', 2),
(3, 'Biografia completa', 3),
(3, 'Layout perfil e capa', 4),
(3, 'Áudio', 5),
(3, 'QR Code em aço inox', 6);

-- Usuário de teste (senha: 123456)
-- Hash bcrypt para "123456". Após importar, execute: php database/atualizar-senha-teste.php
-- para garantir que a senha 123456 funcione (ou use o hash já gerado abaixo).
INSERT INTO users (id, nome, email, telefone, password_hash, created_at, updated_at) VALUES
(1, 'Usuário Teste', 'teste@imortalize.com', NULL, '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', NOW(), NOW());

-- Assinatura ativa do usuário teste (Plano Tributo, 12 meses)
INSERT INTO subscriptions (user_id, plan_id, status, data_inicio, data_vencimento) VALUES
(1, 2, 'ativo', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 MONTH));

-- ============================================================
-- FIM
-- ============================================================
