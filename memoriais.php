<?php
/**
 * Meus memoriais — Imortalize
 * Lista todos os memoriais do usuário logado.
 */
session_start();
require_once __DIR__ . '/config/database.php';

// Verificar se está logado
if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$nome_usuario = $_SESSION['user_nome'] ?? 'Usuário';

// Buscar memoriais do usuário
$memoriais = buscarMemoriais($user_id);
$total_memoriais = count($memoriais);

$page_title = 'Meus memoriais | Imortalize';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <link rel="stylesheet" href="css/site.css">
  <style>
    .memoriais-page {
      min-height: 100vh;
      padding: 2rem 1rem 3rem;
    }
    .memoriais-inner {
      max-width: 1000px;
      margin: 0 auto;
    }
    .memoriais-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      flex-wrap: wrap;
      gap: 1rem;
    }
    .memoriais-header h1 {
      font-size: clamp(1.5rem, 4vw, 1.875rem);
      font-weight: 600;
      margin: 0;
      color: #f0f2eb;
    }
    .memoriais-header .btn-criar {
      padding: 0.75rem 1.5rem;
      font-size: 1rem;
      font-weight: 600;
      color: #192112;
      background: #b8c4a8;
      border: none;
      border-radius: 12px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: background 0.25s ease, transform 0.25s ease;
    }
    .memoriais-header .btn-criar:hover {
      background: #f0f2eb;
      transform: translateY(-2px);
    }
    .memoriais-stats {
      font-size: 0.9375rem;
      color: #b8c4a8;
      margin-bottom: 2rem;
    }
    .memoriais-grid {
      display: grid;
      gap: 1.5rem;
      grid-template-columns: 1fr;
    }
    @media (min-width: 640px) {
      .memoriais-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    @media (min-width: 900px) {
      .memoriais-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }
    .memorial-card {
      background: rgba(39, 51, 28, 0.6);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 16px;
      padding: 1.5rem;
      transition: border-color 0.25s ease, transform 0.25s ease, box-shadow 0.25s ease;
      text-decoration: none;
      color: inherit;
      display: block;
    }
    .memorial-card:hover {
      border-color: rgba(255, 255, 255, 0.18);
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(0, 0, 0, 0.25);
    }
    .memorial-card__status {
      display: inline-block;
      padding: 0.25rem 0.65rem;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      border-radius: 20px;
      margin-bottom: 0.75rem;
    }
    .memorial-card__status.rascunho {
      background: rgba(255, 255, 255, 0.1);
      color: #b8c4a8;
    }
    .memorial-card__status.publicado {
      background: rgba(184, 196, 168, 0.2);
      color: #b8c4a8;
    }
    .memorial-card h2 {
      font-size: 1.25rem;
      font-weight: 600;
      margin: 0 0 0.5rem;
      color: #f0f2eb;
    }
    .memorial-card__bio {
      font-size: 0.875rem;
      color: #b8c4a8;
      margin: 0 0 1rem;
      line-height: 1.5;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .memorial-card__meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.8125rem;
      color: #b8c4a8;
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
    .memorial-card__stats {
      display: flex;
      gap: 1rem;
    }
    .memorial-card__stats span {
      display: flex;
      align-items: center;
      gap: 0.25rem;
    }
    .memorial-card__link {
      text-decoration: none;
      color: inherit;
      display: block;
    }
    .memorial-card__copy {
      margin-top: 0.75rem;
      padding-top: 0.75rem;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
    .btn-copiar-card {
      padding: 0.4rem 0.75rem;
      font-size: 0.8125rem;
      font-weight: 600;
      color: #192112;
      background: #b8c4a8;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      width: 100%;
      transition: background 0.2s ease;
    }
    .btn-copiar-card:hover {
      background: #f0f2eb;
    }
    .memorial-vazio {
      text-align: center;
      padding: 3.5rem 2rem;
      max-width: 420px;
      margin: 0 auto;
      background: linear-gradient(180deg, rgba(39, 51, 28, 0.7) 0%, rgba(25, 33, 18, 0.85) 100%);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 20px;
      box-shadow: 0 16px 48px rgba(0, 0, 0, 0.2);
    }
    .memorial-vazio__icon {
      width: 72px;
      height: 72px;
      margin: 0 auto 1.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(184, 196, 168, 0.15);
      border-radius: 50%;
      color: #b8c4a8;
    }
    .memorial-vazio__icon svg {
      width: 36px;
      height: 36px;
      stroke-width: 1.5;
    }
    .memorial-vazio h2 {
      font-size: 1.375rem;
      font-weight: 600;
      color: #f0f2eb;
      margin: 0 0 0.5rem;
      letter-spacing: -0.02em;
    }
    .memorial-vazio p {
      font-size: 0.9375rem;
      color: #b8c4a8;
      margin: 0 auto 1.75rem;
      line-height: 1.5;
      max-width: 280px;
    }
    .memorial-vazio .btn-criar-vazio {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0.9rem 1.75rem;
      font-size: 1rem;
      font-weight: 600;
      color: #192112;
      background: #b8c4a8;
      border: none;
      border-radius: 12px;
      text-decoration: none;
      transition: background 0.25s ease, transform 0.25s ease, box-shadow 0.25s ease;
    }
    .memorial-vazio .btn-criar-vazio:hover {
      background: #f0f2eb;
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    }
    .memorial-vazio .btn-criar-vazio svg {
      width: 20px;
      height: 20px;
    }
    .memoriais-footer {
      margin-top: 2.5rem;
      text-align: center;
      padding-top: 1.5rem;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
    .memoriais-footer a {
      font-size: 0.875rem;
      color: #b8c4a8;
      text-decoration: none;
    }
    .memoriais-footer a:hover {
      color: #f0f2eb;
    }
  </style>
</head>
<body>
  <?php 
  // Header inline (evitar double session_start)
  $logado_header = !empty($_SESSION['user_id']);
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

  <main class="memoriais-page">
    <div class="memoriais-inner">
      <div class="memoriais-header">
        <h1>Meus memoriais</h1>
        <a href="memoriais-criar.php" class="btn-criar">+ Criar memorial</a>
      </div>

      <?php if (isset($_GET['criado']) && $_GET['criado'] == '1') : ?>
      <div style="background: rgba(0, 255, 0, 0.1); border: 1px solid rgba(0, 255, 0, 0.3); border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem; color: #99ff99; font-size: 0.9375rem;">
        ✓ Memorial criado com sucesso!
      </div>
      <?php endif; ?>

      <p class="memoriais-stats">
        <?php if ($total_memoriais > 0) : ?>
          Você tem <strong><?php echo $total_memoriais; ?></strong> <?php echo $total_memoriais === 1 ? 'memorial' : 'memoriais'; ?>
        <?php else : ?>
          Você ainda não criou nenhum memorial.
        <?php endif; ?>
      </p>

      <?php if ($total_memoriais > 0) : ?>
      <div class="memoriais-grid">
        <?php foreach ($memoriais as $memorial) : ?>
        <div class="memorial-card">
          <a href="memorial.php?id=<?php echo $memorial['id']; ?>" class="memorial-card__link" title="Ver detalhes do memorial">
            <span class="memorial-card__status <?php echo htmlspecialchars($memorial['status']); ?>">
              <?php echo $memorial['status'] === 'publicado' ? 'Publicado' : 'Rascunho'; ?>
            </span>
            <h2><?php echo htmlspecialchars($memorial['titulo']); ?></h2>
            <?php if ($memorial['biografia']) : ?>
            <p class="memorial-card__bio"><?php echo htmlspecialchars($memorial['biografia']); ?></p>
            <?php endif; ?>
            <div class="memorial-card__meta">
              <div class="memorial-card__stats">
                <?php if ($memorial['total_fotos'] > 0) : ?>
                <span>📷 <?php echo $memorial['total_fotos']; ?></span>
                <?php endif; ?>
                <?php if ($memorial['total_videos'] > 0) : ?>
                <span>🎥 <?php echo $memorial['total_videos']; ?></span>
                <?php endif; ?>
                <?php if ($memorial['total_audios'] > 0) : ?>
                <span>🎵 <?php echo $memorial['total_audios']; ?></span>
                <?php endif; ?>
              </div>
              <span><?php echo $memorial['data_formatada']; ?></span>
            </div>
          </a>
          <?php if ($memorial['status'] === 'publicado') : 
            $url_pub = urlMemorialPublico($memorial['slug']);
          ?>
          <div class="memorial-card__copy">
            <button type="button" class="btn-copiar-card" data-url="<?php echo htmlspecialchars($url_pub); ?>" onclick="copiarLinkCard(this)">Copiar link</button>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else : ?>
      <div class="memorial-vazio">
        <div class="memorial-vazio__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            <path d="M8 7h8"/>
            <path d="M8 11h8"/>
          </svg>
        </div>
        <h2>Nenhum memorial ainda</h2>
        <p>Crie seu primeiro memorial para preservar memórias que importam.</p>
        <a href="memoriais-criar.php" class="btn-criar-vazio">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Criar meu primeiro memorial
        </a>
      </div>
      <?php endif; ?>

      <footer class="memoriais-footer">
        <a href="dashboard.php">← Voltar ao dashboard</a>
      </footer>
    </div>
  </main>
  <script>
  function copiarLinkCard(btn) {
    var url = btn.getAttribute('data-url');
    navigator.clipboard.writeText(url).then(function() {
      var txt = btn.textContent;
      btn.textContent = 'Copiado!';
      setTimeout(function() { btn.textContent = txt; }, 2000);
    });
  }
  </script>
</body>
</html>
