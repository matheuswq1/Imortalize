<?php
/**
 * Painel Admin — E-mails da newsletter
 */
$admin_page_title = 'Newsletter';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$emails = $pdo->query('SELECT id, email, created_at FROM newsletter ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
      <section class="admin-card" aria-labelledby="titulo-newsletter">
        <h2 id="titulo-newsletter">Newsletter (<?php echo count($emails); ?> inscritos)</h2>
        <div style="overflow-x: auto;">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>E-mail</th>
                <th>Data</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($emails as $e) : ?>
              <tr>
                <td><?php echo (int) $e['id']; ?></td>
                <td><?php echo htmlspecialchars($e['email']); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($e['created_at'])); ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($emails)) : ?>
              <tr><td colspan="3" style="color: #b8c4a8;">Nenhum e-mail inscrito na newsletter.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <footer style="margin-top: 1.5rem; font-size: 0.875rem; color: #b8c4a8;">
        <a href="<?php echo $site_base; ?>index.php" style="color: #b8c4a8;">← Voltar ao site</a>
      </footer>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
