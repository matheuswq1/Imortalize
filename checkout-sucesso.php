<?php
/**
 * Checkout — Sucesso
 * Exibida após pagamento (simulado ou real) e ativação do plano.
 * No fluxo Stripe: recebe session_id, valida pagamento e ativa o plano.
 */
session_start();
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$user_id = (int) $_SESSION['user_id'];
$plan_id = isset($_GET['plan_id']) ? (int) $_GET['plan_id'] : 0;
$session_id = isset($_GET['session_id']) ? trim($_GET['session_id']) : '';

if ($session_id !== '') {
  // Fluxo Stripe: validar sessão e ativar plano
  $autoload = __DIR__ . '/vendor/autoload.php';
  if (file_exists($autoload)) {
    require_once $autoload;
  }
  $stripe_config = file_exists(__DIR__ . '/config/stripe.config.php') ? require __DIR__ . '/config/stripe.config.php' : ['mode' => 'fake'];
  $secret = trim($stripe_config['secret_key'] ?? '');
  $modo_fake = ($stripe_config['mode'] ?? 'fake') === 'fake';
  $ativado = false;
  if (!$modo_fake && $secret !== '' && strpos($secret, 'FAKE') === false && class_exists('\Stripe\StripeClient')) {
    try {
      $stripe = new \Stripe\StripeClient($secret);
      $session = $stripe->checkout->sessions->retrieve($session_id, ['expand' => ['line_items']]);
      if ($session->payment_status === 'paid' && !empty($session->client_reference_id)) {
        $ref = $session->client_reference_id;
        $parts = explode('|', $ref);
        if (count($parts) === 2 && (int) $parts[0] === $user_id) {
          $plan_id = (int) $parts[1];
          if (alterarPlano($user_id, $plan_id)) {
            $ativado = true;
          }
        }
      }
    } catch (\Exception $e) {
      // session inválida ou erro de API
    }
  }
  if (!$ativado) {
    header('Location: checkout.php?plan_id=' . $plan_id . '&erro=1');
    exit;
  }
}

$plano = $plan_id > 0 ? buscarPlanoPorId($plan_id) : null;
$page_title = 'Pagamento confirmado | Imortalize';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <link rel="stylesheet" href="css/site.css">
  <style>
    .sucesso-page { min-height: 100vh; padding: 2rem 1rem 4rem; display: flex; align-items: center; justify-content: center; }
    .sucesso-inner { max-width: 420px; margin: 0 auto; text-align: center; }
    .sucesso-icon { font-size: 3rem; margin-bottom: 1rem; color: #b8c4a8; }
    .sucesso-title { font-size: clamp(1.25rem, 3vw, 1.5rem); font-weight: 600; color: #f0f2eb; margin: 0 0 0.75rem; }
    .sucesso-text { font-size: 1rem; color: #b8c4a8; margin-bottom: 1.5rem; line-height: 1.5; }
    .sucesso-plano { font-size: 1.125rem; font-weight: 600; color: #f0f2eb; margin-bottom: 1.5rem; }
    .sucesso-actions { display: flex; flex-direction: column; gap: 0.75rem; }
    .sucesso-actions a {
      display: inline-block; padding: 0.875rem 1.5rem; font-size: 1rem; font-weight: 600; text-decoration: none;
      border-radius: 8px; transition: background 0.2s, transform 0.2s;
    }
    .sucesso-actions .btn-primary { background: #b8c4a8; color: #192112; }
    .sucesso-actions .btn-primary:hover { background: #f0f2eb; transform: translateY(-2px); }
    .sucesso-actions .btn-secondary { background: transparent; color: #b8c4a8; border: 1px solid rgba(255,255,255,0.2); }
    .sucesso-actions .btn-secondary:hover { color: #f0f2eb; border-color: rgba(255,255,255,0.3); }
  </style>
</head>
<body>
  <div class="sucesso-page">
    <div class="sucesso-inner">
      <div class="sucesso-icon" aria-hidden="true">✓</div>
      <h1 class="sucesso-title">Pagamento confirmado</h1>
      <p class="sucesso-text">Sua assinatura foi ativada com sucesso. O plano já está disponível na sua área.</p>
      <?php if ($plano) : ?>
      <p class="sucesso-plano"><?php echo htmlspecialchars($plano['nome']); ?> — 12 meses</p>
      <?php endif; ?>
      <div class="sucesso-actions">
        <a href="dashboard.php" class="btn-primary">Ir para Minha área</a>
        <a href="alterar-plano.php" class="btn-secondary">Ver planos</a>
      </div>
    </div>
  </div>
</body>
</html>
