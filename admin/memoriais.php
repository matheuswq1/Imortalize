<?php
/**
 * Painel Admin — Lista de memoriais
 */
$admin_page_title = 'Memoriais';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$memoriais = $pdo->query('
  SELECT m.id, m.titulo, m.slug, m.status, m.created_at, m.updated_at,
    u.id as user_id, u.nome as user_nome, u.email as user_email,
    (SELECT COUNT(*) FROM memorial_media mm WHERE mm.memorial_id = m.id AND mm.tipo = "foto") as total_fotos
  FROM memorials m
  INNER JOIN users u ON u.id = m.user_id
  ORDER BY m.updated_at DESC
')->fetchAll(PDO::FETCH_ASSOC);
?>
      <section class="admin-card" aria-labelledby="titulo-memoriais">
        <h2 id="titulo-memoriais">Memoriais (<?php echo count($memoriais); ?>)</h2>
        <div style="overflow-x: auto;">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Usuário</th>
                <th>Fotos</th>
                <th>Status</th>
                <th>Link público</th>
                <th>Atualizado</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($memoriais as $m) : ?>
              <tr>
                <td><?php echo (int) $m['id']; ?></td>
                <td><?php echo htmlspecialchars($m['titulo']); ?></td>
                <td><?php echo htmlspecialchars($m['user_nome']); ?> <span style="color: #b8c4a8; font-size: 0.8125rem;">(<?php echo htmlspecialchars($m['user_email']); ?>)</span></td>
                <td><?php echo (int) $m['total_fotos']; ?></td>
                <td><span class="admin-badge admin-badge--<?php echo $m['status']; ?>"><?php echo $m['status']; ?></span></td>
                <td>
                  <?php if ($m['status'] === 'publicado') : ?>
                  <a href="<?php echo htmlspecialchars(urlMemorialPublico($m['slug'])); ?>" target="_blank" rel="noopener" style="color: #b8c4a8; font-size: 0.8125rem;">Ver página</a>
                  <?php else : ?>
                  —
                  <?php endif; ?>
                </td>
                <td><?php echo date('d/m/Y H:i', strtotime($m['updated_at'])); ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($memoriais)) : ?>
              <tr><td colspan="7" style="color: #b8c4a8;">Nenhum memorial criado.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <footer style="margin-top: 1.5rem; font-size: 0.875rem; color: #b8c4a8;">
        <a href="<?php echo $site_base; ?>index.php" style="color: #b8c4a8;">← Voltar ao site</a>
      </footer>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
