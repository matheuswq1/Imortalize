-- Adiciona campos para Stripe e preço anual na tabela plans
-- Execute este script uma vez (ex.: via phpMyAdmin ou mysql CLI).

ALTER TABLE plans
  ADD COLUMN preco_anual_cents INT UNSIGNED NULL DEFAULT NULL COMMENT 'Preço anual em centavos (BRL)' AFTER ativo,
  ADD COLUMN stripe_product_id VARCHAR(255) NULL DEFAULT NULL COMMENT 'ID do Product no Stripe' AFTER preco_anual_cents,
  ADD COLUMN stripe_price_id VARCHAR(255) NULL DEFAULT NULL COMMENT 'ID do Price (anual) no Stripe' AFTER stripe_product_id,
  ADD INDEX plans_stripe_product_id (stripe_product_id),
  ADD INDEX plans_stripe_price_id (stripe_price_id);
