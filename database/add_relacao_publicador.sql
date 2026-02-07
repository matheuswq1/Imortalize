-- Campo "Qual sua relação com essa pessoa?" — exibido como "Publicado por [nome] ([relação])" na capa.
-- Execute no phpMyAdmin. Se aparecer "Nome da coluna duplicado", a coluna já existe.

ALTER TABLE memorials
  ADD COLUMN relacao_publicador VARCHAR(50) DEFAULT NULL COMMENT 'Relação do publicador com a pessoa: filha, filho, mae, pai, etc.' AFTER nome;
