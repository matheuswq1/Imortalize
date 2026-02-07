<?php
/**
 * Preview do QR Code por estilo (Padrão, Circular, Com logo).
 * Retorna imagem PNG. Requer admin logado.
 */
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/qrcode-gerar.php';

if (empty($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'] ?? 0, $_SESSION['user_email'] ?? null)) {
  header('HTTP/1.0 403 Forbidden');
  exit;
}

$layout_style = isset($_GET['layout_style']) ? $_GET['layout_style'] : (isset($_POST['layout_style']) ? $_POST['layout_style'] : 'padrao');
$layout_style = in_array($layout_style, ['padrao', 'circular', 'com_logo'], true) ? $layout_style : 'padrao';

$root_path = dirname(__DIR__);
$logo_path = null;

if ($layout_style === 'com_logo') {
  if (!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['logo']['tmp_name']);
    finfo_close($finfo);
    if (in_array($mime, ['image/png', 'image/jpeg', 'image/jpg'], true)) {
      $ext = ($mime === 'image/png') ? '.png' : '.jpg';
      $logo_temp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imortalize_logo_preview_' . uniqid('', true) . $ext;
      if (@copy($_FILES['logo']['tmp_name'], $logo_temp)) {
        $logo_path = $logo_temp;
      }
    }
  }
  if ($logo_path === null) {
    $global_logo = $root_path . DIRECTORY_SEPARATOR . 'qrcodes' . DIRECTORY_SEPARATOR . 'logo.png';
    if (is_file($global_logo)) {
      $logo_path = $global_logo;
    }
  }
}

$url = rtrim(getSiteBaseUrl(), '/') . '/redirect.php?id=preview';
$temp_dir = sys_get_temp_dir();
$temp_file = $temp_dir . DIRECTORY_SEPARATOR . 'imortalize_qr_preview_' . uniqid('', true) . '.png';

$opcoes = ['layout_style' => $layout_style];
if ($logo_path) {
  $opcoes['logo_path'] = $logo_path;
}

if (gerarQrCodePng($url, $temp_file, $opcoes) && is_file($temp_file)) {
  if (isset($logo_temp) && is_file($logo_temp)) {
    @unlink($logo_temp);
  }
  header('Content-Type: image/png');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  readfile($temp_file);
  @unlink($temp_file);
} else {
  if (isset($logo_temp) && is_file($logo_temp)) {
    @unlink($logo_temp);
  }
  header('HTTP/1.0 500 Internal Server Error');
}
exit;
