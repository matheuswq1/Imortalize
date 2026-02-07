-- Datas e mensagem curta no memorial (usados no formulário de edição e criação).
-- Execute no phpMyAdmin. Se aparecer "Nome da coluna duplicado", a coluna já existe.

USE imortalize;

ALTER TABLE memorials
  ADD COLUMN data_nascimento DATE DEFAULT NULL COMMENT 'Data de nascimento da pessoa homenageada' AFTER relacao_publicador;

ALTER TABLE memorials
  ADD COLUMN data_obito DATE DEFAULT NULL COMMENT 'Data do óbito' AFTER data_nascimento;

ALTER TABLE memorials
  ADD COLUMN mensagem_curta VARCHAR(120) DEFAULT NULL COMMENT 'Frase curta (até 120 caracteres)' AFTER data_obito;
