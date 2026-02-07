<?php
/**
 * Checkout — Imortalize
 * Resumo do plano + formulário de pagamento. Preparado para Stripe; modo fake usa dados simulados.
 */
session_start();
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['user_id'])) {
  header('Location: login.php?redirect=' . urlencode('checkout.php' . (isset($_GET['plan_id']) ? '?plan_id=' . (int) $_GET['plan_id'] : '')));
  exit;
}

$user_id = (int) $_SESSION['user_id'];
$plan_id = isset($_GET['plan_id']) ? (int) $_GET['plan_id'] : 0;
$plano = $plan_id > 0 ? buscarPlanoPorId($plan_id) : null;

if (!$plano) {
  header('Location: alterar-plano.php?erro=plano');
  exit;
}

$stripe_config = file_exists(__DIR__ . '/config/stripe.config.php') ? require __DIR__ . '/config/stripe.config.php' : ['mode' => 'fake'];
$modo_fake = ($stripe_config['mode'] ?? 'fake') === 'fake';

$page_title = 'Checkout | Imortalize';
$erro_get = isset($_GET['erro']) ? $_GET['erro'] : '';
$erro_msg = '';
if ($erro_get === 'csrf') {
  $erro_msg = 'Sua sessão expirou ou o formulário foi enviado duas vezes. Volte à página de planos e tente novamente.';
} elseif ($erro_get === 'cartao') {
  $erro_msg = 'Número do cartão inválido. Use pelo menos 13 dígitos (no modo teste pode ser 4242 4242 4242 4242).';
} elseif ($erro_get === 'plano') {
  $erro_msg = 'Plano não encontrado ou não está mais disponível. Escolha outro plano.';
} elseif ($erro_get === 'email') {
  $erro_msg = 'Seu cadastro não possui um e-mail válido. Atualize em Configurações antes de pagar.';
} elseif ($erro_get === 'stripe') {
  $erro_msg = 'Erro ao abrir a página de pagamento do Stripe. Verifique se as chaves em config/stripe.config.php estão corretas e se o plano está vinculado. Detalhes em logs/checkout.log.';
} elseif ($erro_get !== '') {
  $erro_msg = 'Não foi possível processar o pagamento. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <link rel="stylesheet" href="css/site.css">
  <style>
    .checkout-page { min-height: 100vh; padding: 2rem 1rem 4rem; }
    .checkout-inner { max-width: 560px; margin: 0 auto; }
    .checkout-nav { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; }
    .checkout-nav a { font-size: 0.9375rem; color: #b8c4a8; text-decoration: none; }
    .checkout-nav a:hover { color: #f0f2eb; }
    .checkout-title { font-size: clamp(1.25rem, 3vw, 1.5rem); font-weight: 600; color: #f0f2eb; margin: 0 0 1.5rem; }
    .checkout-card {
      background: rgba(39, 51, 28, 0.7); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px;
      padding: 1.5rem 1.75rem; margin-bottom: 1.5rem;
    }
    .checkout-card h2 { font-size: 1rem; font-weight: 600; color: #b8c4a8; margin: 0 0 0.75rem; }
    .checkout-plano-nome { font-size: 1.25rem; color: #f0f2eb; margin-bottom: 0.5rem; }
    .checkout-plano-desc { font-size: 0.9375rem; color: #b8c4a8; margin-bottom: 0.75rem; }
    .checkout-valor { font-size: 1.5rem; font-weight: 700; color: #f0f2eb; margin-top: 0.5rem; }
    .checkout-form label { display: block; font-size: 0.9375rem; color: #b8c4a8; margin-bottom: 0.35rem; margin-top: 1rem; }
    .checkout-form label:first-of-type { margin-top: 0; }
    .checkout-form input {
      width: 100%; box-sizing: border-box; padding: 0.75rem 1rem; font-size: 1rem; color: #f0f2eb;
      background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; margin-bottom: 0.5rem;
    }
    .checkout-form input::placeholder { color: #8a9a7a; }
    .checkout-form .row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .checkout-form button[type="submit"] {
      width: 100%; margin-top: 1.5rem; padding: 0.875rem 1.5rem; font-size: 1rem; font-weight: 600; color: #192112;
      background: #b8c4a8; border: none; border-radius: 8px; cursor: pointer;
    }
    .checkout-form button[type="submit"]:hover { background: #f0f2eb; }
    .checkout-btn-stripe { background: #635bff !important; color: #fff !important; }
    .checkout-btn-stripe:hover { background: #7b73ff !important; color: #fff !important; }
    .checkout-fake-badge { font-size: 0.75rem; color: #8a9a7a; background: rgba(255,255,255,0.06); padding: 0.25rem 0.5rem; border-radius: 6px; margin-top: 0.5rem; display: inline-block; }
  </style>
</head>
<body>
  <div class="checkout-page">
    <div class="checkout-inner">
      <nav class="checkout-nav" aria-label="Navegação">
        <a href="alterar-plano.php">← Voltar aos planos</a>
        <a href="dashboard.php">Minha área</a>
      </nav>

      <h1 class="checkout-title">Checkout</h1>

      <?php if ($erro_msg !== '') : ?>
      <p role="alert" style="background: rgba(200,100,100,0.15); border: 1px solid rgba(200,100,100,0.3); border-radius: 8px; padding: 0.75rem 1rem; color: #e8a0a0; margin-bottom: 1.5rem;">
        <?php echo htmlspecialchars($erro_msg); ?>
      </p>
      <?php endif; ?>

      <section class="checkout-card" aria-labelledby="resumo-plano">
        <h2 id="resumo-plano">Resumo do pedido</h2>
        <p class="checkout-plano-nome"><?php echo htmlspecialchars($plano['nome']); ?></p>
        <?php if (!empty($plano['descricao_curta'])) : ?>
        <p class="checkout-plano-desc"><?php echo htmlspecialchars($plano['descricao_curta']); ?></p>
        <?php endif; ?>
        <p class="checkout-plano-desc">Assinatura por 12 meses.</p>
        <?php
          $cents = isset($plano['preco_anual_cents']) ? (int) $plano['preco_anual_cents'] : 0;
          $valor_fmt = $cents > 0 ? number_format($cents / 100, 2, ',', '.') : '0,00';
        ?>
        <p class="checkout-valor">R$ <?php echo htmlspecialchars($valor_fmt); ?> <span style="font-size: 0.875rem; font-weight: 400; color: #b8c4a8;">/ano</span></p>
      </section>

      <section class="checkout-card" aria-labelledby="dados-pagamento">
        <h2 id="dados-pagamento">Dados do pagamento</h2>
        <?php
          $usar_stripe_real = !$modo_fake && !empty($plano['stripe_price_id']);
          $plano_sem_preco_stripe = !$modo_fake && empty($plano['stripe_price_id']);
        ?>
        <?php if ($plano_sem_preco_stripe) : ?>
        <p role="alert" style="background: rgba(200,150,80,0.15); border: 1px solid rgba(200,150,80,0.4); border-radius: 8px; padding: 0.75rem 1rem; color: #e8c890; margin-bottom: 1rem;">
          Este plano ainda não tem preço vinculado ao Stripe. No painel admin, edite o plano e informe o <strong>Preço anual (R$)</strong> e salve para gerar o link de pagamento.
        </p>
        <?php endif; ?>
        <?php if ($modo_fake) : ?>
        <span class="checkout-fake-badge">Modo teste — use qualquer número de cartão; o pagamento será simulado (não usa Stripe).</span>
        <?php elseif ($usar_stripe_real) : ?>
        <p class="checkout-plano-desc" style="margin-bottom: 1rem;">Você será redirecionado ao ambiente seguro do Stripe para concluir o pagamento.</p>
        <?php endif; ?>

        <?php if ($usar_stripe_real) : ?>
        <form class="checkout-form" method="post" action="checkout-processar.php" id="checkout-form">
          <input type="hidden" name="plan_id" value="<?php echo (int) $plan_id; ?>">
          <input type="hidden" name="csrf_checkout" value="<?php echo htmlspecialchars($_SESSION['csrf_checkout'] = bin2hex(random_bytes(16))); ?>">
          <button type="submit" class="checkout-btn-stripe">Pagar com Stripe (pagamento seguro)</button>
        </form>
        <?php elseif (!$plano_sem_preco_stripe) : ?>
        <form class="checkout-form" method="post" action="checkout-processar.php" id="checkout-form">
          <input type="hidden" name="plan_id" value="<?php echo (int) $plan_id; ?>">
          <input type="hidden" name="csrf_checkout" value="<?php echo htmlspecialchars($_SESSION['csrf_checkout'] = bin2hex(random_bytes(16))); ?>">

          <label for="card_name">Nome no cartão</label>
          <input type="text" id="card_name" name="card_name" placeholder="Como está no cartão" required maxlength="100" autocomplete="cc-name">

          <label for="card_number">Número do cartão</label>
          <input type="text" id="card_number" name="card_number" placeholder="4242 4242 4242 4242" required maxlength="19" autocomplete="cc-number" inputmode="numeric" pattern="[0-9\s]*">

          <div class="row">
            <div>
              <label for="card_expiry">Validade (MM/AA)</label>
              <input type="text" id="card_expiry" name="card_expiry" placeholder="12/28" required maxlength="5" autocomplete="cc-exp" inputmode="numeric">
            </div>
            <div>
              <label for="card_cvc">CVC</label>
              <input type="text" id="card_cvc" name="card_cvc" placeholder="123" required maxlength="4" autocomplete="cc-csc" inputmode="numeric">
            </div>
          </div>

          <button type="submit">Pagar e ativar plano (simulado)</button>
        </form>
        <?php endif; ?>
      </section>
    </div>
  </div>
</body>
</html>
