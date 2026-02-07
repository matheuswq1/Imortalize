<?php
/**
 * Inicia o fluxo de login com Google OAuth.
 * Redireciona o usuário para o consentimento do Google.
 */
session_start();

$config_file = __DIR__ . '/config/google-oauth.config.php';
if (!file_exists($config_file)) {
  $_SESSION['login_erro'] = 'Login com Google não configurado.';
  header('Location: login.php');
  exit;
}

$config = require $config_file;
$client_id = isset($config['client_id']) ? trim($config['client_id']) : '';
$client_secret = isset($config['client_secret']) ? trim($config['client_secret']) : '';

if ($client_id === '' || $client_secret === '') {
  $_SESSION['login_erro'] = 'Login com Google não configurado (credenciais faltando).';
  header('Location: login.php');
  exit;
}

// redirect_uri: usar o da config se estiver definido (evita redirect_uri_mismatch)
if (!empty($config['redirect_uri']) && is_string($config['redirect_uri'])) {
  $redirect_uri = rtrim(trim($config['redirect_uri']), '/');
  if (strpos($redirect_uri, 'http') !== 0) {
    $redirect_uri = '';
  }
}
if (empty($redirect_uri)) {
  $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
  $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
  $redirect_uri = $protocol . '://' . $host . $base . '/login-google-callback.php';
}

$params = [
  'client_id'     => $client_id,
  'redirect_uri'  => $redirect_uri,
  'response_type' => 'code',
  'scope'         => 'openid email profile',
  'access_type'   => 'online',
  'prompt'        => 'select_account',
];

$_SESSION['oauth2_state_google'] = bin2hex(random_bytes(16));
$params['state'] = $_SESSION['oauth2_state_google'];

$url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
header('Location: ' . $url);
exit;
