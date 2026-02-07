<?php
/**
 * Helpers de autenticação — CSRF e sessão
 * Garante que session_start() foi chamado antes de usar estes helpers.
 */

define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_INPUT_NAME', '_csrf');

/**
 * Gera e armazena um token CSRF na sessão; retorna o token para colocar no formulário.
 * @return string
 */
function csrf_token() {
  if (empty($_SESSION[CSRF_TOKEN_NAME]) || !is_string($_SESSION[CSRF_TOKEN_NAME])) {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
  }
  return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Gera um input hidden HTML com o token CSRF.
 * @return string
 */
function csrf_field() {
  $name = htmlspecialchars(CSRF_INPUT_NAME, ENT_QUOTES, 'UTF-8');
  $value = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
  return '<input type="hidden" name="' . $name . '" value="' . $value . '">';
}

/**
 * Valida o token CSRF enviado no POST.
 * @return bool
 */
function csrf_validate() {
  $token = isset($_POST[CSRF_INPUT_NAME]) ? $_POST[CSRF_INPUT_NAME] : '';
  $expected = isset($_SESSION[CSRF_TOKEN_NAME]) ? $_SESSION[CSRF_TOKEN_NAME] : '';
  if (!is_string($token) || !is_string($expected) || $token === '' || $expected === '') {
    return false;
  }
  return hash_equals($expected, $token);
}

/**
 * Regenera o ID da sessão (anti-fixation) e opcionalmente invalida o token CSRF.
 */
function session_regenerate_secure() {
  session_regenerate_id(true);
  // Novo token após login para evitar reuso
  $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}
