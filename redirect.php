<?php
/**
 * Redirect dinâmico para QR Code — Imortalize
 * URL: /redirect.php?id={unique_id}
 * Redireciona para a URL do memorial (ou fallback) e incrementa scans.
 */
require_once __DIR__ . '/config/database.php';

$unique_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if ($unique_id === '') {
  http_response_code(404);
  header('Content-Type: text/html; charset=utf-8');
  echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>QR não encontrado</title></head><body><p>QR não encontrado.</p><p><a href="index.php">Ir para o Imortalize</a></p></body></html>';
  exit;
}

$qr = qrcodePorUniqueId($unique_id);

if (!$qr) {
  http_response_code(404);
  header('Content-Type: text/html; charset=utf-8');
  echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>QR inválido</title></head><body><p>Este QR Code não é válido ou não está ativado.</p><p><a href="index.php">Ir para o Imortalize</a></p></body></html>';
  exit;
}

qrcodeIncrementarScans($unique_id);

$url = getBaseUrl() . '/index.php';
if ($qr['status'] === 'usado' && $qr['content'] !== null) {
  $data = json_decode($qr['content'], true);
  if (!empty($data['url'])) {
    $url = function_exists('normalizarUrlQrRedirect') ? normalizarUrlQrRedirect($data['url']) : $data['url'];
  }
}

header('Location: ' . $url, true, 302);
exit;
