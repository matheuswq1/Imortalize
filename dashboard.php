<?php
/**
 * Dashboard — Imortalize
 * Área do usuário após login. Busca dados reais do banco MySQL.
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
$user_email = $_SESSION['user_email'] ?? '';
$eh_admin = isAdmin($user_id, $user_email);

// Buscar plano ativo e memoriais para o dashboard
$plano = buscarPlanoAtivo($user_id);
$tem_plano_ativo = !empty($plano);
$memoriais = buscarMemoriais($user_id);
$total_memoriais = count($memoriais);
$primeiro_nome = trim(explode(' ', $nome_usuario)[0]) ?: 'Usuário';

$page_title = 'Minha área | Imortalize';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <link rel="stylesheet" href="css/site.css">
  <style>
    .dashboard-page {
      min-height: 100vh;
      padding: 2rem 1rem 3rem;
    }
    .dashboard-inner {
      max-width: 960px;
      margin: 0 auto;
    }
    .dashboard-hero {
      margin-bottom: 2rem;
    }
    .dashboard-hero h1 {
      font-size: clamp(1.75rem, 4vw, 2.25rem);
      font-weight: 600;
      margin: 0 0 0.35rem;
      color: #f0f2eb;
      letter-spacing: -0.02em;
    }
    .dashboard-hero__sub {
      font-size: 1rem;
      color: #b8c4a8;
      margin: 0 0 1.5rem;
    }
    .dashboard-stats {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
    }
    .dashboard-stat {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      padding: 0.75rem 1.25rem;
      background: rgba(39, 51, 28, 0.7);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      min-width: 140px;
    }
    .dashboard-stat__icon {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(184, 196, 168, 0.2);
      border-radius: 10px;
      color: #b8c4a8;
    }
    .dashboard-stat__icon svg {
      width: 22px;
      height: 22px;
    }
    .dashboard-stat__val {
      font-size: 1.5rem;
      font-weight: 700;
      color: #f0f2eb;
      line-height: 1.2;
    }
    .dashboard-stat__label {
      font-size: 0.8125rem;
      color: #b8c4a8;
    }
    .dashboard-section-title {
      font-size: 1.125rem;
      font-weight: 600;
      color: #f0f2eb;
      margin: 0 0 1.25rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .dashboard-section-title svg {
      width: 22px;
      height: 22px;
      color: #b8c4a8;
    }
    .dashboard-cards {
      display: grid;
      gap: 1.25rem;
      grid-template-columns: 1fr;
    }
    @media (min-width: 640px) {
      .dashboard-cards {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    @media (min-width: 900px) {
      .dashboard-cards {
        grid-template-columns: repeat(3, 1fr);
      }
    }
    .dashboard-card {
      background: linear-gradient(180deg, rgba(39, 51, 28, 0.75) 0%, rgba(25, 33, 18, 0.85) 100%);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 16px;
      padding: 1.5rem;
      text-decoration: none;
      color: inherit;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      transition: border-color 0.25s ease, transform 0.25s ease, box-shadow 0.25s ease;
    }
    .dashboard-card:hover {
      border-color: rgba(255, 255, 255, 0.18);
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(0, 0, 0, 0.25);
    }
    .dashboard-card__icon {
      width: 44px;
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(184, 196, 168, 0.15);
      border-radius: 12px;
      color: #b8c4a8;
      margin-bottom: 0.25rem;
    }
    .dashboard-card__icon svg {
      width: 24px;
      height: 24px;
    }
    .dashboard-card h2 {
      font-size: 1.125rem;
      font-weight: 600;
      margin: 0;
      color: #f0f2eb;
    }
    .dashboard-card p {
      font-size: 0.875rem;
      color: #b8c4a8;
      margin: 0;
      line-height: 1.5;
      flex: 1;
    }
    .dashboard-card--disabled {
      opacity: 0.85;
      pointer-events: none;
      cursor: default;
    }
    .dashboard-tip {
      margin-top: 2rem;
      padding: 1.25rem 1.5rem;
      background: rgba(184, 196, 168, 0.08);
      border: 1px solid rgba(184, 196, 168, 0.2);
      border-radius: 16px;
      display: flex;
      gap: 1rem;
      align-items: flex-start;
    }
    .dashboard-tip__icon {
      width: 40px;
      height: 40px;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(184, 196, 168, 0.2);
      border-radius: 10px;
      color: #b8c4a8;
    }
    .dashboard-tip__icon svg {
      width: 22px;
      height: 22px;
    }
    .dashboard-tip h3 {
      font-size: 0.9375rem;
      font-weight: 600;
      color: #f0f2eb;
      margin: 0 0 0.25rem;
    }
    .dashboard-tip p {
      font-size: 0.875rem;
      color: #b8c4a8;
      margin: 0;
      line-height: 1.5;
    }
    .dashboard-tip a {
      font-size: 0.875rem;
      font-weight: 600;
      color: #b8c4a8;
      text-decoration: none;
      margin-top: 0.5rem;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
    }
    .dashboard-tip a:hover {
      color: #f0f2eb;
    }
    .dashboard-footer {
      margin-top: 2.5rem;
      text-align: center;
      padding-top: 1.5rem;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
    .dashboard-footer a {
      font-size: 0.875rem;
      color: #b8c4a8;
      text-decoration: none;
    }
    .dashboard-footer a:hover {
      color: #f0f2eb;
    }
    .dashboard-footer .sep {
      margin: 0 0.5rem;
      color: rgba(255, 255, 255, 0.3);
    }
    .plano-ativo {
      background: linear-gradient(145deg, rgba(39, 51, 28, 0.95) 0%, rgba(45, 61, 34, 0.9) 100%);
      border: 1px solid rgba(184, 196, 168, 0.25);
      border-radius: 20px;
      padding: 1.75rem 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
      display: flex;
      flex-wrap: wrap;
      gap: 1.5rem;
      align-items: flex-start;
      justify-content: space-between;
    }
    .plano-ativo__content {
      flex: 1;
      min-width: 200px;
    }
    .plano-ativo__badge {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.35rem 0.75rem;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: #192112;
      background: #b8c4a8;
      border-radius: 20px;
      margin-bottom: 0.75rem;
    }
    .plano-ativo__badge svg {
      width: 14px;
      height: 14px;
    }
    .plano-ativo h2 {
      font-size: 1.35rem;
      font-weight: 600;
      margin: 0 0 0.5rem;
      color: #f0f2eb;
    }
    .plano-ativo__meta {
      font-size: 0.875rem;
      color: #b8c4a8;
      margin-bottom: 1rem;
    }
    .plano-ativo__meta strong {
      color: #f0f2eb;
    }
    .plano-ativo__lista {
      list-style: none;
      padding: 0;
      margin: 0;
      font-size: 0.875rem;
      color: #b8c4a8;
      line-height: 1.7;
    }
    .plano-ativo__lista li {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .plano-ativo__lista li::before {
      content: "";
      width: 6px;
      height: 6px;
      background: #b8c4a8;
      border-radius: 50%;
    }
    .plano-ativo__cta {
      flex-shrink: 0;
    }
    .plano-ativo .btn-plano {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.65rem 1.25rem;
      font-size: 0.9375rem;
      font-weight: 600;
      color: #192112;
      background: #b8c4a8;
      border: none;
      border-radius: 10px;
      text-decoration: none;
      transition: background 0.2s ease, transform 0.2s ease;
    }
    .plano-ativo .btn-plano:hover {
      background: #f0f2eb;
      transform: translateY(-1px);
    }
    .plano-ativo .btn-plano svg {
      width: 18px;
      height: 18px;
    }
  </style>
</head>
<body>
  <?php $logado_header = true; ?>
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
        <a href="dashboard.php" class="btn-minha-area">Minha área</a>
        <a href="memoriais.php" class="nav-link">Memoriais</a>
        <a href="logout.php" class="btn-sair">Sair</a>
      </nav>
    </div>
  </header>

  <main class="dashboard-page">
    <div class="dashboard-inner">
      <header class="dashboard-hero" role="banner">
        <h1>Olá, <?php echo htmlspecialchars($primeiro_nome); ?>!</h1>
        <p class="dashboard-hero__sub"><?php echo $tem_plano_ativo ? 'Seu plano está ativo. Aqui está o resumo da sua área.' : 'Gerencie seus memoriais e sua conta.'; ?></p>
        <div class="dashboard-stats">
          <div class="dashboard-stat">
            <div class="dashboard-stat__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                <path d="M8 7h8"/>
                <path d="M8 11h8"/>
              </svg>
            </div>
            <div>
              <div class="dashboard-stat__val"><?php echo $total_memoriais; ?></div>
              <div class="dashboard-stat__label"><?php echo $total_memoriais === 1 ? 'Memorial' : 'Memoriais'; ?></div>
            </div>
          </div>
          <div class="dashboard-stat">
            <div class="dashboard-stat__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2v4"/>
                <path d="M12 18v4"/>
                <path d="M4.93 4.93l2.83 2.83"/>
                <path d="M16.24 16.24l2.83 2.83"/>
                <path d="M2 12h4"/>
                <path d="M18 12h4"/>
                <path d="M4.93 19.07l2.83-2.83"/>
                <path d="M16.24 7.76l2.83-2.83"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </div>
            <div>
              <div class="dashboard-stat__val"><?php echo $tem_plano_ativo ? htmlspecialchars($plano['plano_nome']) : '—'; ?></div>
              <div class="dashboard-stat__label">Plano atual</div>
            </div>
          </div>
        </div>
      </header>

      <?php if ($tem_plano_ativo && isset($plano)) : ?>
      <section class="plano-ativo" aria-labelledby="plano-ativo-titulo">
        <div class="plano-ativo__content">
          <span class="plano-ativo__badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Plano ativo
          </span>
          <h2 id="plano-ativo-titulo"><?php echo htmlspecialchars($plano['plano_nome']); ?></h2>
          <p class="plano-ativo__meta">
            <strong>Status:</strong> <?php echo htmlspecialchars($plano['status_label']); ?> &nbsp;·&nbsp;
            <strong>Validade:</strong> <?php echo htmlspecialchars($plano['validade']); ?> &nbsp;·&nbsp;
            Válido até <?php echo htmlspecialchars($plano['vencimento_formatado']); ?>
          </p>
          <ul class="plano-ativo__lista">
            <?php foreach ($plano['beneficios'] as $item) : ?>
              <li><?php echo htmlspecialchars($item); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="plano-ativo__cta">
          <a href="alterar-plano.php" class="btn-plano">Ver planos <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
      </section>
      <?php endif; ?>

      <h2 class="dashboard-section-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Ações rápidas
      </h2>
      <div class="dashboard-cards">
        <a href="memoriais.php" class="dashboard-card">
          <div class="dashboard-card__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
              <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
              <path d="M8 7h8"/>
              <path d="M8 11h8"/>
            </svg>
          </div>
          <h2>Meus memoriais</h2>
          <p><?php echo $tem_plano_ativo ? 'Crie e gerencie seus memoriais. Adicione fotos, textos e preserve histórias.' : 'Crie e gerencie seus memoriais. Escolha um plano para começar.'; ?></p>
        </a>
        <?php if (!$tem_plano_ativo) : ?>
        <a href="alterar-plano.php" class="dashboard-card">
          <div class="dashboard-card__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2v4"/>
              <path d="M12 18v4"/>
              <path d="M4.93 4.93l2.83 2.83"/>
              <path d="M16.24 16.24l2.83 2.83"/>
              <path d="M2 12h4"/>
              <path d="M18 12h4"/>
              <path d="M4.93 19.07l2.83-2.83"/>
              <path d="M16.24 7.76l2.83-2.83"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </div>
          <h2>Escolher plano</h2>
          <p>Escolha um plano para começar a criar seus memoriais com fotos, áudios e vídeos.</p>
        </a>
        <?php else : ?>
        <a href="alterar-plano.php" class="dashboard-card">
          <div class="dashboard-card__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2v4"/>
              <path d="M12 18v4"/>
              <path d="M4.93 4.93l2.83 2.83"/>
              <path d="M16.24 16.24l2.83 2.83"/>
              <path d="M2 12h4"/>
              <path d="M18 12h4"/>
              <path d="M4.93 19.07l2.83-2.83"/>
              <path d="M16.24 7.76l2.83-2.83"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </div>
          <h2>Alterar plano</h2>
          <p>Veja outros planos ou renove. Seu <?php echo htmlspecialchars($plano['plano_nome']); ?> está ativo.</p>
        </a>
        <?php endif; ?>
        <a href="configuracoes.php" class="dashboard-card">
          <div class="dashboard-card__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="3"/>
              <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
          </div>
          <h2>Configurações</h2>
          <p>Altere seu e-mail, senha ou dados de perfil.</p>
        </a>
      </div>

      <?php if ($total_memoriais === 0 && $tem_plano_ativo) : ?>
      <div class="dashboard-tip">
        <div class="dashboard-tip__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
          <h3>Próximo passo</h3>
          <p>Crie seu primeiro memorial para preservar a história de quem você ama. É rápido e você pode adicionar fotos, biografia e muito mais.</p>
          <a href="memoriais-criar.php">Criar meu primeiro memorial →</a>
        </div>
      </div>
      <?php elseif ($total_memoriais > 0) : ?>
      <div class="dashboard-tip">
        <div class="dashboard-tip__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <div>
          <h3>Você tem <?php echo $total_memoriais; ?> <?php echo $total_memoriais === 1 ? 'memorial' : 'memoriais'; ?></h3>
          <p>Gerencie, edite ou publique. Cada memorial pode ter fotos, áudios e vídeos conforme seu plano.</p>
          <a href="memoriais.php">Ver todos os memoriais →</a>
        </div>
      </div>
      <?php endif; ?>

      <footer class="dashboard-footer" role="contentinfo">
        <?php if ($eh_admin) : ?>
        <a href="admin/">Painel admin</a>
        <span class="sep">·</span>
        <?php endif; ?>
        <a href="index.php">← Voltar ao site</a>
        <span class="sep">·</span>
        <a href="logout.php">Sair</a>
      </footer>
    </div>
  </main>
</body>
</html>
