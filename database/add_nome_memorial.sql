-- Adiciona o campo "nome" na tabela memorials (exibido abaixo da foto de perfil).
-- Execute no phpMyAdmin. Se aparecer "Nome da coluna duplicado", a coluna já existe.

ALTER TABLE memorials
  ADD COLUMN nome VARCHAR(255) DEFAULT NULL COMMENT 'Nome exibido abaixo da foto de perfil' AFTER capa_media_id;
