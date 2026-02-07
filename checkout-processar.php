<?php
/**
 * Processa o checkout — Imortalize
 * Modo fake: simula pagamento e ativa o plano. Futuro: integrar Stripe (PaymentIntent / Checkout Session).
 */
session_start();
require_once __DIR__ . '/config/database.php';

$log_dir = __DIR__ . '/logs';
$log_file = $log_dir . '/checkout.log';

function checkoutLog($msg, $log_file) {
  $fp = @fopen($log_file, 'a');
  if ($fp) {
    fwrite($fp, date('Y-m-d H:i:s') . ' ' . $msg . "\n");
    fclose($fp);
  }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: alterar-plano.php');
  exit;
}

if (empty($_SESSION['user_id'])) {
  checkoutLog('checkout-processar: usuário não logado', $log_file);
  header('Location: login.php');
  exit;
}

$user_id = (int) $_SESSION['user_id'];
$plan_id = isset($_POST['plan_id']) ? (int) $_POST['plan_id'] : 0;

if ($plan_id <= 0) {
  checkoutLog('checkout-processar: plan_id inválido ou zero', $log_file);
  header('Location: checkout.php?plan_id=' . $plan_id . '&erro=plano');
  exit;
}

// CSRF
$token = $_POST['csrf_checkout'] ?? '';
if (!isset($_SESSION['csrf_checkout']) || $token === '' || !hash_equals($_SESSION['csrf_checkout'], $token)) {
  checkoutLog('checkout-processar: CSRF inválido ou expirado (plan_id=' . $plan_id . ', user_id=' . $user_id . ')', $log_file);
  header('Location: checkout.php?plan_id=' . $plan_id . '&erro=csrf');
  exit;
}

$stripe_config = file_exists(__DIR__ . '/config/stripe.config.php') ? require __DIR__ . '/config/stripe.config.php' : ['mode' => 'fake'];
$modo_fake = ($stripe_config['mode'] ?? 'fake') === 'fake';

if ($modo_fake) {
  // Modo fake: validar apenas presença dos campos (não enviar nada ao Stripe)
  $card_number = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
  if (strlen($card_number) < 13) {
    checkoutLog('checkout-processar: número do cartão inválido (menos de 13 dígitos)', $log_file);
    header('Location: checkout.php?plan_id=' . $plan_id . '&erro=cartao');
    exit;
  }
  // Simula pagamento aprovado; ativa o plano
} else {
  // Stripe real: criar Checkout Session e redirecionar ao Stripe
  $autoload = __DIR__ . '/vendor/autoload.php';
  if (file_exists($autoload)) {
    require_once $autoload;
  }
  $plano = buscarPlanoPorId($plan_id);
  if (!$plano || empty($plano['stripe_price_id'])) {
    checkoutLog('checkout-processar: plano sem stripe_price_id (plan_id=' . $plan_id . ')', $log_file);
    header('Location: checkout.php?plan_id=' . $plan_id . '&erro=plano');
    exit;
  }
  if (!class_exists('\Stripe\StripeClient')) {
    checkoutLog('checkout-processar: Stripe SDK não instalado', $log_file);
    header('Location: checkout.php?plan_id=' . $plan_id . '&erro=1');
    exit;
  }
  $secret = trim($stripe_config['secret_key'] ?? '');
  if ($secret === '' || strpos($secret, 'FAKE') !== false) {
    checkoutLog('checkout-processar: chave secreta Stripe não configurada', $log_file);
    header('Location: checkout.php?plan_id=' . $plan_id . '&erro=1');
    exit;
  }
  $customer_email = trim($_SESSION['user_email'] ?? '');
  if ($customer_email === '') {
    $u = buscarUsuarioPorId($user_id);
    $customer_email = $u ? trim($u['email'] ?? '') : '';
  }
  if ($customer_email === '' || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
    checkoutLog('checkout-processar: usuário sem e-mail válido (user_id=' . $user_id . ')', $log_file);
    header('Location: checkout.php?plan_id=' . $plan_id . '&erro=email');
    exit;
  }

  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
  $base_url_site = $scheme . '://' . $host . $base . '/';
  try {
    $stripe = new \Stripe\StripeClient($secret);
    $session = $stripe->checkout->sessions->create([
      'mode' => 'subscription',
      'line_items' => [
        [
          'price' => $plano['stripe_price_id'],
          'quantity' => 1,
        ],
      ],
      'success_url' => $base_url_site . 'checkout-sucesso.php?session_id={CHECKOUT_SESSION_ID}',
      'cancel_url' => $base_url_site . 'checkout.php?plan_id=' . $plan_id,
      'client_reference_id' => $user_id . '|' . $plan_id,
      'customer_email' => $customer_email,
    ]);
    if (!empty($session->url)) {
      header('Location: ' . $session->url);
      exit;
    }
  } catch (\Exception $e) {
    checkoutLog('checkout-processar: Stripe Session erro: ' . $e->getMessage(), $log_file);
    header('Location: checkout.php?plan_id=' . $plan_id . '&erro=stripe');
    exit;
  }
  header('Location: checkout.php?plan_id=' . $plan_id . '&erro=stripe');
  exit;
}

if (alterarPlano($user_id, $plan_id)) {
  unset($_SESSION['csrf_checkout']);
  header('Location: checkout-sucesso.php?plan_id=' . $plan_id);
  exit;
}

checkoutLog('checkout-processar: alterarPlano retornou false (plan_id=' . $plan_id . ', user_id=' . $user_id . ') — plano pode estar inativo ou não existir', $log_file);
header('Location: checkout.php?plan_id=' . $plan_id . '&erro=plano');
exit;
