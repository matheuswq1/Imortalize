<?php
/**
 * Painel Admin — Vincular todos os planos ao Stripe
 * Sincroniza cada plano que tem preço anual com o Stripe (Product + Price).
 */
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once dirname(__DIR__) . '/config/database.php';
$stripe_config = file_exists(dirname(__DIR__) . '/config/stripe.config.php') ? require dirname(__DIR__) . '/config/stripe.config.php' : ['mode' => 'fake'];
require_once dirname(__DIR__) . '/config/stripe-sync.php';

if (empty($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'] ?? 0, $_SESSION['user_email'] ?? null)) {
  header('Location: ' . dirname($_SERVER['SCRIPT_NAME'], 2) . '/login.php');
  exit;
}

$base_url = dirname($_SERVER['SCRIPT_NAME']) . '/';
$pdo = getDB();

$planos_para_vincular = $pdo->query('
  SELECT id, nome, slug, descricao_curta, preco_anual_cents, stripe_product_id, stripe_price_id
  FROM plans
  WHERE preco_anual_cents IS NOT NULL AND preco_anual_cents > 0
  ORDER BY ordem ASC, id ASC
')->fetchAll(PDO::FETCH_ASSOC);

$modo_fake = ($stripe_config['mode'] ?? 'fake') === 'fake';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $vinculados = 0;
  $erros = [];
  foreach ($planos_para_vincular as $p) {
    $sync = stripeSyncPlan(
      $pdo,
      (int) $p['id'],
      $p['nome'],
      $p['slug'],
      $p['descricao_curta'] ?? '',
      (int) $p['preco_anual_cents'],
      $p['stripe_product_id'] ?? null,
      $p['stripe_price_id'] ?? null,
      $stripe_config
    );
    if ($sync['ok']) {
      $stmt = $pdo->prepare('UPDATE plans SET stripe_product_id = ?, stripe_price_id = ? WHERE id = ?');
      $stmt->execute([$sync['stripe_product_id'], $sync['stripe_price_id'], $p['id']]);
      $vinculados++;
    } else {
      $erros[] = $p['nome'] . ': ' . $sync['erro'];
    }
  }
  if (!empty($erros)) {
    header('Location: ' . $base_url . 'planos.php?erro=' . urlencode(implode(' | ', array_slice($erros, 0, 3)) . (count($erros) > 3 ? '...' : '')));
  } else {
    header('Location: ' . $base_url . 'planos.php?ok=' . urlencode($vinculados . ' plano(s) vinculado(s) ao Stripe com sucesso.'));
  }
  exit;
}

$admin_page_title = 'Vincular planos ao Stripe';
require_once __DIR__ . '/includes/header.php';
?>
      <section class="admin-card" aria-labelledby="titulo-vincular">
        <h2 id="titulo-vincular" style="margin: 0 0 1rem;">Vincular todos os planos ao Stripe</h2>
        <?php if ($modo_fake) : ?>
        <p style="background: rgba(200,150,80,0.15); border: 1px solid rgba(200,150,80,0.4); border-radius: 8px; padding: 0.75rem 1rem; color: #e8c890; margin-bottom: 1rem;">
          O Stripe está em <strong>modo fake</strong>. Altere <code>config/stripe.config.php</code> para <code>mode => 'live'</code> ou <code>'test'</code> e configure as chaves para vincular de verdade.
        </p>
        <?php endif; ?>
        <?php if (empty($planos_para_vincular)) : ?>
        <p style="color: #b8c4a8;">Nenhum plano com preço anual cadastrado. Edite os planos e informe o campo <strong>Preço anual (R$)</strong> antes de vincular ao Stripe.</p>
        <p style="margin-top: 1rem;"><a href="<?php echo $base_url; ?>planos.php" style="color: #b8c4a8;">← Voltar para Planos</a></p>
        <?php else : ?>
        <p style="color: #b8c4a8; margin-bottom: 1rem;">Os planos abaixo serão sincronizados com o Stripe (Product + Price anual). Planos já vinculados serão atualizados se necessário.</p>
        <ul style="margin: 0 0 1rem; padding-left: 1.5rem; color: #f0f2eb;">
          <?php foreach ($planos_para_vincular as $p) : ?>
          <li><strong><?php echo htmlspecialchars($p['nome']); ?></strong> — R$ <?php echo number_format($p['preco_anual_cents'] / 100, 2, ',', '.'); ?> /ano</li>
          <?php endforeach; ?>
        </ul>
        <form method="post" action="">
          <button type="submit" style="padding: 0.5rem 1rem; cursor: pointer; border-radius: 8px; border: 1px solid rgba(184,196,168,0.4); background: rgba(184,196,168,0.2); color: #f0f2eb; font-size: 0.9375rem;">Vincular todos ao Stripe</button>
          <a href="<?php echo $base_url; ?>planos.php" style="padding: 0.5rem 1rem; color: #b8c4a8; text-decoration: none; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15); margin-left: 0.5rem;">Cancelar</a>
        </form>
        <?php endif; ?>
      </section>
      <p style="margin-top: 1rem;"><a href="<?php echo $base_url; ?>planos.php" style="color: #b8c4a8;">← Voltar para Planos</a></p>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
