<?php
/**
 * Configuração do Google OAuth — Imortalize
 * Lê as configurações do arquivo .env
 */

require_once __DIR__ . '/env.php';

return [
  'client_id'     => env('GOOGLE_CLIENT_ID', ''),
  'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
  'redirect_uri'  => env('GOOGLE_REDIRECT_URI', ''),
];
