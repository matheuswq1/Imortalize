<?php
/**
 * Recebe imagem recortada (perfil ou capa) e atualiza o memorial — Imortalize
 * Espera: memorial_id, tipo (perfil|capa), imagem (file).
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/firebase-storage.php';

if (empty($_SESSION['user_id'])) {
  echo json_encode(['ok' => false, 'erro' => 'Não autorizado']);
  exit;
}

$user_id = (int) $_SESSION['user_id'];
$memorial_id = isset($_POST['memorial_id']) ? (int) $_POST['memorial_id'] : 0;
$tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';

if ($memorial_id <= 0 || !in_array($tipo, ['perfil', 'capa'], true)) {
  echo json_encode(['ok' => false, 'erro' => 'Dados inválidos']);
  exit;
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT id FROM memorials WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$memorial_id, $user_id]);
if (!$stmt->fetch()) {
  echo json_encode(['ok' => false, 'erro' => 'Memorial não encontrado']);
  exit;
}

if (empty($_FILES['imagem']['tmp_name']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
  echo json_encode(['ok' => false, 'erro' => 'Nenhuma imagem enviada']);
  exit;
}

$tmp = $_FILES['imagem']['tmp_name'];
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $tmp);
finfo_close($finfo);
if (!in_array($mime, $allowed, true)) {
  echo json_encode(['ok' => false, 'erro' => 'Formato de imagem não permitido']);
  exit;
}

$nome_arquivo = $tipo === 'perfil' ? 'perfil_recorte.jpg' : 'capa_recorte.jpg';

if (!firebaseStorageDisponivel()) {
  echo json_encode(['ok' => false, 'erro' => 'Upload não disponível']);
  exit;
}

$url = uploadMemorialImageToFirebase($tmp, $memorial_id, $nome_arquivo);
if (!$url) {
  echo json_encode(['ok' => false, 'erro' => 'Falha ao enviar imagem']);
  exit;
}

$novo_id = adicionarMidiaMemorial($memorial_id, $user_id, 'foto', $url, null);
if (!$novo_id) {
  echo json_encode(['ok' => false, 'erro' => 'Falha ao salvar mídia']);
  exit;
}

if ($tipo === 'perfil') {
  $pdo->prepare('UPDATE memorials SET perfil_media_id = ? WHERE id = ? AND user_id = ?')->execute([$novo_id, $memorial_id, $user_id]);
} else {
  $pdo->prepare('UPDATE memorials SET capa_media_id = ? WHERE id = ? AND user_id = ?')->execute([$novo_id, $memorial_id, $user_id]);
}

echo json_encode(['ok' => true, 'url' => $url, 'id' => $novo_id]);
exit;
