<?php
/**
 * Exemplo de configuração Stripe — Imortalize
 * Copie para stripe.config.php e preencha com suas chaves reais quando for integrar.
 *
 * Modo fake: use mode => 'fake' e chaves placeholder para simular pagamento sem API.
 * Modo live: use mode => 'live', pk_live_* e sk_live_* para produção.
 */

return [
  'mode' => 'fake', // 'fake' | 'live'
  'publishable_key' => 'pk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
  'secret_key' => 'sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
  'currency' => 'brl',
];

// NOTA: Este arquivo é apenas um exemplo.
// A configuração real agora é feita através do arquivo .env
// Copie .env.example para .env e preencha com suas credenciais reais.
