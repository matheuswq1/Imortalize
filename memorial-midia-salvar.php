<?php
/**
 * Salvar alterações nas fotos do memorial — Imortalize
 * Aplica remoções (mídias não enviadas em keep_ids[]) e novos uploads em uma única ação.
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

$pdo = getDB();
$stmt = $pdo->prepare('SELECT id FROM memorials WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$memorial_id, $user_id]);
if (!$stmt->fetch()) {
  header('Location: memoriais.php');
  exit;
}

$keep_ids = [];
if (!empty($_POST['keep_ids']) && is_array($_POST['keep_ids'])) {
  foreach ($_POST['keep_ids'] as $id) {
    $id = (int) $id;
    if ($id > 0) {
      $keep_ids[] = $id;
    }
  }
}

// Perfil e capa não aparecem na grade Memórias, mas devem ser sempre mantidos
$perfil_media_id = isset($_POST['perfil_media_id']) ? (int) $_POST['perfil_media_id'] : 0;
$capa_media_id = isset($_POST['capa_media_id']) ? (int) $_POST['capa_media_id'] : 0;
if ($perfil_media_id > 0 && !in_array($perfil_media_id, $keep_ids, true)) {
  $keep_ids[] = $perfil_media_id;
}
if ($capa_media_id > 0 && !in_array($capa_media_id, $keep_ids, true)) {
  $keep_ids[] = $capa_media_id;
}

$removidas = removerMidiasMemorialExceto($memorial_id, $user_id, $keep_ids);

// Atualizar foto de perfil e de capa (IDs já validados acima)
$stmt = $pdo->prepare('UPDATE memorials SET perfil_media_id = ?, capa_media_id = ? WHERE id = ? AND user_id = ?');
$stmt->execute([$perfil_media_id ?: null, $capa_media_id ?: null, $memorial_id, $user_id]);

// Contar fotos atuais no memorial (após remoções) e limite do plano
$plano = buscarPlanoAtivo($user_id);
$max_fotos_plano = $plano ? (int) ($plano['max_fotos'] ?? 0) : 0;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM memorial_media WHERE memorial_id = ? AND tipo = 'foto'");
$stmt->execute([$memorial_id]);
$total_fotos_agora = (int) $stmt->fetchColumn();
$vagas_fotos = max(0, $max_fotos_plano - $total_fotos_agora);

$upload_ok_count = 0;
$erros_lista = [];
$limite_atingido_msg = null; // mensagem quando limite do plano impede adicionar mais fotos

if (firebaseStorageDisponivel() && $vagas_fotos > 0 && !empty($_FILES['foto']['name']) && (is_array($_FILES['foto']['name']) ? array_filter($_FILES['foto']['name']) : true)) {
  $files = $_FILES['foto'];
  $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  $maxSize = 10 * 1024 * 1024;

  $nomes = is_array($files['name']) ? $files['name'] : [$files['name']];
  $tmp_names = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
  $errors = is_array($files['error']) ? $files['error'] : [$files['error']];
  $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];

  $adicionadas = 0;
  foreach ($nomes as $i => $name) {
    if ($adicionadas >= $vagas_fotos) {
      $limite_atingido_msg = 'Limite do plano: ' . $max_fotos_plano . ' fotos por memorial. As demais não foram adicionadas.';
      break;
    }
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
      $erros_lista[] = basename($name) . ': formato não permitido.';
      continue;
    }
    $url = uploadMemorialImageToFirebase($tmp_names[$i], $memorial_id, $name);
    if ($url && adicionarMidiaMemorial($memorial_id, $user_id, 'foto', $url, null)) {
      $upload_ok_count++;
      $adicionadas++;
    } else {
      $erros_lista[] = basename($name) . ': não foi possível salvar.';
    }
  }
  if ($adicionadas >= $vagas_fotos && count(array_filter($nomes)) > $vagas_fotos) {
    $limite_atingido_msg = 'Limite do plano: ' . $max_fotos_plano . ' fotos por memorial. Apenas ' . $upload_ok_count . ' nova(s) foi(ram) adicionada(s).';
  }
} elseif ($vagas_fotos === 0 && !empty($_FILES['foto']['name']) && (is_array($_FILES['foto']['name']) ? array_filter($_FILES['foto']['name']) : true)) {
  $limite_atingido_msg = 'Limite do plano (' . $max_fotos_plano . ' fotos por memorial) já atingido. Remova fotos para adicionar outras ou altere seu plano.';
}

$destino = 'memorial.php?id=' . $memorial_id;
$mensagens = [];
if ($removidas > 0) {
  $mensagens[] = $removidas === 1 ? '1 foto removida.' : $removidas . ' fotos removidas.';
}
if ($upload_ok_count > 0) {
  $mensagens[] = $upload_ok_count === 1 ? '1 foto adicionada.' : $upload_ok_count . ' fotos adicionadas.';
}
if (!empty($mensagens)) {
  $destino .= '&salvo=1&msg=' . rawurlencode(implode(' ', $mensagens));
}
if (!empty($erros_lista)) {
  $msg_erro = implode(' ', array_slice($erros_lista, 0, 3));
  if (count($erros_lista) > 3) {
    $msg_erro .= ' (+' . (count($erros_lista) - 3) . ' mais)';
  }
  if ($limite_atingido_msg !== null) {
    $msg_erro = $limite_atingido_msg . ' ' . $msg_erro;
  }
  $destino .= '&salvo_erro=' . rawurlencode($msg_erro);
} elseif ($limite_atingido_msg !== null) {
  $destino .= '&salvo_erro=' . rawurlencode($limite_atingido_msg);
}
header('Location: ' . $destino);
exit;
