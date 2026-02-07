<?php
/**
 * Remove a foto de perfil ou de capa do memorial: limpa referência, remove registro em memorial_media e arquivo no Firebase.
 */
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/firebase-storage.php';

if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$user_id = (int) $_SESSION['user_id'];
$memorial_id = isset($_POST['memorial_id']) ? (int) $_POST['memorial_id'] : 0;
$tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';

if ($memorial_id <= 0 || !in_array($tipo, ['perfil', 'capa'], true)) {
  header('Location: memoriais.php');
  exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT id, perfil_media_id, capa_media_id FROM memorials WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$memorial_id, $user_id]);
$memorial = $stmt->fetch();
if (!$memorial) {
  header('Location: memoriais.php');
  exit;
}

$media_id = $tipo === 'perfil' ? (int) $memorial['perfil_media_id'] : (int) $memorial['capa_media_id'];
if ($media_id > 0) {
  $stmt = $pdo->prepare('SELECT id, caminho FROM memorial_media WHERE id = ? AND memorial_id = ? LIMIT 1');
  $stmt->execute([$media_id, $memorial_id]);
  $media = $stmt->fetch();
  if ($media && !empty($media['caminho'])) {
    deleteFirebaseObjectByUrl($media['caminho']);
  }
  removerMidiaMemorial($media_id, $user_id);
}

header('Location: memorial.php?id=' . $memorial_id . '&salvo=1&msg=' . rawurlencode('Foto removida.'));
exit;
