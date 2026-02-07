<?php
/**
 * Sincronização de planos com o Stripe (Product + Price anual).
 * Usado ao criar, editar ou excluir um plano no admin.
 *
 * Modo 'fake': não chama a API; apenas salva preco_anual_cents no banco.
 */

if (!defined('STRIPE_SYNC_LOADED')) {
  define('STRIPE_SYNC_LOADED', true);
  $autoload = dirname(__DIR__) . '/vendor/autoload.php';
  if (file_exists($autoload)) {
    require_once $autoload;
  }
}

/**
 * Sincroniza um plano com o Stripe: cria ou atualiza Product e Price (anual).
 * Retorna array ['ok' => true, 'stripe_product_id' => ..., 'stripe_price_id' => ...]
 * ou ['ok' => false, 'erro' => 'mensagem'].
 *
 * @param PDO $pdo
 * @param int $plan_id ID do plano no banco
 * @param string $nome Nome do plano
 * @param string $slug Slug (para metadata)
 * @param string $descricao_curta Descrição
 * @param int|null $preco_anual_cents Preço anual em centavos (BRL). Null = não cobrar / não criar price
 * @param string|null $stripe_product_id ID do Product no Stripe (se já existir)
 * @param string|null $stripe_price_id ID do Price atual no Stripe (se já existir)
 * @param array $stripe_config Config do Stripe (mode, secret_key, currency)
 * @return array
 */
function stripeSyncPlan($pdo, $plan_id, $nome, $slug, $descricao_curta, $preco_anual_cents, $stripe_product_id, $stripe_price_id, $stripe_config) {
  $mode = $stripe_config['mode'] ?? 'fake';
  if ($mode === 'fake') {
    return ['ok' => true, 'stripe_product_id' => $stripe_product_id, 'stripe_price_id' => $stripe_price_id];
  }

  if (!class_exists('\Stripe\StripeClient')) {
    return ['ok' => false, 'erro' => 'SDK Stripe não instalado. Execute: composer require stripe/stripe-php'];
  }

  $secret = $stripe_config['secret_key'] ?? '';
  if ($secret === '' || strpos($secret, 'FAKE') !== false) {
    return ['ok' => false, 'erro' => 'Chave secreta do Stripe não configurada em config/stripe.config.php'];
  }

  $currency = strtolower($stripe_config['currency'] ?? 'brl');

  try {
    $stripe = new \Stripe\StripeClient($secret);

    if ($stripe_product_id === null || $stripe_product_id === '') {
      // Criar novo Product
      $product = $stripe->products->create([
        'name' => $nome,
        'description' => $descricao_curta !== '' ? $descricao_curta : null,
        'metadata' => ['plan_id' => (string) $plan_id, 'slug' => $slug],
      ]);
      $stripe_product_id = $product->id;
    } else {
      // Atualizar Product existente
      $stripe->products->update($stripe_product_id, [
        'name' => $nome,
        'description' => $descricao_curta !== '' ? $descricao_curta : null,
        'metadata' => ['plan_id' => (string) $plan_id, 'slug' => $slug],
      ]);
    }

    $novo_price_id = null;
    if ($preco_anual_cents !== null && $preco_anual_cents > 0) {
      // No Stripe, preços são imutáveis. Se o valor mudou, criar novo Price.
      $criarNovoPrice = true;
      if ($stripe_price_id) {
        try {
          $priceAtual = $stripe->prices->retrieve($stripe_price_id);
          if ($priceAtual->unit_amount == $preco_anual_cents && $priceAtual->active) {
            $novo_price_id = $stripe_price_id;
            $criarNovoPrice = false;
          }
        } catch (\Exception $e) {
          // Price não existe mais; criar novo
        }
      }

      if ($criarNovoPrice) {
        $price = $stripe->prices->create([
          'product' => $stripe_product_id,
          'unit_amount' => (int) $preco_anual_cents,
          'currency' => $currency,
          'recurring' => ['interval' => 'year'],
          'metadata' => ['plan_id' => (string) $plan_id],
        ]);
        $novo_price_id = $price->id;
        // Desativar o price antigo (se existia e era outro)
        if ($stripe_price_id && $stripe_price_id !== $novo_price_id) {
          try {
            $stripe->prices->update($stripe_price_id, ['active' => false]);
          } catch (\Exception $e) {
            // ignora
          }
        }
      }
    }

    return [
      'ok' => true,
      'stripe_product_id' => $stripe_product_id,
      'stripe_price_id' => $novo_price_id,
    ];
  } catch (\Stripe\Exception\ApiErrorException $e) {
    return ['ok' => false, 'erro' => 'Stripe: ' . $e->getMessage()];
  } catch (\Exception $e) {
    return ['ok' => false, 'erro' => $e->getMessage()];
  }
}

/**
 * Arquiva (desativa) um Product no Stripe ao excluir o plano.
 * Retorna true em sucesso ou se modo fake; false em erro.
 *
 * @param string|null $stripe_product_id
 * @param array $stripe_config
 * @return bool
 */
function stripeArchivePlanProduct($stripe_product_id, $stripe_config) {
  $mode = $stripe_config['mode'] ?? 'fake';
  if ($mode === 'fake' || $stripe_product_id === null || $stripe_product_id === '') {
    return true;
  }

  if (!class_exists('\Stripe\StripeClient')) {
    return true;
  }

  $secret = $stripe_config['secret_key'] ?? '';
  if ($secret === '' || strpos($secret, 'FAKE') !== false) {
    return true;
  }

  try {
    $stripe = new \Stripe\StripeClient($secret);
    $stripe->products->update($stripe_product_id, ['active' => false]);
    return true;
  } catch (\Exception $e) {
    return false;
  }
}
