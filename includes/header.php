<?php
/**
 * Header reutilizável — Imortalize
 */
session_start();
$logado = !empty($_SESSION['user_id']);
?>
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
      <?php if ($logado) : ?>
      <a href="dashboard.php" class="btn-minha-area">Minha área</a>
      <a href="logout.php" class="btn-sair">Sair</a>
      <?php else : ?>
      <a href="login.php" class="btn-entrar">Entrar</a>
      <a href="cadastro.php" class="btn-criar-conta">Criar conta</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
