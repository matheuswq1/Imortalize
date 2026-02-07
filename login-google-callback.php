<?php
/**
 * Callback do Google OAuth: troca o código por token, obtém dados do usuário e faz login.
 */
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

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
  $_SESSION['login_erro'] = 'Login com Google não configurado.';
  header('Location: login.php');
  exit;
}

// redirect_uri deve ser idêntico ao enviado na autorização (usar o da config se definido)
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

// Validar state (CSRF do OAuth)
$state = isset($_GET['state']) ? $_GET['state'] : '';
if ($state === '' || empty($_SESSION['oauth2_state_google']) || !hash_equals($_SESSION['oauth2_state_google'], $state)) {
  $_SESSION['login_erro'] = 'Requisição inválida. Tente entrar com Google novamente.';
  unset($_SESSION['oauth2_state_google']);
  header('Location: login.php');
  exit;
}
unset($_SESSION['oauth2_state_google']);

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
if ($code === '') {
  $_SESSION['login_erro'] = 'Autorização cancelada ou inválida.';
  header('Location: login.php');
  exit;
}

// Trocar código por access_token
$token_url = 'https://oauth2.googleapis.com/token';
$token_body = http_build_query([
  'code'          => $code,
  'client_id'     => $client_id,
  'client_secret' => $client_secret,
  'redirect_uri'  => $redirect_uri,
  'grant_type'    => 'authorization_code',
], '', '&');

$ctx = stream_context_create([
  'http' => [
    'method'  => 'POST',
    'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
    'content' => $token_body,
  ],
]);
$token_json = @file_get_contents($token_url, false, $ctx);
if ($token_json === false) {
  $_SESSION['login_erro'] = 'Erro ao comunicar com o Google. Tente novamente.';
  header('Location: login.php');
  exit;
}

$token_data = json_decode($token_json, true);
$access_token = isset($token_data['access_token']) ? $token_data['access_token'] : '';
if ($access_token === '') {
  error_log('Google token response: ' . $token_json);
  $_SESSION['login_erro'] = 'Não foi possível obter acesso do Google. Tente novamente.';
  header('Location: login.php');
  exit;
}

// Obter dados do perfil
$userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ctx_user = stream_context_create([
  'http' => [
    'method' => 'GET',
    'header' => "Authorization: Bearer " . $access_token . "\r\n",
  ],
]);
$userinfo_json = @file_get_contents($userinfo_url, false, $ctx_user);
if ($userinfo_json === false) {
  $_SESSION['login_erro'] = 'Erro ao obter dados do perfil Google.';
  header('Location: login.php');
  exit;
}

$userinfo = json_decode($userinfo_json, true);
$google_id = isset($userinfo['id']) ? (string) $userinfo['id'] : '';
$email = isset($userinfo['email']) ? trim($userinfo['email']) : '';
$nome = isset($userinfo['name']) ? trim($userinfo['name']) : '';

if ($google_id === '' || $email === '') {
  $_SESSION['login_erro'] = 'O Google não retornou e-mail. Verifique as permissões da conta.';
  header('Location: login.php');
  exit;
}

$user = buscarOuCriarUsuarioGoogle($google_id, $email, $nome);
if (!$user) {
  $_SESSION['login_erro'] = 'Erro ao criar ou recuperar sua conta. Tente novamente.';
  header('Location: login.php');
  exit;
}

session_regenerate_secure();
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_nome'] = $user['nome'];
$_SESSION['user_email'] = $user['email'];
header('Location: dashboard.php');
exit;
