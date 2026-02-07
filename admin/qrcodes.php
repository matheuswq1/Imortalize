<?php
/**
 * Painel Admin — Gerenciador de QR Codes
 * Filtro: todos / disponíveis / vinculados. Paginação por página.
 */
$admin_page_title = 'QR Codes';
require_once __DIR__ . '/includes/header.php';

$per_page = 20;
$filtro = isset($_GET['filtro']) && in_array($_GET['filtro'], ['todos', 'disponivel', 'usado'], true) ? $_GET['filtro'] : 'todos';
$pagina = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
$busca_id = isset($_GET['busca_id']) && (int) $_GET['busca_id'] > 0 ? (int) $_GET['busca_id'] : null;

$total = qrcodesContarAdmin($filtro, $busca_id);
$total_paginas = $total > 0 ? (int) ceil($total / $per_page) : 1;
$pagina = min($pagina, max(1, $total_paginas));
$offset = ($pagina - 1) * $per_page;

$qrcodes = qrcodesListarAdminFiltrado($filtro, $per_page, $offset, $busca_id);
$stats = qrcodesContarPorStatus();
$total_scans = function_exists('qrcodesTotalScans') ? qrcodesTotalScans() : 0;
$top_scans = function_exists('qrcodesTopPorScans') ? qrcodesTopPorScans(5) : [];
// $site_base já definido no header (getSiteBaseUrl()) — usado nas miniaturas e links do site
$ok = isset($_GET['ok']) ? trim($_GET['ok']) : '';

$base_query = $base_url . 'qrcodes.php?filtro=' . urlencode($filtro);
if ($busca_id !== null) {
  $base_query .= '&busca_id=' . $busca_id;
}
$num_inicio = $total > 0 ? $offset + 1 : 0;
?>
      <?php if ($ok !== '') : ?>
      <section class="admin-card" style="border-color: rgba(184,196,168,0.3);">
        <p style="margin: 0; color: #b8c4a8;"><?php echo htmlspecialchars($ok); ?></p>
      </section>
      <?php endif; ?>
      <section class="admin-card">
        <h2>Resumo</h2>
        <div class="admin-stats">
          <div class="admin-stat">
            <span class="admin-stat__num"><?php echo $stats['disponivel']; ?></span>
            <span class="admin-stat__label">Disponíveis (estoque)</span>
          </div>
          <div class="admin-stat">
            <span class="admin-stat__num"><?php echo $stats['usado']; ?></span>
            <span class="admin-stat__label">Vinculados a memoriais</span>
          </div>
          <div class="admin-stat">
            <span class="admin-stat__num"><?php echo $stats['disponivel'] + $stats['usado']; ?></span>
            <span class="admin-stat__label">Total de QRs</span>
          </div>
          <div class="admin-stat">
            <span class="admin-stat__num"><?php echo number_format($total_scans, 0, ',', '.'); ?></span>
            <span class="admin-stat__label">Total de acessos</span>
          </div>
        </div>
        <?php if (!empty($top_scans)) : ?>
        <p style="margin: 0.75rem 0 0.5rem; font-size: 0.875rem; color: #b8c4a8;">QRs mais acessados:</p>
        <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.8125rem; color: #b8c4a8;">
          <?php foreach ($top_scans as $t) : ?>
          <li>
            <a href="<?php echo $base_url; ?>qrcodes-metricas.php?id=<?php echo (int) $t['id']; ?>" style="color: #b8c4a8;">ID <?php echo (int) $t['id']; ?></a>
            — <?php echo (int) $t['scans']; ?> <?php echo (int) $t['scans'] === 1 ? 'acesso' : 'acessos'; ?>
            <?php if (!empty($t['memorial_titulo'])) : ?>(<?php echo htmlspecialchars($t['memorial_titulo']); ?>)<?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <p style="margin: 0; font-size: 0.875rem; color: #b8c4a8;">
          <a href="<?php echo $base_url; ?>qrcodes-gerar-estoque.php" style="color: #b8c4a8;">Gerar estoque de QR Codes</a> &middot;
          <a href="<?php echo $base_url; ?>qrcodes-exportar-pdf.php" style="color: #b8c4a8;">Exportar estoque para PDF</a> &middot;
          <a href="<?php echo $base_url; ?>qrcodes-regenerar-url.php" style="color: #b8c4a8;">Regenerar URL nas imagens</a>
        </p>
      </section>

      <section class="admin-card" aria-labelledby="titulo-qrcodes">
        <h2 id="titulo-qrcodes">QR Codes</h2>
        <p style="margin: 0 0 0.5rem; font-size: 0.875rem; color: #b8c4a8;">
          Filtrar:
          <a href="<?php echo $base_url; ?>qrcodes.php?filtro=todos<?php if ($busca_id !== null) echo '&busca_id=' . $busca_id; ?>" style="color: <?php echo $filtro === 'todos' ? '#f0f2eb' : '#b8c4a8'; ?>;">Todos</a>
          &middot;
          <a href="<?php echo $base_url; ?>qrcodes.php?filtro=disponivel<?php if ($busca_id !== null) echo '&busca_id=' . $busca_id; ?>" style="color: <?php echo $filtro === 'disponivel' ? '#f0f2eb' : '#b8c4a8'; ?>;">Disponíveis</a>
          &middot;
          <a href="<?php echo $base_url; ?>qrcodes.php?filtro=usado<?php if ($busca_id !== null) echo '&busca_id=' . $busca_id; ?>" style="color: <?php echo $filtro === 'usado' ? '#f0f2eb' : '#b8c4a8'; ?>;">Vinculados</a>
        </p>
        <form method="get" action="<?php echo $base_url; ?>qrcodes.php" style="margin-bottom: 1rem; display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
          <input type="hidden" name="filtro" value="<?php echo htmlspecialchars($filtro); ?>">
          <label for="busca_id" style="font-size: 0.875rem; color: #b8c4a8;">Buscar por ID:</label>
          <input type="number" name="busca_id" id="busca_id" value="<?php echo $busca_id !== null ? (int) $busca_id : ''; ?>" min="1" placeholder="Ex.: 5" style="width: 6rem; padding: 0.35rem 0.5rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #f0f2eb;">
          <button type="submit" style="padding: 0.35rem 0.75rem; font-size: 0.875rem; cursor: pointer; border-radius: 6px; border: 1px solid rgba(184,196,168,0.4); background: rgba(184,196,168,0.15); color: #f0f2eb;">Buscar</button>
          <?php if ($busca_id !== null) : ?>
          <a href="<?php echo $base_url; ?>qrcodes.php?filtro=<?php echo urlencode($filtro); ?>" style="font-size: 0.875rem; color: #b8c4a8;">Limpar busca</a>
          <?php endif; ?>
        </form>
        <?php if ($busca_id !== null && empty($qrcodes)) : ?>
        <p style="margin: 0 0 1rem; color: #c88;">Nenhum QR Code encontrado com ID <?php echo (int) $busca_id; ?>.</p>
        <?php endif; ?>
        <div style="overflow-x: auto;">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Nº</th>
                <th>ID</th>
                <th>Preview</th>
                <th>Unique ID</th>
                <th>Memorial</th>
                <th>Status</th>
                <th>Scans</th>
                <th>Criado</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($qrcodes as $i => $qr) : $numero = $num_inicio + $i; ?>
              <tr>
                <td><?php echo $numero; ?></td>
                <td><?php echo (int) $qr['id']; ?></td>
                <td>
                  <?php if (!empty($qr['imagem_path'])) : ?>
                  <img src="<?php echo htmlspecialchars($site_base . $qr['imagem_path']); ?>" width="56" height="56" alt="QR" style="vertical-align: middle;">
                  <?php else : ?>
                  <span style="color: #b8c4a8; font-size: 0.75rem;">—</span>
                  <?php endif; ?>
                </td>
                <td><code style="font-size: 0.75rem;"><?php echo htmlspecialchars(substr($qr['unique_id'], 0, 13)); ?>…</code></td>
                <td>
                  <?php if (!empty($qr['memorial_titulo'])) : ?>
                  <a href="<?php echo $site_base; ?>memorial.php?id=<?php echo (int) $qr['memorial_id']; ?>" style="color: #b8c4a8;"><?php echo htmlspecialchars($qr['memorial_titulo']); ?></a>
                  <?php else : ?>
                  <span style="color: #b8c4a8;">—</span>
                  <?php endif; ?>
                </td>
                <td><span class="admin-badge admin-badge--<?php echo $qr['status']; ?>"><?php echo $qr['status']; ?></span></td>
                <td><?php echo (int) $qr['scans']; ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($qr['created_at'])); ?></td>
                <td>
                  <a href="<?php echo $base_url; ?>qrcodes-metricas.php?id=<?php echo (int) $qr['id']; ?>" style="font-size: 0.8125rem; color: #b8c4a8;">Acessos</a>
                  &middot;
                  <a href="<?php echo $base_url; ?>qrcodes-vincular.php?id=<?php echo (int) $qr['id']; ?>" style="font-size: 0.8125rem; color: #b8c4a8;"><?php echo $qr['status'] === 'usado' ? 'Editar link' : 'Vincular'; ?></a>
                  &middot;
                  <a href="<?php echo $base_url; ?>qrcodes-layout.php?id=<?php echo (int) $qr['id']; ?>" style="font-size: 0.8125rem; color: #b8c4a8;">Layout</a>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($qrcodes)) : ?>
              <tr><td colspan="9" style="color: #b8c4a8;">Nenhum QR Code nesta lista. Gere um estoque ou publique um memorial.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($total_paginas > 1) : ?>
        <nav class="admin-pagination" aria-label="Páginas de QR Codes" style="margin-top: 1rem; display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
          <span style="font-size: 0.875rem; color: #b8c4a8;">Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?></span>
          <?php if ($pagina > 1) : ?>
          <a href="<?php echo htmlspecialchars($base_query . '&pagina=' . ($pagina - 1)); ?>" style="padding: 0.35rem 0.75rem; font-size: 0.875rem; color: #b8c4a8; text-decoration: none; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px;">← Anterior</a>
          <?php endif; ?>
          <?php
          $inicio = max(1, $pagina - 2);
          $fim = min($total_paginas, $pagina + 2);
          for ($p = $inicio; $p <= $fim; $p++) :
            if ($p === $pagina) : ?>
          <span style="padding: 0.35rem 0.75rem; font-size: 0.875rem; color: #f0f2eb; background: rgba(184,196,168,0.2); border-radius: 6px;"><?php echo $p; ?></span>
          <?php else : ?>
          <a href="<?php echo htmlspecialchars($base_query . '&pagina=' . $p); ?>" style="padding: 0.35rem 0.75rem; font-size: 0.875rem; color: #b8c4a8; text-decoration: none; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px;"><?php echo $p; ?></a>
          <?php endif;
          endfor; ?>
          <?php if ($pagina < $total_paginas) : ?>
          <a href="<?php echo htmlspecialchars($base_query . '&pagina=' . ($pagina + 1)); ?>" style="padding: 0.35rem 0.75rem; font-size: 0.875rem; color: #b8c4a8; text-decoration: none; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px;">Próxima →</a>
          <?php endif; ?>
        </nav>
        <?php endif; ?>
      </section>

      <footer style="margin-top: 1.5rem; font-size: 0.875rem; color: #b8c4a8;">
        <a href="<?php echo $site_base; ?>index.php" style="color: #b8c4a8;">← Voltar ao site</a>
      </footer>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
