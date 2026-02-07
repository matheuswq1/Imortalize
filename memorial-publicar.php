<?php
/**
 * Publicar memorial — Imortalize
 * Altera status para "publicado", vincula ou gera QR Code e redireciona.
 */
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/qrcode-gerar.php';

if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$memorial_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$user_id = (int) $_SESSION['user_id'];

if (!$memorial_id) {
  header('Location: memoriais.php');
  exit;
}

if (!publicarMemorial($memorial_id, $user_id)) {
  header('Location: memoriais.php');
  exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT slug, qr_code_path FROM memorials WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$memorial_id, $user_id]);
$memorial = $stmt->fetch();
if (!$memorial) {
  header('Location: memorial.php?id=' . $memorial_id . '&publicado=1');
  exit;
}

$url_memorial = urlMemorialPublico($memorial['slug']);
$base_url = getSiteBaseUrl(); // URL do redirect no QR (usar base do site para produção)
$qr_path_rel = null;

// 1) Tentar usar um QR do estoque
$qr = qrcodePegarDisponivel();
if ($qr) {
  $content = json_encode(['url' => $url_memorial]);
  $pdo->prepare("UPDATE qrcodes SET memorial_id = ?, status = 'usado', content = ? WHERE id = ?")
    ->execute([$memorial_id, $content, $qr['id']]);
  if (!empty($qr['imagem_path'])) {
    $qr_path_rel = $qr['imagem_path'];
    memorialAtualizarQrCodePath($memorial_id, $qr_path_rel);
  }
} else {
  // 2) Gerar QR on-demand
  $unique_id = qrcodeGerarUniqueId();
  $redirect_url = $base_url . '/redirect.php?id=' . $unique_id;

  $dir_qr = __DIR__ . '/qrcodes';
  if (!is_dir($dir_qr)) {
    @mkdir($dir_qr, 0755, true);
  }
  $filename = 'qr_' . preg_replace('/[^a-zA-Z0-9\-]/', '', $unique_id) . '.png';
  $path_abs = $dir_qr . '/' . $filename;
  $path_rel = 'qrcodes/' . $filename;

  if (gerarQrCodePng($redirect_url, $path_abs)) {
    $content = json_encode(['url' => $url_memorial]);
    qrcodeInserir($unique_id, $path_rel, $memorial_id, $content, 'usado');
    memorialAtualizarQrCodePath($memorial_id, $path_rel);
    $qr_path_rel = $path_rel;
  } else {
    qrcodeInserir($unique_id, null, $memorial_id, json_encode(['url' => $url_memorial]), 'usado');
  }
}

header('Location: memorial.php?id=' . $memorial_id . '&publicado=1');
exit;
