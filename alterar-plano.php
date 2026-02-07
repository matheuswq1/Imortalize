<?php
/**
 * Alterar plano — Imortalize
 * Lista planos ativos e permite trocar de plano (nova assinatura 12 meses).
 */
session_start();
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$user_id = (int) $_SESSION['user_id'];
$nome_usuario = $_SESSION['user_nome'] ?? 'Usuário';

$plano_ativo = buscarPlanoAtivo($user_id);
$planos = listarPlanos();
$page_title = 'Alterar plano | Imortalize';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <link rel="stylesheet" href="css/site.css">
  <style>
    .alterar-plano-page {
      min-height: 100vh;
      padding: 2rem 1rem 4rem;
    }
    .alterar-plano-inner {
      max-width: 960px;
      margin: 0 auto;
    }
    .alterar-plano-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    .alterar-plano-header h1 {
      font-size: clamp(1.5rem, 4vw, 1.875rem);
      font-weight: 600;
      margin: 0 0 0.5rem;
      color: #f0f2eb;
    }
    .alterar-plano-header p {
      font-size: 1rem;
      color: #b8c4a8;
      margin: 0;
    }
    .alterar-plano-nav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 1rem;
      margin-bottom: 2rem;
    }
    .alterar-plano-nav a {
      font-size: 0.9375rem;
      color: #b8c4a8;
      text-decoration: none;
    }
    .alterar-plano-nav a:hover {
      color: #f0f2eb;
    }
    .msg-sucesso {
      background: rgba(184, 196, 168, 0.15);
      border: 1px solid rgba(184, 196, 168, 0.3);
      border-radius: 12px;
      padding: 1rem 1.25rem;
      margin-bottom: 2rem;
      color: #f0f2eb;
      font-size: 0.9375rem;
    }
    .seu-plano-ativo {
      background: linear-gradient(145deg, rgba(39, 51, 28, 0.9) 0%, rgba(45, 61, 34, 0.85) 100%);
      border: 1px solid rgba(184, 196, 168, 0.2);
      border-radius: 16px;
      padding: 1.5rem 1.75rem;
      margin-bottom: 2.5rem;
    }
    .seu-plano-ativo h2 {
      font-size: 1.125rem;
      font-weight: 600;
      margin: 0 0 0.5rem;
      color: #b8c4a8;
    }
    .seu-plano-ativo .nome {
      font-size: 1.25rem;
      color: #f0f2eb;
      margin-bottom: 0.5rem;
    }
    .seu-plano-ativo .meta {
      font-size: 0.875rem;
      color: #b8c4a8;
      margin-bottom: 0.75rem;
    }
    .section-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: #f0f2eb;
      margin: 0 0 1.25rem;
    }
    .plano-atual-badge {
      display: inline-block;
      padding: 0.25rem 0.65rem;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: #192112;
      background: #b8c4a8;
      border-radius: 20px;
      margin-bottom: 0.5rem;
    }
    .plano__preco {
      font-size: 1.125rem;
      font-weight: 600;
      color: #f0f2eb;
      margin: 0.75rem 0 0.5rem;
    }
    .plano__preco span {
      font-weight: 400;
      font-size: 0.9375rem;
      color: #b8c4a8;
    }
    .plano .plano__cta-wrap form {
      margin: 0;
    }
    .plano .plano__cta {
      cursor: pointer;
      border: none;
      font-family: inherit;
      width: 100%;
    }
    .plano--atual {
      border-color: rgba(184, 196, 168, 0.35);
      background: rgba(39, 51, 28, 0.8);
    }
    .plano--atual .plano__cta {
      background: rgba(184, 196, 168, 0.4);
      color: #f0f2eb;
      cursor: default;
    }
    .plano--atual .plano__cta:hover {
      background: rgba(184, 196, 168, 0.4);
      transform: none;
    }
  </style>
</head>
<body>
  <div class="alterar-plano-page">
    <div class="alterar-plano-inner">
      <nav class="alterar-plano-nav" aria-label="Navegação">
        <a href="dashboard.php">← Minha área</a>
        <a href="index.php">Início do site</a>
        <a href="logout.php">Sair</a>
      </nav>

      <header class="alterar-plano-header" role="banner">
        <h1>Alterar plano</h1>
        <p>Escolha outro plano ou renove. Sua assinatura terá validade de 12 meses.</p>
      </header>

      <?php if (isset($_GET['alterado']) && $_GET['alterado'] === '1') : ?>
      <div class="msg-sucesso" role="alert">
        Plano alterado com sucesso. Sua nova assinatura já está ativa.
      </div>
      <?php endif; ?>

      <?php if ($plano_ativo) : ?>
      <section class="seu-plano-ativo" aria-labelledby="plano-ativo-titulo">
        <h2 id="plano-ativo-titulo">Seu plano atual</h2>
        <p class="nome"><?php echo htmlspecialchars($plano_ativo['plano_nome']); ?></p>
        <p class="meta">
          Válido até <?php echo htmlspecialchars($plano_ativo['vencimento_formatado']); ?>
          &nbsp;·&nbsp;<?php echo htmlspecialchars($plano_ativo['validade']); ?>
        </p>
      </section>
      <?php endif; ?>

      <h2 class="section-title">Escolha um plano</h2>
      <div class="planos">
        <?php
        $plano_ativo_id = $plano_ativo ? (int) $plano_ativo['plan_id'] : 0;
        $ordem_destaque = 2; // Tributo = mais popular
        foreach ($planos as $i => $p) :
          $é_atual = ((int) $p['id']) === $plano_ativo_id;
          $é_destaque = ((int) $p['id']) === $ordem_destaque;
          $classe = 'plano';
          if ($é_destaque) $classe .= ' plano--destaque';
          if ($é_atual) $classe .= ' plano--atual';
        ?>
        <article class="<?php echo htmlspecialchars($classe); ?>">
          <?php if ($é_atual) : ?>
            <span class="plano-atual-badge">Seu plano atual</span>
          <?php elseif ($é_destaque) : ?>
            <span class="plano__badge" aria-hidden="true">Mais popular</span>
          <?php endif; ?>
          <h3 class="plano__nome"><?php echo htmlspecialchars($p['nome']); ?></h3>
          <p class="plano__destaque"><?php echo htmlspecialchars($p['descricao_curta'] ?? ''); ?></p>
          <ul class="plano__lista">
            <?php foreach ($p['beneficios'] as $item) : ?>
              <li><?php echo htmlspecialchars($item); ?></li>
            <?php endforeach; ?>
          </ul>
          <?php
            $cents = isset($p['preco_anual_cents']) ? (int) $p['preco_anual_cents'] : 0;
            if ($cents > 0) :
              $valor_ano = number_format($cents / 100, 2, ',', '.');
          ?>
          <p class="plano__preco">R$ <?php echo htmlspecialchars($valor_ano); ?> <span>/ano</span></p>
          <?php endif; ?>
          <div class="plano__cta-wrap">
            <?php if ($é_atual) : ?>
              <span class="plano__cta">Plano atual</span>
            <?php else : ?>
              <a href="checkout.php?plan_id=<?php echo (int) $p['id']; ?>" class="plano__cta">Ir para pagamento</a>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</body>
</html>
