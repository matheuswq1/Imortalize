-- Coluna layout_style na tabela qrcodes (padrao | circular | com_logo).
-- Execute um bloco por vez no phpMyAdmin se preferir.

ALTER TABLE qrcodes
  ADD COLUMN layout_style VARCHAR(20) NOT NULL DEFAULT 'padrao'
  COMMENT 'padrao, circular, com_logo' AFTER status;
