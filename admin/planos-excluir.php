<?php
/**
 * Painel Admin — Excluir (desativar) plano e arquivar no Stripe
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
$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : 0);

if ($id <= 0) {
  header('Location: ' . $base_url . 'planos.php');
  exit;
}

$stmt = $pdo->prepare('SELECT id, nome, stripe_product_id FROM plans WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$plano = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plano) {
  header('Location: ' . $base_url . 'planos.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  stripeArchivePlanProduct($plano['stripe_product_id'] ?? null, $stripe_config);
  $stmt = $pdo->prepare('UPDATE plans SET ativo = 0 WHERE id = ?');
  $stmt->execute([$id]);
  header('Location: ' . $base_url . 'planos.php?ok=' . urlencode('Plano desativado e arquivado no Stripe.'));
  exit;
}

$admin_page_title = 'Excluir plano';
require_once __DIR__ . '/includes/header.php';
?>
      <section class="admin-card">
        <h2 id="titulo-excluir">Excluir plano</h2>
        <p style="color: #f0f2eb;">Tem certeza que deseja excluir o plano <strong><?php echo htmlspecialchars($plano['nome']); ?></strong>?</p>
        <p style="font-size: 0.875rem; color: #b8c4a8;">O plano será desativado (não aparecerá mais para novas assinaturas). Se estiver configurado, o produto será arquivado no Stripe. Assinantes atuais continuam até o vencimento.</p>
        <form method="post" action="" style="margin-top: 1rem;">
          <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
          <button type="submit" style="padding: 0.5rem 1rem; cursor: pointer; border-radius: 8px; border: none; background: rgba(200, 100, 100, 0.8); color: #fff;">Sim, excluir este plano</button>
          <a href="<?php echo $base_url; ?>planos-editar.php?id=<?php echo (int) $id; ?>" style="padding: 0.5rem 1rem; color: #b8c4a8; text-decoration: none; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15); margin-left: 0.5rem;">Cancelar</a>
        </form>
      </section>
      <p><a href="<?php echo $base_url; ?>planos.php" style="color: #b8c4a8;">← Voltar para Planos</a></p>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
