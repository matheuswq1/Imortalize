<?php
/**
 * Visualizar memorial — Imortalize
 * Exibe os detalhes de um memorial específico e permite upload de fotos (Firebase Storage).
 */
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/firebase-storage.php';

// Verificar se está logado
if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$memorial_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$memorial_id) {
  header('Location: memoriais.php');
  exit;
}

// Buscar memorial e nome do publicador
$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM memorials WHERE id = ? AND user_id = ?');
$stmt->execute([$memorial_id, $user_id]);
$memorial = $stmt->fetch();

if (!$memorial) {
  header('Location: memoriais.php');
  exit;
}

$stmt = $pdo->prepare('SELECT nome FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$memorial['user_id']]);
$memorial['publicador_nome'] = $stmt->fetchColumn() ?: '';

// Buscar mídias do memorial
$stmt = $pdo->prepare('SELECT * FROM memorial_media WHERE memorial_id = ? ORDER BY ordem ASC, created_at ASC');
$stmt->execute([$memorial_id]);
$medias = $stmt->fetchAll();

// Limite de fotos conforme plano ativo
$plano_ativo = buscarPlanoAtivo($user_id);
$max_fotos_plano = $plano_ativo ? (int) ($plano_ativo['max_fotos'] ?? 0) : 0;
$total_fotos_atual = count(array_filter($medias, function ($m) { return $m['tipo'] === 'foto'; }));
$pode_adicionar = max(0, $max_fotos_plano - $total_fotos_atual);

$page_title = htmlspecialchars($memorial['titulo']) . ' | Imortalize';

$fotos = array_filter($medias, function ($m) { return $m['tipo'] === 'foto'; });
$outras = array_filter($medias, function ($m) { return $m['tipo'] !== 'foto'; });

// Foto de capa e perfil (como na página pública) para o layout de edição
$perfil_media_id = isset($memorial['perfil_media_id']) ? (int) $memorial['perfil_media_id'] : 0;
$capa_media_id = isset($memorial['capa_media_id']) ? (int) $memorial['capa_media_id'] : 0;
$foto_perfil = null;
$foto_capa = null;
foreach ($fotos as $m) {
  if ((int) $m['id'] === $perfil_media_id) $foto_perfil = $m;
  if ((int) $m['id'] === $capa_media_id) $foto_capa = $m;
}

// Fotos para a aba Memórias (exclui perfil e capa — elas ficam só no topo da página)
$ids_perfil_capa = array_filter([$perfil_media_id, $capa_media_id]);
$fotos_memorias = array_values(array_filter($fotos, function ($m) use ($ids_perfil_capa) {
  return !in_array((int) $m['id'], $ids_perfil_capa, true);
}));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $page_title; ?></title>
  <link rel="stylesheet" href="css/site.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
  <style>
    .memorial-page {
      min-height: 100vh;
      padding: 2rem 1rem 3rem;
    }
    .memorial-inner {
      max-width: 720px;
      margin: 0 auto;
    }
    /* Layout igual à página pública (capa + perfil + header + abas) */
    .memorial-editor-capa {
      width: 100%;
      aspect-ratio: 21/9;
      max-height: 280px;
      overflow: hidden;
      background: rgba(0, 0, 0, 0.3);
    }
    .memorial-editor-capa img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .memorial-editor-perfil-wrap {
      padding: 0 1rem;
      margin-top: -3rem;
      position: relative;
      z-index: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .memorial-editor-perfil-container {
      position: relative;
      display: inline-block;
    }
    .memorial-editor-perfil-container .memorial-editor-editar-wrap {
      bottom: 0.25rem;
      right: 0.25rem;
      transform: translateX(0);
    }
    .memorial-editor-perfil-container .memorial-editor-editar-wrap .memorial-editor-btn-editar {
      position: absolute;
      bottom: 0.25rem;
      right: 0.25rem;
    }
    .memorial-editor-perfil {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      overflow: hidden;
      border: 4px solid rgba(26, 32, 22, 1);
      background: rgba(0, 0, 0, 0.3);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
    }
    .memorial-editor-perfil img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .memorial-editor-capa-wrap,
    .memorial-editor-perfil-wrap {
      position: relative;
    }
    .memorial-capa-publicado-por {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      margin: 0;
      padding: 0.75rem 1rem;
      background: rgba(0, 0, 0, 0.55);
      color: rgba(240, 242, 235, 0.95);
      font-size: 0.875rem;
      z-index: 2;
    }
    .memorial-editor-capa-wrap--sem-foto {
      position: relative;
    }
    .memorial-editor-capa--vazio {
      min-height: 140px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      padding: 1rem;
      position: relative;
    }
    .memorial-editor-capa--vazio .memorial-editor-editar-wrap {
      position: static;
      transform: none;
    }
    .memorial-editor-capa-texto {
      color: rgba(184, 196, 168, 0.5);
      font-size: 0.875rem;
    }
    .memorial-editor-perfil--vazio {
      background: rgba(0, 0, 0, 0.4);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      position: relative;
    }
    .memorial-editor-perfil-icone {
      color: rgba(184, 196, 168, 0.5);
      font-size: 2rem;
    }
    .memorial-editor-editar-wrap--centro {
      position: absolute;
      bottom: 0.25rem;
      left: 50%;
      transform: translateX(-50%);
      right: auto;
    }
    .memorial-editor-btn-editar {
      position: absolute;
      bottom: 0.5rem;
      right: 0.5rem;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: rgba(0, 0, 0, 0.6);
      border: 2px solid rgba(255, 255, 255, 0.3);
      color: #fff;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.2s, transform 0.2s;
      z-index: 2;
    }
    .memorial-editor-nome {
      margin: 0.5rem 0 0;
      text-align: center;
      color: #f0f2eb;
      font-size: 1.125rem;
      font-weight: 500;
    }
    .memorial-editor-btn-editar:hover {
      background: rgba(184, 196, 168, 0.9);
      color: #192112;
      transform: scale(1.08);
    }
    .memorial-editor-btn-editar svg {
      width: 18px;
      height: 18px;
    }
    .memorial-editor-editar-wrap {
      position: absolute;
      bottom: 0.5rem;
      right: 0.5rem;
      z-index: 2;
    }
    .memorial-editor-menu {
      display: none;
      position: absolute;
      bottom: 100%;
      right: 0;
      margin-bottom: 6px;
      min-width: 160px;
      background: rgba(26, 32, 22, 0.98);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 10px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
      padding: 0.35rem 0;
      z-index: 10;
    }
    .memorial-editor-menu.ativo {
      display: block;
    }
    .memorial-editor-menu button {
      display: block;
      width: 100%;
      padding: 0.6rem 1rem;
      font-size: 0.9375rem;
      font-family: inherit;
      color: #f0f2eb;
      background: none;
      border: none;
      text-align: left;
      cursor: pointer;
      transition: background 0.15s;
    }
    .memorial-editor-menu button:hover {
      background: rgba(255, 255, 255, 0.08);
    }
    .memorial-editor-menu button[data-acao="excluir"]:hover {
      color: #ff9999;
    }
    .memorial-crop-modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.85);
      z-index: 10000;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      box-sizing: border-box;
    }
    .memorial-crop-modal.ativo {
      display: flex;
    }
    .memorial-crop-modal__box {
      background: #1a2016;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 16px;
      max-width: 96vw;
      max-height: 90vh;
      width: 640px;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .memorial-crop-modal__titulo {
      padding: 1rem 1.25rem;
      font-size: 1.125rem;
      font-weight: 600;
      color: #f0f2eb;
      margin: 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .memorial-crop-modal__area {
      padding: 1rem;
      min-height: 320px;
      height: 55vh;
      max-height: 500px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0d100b;
      overflow: hidden;
    }
    .memorial-crop-modal__area img {
      max-width: 100%;
      max-height: 100%;
      display: block;
    }
    .memorial-crop-modal__acoes {
      padding: 1rem 1.25rem;
      display: flex;
      gap: 0.75rem;
      justify-content: flex-end;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
    .memorial-crop-modal__acoes button {
      padding: 0.75rem 1.5rem;
      font-size: 0.9375rem;
      font-weight: 600;
      border-radius: 8px;
      cursor: pointer;
      font-family: inherit;
      transition: background 0.2s;
    }
    .memorial-crop-modal__btn-cancelar {
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #b8c4a8;
    }
    .memorial-crop-modal__btn-cancelar:hover {
      background: rgba(255, 255, 255, 0.06);
      color: #f0f2eb;
    }
    .memorial-crop-modal__btn-cancelar:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
    .memorial-crop-modal__btn-aplicar {
      background: #b8c4a8;
      border: none;
      color: #192112;
    }
    .memorial-crop-modal__btn-aplicar:hover {
      background: #f0f2eb;
    }
    .memorial-editor-header {
      padding: 1rem 1rem 0;
      margin-bottom: 0;
      text-align: center;
    }
    .memorial-editor-header .memorial-status {
      margin-bottom: 0.5rem;
    }
    .memorial-editor-header h1 {
      font-size: clamp(1.75rem, 4vw, 2.25rem);
      font-weight: 600;
      margin: 0 0 0.25rem;
      color: #f0f2eb;
    }
    .memorial-editor-header .subtitulo {
      font-size: 0.9375rem;
      color: #b8c4a8;
      margin: 0 0 0.5rem;
    }
    .memorial-editor-mensagem {
      font-size: 1rem;
      line-height: 1.6;
      color: #b8c4a8;
      margin: 0 0 1rem;
    }
    .memorial-editor-tabs {
      display: flex;
      gap: 0;
      padding: 0 1rem;
      margin-top: 1.5rem;
      margin-bottom: 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .memorial-editor-tab {
      font-size: 1.125rem;
      font-weight: 600;
      color: #b8c4a8;
      padding: 0.75rem 1.25rem 0.5rem;
      margin-bottom: -1px;
      border: none;
      border-bottom: 3px solid transparent;
      background: none;
      cursor: pointer;
      font-family: inherit;
      transition: color 0.2s, border-color 0.2s;
    }
    .memorial-editor-tab:hover { color: #f0f2eb; }
    .memorial-editor-tab[aria-selected="true"] {
      color: #f0f2eb;
      border-bottom-color: #b8c4a8;
    }
    .memorial-editor-panel {
      display: none;
      padding: 1.5rem 1rem 2rem;
      margin-bottom: 0;
    }
    .memorial-editor-panel[aria-hidden="false"] { display: block; }
    .memorial-header {
      margin-bottom: 2rem;
    }
    .memorial-header h1 {
      font-size: clamp(1.5rem, 4vw, 2rem);
      font-weight: 600;
      margin: 0 0 0.5rem;
      color: #f0f2eb;
    }
    .memorial-status {
      display: inline-block;
      padding: 0.25rem 0.65rem;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      border-radius: 20px;
      margin-bottom: 1rem;
    }
    .memorial-status.rascunho {
      background: rgba(255, 255, 255, 0.1);
      color: #b8c4a8;
    }
    .memorial-status.publicado {
      background: rgba(184, 196, 168, 0.2);
      color: #b8c4a8;
    }
    .memorial-bio {
      font-size: 1rem;
      line-height: 1.7;
      color: #b8c4a8;
      margin-bottom: 2rem;
    }
    .memorial-dados-section {
      background: rgba(39, 51, 28, 0.6);
      border: 1px solid rgba(184, 196, 168, 0.15);
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 2rem;
    }
    .memorial-dados-section h2 {
      font-size: 1.125rem;
      font-weight: 600;
      color: #f0f2eb;
      margin: 0 0 1rem;
    }
    .memorial-campo {
      margin-bottom: 1.25rem;
    }
    .memorial-campo:last-of-type {
      margin-bottom: 0;
    }
    .memorial-campo label {
      display: block;
      font-size: 0.875rem;
      font-weight: 600;
      color: #b8c4a8;
      margin-bottom: 0.5rem;
    }
    .memorial-campo input[type="text"],
    .memorial-campo input[type="date"],
    .memorial-campo select,
    .memorial-campo textarea {
      width: 100%;
      padding: 0.75rem 1rem;
      font-size: 1rem;
      font-family: inherit;
      color: #f0f2eb;
      background: rgba(0, 0, 0, 0.25);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      transition: border-color 0.2s, box-shadow 0.2s;
      box-sizing: border-box;
    }
    .memorial-campo select {
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23b8c4a8' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 0.75rem center;
      padding-right: 2.25rem;
    }
    .memorial-campo select option {
      background: #2d3d22;
      color: #f0f2eb;
      padding: 0.5rem 0.75rem;
    }
    .memorial-campo input[type="text"]:focus,
    .memorial-campo input[type="date"]:focus,
    .memorial-campo select:focus,
    .memorial-campo textarea:focus {
      outline: none;
      border-color: rgba(184, 196, 168, 0.5);
      box-shadow: 0 0 0 3px rgba(184, 196, 168, 0.1);
    }
    .memorial-campo textarea {
      min-height: 120px;
      resize: vertical;
    }
    .memorial-campo p,
    .memorial-campo-hint {
      font-size: 0.8125rem;
      color: #b8c4a8;
      margin: 0.35rem 0 0;
      line-height: 1.4;
    }
    .memorial-dados-actions {
      margin-top: 1.25rem;
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
    }
    .btn-salvar-dados {
      padding: 0.75rem 1.5rem;
      font-size: 0.9375rem;
      font-weight: 600;
      color: #192112;
      background: #b8c4a8;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn-salvar-dados:hover {
      background: #f0f2eb;
    }
    .btn-salvar-dados:disabled {
      opacity: 0.7;
      cursor: not-allowed;
    }
    .memorial-actions {
      margin-top: 2rem;
      padding-top: 1.5rem;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
    .memorial-actions a {
      display: inline-block;
      padding: 0.75rem 1.5rem;
      font-size: 0.9375rem;
      font-weight: 600;
      color: #b8c4a8;
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 10px;
      text-decoration: none;
      transition: all 0.25s ease;
    }
    .memorial-actions a:hover {
      background: rgba(255, 255, 255, 0.06);
      color: #f0f2eb;
    }
    .memorial-link-box {
      background: rgba(39, 51, 28, 0.8);
      border: 1px solid rgba(184, 196, 168, 0.2);
      border-radius: 12px;
      padding: 1.25rem 1.5rem;
      margin: 2rem 0;
    }
    .memorial-link-box h3 {
      font-size: 0.9375rem;
      font-weight: 600;
      color: #f0f2eb;
      margin: 0 0 0.5rem;
    }
    .memorial-link-box p {
      font-size: 0.8125rem;
      color: #b8c4a8;
      margin: 0 0 0.75rem;
    }
    .memorial-link-box .link-row {
      display: flex;
      gap: 0.75rem;
      align-items: center;
      flex-wrap: wrap;
    }
    .memorial-link-box input[readonly] {
      flex: 1;
      min-width: 0;
      padding: 0.65rem 1rem;
      font-size: 0.875rem;
      color: #f0f2eb;
      background: rgba(0, 0, 0, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 8px;
    }
    .memorial-link-box .btn-copiar {
      padding: 0.65rem 1.25rem;
      font-size: 0.875rem;
      font-weight: 600;
      color: #192112;
      background: #b8c4a8;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      white-space: nowrap;
    }
    .memorial-link-box .btn-copiar:hover {
      background: #f0f2eb;
    }
    .memorial-link-box .link-actions {
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
      align-items: center;
    }
    .btn-despublicar {
      padding: 0.5rem 1rem;
      font-size: 0.8125rem;
      font-weight: 600;
      color: #b8c4a8;
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 8px;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s;
    }
    .btn-despublicar:hover {
      background: rgba(255, 100, 100, 0.1);
      border-color: rgba(255, 100, 100, 0.3);
      color: #ff9999;
    }
    .memorial-publicar-box {
      background: rgba(255, 255, 255, 0.04);
      border: 1px dashed rgba(255, 255, 255, 0.15);
      border-radius: 12px;
      padding: 1.25rem 1.5rem;
      margin: 2rem 0;
    }
    .memorial-publicar-box p {
      font-size: 0.9375rem;
      color: #b8c4a8;
      margin: 0 0 1rem;
    }
    .memorial-publicar-box .btn-publicar {
      display: inline-block;
      padding: 0.75rem 1.5rem;
      font-size: 0.9375rem;
      font-weight: 600;
      color: #192112;
      background: #b8c4a8;
      border: none;
      border-radius: 10px;
      text-decoration: none;
      transition: background 0.25s ease;
    }
    .memorial-publicar-box .btn-publicar:hover {
      background: #f0f2eb;
    }
    .alert-sucesso {
      background: rgba(0, 255, 0, 0.1);
      border: 1px solid rgba(0, 255, 0, 0.3);
      border-radius: 10px;
      padding: 1rem;
      margin-bottom: 1.5rem;
      color: #99ff99;
      font-size: 0.9375rem;
    }
    .alert-erro {
      background: rgba(255, 100, 100, 0.1);
      border: 1px solid rgba(255, 100, 100, 0.3);
      border-radius: 10px;
      padding: 1rem;
      margin-bottom: 1.5rem;
      color: #ffb3b3;
      font-size: 0.9375rem;
    }
    .memorial-upload {
      background: rgba(39, 51, 28, 0.6);
      border: 1px solid rgba(184, 196, 168, 0.2);
      border-radius: 12px;
      padding: 1.5rem;
      margin: 2rem 0;
    }
    .memorial-upload h3 {
      font-size: 1rem;
      font-weight: 600;
      color: #f0f2eb;
      margin: 0 0 0.75rem;
    }
    .memorial-upload p {
      font-size: 0.875rem;
      color: #b8c4a8;
      margin: 0 0 0.75rem;
      line-height: 1.5;
    }
    .memorial-upload p:last-of-type {
      margin-bottom: 1rem;
    }
    .memorial-upload .file-wrap {
      display: block;
      margin-bottom: 0.5rem;
    }
    .memorial-upload input[type="file"] {
      width: 100%;
      max-width: 400px;
      padding: 0.65rem 1rem;
      font-size: 0.9375rem;
      font-family: inherit;
      color: #f0f2eb;
      background: rgba(0, 0, 0, 0.25);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 8px;
      cursor: pointer;
      box-sizing: border-box;
    }
    .memorial-upload input[type="file"]::file-selector-button {
      padding: 0.5rem 1rem;
      margin-right: 1rem;
      font-size: 0.875rem;
      font-weight: 600;
      font-family: inherit;
      color: #192112;
      background: #b8c4a8;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      transition: background 0.2s;
    }
    .memorial-upload input[type="file"]::file-selector-button:hover {
      background: #f0f2eb;
    }
    .memorial-upload-hint {
      font-size: 0.8125rem;
      color: #b8c4a8;
      margin: 0.5rem 0 0;
      line-height: 1.4;
    }
    .memorial-upload-preview {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
      gap: 0.75rem;
      margin-top: 1rem;
    }
    .memorial-upload-preview__item {
      position: relative;
      border-radius: 10px;
      overflow: hidden;
      aspect-ratio: 1;
      background: rgba(0, 0, 0, 0.3);
      border: 2px solid rgba(184, 196, 168, 0.3);
    }
    .memorial-upload-preview__item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .memorial-upload-preview__remove {
      position: absolute;
      top: 4px;
      right: 4px;
      width: 26px;
      height: 26px;
      padding: 0;
      font-size: 1.1rem;
      line-height: 1;
      color: #fff;
      background: rgba(200, 80, 80, 0.95);
      border: none;
      border-radius: 50%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.2s;
      z-index: 2;
    }
    .memorial-upload-preview__remove:hover {
      background: rgba(220, 100, 100, 1);
    }
    .memorial-plano-limite {
      font-size: 0.875rem;
      color: #b8c4a8;
      margin: 0 0 0.75rem;
      padding: 0.5rem 0;
      line-height: 1.5;
    }
    .memorial-upload .btn-upload {
      padding: 0.65rem 1.25rem;
      font-size: 0.9375rem;
      font-weight: 600;
      color: #192112;
      background: #b8c4a8;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }
    .memorial-upload .btn-upload:hover {
      background: #f0f2eb;
    }
    .memorial-fotos {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
      gap: 1rem;
      margin-top: 1.5rem;
    }
    .memorial-foto-item {
      position: relative;
      border-radius: 12px;
      overflow: hidden;
      aspect-ratio: 1;
      background: rgba(0, 0, 0, 0.3);
    }
    .memorial-foto-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .memorial-foto-item .legenda {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 0.5rem;
      font-size: 0.75rem;
      color: #fff;
      background: linear-gradient(transparent, rgba(0,0,0,0.7));
    }
    .memorial-fotos-section {
      margin-top: 0;
      margin-bottom: 2rem;
    }
    .memorial-foto-excluir {
      position: absolute;
      top: 0.5rem;
      right: 0.5rem;
      padding: 0.35rem 0.65rem;
      font-size: 0.75rem;
      font-weight: 600;
      color: #fff;
      background: rgba(200, 80, 80, 0.9);
      border-radius: 6px;
      text-decoration: none;
      z-index: 2;
      transition: background 0.2s;
    }
    .memorial-foto-excluir:hover {
      background: rgba(220, 100, 100, 1);
      color: #fff;
    }
    .memorial-foto-item-edit .memorial-foto-excluir {
      cursor: pointer;
      border: none;
      font-family: inherit;
    }
    .memorial-foto-item-edit.removido {
      opacity: 0.5;
      pointer-events: none;
    }
    .memorial-fotos-form-actions {
      margin-top: 1.5rem;
      padding-top: 1rem;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
    .btn-salvar-alteracoes {
      padding: 0.75rem 1.5rem;
      font-size: 1rem;
      font-weight: 600;
      color: #192112;
      background: #b8c4a8;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn-salvar-alteracoes:hover {
      background: #f0f2eb;
    }
    .memorial-hero-fotos {
      margin-bottom: 2rem;
      border-radius: 16px;
      overflow: hidden;
      background: rgba(0, 0, 0, 0.2);
    }
    .memorial-carrossel {
      position: relative;
      width: 100%;
      padding: 0 0 3rem;
    }
    .memorial-carrossel__inner {
      position: relative;
      width: 100%;
      overflow: hidden;
      border-radius: 16px;
      aspect-ratio: 4/3;
      max-height: 380px;
      touch-action: pan-y pinch-zoom;
      cursor: grab;
    }
    .memorial-carrossel__inner:active {
      cursor: grabbing;
    }
    .memorial-carrossel__track {
      display: flex;
      height: 100%;
      transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      will-change: transform;
    }
    .memorial-carrossel__track.dragging {
      transition: none;
    }
    .memorial-carrossel__slide {
      flex: 0 0 70%;
      width: 70%;
      height: 100%;
      padding: 0 1%;
      box-sizing: border-box;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      border-radius: 16px;
      transition: transform 0.45s ease, opacity 0.35s ease;
      transform: scale(0.88);
      opacity: 0.85;
      z-index: 0;
    }
    .memorial-carrossel__slide.ativo {
      transform: scale(1);
      opacity: 1;
      z-index: 1;
    }
    .memorial-carrossel__slide img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      display: block;
      border-radius: 16px;
      pointer-events: none;
    }
    .memorial-carrossel__prev,
    .memorial-carrossel__next {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 40px;
      height: 40px;
      border: none;
      border-radius: 50%;
      background: rgba(0, 0, 0, 0.5);
      color: #fff;
      font-size: 1.35rem;
      cursor: pointer;
      z-index: 4;
      transition: background 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      line-height: 1;
      padding: 0;
    }
    .memorial-carrossel__prev:hover,
    .memorial-carrossel__next:hover {
      background: rgba(0, 0, 0, 0.75);
    }
    .memorial-carrossel__prev { left: 12px; }
    .memorial-carrossel__next { right: 12px; }
    .memorial-carrossel__dots {
      position: absolute;
      bottom: 12px;
      left: 0;
      right: 0;
      text-align: center;
      z-index: 3;
      display: flex;
      justify-content: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    .memorial-carrossel__dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.5);
      border: none;
      padding: 0;
      cursor: pointer;
      transition: background 0.2s;
    }
    .memorial-carrossel__dot.ativo {
      background: #fff;
    }
    .memorial-hero-foto-unica {
      border-radius: 16px;
      overflow: hidden;
      max-height: 480px;
      background: rgba(0, 0, 0, 0.2);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .memorial-hero-foto-unica img {
      width: 100%;
      height: auto;
      max-height: 480px;
      object-fit: contain;
      display: block;
      border-radius: 16px;
    }
    .memorial-salvando-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.25s, visibility 0.25s;
    }
    .memorial-salvando-overlay.ativo {
      opacity: 1;
      visibility: visible;
    }
    .memorial-salvando-overlay__box {
      background: rgba(39, 51, 28, 0.98);
      border: 1px solid rgba(184, 196, 168, 0.25);
      border-radius: 16px;
      padding: 2rem 2.5rem;
      text-align: center;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    }
    .memorial-salvando-spinner {
      width: 48px;
      height: 48px;
      margin: 0 auto 1rem;
      border: 4px solid rgba(184, 196, 168, 0.25);
      border-top-color: #b8c4a8;
      border-radius: 50%;
      animation: memorial-spin 0.9s linear infinite;
    }
    @keyframes memorial-spin {
      to { transform: rotate(360deg); }
    }
    .memorial-salvando-overlay__texto {
      font-size: 1.0625rem;
      font-weight: 600;
      color: #f0f2eb;
      margin: 0;
    }
    .memorial-salvando-overlay__sub {
      font-size: 0.875rem;
      color: #b8c4a8;
      margin: 0.5rem 0 0;
    }
    .btn-salvar-alteracoes:disabled,
    .btn-salvar-alteracoes.salvando {
      opacity: 0.8;
      cursor: not-allowed;
      pointer-events: none;
    }
    .memorial-contador {
      font-variant-numeric: tabular-nums;
      color: var(--texto-secundario, #666);
    }
  </style>
</head>
<body>
  <?php $logado_header = !empty($_SESSION['user_id']); ?>
  <header class="site-header" role="banner">
    <div class="site-header__inner">
      <a href="index.php" class="site-header__logo-link" aria-label="Imortalize - Página inicial">
        <img src="img/logo branca sem fundo.png" alt="Imortalize" class="site-header__logo" width="140" height="36">
      </a>
      <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-hidden="true" hidden>
      <label for="nav-toggle" class="nav-toggle-label" aria-label="Abrir ou fechar menu">
        <span class="nav-toggle-icon"></span>
      </label>
      <nav class="site-nav" aria-label="Navegação principal">
        <a href="index.php" class="nav-link">Início</a>
        <a href="index.php#quem-somos" class="nav-link">Quem Somos</a>
        <a href="index.php#contato" class="nav-link">Contato</a>
        <a href="index.php#planos" class="nav-link">Comprar</a>
        <?php if ($logado_header) : ?>
        <a href="dashboard.php" class="btn-minha-area">Minha área</a>
        <a href="logout.php" class="btn-sair">Sair</a>
        <?php else : ?>
        <a href="login.php" class="btn-entrar">Entrar</a>
        <a href="cadastro.php" class="btn-criar-conta">Criar conta</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main class="memorial-page" id="memorial-main" data-memorial-id="<?php echo (int) $memorial['id']; ?>">
    <div class="memorial-inner">
      <?php
      $publicador_texto = '';
      $pub_nome = trim($memorial['publicador_nome'] ?? '');
      $relacao_label = function_exists('getRelacaoPublicadorLabel') ? getRelacaoPublicadorLabel($memorial['relacao_publicador'] ?? null) : ($memorial['relacao_publicador'] ?? '');
      if ($pub_nome !== '' || $relacao_label !== '') {
        $partes = [];
        if ($pub_nome !== '') $partes[] = 'Publicado por ' . htmlspecialchars($pub_nome);
        if ($relacao_label !== '') $partes[] = '(' . htmlspecialchars($relacao_label) . ')';
        $publicador_texto = implode(' ', $partes);
      }
      ?>
      <?php if (!empty($foto_capa)) : ?>
      <div class="memorial-editor-capa-wrap">
        <?php if ($publicador_texto !== '') : ?>
        <p class="memorial-capa-publicado-por" aria-label="<?php echo $publicador_texto; ?>"><?php echo $publicador_texto; ?></p>
        <?php endif; ?>
        <div class="memorial-editor-capa">
          <img src="<?php echo htmlspecialchars($foto_capa['caminho']); ?>" alt="Capa do memorial" loading="eager" id="preview-capa">
        </div>
        <div class="memorial-editor-editar-wrap">
          <button type="button" class="memorial-editor-btn-editar" aria-label="Editar foto de capa" title="Editar capa" data-tipo="capa" data-src="<?php echo htmlspecialchars($foto_capa['caminho']); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <div class="memorial-editor-menu" role="menu" aria-hidden="true">
            <button type="button" role="menuitem" data-acao="excluir">Excluir foto</button>
            <button type="button" role="menuitem" data-acao="alterar">Alterar foto</button>
          </div>
        </div>
      </div>
      <?php else : ?>
      <div class="memorial-editor-capa-wrap memorial-editor-capa-wrap--sem-foto">
        <?php if ($publicador_texto !== '') : ?>
        <p class="memorial-capa-publicado-por"><?php echo $publicador_texto; ?></p>
        <?php endif; ?>
        <div class="memorial-editor-capa memorial-editor-capa--vazio">
          <span class="memorial-editor-capa-texto">Sem foto de capa</span>
          <?php if (firebaseStorageDisponivel()) : ?>
          <div class="memorial-editor-editar-wrap">
            <button type="button" class="memorial-editor-btn-editar" aria-label="Adicionar foto de capa" title="Adicionar foto de capa" data-tipo="capa">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="memorial-editor-perfil-wrap">
        <?php if (!empty($foto_perfil)) : ?>
        <div class="memorial-editor-perfil-container">
          <div class="memorial-editor-perfil">
            <img src="<?php echo htmlspecialchars($foto_perfil['caminho']); ?>" alt="<?php echo htmlspecialchars($memorial['titulo']); ?>" loading="eager" id="preview-perfil">
          </div>
          <div class="memorial-editor-editar-wrap">
            <button type="button" class="memorial-editor-btn-editar" aria-label="Editar foto de perfil" title="Editar perfil" data-tipo="perfil" data-src="<?php echo htmlspecialchars($foto_perfil['caminho']); ?>">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            <div class="memorial-editor-menu" role="menu" aria-hidden="true">
              <button type="button" role="menuitem" data-acao="excluir">Excluir foto</button>
              <button type="button" role="menuitem" data-acao="alterar">Alterar foto</button>
            </div>
          </div>
        </div>
        <?php else : ?>
        <div class="memorial-editor-perfil memorial-editor-perfil--vazio">
          <span class="memorial-editor-perfil-icone" aria-hidden="true">👤</span>
          <?php if (firebaseStorageDisponivel()) : ?>
          <div class="memorial-editor-editar-wrap memorial-editor-editar-wrap--centro">
            <button type="button" class="memorial-editor-btn-editar" aria-label="Adicionar foto de perfil" title="Adicionar foto de perfil" data-tipo="perfil">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty(trim($memorial['nome'] ?? ''))) : ?>
        <p class="memorial-editor-nome"><?php echo htmlspecialchars(trim($memorial['nome'])); ?></p>
        <?php endif; ?>
        <?php
        $dn = !empty($memorial['data_nascimento']) ? date('d/m/Y', strtotime($memorial['data_nascimento'])) : '';
        $do = !empty($memorial['data_obito']) ? date('d/m/Y', strtotime($memorial['data_obito'])) : '';
        if ($dn !== '' || $do !== '') :
        ?>
        <p class="memorial-editor-datas" aria-label="<?php echo $dn !== '' ? 'Nascimento: ' . $dn : ''; ?><?php echo ($dn !== '' && $do !== '') ? ' ' : ''; ?><?php echo $do !== '' ? 'Óbito: ' . $do : ''; ?>">
          <?php if ($dn !== '') : ?>
          <span class="memorial-datas-item"><svg class="memorial-datas-icon memorial-datas-icon-nascimento" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><span><?php echo $dn; ?></span></span>
          <?php endif; ?>
          <?php if ($dn !== '' && $do !== '') : ?><span class="memorial-datas-sep" aria-hidden="true">·</span><?php endif; ?>
          <?php if ($do !== '') : ?>
          <span class="memorial-datas-item"><svg class="memorial-datas-icon memorial-datas-icon-obito" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2v8h6v2h-6v10h-2V12H4v-2h6V2h2z"/></svg><span><?php echo $do; ?></span></span>
          <?php endif; ?>
        </p>
        <?php endif; ?>
      </div>

      <div class="memorial-editor-header">
        <span class="memorial-status <?php echo htmlspecialchars($memorial['status']); ?>">
          <?php echo $memorial['status'] === 'publicado' ? 'Publicado' : 'Rascunho'; ?>
        </span>
        <h1><?php echo htmlspecialchars($memorial['titulo']); ?></h1>
        <p class="subtitulo">Memorial Imortalize · Edição · Criado em <?php echo date('d/m/Y', strtotime($memorial['created_at'])); ?></p>
        <?php if (!empty($memorial['mensagem_curta'])) : ?>
        <p class="memorial-editor-mensagem"><?php echo nl2br(htmlspecialchars($memorial['mensagem_curta'])); ?></p>
        <?php endif; ?>
      </div>

      <?php if (isset($_GET['foto_atualizada']) && $_GET['foto_atualizada'] == '1') : ?>
      <div class="alert-sucesso" style="margin: 0 1rem 1rem;">✓ Foto atualizada.</div>
      <?php endif; ?>
      <?php if (isset($_GET['criado']) && $_GET['criado'] == '1') : ?>
      <div class="alert-sucesso" style="margin: 0 1rem 1rem;">✓ Memorial criado! Agora você pode adicionar fotos na aba Memórias e publicar.</div>
      <?php endif; ?>
      <?php if (isset($_GET['publicado']) && $_GET['publicado'] == '1') : ?>
      <div class="alert-sucesso" style="margin: 0 1rem 1rem;">✓ Memorial publicado! Agora você pode compartilhar o link ou usá-lo no sistema de QR Code.</div>
      <?php endif; ?>
      <?php if (isset($_GET['salvo']) && $_GET['salvo'] == '1') : ?>
      <div class="alert-sucesso" style="margin: 0 1rem 1rem;">✓ Alterações salvas. <?php echo isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : ''; ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['salvo_erro'])) : ?>
      <div class="alert-erro" style="margin: 0 1rem 1rem;"><?php echo htmlspecialchars($_GET['salvo_erro']); ?></div>
      <?php endif; ?>

      <div class="memorial-editor-tabs" role="tablist" aria-label="História e Memórias">
        <button type="button" class="memorial-editor-tab" role="tab" id="tab-historia" aria-selected="true" aria-controls="panel-historia">História</button>
        <button type="button" class="memorial-editor-tab" role="tab" id="tab-memorias" aria-selected="false" aria-controls="panel-memorias">Memórias</button>
      </div>

      <div class="memorial-editor-panel" id="panel-historia" role="tabpanel" aria-labelledby="tab-historia" aria-hidden="false">
      <div class="memorial-dados-section">
        <h2>Informações do memorial</h2>
        <form id="form-dados-memorial" action="memorial-salvar-dados.php" method="post">
          <input type="hidden" name="memorial_id" value="<?php echo (int) $memorial['id']; ?>">
          <div class="memorial-campo">
            <label for="nome">Nome (abaixo da foto de perfil)</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($memorial['nome'] ?? ''); ?>" maxlength="120" placeholder="Ex.: Maria Silva">
            <p class="memorial-campo-hint"><span class="memorial-contador" data-max="120" aria-live="polite"></span></p>
          </div>
          <div class="memorial-campo">
            <label for="titulo">Título</label>
            <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($memorial['titulo']); ?>" required maxlength="35">
            <p class="memorial-campo-hint"><span class="memorial-contador" data-max="35" aria-live="polite"></span></p>
          </div>
          <div class="memorial-campo">
            <label for="relacao_publicador">Qual sua relação com essa pessoa?</label>
            <select id="relacao_publicador" name="relacao_publicador">
              <option value="">— Selecione —</option>
              <?php
              $opcoes_relacao = function_exists('getRelacaoPublicadorOpcoes') ? getRelacaoPublicadorOpcoes() : [];
              $relacao_atual = $memorial['relacao_publicador'] ?? '';
              foreach ($opcoes_relacao as $valor => $rotulo) :
              ?>
              <option value="<?php echo htmlspecialchars($valor); ?>"<?php echo $relacao_atual === $valor ? ' selected' : ''; ?>><?php echo htmlspecialchars($rotulo); ?></option>
              <?php endforeach; ?>
            </select>
            <p class="memorial-campo-hint">Será exibido na capa como &quot;Publicado por [seu nome] ([relação])&quot;.</p>
          </div>
          <div class="memorial-campo" style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div style="flex: 1 1 140px; min-width: 0;">
              <label for="data_nascimento">Data de nascimento</label>
              <input type="date" id="data_nascimento" name="data_nascimento" min="1000-01-01" max="<?php echo date('Y-m-d'); ?>" value="<?php echo !empty($memorial['data_nascimento']) ? htmlspecialchars($memorial['data_nascimento']) : ''; ?>">
            </div>
            <div style="flex: 1 1 140px; min-width: 0;">
              <label for="data_obito">Data do óbito</label>
              <input type="date" id="data_obito" name="data_obito" min="1000-01-01" max="<?php echo date('Y-m-d'); ?>" value="<?php echo !empty($memorial['data_obito']) ? htmlspecialchars($memorial['data_obito']) : ''; ?>">
            </div>
          </div>
          <div class="memorial-campo">
            <label for="mensagem_curta">Mensagem curta (até 120 caracteres)</label>
            <input type="text" id="mensagem_curta" name="mensagem_curta" maxlength="120" value="<?php echo htmlspecialchars($memorial['mensagem_curta'] ?? ''); ?>" placeholder="Uma frase marcante sobre o ente querido">
            <p class="memorial-campo-hint"><span class="memorial-contador" data-max="120" aria-live="polite"></span></p>
          </div>
          <div class="memorial-campo">
            <label for="biografia">Biografia</label>
            <textarea id="biografia" name="biografia" maxlength="5000" placeholder="Escreva uma biografia ou história sobre a pessoa homenageada..."><?php echo htmlspecialchars($memorial['biografia'] ?? ''); ?></textarea>
            <p class="memorial-campo-hint"><span class="memorial-contador" data-max="5000" aria-live="polite"></span></p>
          </div>
          <div class="memorial-dados-actions">
            <button type="submit" class="btn-salvar-dados">Salvar informações</button>
          </div>
        </form>
      </div>
      </div>

      <div class="memorial-editor-panel" id="panel-memorias" role="tabpanel" aria-labelledby="tab-memorias" aria-hidden="true">
      <div class="memorial-fotos-section">
        <h2 style="font-size: 1.25rem; color: #f0f2eb; margin-bottom: 1rem;">Fotos e mídias</h2>
        <p style="color: #b8c4a8; font-size: 0.875rem; margin-bottom: 1rem;">Remova fotos ou adicione novas abaixo. As alterações só são aplicadas ao clicar em <strong>Salvar alterações</strong>.</p>

        <?php if (firebaseStorageDisponivel()) : ?>
        <form id="form-fotos-memorial" action="memorial-midia-salvar.php" method="post" enctype="multipart/form-data">
          <input type="hidden" name="memorial_id" value="<?php echo (int) $memorial['id']; ?>">
          <?php
          $perfil_id = isset($memorial['perfil_media_id']) ? (int) $memorial['perfil_media_id'] : 0;
          $capa_id = isset($memorial['capa_media_id']) ? (int) $memorial['capa_media_id'] : 0;
          ?>
          <input type="hidden" name="perfil_media_id" value="<?php echo $perfil_id; ?>">
          <input type="hidden" name="capa_media_id" value="<?php echo $capa_id; ?>">

          <?php if (count($fotos_memorias) > 0) : ?>
          <p style="color: #b8c4a8; font-size: 0.875rem; margin-bottom: 0.5rem;">Fotos da seção Memórias (perfil e capa ficam apenas no topo da página; use o lápis para alterá-las).</p>
          <div class="memorial-fotos" id="fotos-atuais">
            <?php foreach ($fotos_memorias as $m) : ?>
            <div class="memorial-foto-item memorial-foto-item-edit" data-media-id="<?php echo (int) $m['id']; ?>">
              <input type="hidden" name="keep_ids[]" value="<?php echo (int) $m['id']; ?>">
              <img src="<?php echo htmlspecialchars($m['caminho']); ?>" alt="<?php echo htmlspecialchars($m['legenda'] ?? 'Foto'); ?>" loading="lazy">
              <button type="button" class="memorial-foto-excluir btn-remover-foto" title="Remover esta foto ao salvar">Remover</button>
              <?php if (!empty($m['legenda'])) : ?>
              <span class="legenda"><?php echo htmlspecialchars($m['legenda']); ?></span>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <div class="memorial-upload" style="margin-top: 1.5rem;">
            <h3>Adicionar novas fotos</h3>
            <?php if ($max_fotos_plano > 0) : ?>
            <p class="memorial-plano-limite">Seu plano <strong><?php echo htmlspecialchars($plano_ativo['plano_nome'] ?? 'Plano'); ?></strong> permite até <strong><?php echo $max_fotos_plano; ?></strong> <?php echo $max_fotos_plano === 1 ? 'foto' : 'fotos'; ?> por memorial. Atualmente: <strong><?php echo $total_fotos_atual; ?></strong>. Você pode adicionar até <strong><?php echo $pode_adicionar; ?></strong> <?php echo $pode_adicionar === 1 ? 'foto' : 'fotos'; ?>.</p>
            <p>Selecione uma ou mais imagens (JPG, PNG, GIF ou WebP, até 10 MB cada).</p>
            <input type="file" name="foto[]" id="foto-multiplas" accept="image/jpeg,image/png,image/gif,image/webp" multiple data-max="<?php echo $pode_adicionar; ?>"<?php if ($pode_adicionar === 0) echo ' disabled'; ?>>
            <p class="memorial-upload-hint" id="upload-hint">Nenhum arquivo selecionado</p>
            <div id="upload-preview" class="memorial-upload-preview" aria-live="polite"></div>
            <?php if ($pode_adicionar === 0) : ?>
            <p style="color: #b8c4a8; font-size: 0.875rem; margin-top: 0.5rem;">Você atingiu o limite de fotos deste memorial. Remova algumas para adicionar outras ou <a href="alterar-plano.php" style="color: #b8c4a8;">altere seu plano</a>.</p>
            <?php endif; ?>
            <?php else : ?>
            <p style="color: #b8c4a8;">Para adicionar fotos, é necessário um plano ativo. <a href="alterar-plano.php" style="color: #b8c4a8;">Escolha um plano</a>.</p>
            <?php endif; ?>
          </div>

          <div class="memorial-fotos-form-actions">
            <button type="submit" class="btn-salvar-alteracoes">Salvar alterações</button>
          </div>
        </form>
        <?php else : ?>
        <p style="color: #b8c4a8; font-size: 0.875rem;">Para adicionar fotos, configure o Firebase Storage (veja <code>config/firebase.config.example.php</code>).</p>
        <?php if (count($fotos_memorias) > 0) : ?>
        <div class="memorial-fotos">
          <?php foreach ($fotos_memorias as $m) : ?>
          <div class="memorial-foto-item">
            <img src="<?php echo htmlspecialchars($m['caminho']); ?>" alt="<?php echo htmlspecialchars($m['legenda'] ?? 'Foto'); ?>" loading="lazy">
            <?php if (!empty($m['legenda'])) : ?>
            <span class="legenda"><?php echo htmlspecialchars($m['legenda']); ?></span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (count($outras) > 0) : ?>
        <p style="color: #b8c4a8; font-size: 0.875rem; margin-top: 1rem;">Vídeos/áudios: <?php echo count($outras); ?> <?php echo count($outras) === 1 ? 'item' : 'itens'; ?>.</p>
        <?php endif; ?>
        <?php if (count($fotos_memorias) === 0 && count($outras) === 0 && !firebaseStorageDisponivel()) : ?>
        <p style="color: #b8c4a8; font-size: 0.875rem; margin-top: 1rem;">Nenhuma foto na seção Memórias ainda.</p>
        <?php endif; ?>
      </div>
      </div>

      <script>
      (function() {
        var tablist = document.querySelector('.memorial-editor-tabs');
        if (!tablist) return;
        var tabs = tablist.querySelectorAll('.memorial-editor-tab');
        var panels = document.querySelectorAll('.memorial-editor-panel');
        function selectTab(selectedTab) {
          var id = selectedTab.getAttribute('aria-controls');
          tabs.forEach(function(t) { t.setAttribute('aria-selected', t === selectedTab ? 'true' : 'false'); });
          panels.forEach(function(p) {
            var show = p.id === id;
            p.setAttribute('aria-hidden', show ? 'false' : 'true');
          });
        }
        tabs.forEach(function(t) {
          t.addEventListener('click', function() { selectTab(t); });
        });
      })();
      </script>
      <script>
      (function() {
        function normalizarAnoQuatroDigitos(el) {
          if (!el || el.type !== 'date') return;
          var v = (el.value || '').trim();
          if (v.length < 10) return;
          var parts = v.split('-');
          if (parts.length >= 1 && parts[0].length > 4) {
            parts[0] = parts[0].substring(0, 4);
            el.value = parts.join('-');
          }
        }
        var dn = document.getElementById('data_nascimento');
        var do_ = document.getElementById('data_obito');
        [dn, do_].forEach(function(input) {
          if (input) {
            input.addEventListener('input', function() { normalizarAnoQuatroDigitos(input); });
            input.addEventListener('change', function() { normalizarAnoQuatroDigitos(input); });
          }
        });
      })();
      </script>
      <script>
      (function() {
        function atualizarContador(span, max, len) {
          var restantes = Math.max(0, max - len);
          span.textContent = restantes + ' restantes / ' + max;
        }
        document.querySelectorAll('.memorial-contador').forEach(function(span) {
          var max = parseInt(span.getAttribute('data-max'), 10);
          if (isNaN(max)) return;
          var camp = span.closest('.memorial-campo');
          var input = camp ? camp.querySelector('input[maxlength], textarea[maxlength]') : null;
          if (!input) return;
          atualizarContador(span, max, (input.value || '').length);
          input.addEventListener('input', function() { atualizarContador(span, max, (input.value || '').length); });
        });
      })();
      </script>

      <?php if ($memorial['status'] === 'publicado') : 
        $url_publica = urlMemorialPublico($memorial['slug']);
      ?>
      <div class="memorial-link-box">
        <h3>Link do memorial</h3>
        <p>Compartilhe este link ou use o QR Code abaixo. Qualquer pessoa com o link ou que escanear o QR pode acessar a página pública.</p>
        <?php if (!empty($memorial['qr_code_path'])) : ?>
        <div style="margin-bottom: 1rem;">
          <img src="<?php echo htmlspecialchars($memorial['qr_code_path']); ?>" alt="QR Code do memorial" width="120" height="120" style="display: block;">
          <p style="font-size: 0.8125rem; color: #b8c4a8; margin-top: 0.25rem;">Imprima ou compartilhe esta imagem para lápides e lembranças.</p>
        </div>
        <?php endif; ?>
        <div class="link-row">
          <input type="text" id="url-memorial" readonly value="<?php echo htmlspecialchars($url_publica); ?>">
          <button type="button" class="btn-copiar" onclick="copiarLink()">Copiar link</button>
        </div>
        <div class="link-actions">
          <a href="memorial-despublicar.php?id=<?php echo $memorial['id']; ?>" class="btn-despublicar" onclick="return confirm('Tem certeza que deseja voltar este memorial para rascunho? O link público deixará de funcionar.');">Voltar para rascunho</a>
          <span style="font-size: 0.75rem; color: rgba(184, 196, 168, 0.6);">O link público deixará de funcionar até publicar novamente.</span>
        </div>
      </div>
      <?php else : ?>
      <div class="memorial-publicar-box">
        <p>Publique o memorial para gerar o link público. Depois você pode compartilhar o link ou usá-lo no sistema de QR Code.</p>
        <a href="memorial-publicar.php?id=<?php echo $memorial['id']; ?>" class="btn-publicar">Publicar memorial</a>
      </div>
      <?php endif; ?>

      <div class="memorial-actions">
        <a href="memoriais.php">← Voltar para meus memoriais</a>
      </div>
    </div>
  </main>

  <input type="file" id="input-alterar-foto" accept="image/jpeg,image/png,image/gif,image/webp" style="position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none;">

  <div id="modal-recorte" class="memorial-crop-modal" role="dialog" aria-labelledby="modal-recorte-titulo" aria-modal="true" aria-hidden="true">
    <div class="memorial-crop-modal__box">
      <h2 id="modal-recorte-titulo" class="memorial-crop-modal__titulo">Recortar imagem</h2>
      <p class="memorial-crop-modal__dica" style="padding: 0 1.25rem; margin: 0; font-size: 0.875rem; color: #b8c4a8;">Arraste e ajuste a área. A proporção é fixa (perfil: quadrado, capa: largura).</p>
      <div class="memorial-crop-modal__area">
        <img id="crop-img" src="" alt="Imagem para recorte" style="max-width: 100%; display: block;">
      </div>
      <div id="crop-enviando" class="memorial-crop-modal__enviando" aria-live="polite" style="display: none; padding: 0 1.25rem; margin: 0.75rem 0 0; font-size: 0.9375rem; color: #b8c4a8;">
        Enviando imagem… Não feche a janela.
      </div>
      <div class="memorial-crop-modal__acoes">
        <button type="button" class="memorial-crop-modal__btn-cancelar" id="crop-btn-cancelar">Cancelar</button>
        <button type="button" class="memorial-crop-modal__btn-aplicar" id="crop-btn-aplicar">Aplicar recorte</button>
      </div>
    </div>
  </div>

  <div id="overlay-salvando" class="memorial-salvando-overlay" aria-live="polite" aria-busy="false" role="status" aria-hidden="true">
    <div class="memorial-salvando-overlay__box">
      <div class="memorial-salvando-spinner" aria-hidden="true"></div>
      <p class="memorial-salvando-overlay__texto">Salvando alterações...</p>
      <p class="memorial-salvando-overlay__sub">Enviando imagens. Não feche a página.</p>
    </div>
  </div>

  <?php if (firebaseStorageDisponivel()) : ?>
  <script>
  (function() {
    var form = document.getElementById('form-fotos-memorial');
    var overlay = document.getElementById('overlay-salvando');
    var btnSalvar = form ? form.querySelector('.btn-salvar-alteracoes') : null;
    if (form && overlay) {
      form.addEventListener('submit', function() {
        overlay.classList.add('ativo');
        overlay.setAttribute('aria-busy', 'true');
        overlay.setAttribute('aria-hidden', 'false');
        if (btnSalvar) {
          btnSalvar.classList.add('salvando');
          btnSalvar.disabled = true;
          btnSalvar.textContent = 'Salvando...';
        }
      });
    }

    var input = document.getElementById('foto-multiplas');
    var hint = document.getElementById('upload-hint');
    var previewEl = document.getElementById('upload-preview');
    var maxFotos = input ? parseInt(input.getAttribute('data-max'), 10) : 0;

    function setHintText(n) {
      if (!hint) return;
      if (maxFotos > 0 && n > maxFotos) {
        hint.textContent = 'Máximo do plano: ' + maxFotos + '. ' + maxFotos + ' arquivo(s) selecionado(s).';
      } else {
        hint.textContent = n === 0 ? 'Nenhum arquivo selecionado' : (n === 1 ? '1 arquivo selecionado' : n + ' arquivos selecionados');
      }
    }

    function removeFileAtIndex(indexToRemove) {
      var files = input.files;
      var dt = new DataTransfer();
      for (var i = 0; i < files.length; i++) {
        if (i !== indexToRemove) dt.items.add(files[i]);
      }
      input.files = dt.files;
      renderPreview();
      setHintText(input.files.length);
    }

    function renderPreview() {
      if (!previewEl || !input) return;
      previewEl.innerHTML = '';
      var files = input.files;
      for (var i = 0; i < files.length; i++) {
        (function(index) {
          var file = files[index];
          if (!file.type.match(/^image\//)) return;
          var item = document.createElement('div');
          item.className = 'memorial-upload-preview__item';
          item.setAttribute('data-index', index);
          var img = document.createElement('img');
          img.alt = file.name || 'Preview';
          var reader = new FileReader();
          reader.onload = function(e) {
            img.src = e.target.result;
          };
          reader.readAsDataURL(file);
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'memorial-upload-preview__remove';
          btn.title = 'Remover esta imagem da seleção';
          btn.innerHTML = '&times;';
          btn.addEventListener('click', function() {
            removeFileAtIndex(index);
          });
          item.appendChild(img);
          item.appendChild(btn);
          previewEl.appendChild(item);
        })(i);
      }
    }

    if (input && hint) {
      input.addEventListener('change', function() {
        var files = this.files;
        var n = files.length;
        if (maxFotos > 0 && n > maxFotos) {
          var dt = new DataTransfer();
          for (var i = 0; i < maxFotos; i++) dt.items.add(files[i]);
          this.files = dt.files;
          n = maxFotos;
        }
        setHintText(this.files.length);
        renderPreview();
      });
    }
    document.querySelectorAll('.btn-remover-foto').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var card = this.closest('.memorial-foto-item-edit');
        if (card) {
          var keepInput = card.querySelector('input[name="keep_ids[]"]');
          if (keepInput) keepInput.remove();
          card.classList.add('removido');
          card.style.display = 'none';
        }
      });
    });
    var car = document.getElementById('carrossel-memorial');
    if (car) {
      var inner = car.querySelector('.memorial-carrossel__inner');
      var track = car.querySelector('.memorial-carrossel__track');
      var slides = car.querySelectorAll('.memorial-carrossel__slide');
      var prev = car.querySelector('.memorial-carrossel__prev');
      var next = car.querySelector('.memorial-carrossel__next');
      var dots = car.querySelectorAll('.memorial-carrossel__dot');
      var n = slides.length;
      var idx = 0;
      var autoplayTimer = null;

      function getOffset() {
        if (!inner || !slides[idx]) return 0;
        var slide = slides[idx];
        var innerW = inner.offsetWidth;
        return (innerW / 2) - (slide.offsetLeft + slide.offsetWidth / 2);
      }
      function applyTransform(px) {
        track.style.transform = 'translateX(' + (px !== undefined ? px : getOffset()) + 'px)';
      }
      function goTo(i) {
        idx = (i + n) % n;
        applyTransform();
        slides.forEach(function(s, j) { s.classList.toggle('ativo', j === idx); });
        dots.forEach(function(d, j) { d.classList.toggle('ativo', j === idx); });
        if (autoplayTimer) clearInterval(autoplayTimer);
        autoplayTimer = setInterval(function() { goTo(idx + 1); }, 15000);
      }

      function initCarousel() {
        applyTransform();
        if (autoplayTimer) clearInterval(autoplayTimer);
        autoplayTimer = setInterval(function() { goTo(idx + 1); }, 15000);
      }
      if (inner) {
        window.addEventListener('resize', function() { applyTransform(); });
        initCarousel();
      }

      var dragStartX = 0, dragCurrentX = 0, dragBaseOffset = 0;
      function onDragStart(e) {
        dragStartX = e.touches ? e.touches[0].clientX : e.clientX;
        dragCurrentX = dragStartX;
        dragBaseOffset = getOffset();
        track.classList.add('dragging');
      }
      function onDragMove(e) {
        dragCurrentX = e.touches ? e.touches[0].clientX : e.clientX;
        applyTransform(dragBaseOffset + (dragCurrentX - dragStartX));
      }
      function onDragEnd() {
        track.classList.remove('dragging');
        var dx = dragCurrentX - dragStartX;
        if (dx < -40) goTo(idx + 1);
        else if (dx > 40) goTo(idx - 1);
        else applyTransform();
      }
      if (inner) {
        inner.addEventListener('mousedown', onDragStart);
        inner.addEventListener('touchstart', onDragStart, { passive: true });
        document.addEventListener('mousemove', function(e) { if (track.classList.contains('dragging')) onDragMove(e); });
        document.addEventListener('touchmove', function(e) { if (track.classList.contains('dragging') && e.touches.length) onDragMove(e); }, { passive: true });
        document.addEventListener('mouseup', onDragEnd);
        document.addEventListener('touchend', onDragEnd);
      }

      if (prev) prev.addEventListener('click', function() { goTo(idx - 1); });
      if (next) next.addEventListener('click', function() { goTo(idx + 1); });
      dots.forEach(function(d) {
        d.addEventListener('click', function() { goTo(parseInt(this.getAttribute('data-index'), 10)); });
      });
    }
  })();
  </script>
  <?php endif; ?>
  <?php if ($memorial['status'] === 'publicado') : ?>
  <script>
  function copiarLink() {
    var input = document.getElementById('url-memorial');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(function() {
      var btn = document.querySelector('.btn-copiar');
      var txt = btn.textContent;
      btn.textContent = 'Copiado!';
      setTimeout(function() { btn.textContent = txt; }, 2000);
    });
  }
  </script>
  <?php endif; ?>

  <?php if (firebaseStorageDisponivel()) : ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
  <script>
  (function() {
    var modal = document.getElementById('modal-recorte');
    var cropImg = document.getElementById('crop-img');
    var btnCancelar = document.getElementById('crop-btn-cancelar');
    var btnAplicar = document.getElementById('crop-btn-aplicar');
    var main = document.getElementById('memorial-main');
    var memorialId = main ? main.getAttribute('data-memorial-id') : '';
    var inputAlterar = document.getElementById('input-alterar-foto');

    var cropper = null;
    var tipoAtual = '';
    var objectURLParaRevogar = null;

    function fecharMenus() {
      document.querySelectorAll('.memorial-editor-menu.ativo').forEach(function(m) {
        m.classList.remove('ativo');
        m.setAttribute('aria-hidden', 'true');
      });
    }

    function abrirRecorte(src, tipo) {
      if (!src || !memorialId) return;
      tipoAtual = tipo;
      if (objectURLParaRevogar) {
        URL.revokeObjectURL(objectURLParaRevogar);
        objectURLParaRevogar = null;
      }
      if (src.indexOf('blob:') === 0) {
        objectURLParaRevogar = src;
        cropImg.removeAttribute('crossOrigin');
      } else {
        cropImg.crossOrigin = 'anonymous';
      }
      cropImg.src = src;
      modal.classList.add('ativo');
      modal.setAttribute('aria-hidden', 'false');
      if (cropper) {
        cropper.destroy();
        cropper = null;
      }
      cropImg.onload = function() {
        var aspectRatio = tipo === 'perfil' ? 1 : (21 / 9);
        cropper = new Cropper(cropImg, {
          aspectRatio: aspectRatio,
          viewMode: 1,
          dragMode: 'move',
          autoCropArea: 0.8,
          restore: false,
          guides: true,
          center: true,
          highlight: false,
          cropBoxMovable: true,
          cropBoxResizable: true
        });
      };
    }

    function fecharRecorte() {
      if (cropper) {
        cropper.destroy();
        cropper = null;
      }
      if (objectURLParaRevogar) {
        URL.revokeObjectURL(objectURLParaRevogar);
        objectURLParaRevogar = null;
      }
      modal.classList.remove('ativo');
      modal.setAttribute('aria-hidden', 'true');
      cropImg.src = '';
      if (inputAlterar) inputAlterar.value = '';
    }

    btnCancelar.addEventListener('click', fecharRecorte);
    modal.addEventListener('click', function(e) {
      if (e.target === modal) fecharRecorte();
    });

    var cropEnviando = document.getElementById('crop-enviando');
    var cropCancelar = document.getElementById('crop-btn-cancelar');

    btnAplicar.addEventListener('click', function() {
      if (!cropper || !memorialId || !tipoAtual) return;
      btnAplicar.disabled = true;
      btnAplicar.textContent = 'Enviando...';
      if (cropEnviando) cropEnviando.style.display = 'block';
      if (cropCancelar) cropCancelar.disabled = true;
      var maxSize = tipoAtual === 'perfil' ? 800 : 1600;
      cropper.getCroppedCanvas({ maxWidth: maxSize, maxHeight: tipoAtual === 'perfil' ? 800 : 600 }).toBlob(function(blob) {
        var fd = new FormData();
        fd.append('memorial_id', memorialId);
        fd.append('tipo', tipoAtual);
        fd.append('imagem', blob, 'crop.jpg');
        fetch('memorial-midia-recortar.php', { method: 'POST', body: fd })
          .then(function(r) { return r.json(); })
          .then(function(data) {
            btnAplicar.disabled = false;
            btnAplicar.textContent = 'Aplicar recorte';
            if (cropEnviando) cropEnviando.style.display = 'none';
            if (cropCancelar) cropCancelar.disabled = false;
            if (data.ok && data.url) {
              var preview = document.getElementById('preview-' + tipoAtual);
              if (preview) {
                preview.src = data.url + (data.url.indexOf('?') >= 0 ? '&' : '?') + 't=' + Date.now();
                fecharRecorte();
              } else {
                fecharRecorte();
                var params = new URLSearchParams(window.location.search);
                params.set('foto_atualizada', '1');
                window.location.href = window.location.pathname + '?' + params.toString();
              }
            } else {
              alert(data.erro || 'Não foi possível aplicar o recorte.');
            }
          })
          .catch(function() {
            btnAplicar.disabled = false;
            btnAplicar.textContent = 'Aplicar recorte';
            if (cropEnviando) cropEnviando.style.display = 'none';
            if (cropCancelar) cropCancelar.disabled = false;
            alert('Erro ao enviar. Tente novamente.');
          });
      }, 'image/jpeg', 0.92);
    });

    document.querySelectorAll('.memorial-editor-btn-editar').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        var src = this.getAttribute('data-src');
        tipoAtual = this.getAttribute('data-tipo') || '';
        fecharMenus();
        if (!src || src === '') {
          if (tipoAtual && inputAlterar) {
            inputAlterar.value = '';
            inputAlterar.click();
          }
          return;
        }
        var wrap = this.closest('.memorial-editor-editar-wrap');
        var menu = wrap ? wrap.querySelector('.memorial-editor-menu') : null;
        if (menu) {
          menu.classList.add('ativo');
          menu.setAttribute('aria-hidden', 'false');
        }
      });
    });

    document.querySelectorAll('.memorial-editor-menu').forEach(function(menu) {
      menu.querySelectorAll('[data-acao="excluir"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var wrap = menu.closest('.memorial-editor-editar-wrap');
          var botao = wrap ? wrap.querySelector('.memorial-editor-btn-editar') : null;
          var tipo = botao ? botao.getAttribute('data-tipo') : tipoAtual;
          fecharMenus();
          if (!memorialId || !tipo) return;
          if (!confirm('Remover esta foto? A foto continuará na aba Memórias, mas não será mais usada como ' + (tipo === 'perfil' ? 'perfil' : 'capa') + '.')) return;
          var form = document.createElement('form');
          form.method = 'POST';
          form.action = 'memorial-perfil-capa-remover.php';
          var id = document.createElement('input');
          id.type = 'hidden';
          id.name = 'memorial_id';
          id.value = memorialId;
          var t = document.createElement('input');
          t.type = 'hidden';
          t.name = 'tipo';
          t.value = tipo;
          form.appendChild(id);
          form.appendChild(t);
          document.body.appendChild(form);
          form.submit();
        });
      });
      menu.querySelectorAll('[data-acao="alterar"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
          fecharMenus();
          if (inputAlterar) {
            inputAlterar.value = '';
            inputAlterar.click();
          }
        });
      });
    });

    if (inputAlterar) {
      inputAlterar.addEventListener('change', function() {
        var file = this.files && this.files[0];
        if (!file || !memorialId || !tipoAtual) return;
        var url = URL.createObjectURL(file);
        abrirRecorte(url, tipoAtual);
      });
    }

    document.addEventListener('click', fecharMenus);
  })();
  </script>
  <?php endif; ?>
</body>
</html>
