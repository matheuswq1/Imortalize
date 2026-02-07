<?php
/**
 * Painel Admin — Métricas individuais de um QR Code (acessos e detalhes)
 */
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once dirname(__DIR__) . '/config/database.php';

if (empty($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'] ?? 0, $_SESSION['user_email'] ?? null)) {
  header('Location: ' . dirname($_SERVER['SCRIPT_NAME'], 2) . '/login.php');
  exit;
}

$base_url = dirname($_SERVER['SCRIPT_NAME']) . '/';
$site_base = dirname($_SERVER['SCRIPT_NAME'], 2) . '/';
$root_path = dirname(__DIR__);
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
  header('Location: ' . $base_url . 'qrcodes.php');
  exit;
}

$qr = function_exists('qrcodePorId') ? qrcodePorId($id) : null;
if (!$qr) {
  header('Location: ' . $base_url . 'qrcodes.php');
  exit;
}

$redirect_url = rtrim(getSiteBaseUrl(), '/') . '/redirect.php?id=' . urlencode($qr['unique_id']);
$admin_page_title = 'Acessos — QR #' . $id;
require_once __DIR__ . '/includes/header.php';
?>
      <section class="admin-card">
        <h2>Métricas do QR Code #<?php echo (int) $qr['id']; ?></h2>
        <div style="display: flex; flex-wrap: wrap; align-items: flex-start; gap: 2rem;">
          <?php if (!empty($qr['imagem_path']) && is_file($root_path . '/' . $qr['imagem_path'])) : ?>
          <div>
            <p style="margin: 0 0 0.5rem; font-size: 0.875rem; color: #b8c4a8;">Preview</p>
            <img src="<?php echo htmlspecialchars($site_base . $qr['imagem_path']); ?>" width="120" height="120" alt="QR" style="border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);">
          </div>
          <?php endif; ?>
          <div style="flex: 1; min-width: 200px;">
            <div class="admin-stat" style="display: inline-block; margin-bottom: 1rem; padding: 1rem 1.5rem;">
              <span class="admin-stat__num"><?php echo number_format((int) $qr['scans'], 0, ',', '.'); ?></span>
              <span class="admin-stat__label">Acessos (scans)</span>
            </div>
            <table class="admin-table" style="max-width: 400px;">
              <tbody>
                <tr>
                  <td style="color: #b8c4a8;">ID</td>
                  <td><?php echo (int) $qr['id']; ?></td>
                </tr>
                <tr>
                  <td style="color: #b8c4a8;">Unique ID</td>
                  <td><code style="font-size: 0.8125rem;"><?php echo htmlspecialchars($qr['unique_id']); ?></code></td>
                </tr>
                <tr>
                  <td style="color: #b8c4a8;">Status</td>
                  <td><span class="admin-badge admin-badge--<?php echo $qr['status']; ?>"><?php echo $qr['status']; ?></span></td>
                </tr>
                <tr>
                  <td style="color: #b8c4a8;">Memorial</td>
                  <td>
                    <?php if (!empty($qr['memorial_titulo'])) : ?>
                    <a href="<?php echo $site_base; ?>memorial-publico.php?slug=<?php echo urlencode($qr['memorial_slug']); ?>" style="color: #b8c4a8;"><?php echo htmlspecialchars($qr['memorial_titulo']); ?></a>
                    <?php else : ?>
                    <span style="color: #b8c4a8;">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <td style="color: #b8c4a8;">Criado em</td>
                  <td><?php echo date('d/m/Y H:i', strtotime($qr['created_at'])); ?></td>
                </tr>
              </tbody>
            </table>
            <p style="margin: 1rem 0 0; font-size: 0.875rem; color: #b8c4a8;">
              <strong>URL de redirect:</strong><br>
              <a href="<?php echo htmlspecialchars($redirect_url); ?>" target="_blank" rel="noopener" style="color: #b8c4a8; word-break: break-all;"><?php echo htmlspecialchars($redirect_url); ?></a>
            </p>
          </div>
        </div>
        <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
          <a href="<?php echo $base_url; ?>qrcodes.php" style="padding: 0.5rem 1rem; color: #b8c4a8; text-decoration: none; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15);">← Voltar para QR Codes</a>
          <a href="<?php echo $base_url; ?>qrcodes-vincular.php?id=<?php echo (int) $qr['id']; ?>" style="padding: 0.5rem 1rem; color: #b8c4a8; text-decoration: none; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15);">Vincular / Editar link</a>
          <a href="<?php echo $base_url; ?>qrcodes-layout.php?id=<?php echo (int) $qr['id']; ?>" style="padding: 0.5rem 1rem; color: #b8c4a8; text-decoration: none; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15);">Layout</a>
        </div>
      </section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
