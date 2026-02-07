<?php
/**
 * Upload de imagem para memorial — Imortalize
 * Recebe arquivo, envia para Firebase Storage e grava URL em memorial_media.
 *
 * Segurança: quem grava no Firebase é o servidor (conta de serviço), não o navegador.
 * O servidor só faz upload após validar: (1) sessão (usuário logado) e
 * (2) memorial pertence ao user_id da sessão. Assim, um usuário qualquer
 * não pode alterar/adicionar imagens em memoriais de outros.
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

if (!$memorial_id) {
  header('Location: memoriais.php');
  exit;
}

// Verificar se o memorial pertence ao usuário
$pdo = getDB();
$stmt = $pdo->prepare('SELECT id FROM memorials WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$memorial_id, $user_id]);
if (!$stmt->fetch()) {
  header('Location: memoriais.php');
  exit;
}

$erro = null;
$upload_ok_count = 0;
$erros_lista = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {
  $files = $_FILES['foto'];
  $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  $maxSize = 10 * 1024 * 1024;

  if (!firebaseStorageDisponivel()) {
    $erro = 'Upload temporariamente indisponível. Configure o Firebase Storage (veja config/firebase.config.example.php).';
  } else {
    $nomes = is_array($files['name']) ? $files['name'] : [$files['name']];
    $tmp_names = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
    $errors = is_array($files['error']) ? $files['error'] : [$files['error']];
    $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];

    foreach ($nomes as $i => $name) {
      if (empty($name) || !isset($tmp_names[$i]) || $tmp_names[$i] === '') {
        continue;
      }
      if ($errors[$i] !== UPLOAD_ERR_OK) {
        $erros_lista[] = basename($name) . ': erro no envio.';
        continue;
      }
      if ($sizes[$i] > $maxSize) {
        $erros_lista[] = basename($name) . ': muito grande (máx. 10 MB).';
        continue;
      }
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime = finfo_file($finfo, $tmp_names[$i]);
      finfo_close($finfo);
      if (!in_array($mime, $allowed, true)) {
        $erros_lista[] = basename($name) . ': formato não permitido (use JPG, PNG, GIF ou WebP).';
        continue;
      }
      $url = uploadMemorialImageToFirebase($tmp_names[$i], $memorial_id, $name);
      if ($url && adicionarMidiaMemorial($memorial_id, $user_id, 'foto', $url, null)) {
        $upload_ok_count++;
      } else {
        $erros_lista[] = basename($name) . ': não foi possível salvar.';
      }
    }

    if ($upload_ok_count === 0 && empty($erros_lista)) {
      $erro = 'Selecione ao menos uma imagem para enviar.';
    } elseif ($upload_ok_count === 0) {
      $erro = implode(' ', $erros_lista);
    } elseif (!empty($erros_lista)) {
      $erro = $upload_ok_count . ' foto(s) adicionada(s). Algumas falharam: ' . implode(' ', array_slice($erros_lista, 0, 3));
      if (count($erros_lista) > 3) {
        $erro .= ' (+' . (count($erros_lista) - 3) . ' mais)';
      }
    }
  }
}

$destino = 'memorial.php?id=' . $memorial_id;
if ($erro) {
  $destino .= '&upload_erro=' . rawurlencode($erro);
}
if ($upload_ok_count > 0) {
  $destino .= '&upload_ok=' . $upload_ok_count;
}
header('Location: ' . $destino);
exit;
