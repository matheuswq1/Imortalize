<?php
/**
 * Remove uma foto/mídia do memorial — Imortalize
 * Verifica sessão e se o memorial pertence ao usuário.
 */
session_start();
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$user_id = (int) $_SESSION['user_id'];
$media_id = isset($_REQUEST['media_id']) ? (int) $_REQUEST['media_id'] : 0;
$memorial_id = isset($_REQUEST['memorial_id']) ? (int) $_REQUEST['memorial_id'] : 0;

if (!$media_id) {
  header('Location: ' . ($memorial_id ? 'memorial.php?id=' . $memorial_id : 'memoriais.php'));
  exit;
}

if (!$memorial_id) {
  $pdo = getDB();
  $stmt = $pdo->prepare('SELECT memorial_id FROM memorial_media mm INNER JOIN memorials m ON m.id = mm.memorial_id WHERE mm.id = ? AND m.user_id = ? LIMIT 1');
  $stmt->execute([$media_id, $user_id]);
  $row = $stmt->fetch();
  if ($row) {
    $memorial_id = (int) $row['memorial_id'];
  }
}

if (removerMidiaMemorial($media_id, $user_id)) {
  $destino = 'memorial.php?id=' . $memorial_id . '&removido=1';
} else {
  $destino = 'memorial.php?id=' . $memorial_id . '&removido_erro=1';
}
if (!$memorial_id) {
  $destino = 'memoriais.php';
}
header('Location: ' . $destino);
exit;
