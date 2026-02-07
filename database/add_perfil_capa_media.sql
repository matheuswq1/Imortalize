-- Adiciona perfil_media_id e capa_media_id em memorials.
-- IMPORTANTE: Execute UM BLOCO POR VEZ no phpMyAdmin (SQL).
-- Se aparecer "Nome da coluna duplicado", essa coluna já existe — pule para o próximo bloco.

-- Bloco 1: perfil_media_id (se der "duplicado", pule)
ALTER TABLE memorials
  ADD COLUMN perfil_media_id INT UNSIGNED DEFAULT NULL AFTER biografia;

-- Bloco 2: capa_media_id (se der "duplicado", pule)
ALTER TABLE memorials
  ADD COLUMN capa_media_id INT UNSIGNED DEFAULT NULL AFTER perfil_media_id;

-- Bloco 3: índice perfil (opcional; se der erro de chave duplicada, pule)
ALTER TABLE memorials ADD KEY memorials_perfil_media_id (perfil_media_id);

-- Bloco 4: índice capa (opcional; se der erro de chave duplicada, pule)
ALTER TABLE memorials ADD KEY memorials_capa_media_id (capa_media_id);

-- Bloco 5: foreign key perfil (opcional; se der erro, pule)
ALTER TABLE memorials
  ADD CONSTRAINT memorials_perfil_media_foreign
  FOREIGN KEY (perfil_media_id) REFERENCES memorial_media (id) ON DELETE SET NULL;

-- Bloco 6: foreign key capa (opcional; se der erro, pule)
ALTER TABLE memorials
  ADD CONSTRAINT memorials_capa_media_foreign
  FOREIGN KEY (capa_media_id) REFERENCES memorial_media (id) ON DELETE SET NULL;
