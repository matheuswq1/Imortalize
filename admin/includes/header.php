<?php
/**
 * Cabeçalho do painel admin — verifica login e permissão admin.
 * Layout: sidebar à esquerda + área principal. Responsivo (menu hamburger no mobile).
 * Uso: definir $admin_page_title antes de incluir; depois incluir footer.
 */
if (!isset($admin_page_title)) {
  $admin_page_title = 'Painel Admin';
}

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once dirname(__DIR__, 2) . '/config/database.php';

// URL base do site (com BASE_URL do config quando existir) — usada em redirects, CSS e links
$site_base = (function_exists('getSiteBaseUrl') ? getSiteBaseUrl() . '/' : dirname($_SERVER['SCRIPT_NAME'], 2) . '/');

if (empty($_SESSION['user_id'])) {
  header('Location: ' . $site_base . 'login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
  exit;
}

if (!isAdmin($_SESSION['user_id'] ?? 0, $_SESSION['user_email'] ?? null)) {
  header('Location: ' . $site_base . 'dashboard.php');
  exit;
}

$admin_nome = $_SESSION['user_nome'] ?? 'Admin';
$base_url = dirname($_SERVER['SCRIPT_NAME']) . '/';
$current_script = basename($_SERVER['PHP_SELF']);

function admin_nav_ativo($script, $check) {
  if (is_array($check)) {
    foreach ($check as $c) { if (strpos($script, $c) === 0) return ' ativo'; }
    return '';
  }
  return ($script === $check) ? ' ativo' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($admin_page_title); ?> | Admin Imortalize</title>
  <link rel="stylesheet" href="<?php echo $site_base; ?>css/site.css">
  <style>
    .admin-layout { display: flex; min-height: 100vh; }
    .admin-sidebar {
      width: 240px; flex-shrink: 0; background: rgba(28,36,20,0.95); border-right: 1px solid rgba(255,255,255,0.08);
      padding: 1.25rem 0; display: flex; flex-direction: column; position: fixed; left: 0; top: 0; bottom: 0;
      z-index: 100; overflow-y: auto; transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .admin-sidebar__brand { padding: 0 1.25rem 1rem; font-size: 1.125rem; font-weight: 600; color: #f0f2eb; border-bottom: 1px solid rgba(255,255,255,0.06); margin-bottom: 1rem; }
    .admin-sidebar__nav { display: flex; flex-direction: column; gap: 0.25rem; padding: 0 0.75rem; }
    .admin-sidebar__nav a {
      padding: 0.6rem 1rem; font-size: 0.875rem; color: #b8c4a8; text-decoration: none; border-radius: 8px;
      transition: color 0.2s, background 0.2s; display: block;
    }
    .admin-sidebar__nav a:hover { color: #f0f2eb; background: rgba(255,255,255,0.06); }
    .admin-sidebar__nav a.ativo { color: #f0f2eb; background: rgba(184,196,168,0.15); }
    .admin-sidebar__nav .admin-sidebar__sair { margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.06); color: #c88; }
    .admin-sidebar__nav .admin-sidebar__sair:hover { color: #faa; background: rgba(200,136,136,0.1); }
    .admin-main { flex: 1; min-width: 0; display: flex; flex-direction: column; margin-left: 240px; }
    .admin-topbar {
      display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.08);
      background: rgba(39,51,28,0.5); flex-shrink: 0;
    }
    .admin-menu-toggle {
      display: none; align-items: center; justify-content: center; width: 40px; height: 40px; padding: 0;
      background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
      color: #f0f2eb; font-size: 1.25rem; cursor: pointer; transition: background 0.2s;
    }
    .admin-menu-toggle:hover { background: rgba(255,255,255,0.1); }
    .admin-topbar__title { margin: 0; font-size: 1.25rem; font-weight: 600; color: #f0f2eb; }
    .admin-content { flex: 1; padding: 1.5rem; }
    .admin-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99; }
    .admin-card { background: rgba(39,51,28,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; }
    .admin-card h2 { font-size: 1.125rem; font-weight: 600; margin: 0 0 1rem; color: #f0f2eb; }
    .admin-stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .admin-stat { background: rgba(39,51,28,0.8); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1.25rem; text-align: center; }
    .admin-stat__num { font-size: 1.75rem; font-weight: 700; color: #f0f2eb; display: block; }
    .admin-stat__label { font-size: 0.8125rem; color: #b8c4a8; margin-top: 0.25rem; }
    .admin-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; min-width: 280px; }
    .admin-table th, .admin-table td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.06); }
    .admin-table th { color: #b8c4a8; font-weight: 600; }
    .admin-table td { color: #f0f2eb; word-break: break-word; }
    .admin-table tr:hover td { background: rgba(255,255,255,0.03); }
    .admin-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%; min-width: 0; }
    .admin-dashboard-card { min-width: 0; overflow: hidden; }
    .admin-badge { display: inline-block; padding: 0.2rem 0.5rem; font-size: 0.75rem; border-radius: 6px; }
    .admin-badge--publicado { background: rgba(184,196,168,0.2); color: #b8c4a8; }
    .admin-badge--rascunho { background: rgba(255,255,255,0.1); color: #b8c4a8; }
    .admin-badge--ativo { background: rgba(184,196,168,0.2); color: #b8c4a8; }
    .admin-badge--disponivel { background: rgba(184,196,168,0.2); color: #b8c4a8; }
    .admin-badge--usado { background: rgba(255,255,255,0.15); color: #f0f2eb; }
    @media (max-width: 768px) {
      .admin-sidebar { transform: translateX(-100%); }
      .admin-sidebar.is-open { transform: translateX(0); box-shadow: 4px 0 20px rgba(0,0,0,0.3); }
      .admin-main { margin-left: 0; }
      .admin-menu-toggle { display: flex; }
      .admin-table { font-size: 0.8125rem; }
      .admin-table th, .admin-table td { padding: 0.5rem; }
    }
    body.admin-sidebar-open .admin-overlay { display: block; }
  </style>
</head>
<body>
  <div class="admin-layout">
    <aside class="admin-sidebar" id="admin-sidebar" aria-label="Menu do painel">
      <div class="admin-sidebar__brand">Imortalize Admin</div>
      <nav class="admin-sidebar__nav">
        <a href="<?php echo $base_url; ?>index.php" class="<?php echo admin_nav_ativo($current_script, 'index.php'); ?>">Visão geral</a>
        <a href="<?php echo $base_url; ?>usuarios.php" class="<?php echo admin_nav_ativo($current_script, 'usuarios.php'); ?>">Usuários</a>
        <a href="<?php echo $base_url; ?>memoriais.php" class="<?php echo admin_nav_ativo($current_script, 'memoriais.php'); ?>">Memoriais</a>
        <a href="<?php echo $base_url; ?>planos.php" class="<?php echo admin_nav_ativo($current_script, ['planos']); ?>">Planos</a>
        <a href="<?php echo $base_url; ?>newsletter.php" class="<?php echo admin_nav_ativo($current_script, 'newsletter.php'); ?>">Newsletter</a>
        <a href="<?php echo $base_url; ?>qrcodes.php" class="<?php echo admin_nav_ativo($current_script, ['qrcodes']); ?>">QR Codes</a>
        <a href="<?php echo $base_url; ?>configuracoes.php" class="<?php echo admin_nav_ativo($current_script, 'configuracoes.php'); ?>">Configurações</a>
        <a href="<?php echo $site_base; ?>dashboard.php">Área do usuário</a>
        <a href="<?php echo $site_base; ?>logout.php" class="admin-sidebar__sair">Sair</a>
      </nav>
    </aside>
    <div class="admin-overlay" id="admin-overlay" aria-hidden="true"></div>
    <div class="admin-main">
      <header class="admin-topbar">
        <button type="button" class="admin-menu-toggle" id="admin-menu-toggle" aria-label="Abrir menu" aria-expanded="false">
          <span aria-hidden="true">☰</span>
        </button>
        <h1 class="admin-topbar__title"><?php echo htmlspecialchars($admin_page_title); ?></h1>
      </header>
      <main class="admin-content">
