<?php
/**
 * Painel Admin — Dashboard (visão geral)
 */
$admin_page_title = 'Painel Admin';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$base_url = dirname($_SERVER['SCRIPT_NAME']) . '/';
$site_base = dirname($_SERVER['SCRIPT_NAME'], 2) . '/';

// Estatísticas gerais
$total_usuarios = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$total_memoriais = (int) $pdo->query('SELECT COUNT(*) FROM memorials')->fetchColumn();
$memoriais_publicados = (int) $pdo->query("SELECT COUNT(*) FROM memorials WHERE status = 'publicado'")->fetchColumn();
$memoriais_rascunho = (int) $pdo->query("SELECT COUNT(*) FROM memorials WHERE status = 'rascunho'")->fetchColumn();
$total_assinaturas = (int) $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'ativo' AND data_vencimento >= CURDATE()")->fetchColumn();
$total_planos = (int) $pdo->query('SELECT COUNT(*) FROM plans WHERE ativo = 1')->fetchColumn();
$total_newsletter = (int) $pdo->query('SELECT COUNT(*) FROM newsletter')->fetchColumn();

// QR Codes
$qr_stats = function_exists('qrcodesContarPorStatus') ? qrcodesContarPorStatus() : ['disponivel' => 0, 'usado' => 0];
$total_qrs = $qr_stats['disponivel'] + $qr_stats['usado'];
$total_scans = function_exists('qrcodesTotalScans') ? qrcodesTotalScans() : 0;
$top_qrs = function_exists('qrcodesTopPorScans') ? qrcodesTopPorScans(3) : [];

// Atividade recente
$usuarios_ultimos_7d = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
$memoriais_publicados_mes = (int) $pdo->query("SELECT COUNT(*) FROM memorials WHERE status = 'publicado' AND MONTH(updated_at) = MONTH(CURDATE()) AND YEAR(updated_at) = YEAR(CURDATE())")->fetchColumn();

// Últimos usuários
$ultimos_usuarios = $pdo->query('SELECT id, nome, email, created_at FROM users ORDER BY created_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
// Últimos memoriais
$ultimos_memoriais = $pdo->query('
  SELECT m.id, m.titulo, m.slug, m.status, m.created_at, u.nome as user_nome, u.email as user_email
  FROM memorials m
  INNER JOIN users u ON u.id = m.user_id
  ORDER BY m.created_at DESC
  LIMIT 8
')->fetchAll(PDO::FETCH_ASSOC);
?>
      <p style="margin: 0 0 1.5rem; font-size: 0.9375rem; color: #b8c4a8; max-width: 640px;">
        Resumo da plataforma: <strong><?php echo $total_usuarios; ?></strong> <?php echo $total_usuarios === 1 ? 'usuário' : 'usuários'; ?>,
        <strong><?php echo $memoriais_publicados; ?></strong> memoriais publicados,
        <strong><?php echo $total_assinaturas; ?></strong> assinaturas ativas,
        <strong><?php echo number_format($total_scans, 0, ',', '.'); ?></strong> acessos nos QR Codes.
        <?php if ($usuarios_ultimos_7d > 0) : ?>
        <br><span style="color: #8a9a7a;">+<?php echo $usuarios_ultimos_7d; ?> cadastro(s) nos últimos 7 dias.</span>
        <?php endif; ?>
      </p>

      <section class="admin-stats" aria-label="Resumo">
        <div class="admin-stat">
          <span class="admin-stat__num"><?php echo $total_usuarios; ?></span>
          <span class="admin-stat__label">Usuários</span>
        </div>
        <div class="admin-stat">
          <span class="admin-stat__num"><?php echo $total_memoriais; ?></span>
          <span class="admin-stat__label">Memoriais</span>
        </div>
        <div class="admin-stat">
          <span class="admin-stat__num"><?php echo $memoriais_publicados; ?></span>
          <span class="admin-stat__label">Publicados</span>
        </div>
        <div class="admin-stat">
          <span class="admin-stat__num"><?php echo $memoriais_rascunho; ?></span>
          <span class="admin-stat__label">Rascunhos</span>
        </div>
        <div class="admin-stat">
          <span class="admin-stat__num"><?php echo $total_assinaturas; ?></span>
          <span class="admin-stat__label">Assinaturas ativas</span>
        </div>
        <div class="admin-stat">
          <span class="admin-stat__num"><?php echo $total_planos; ?></span>
          <span class="admin-stat__label">Planos ativos</span>
        </div>
        <div class="admin-stat">
          <span class="admin-stat__num"><?php echo $total_newsletter; ?></span>
          <span class="admin-stat__label">Newsletter</span>
        </div>
        <div class="admin-stat">
          <span class="admin-stat__num"><?php echo number_format($total_scans, 0, ',', '.'); ?></span>
          <span class="admin-stat__label">Acessos (QR)</span>
        </div>
        <div class="admin-stat">
          <span class="admin-stat__num"><?php echo $total_qrs; ?></span>
          <span class="admin-stat__label">QR Codes</span>
        </div>
      </section>

      <section class="admin-card" aria-labelledby="acoes-rapidas" style="margin-bottom: 1.5rem;">
        <h2 id="acoes-rapidas" style="margin-bottom: 1rem;">Ações rápidas</h2>
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
          <a href="<?php echo $base_url; ?>usuarios.php" style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; color: #f0f2eb; text-decoration: none; font-size: 0.875rem;">Usuários</a>
          <a href="<?php echo $base_url; ?>memoriais.php" style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; color: #f0f2eb; text-decoration: none; font-size: 0.875rem;">Memoriais</a>
          <a href="<?php echo $base_url; ?>planos.php" style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; color: #f0f2eb; text-decoration: none; font-size: 0.875rem;">Planos</a>
          <a href="<?php echo $base_url; ?>qrcodes.php" style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; color: #f0f2eb; text-decoration: none; font-size: 0.875rem;">QR Codes</a>
          <a href="<?php echo $base_url; ?>qrcodes-gerar-estoque.php" style="padding: 0.5rem 1rem; background: rgba(184,196,168,0.12); border: 1px solid rgba(184,196,168,0.25); border-radius: 8px; color: #f0f2eb; text-decoration: none; font-size: 0.875rem;">Gerar estoque QR</a>
          <a href="<?php echo $base_url; ?>qrcodes-exportar-pdf.php" style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; color: #f0f2eb; text-decoration: none; font-size: 0.875rem;">Exportar PDF</a>
          <a href="<?php echo $base_url; ?>newsletter.php" style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; color: #f0f2eb; text-decoration: none; font-size: 0.875rem;">Newsletter</a>
        </div>
      </section>

      <section class="admin-card" aria-labelledby="graficos-dashboard" style="margin-bottom: 1.5rem;">
        <h2 id="graficos-dashboard" style="margin-bottom: 1rem;">Gráficos</h2>
        <div style="display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
          <div style="max-width: 320px;">
            <p style="margin: 0 0 0.5rem; font-size: 0.875rem; color: #b8c4a8;">Memoriais (publicados vs rascunho)</p>
            <canvas id="chart-memoriais" width="300" height="220" style="max-width: 100%; height: auto;"></canvas>
          </div>
          <div style="max-width: 400px;">
            <p style="margin: 0 0 0.5rem; font-size: 0.875rem; color: #b8c4a8;">Principais métricas</p>
            <canvas id="chart-metricas" width="360" height="220" style="max-width: 100%; height: auto;"></canvas>
          </div>
          <div style="max-width: 280px;">
            <p style="margin: 0 0 0.5rem; font-size: 0.875rem; color: #b8c4a8;">QR Codes (estoque)</p>
            <canvas id="chart-qrcodes" width="260" height="220" style="max-width: 100%; height: auto;"></canvas>
          </div>
        </div>
      </section>

      <div style="display: grid; gap: 1.5rem; grid-template-columns: 1fr; max-width: 100%;">
      <div style="display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
        <section class="admin-card admin-dashboard-card" aria-labelledby="ultimos-usuarios">
          <h2 id="ultimos-usuarios">Últimos usuários</h2>
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Nome</th>
                  <th>E-mail</th>
                  <th>Cadastro</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($ultimos_usuarios as $u) : ?>
                <tr>
                  <td><?php echo htmlspecialchars($u['nome']); ?></td>
                  <td><?php echo htmlspecialchars($u['email']); ?></td>
                  <td><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($ultimos_usuarios)) : ?>
                <tr><td colspan="3" style="color: #b8c4a8;">Nenhum usuário cadastrado.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <p style="margin: 1rem 0 0; font-size: 0.875rem;"><a href="usuarios.php" style="color: #b8c4a8;">Ver todos os usuários →</a></p>
        </section>

        <section class="admin-card admin-dashboard-card" aria-labelledby="ultimos-memoriais">
          <h2 id="ultimos-memoriais">Últimos memoriais</h2>
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Título</th>
                  <th>Usuário</th>
                  <th>Status</th>
                  <th>Data</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($ultimos_memoriais as $m) : ?>
                <tr>
                  <td><?php echo htmlspecialchars($m['titulo']); ?></td>
                  <td><?php echo htmlspecialchars($m['user_nome']); ?></td>
                  <td><span class="admin-badge admin-badge--<?php echo $m['status']; ?>"><?php echo $m['status']; ?></span></td>
                  <td><?php echo date('d/m/Y', strtotime($m['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($ultimos_memoriais)) : ?>
                <tr><td colspan="4" style="color: #b8c4a8;">Nenhum memorial criado.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <p style="margin: 1rem 0 0; font-size: 0.875rem;"><a href="memoriais.php" style="color: #b8c4a8;">Ver todos os memoriais →</a></p>
        </section>

        <section class="admin-card" aria-labelledby="qr-resumo">
          <h2 id="qr-resumo">QR Codes — resumo</h2>
          <p style="margin: 0 0 1rem; font-size: 0.875rem; color: #b8c4a8;">
            <strong><?php echo number_format($total_scans, 0, ',', '.'); ?></strong> acessos no total &middot;
            <strong><?php echo $total_qrs; ?></strong> QRs (<?php echo $qr_stats['disponivel']; ?> disponíveis, <?php echo $qr_stats['usado']; ?> vinculados)
          </p>
          <?php if (!empty($top_qrs)) : ?>
          <p style="margin: 0 0 0.5rem; font-size: 0.8125rem; color: #b8c4a8;">Mais acessados:</p>
          <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.8125rem; color: #b8c4a8;">
            <?php foreach ($top_qrs as $t) : ?>
            <li>
              <a href="<?php echo $base_url; ?>qrcodes-metricas.php?id=<?php echo (int) $t['id']; ?>" style="color: #b8c4a8;">QR #<?php echo (int) $t['id']; ?></a>
              — <?php echo (int) $t['scans']; ?> <?php echo (int) $t['scans'] === 1 ? 'acesso' : 'acessos'; ?>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
          <p style="margin: 1rem 0 0; font-size: 0.875rem;">
            <a href="<?php echo $base_url; ?>qrcodes.php" style="color: #b8c4a8;">Ver QR Codes →</a>
            &middot;
            <a href="<?php echo $base_url; ?>qrcodes-gerar-estoque.php" style="color: #b8c4a8;">Gerar estoque</a>
          </p>
        </section>
      </div>
      </div>

      <footer style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.08); font-size: 0.875rem; color: #b8c4a8;">
        <a href="<?php echo $site_base; ?>index.php" style="color: #b8c4a8;">← Voltar ao site</a>
      </footer>
      <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
      <script>
      (function() {
        var corTexto = '#b8c4a8';
        var corBorda = 'rgba(255,255,255,0.08)';
        Chart.defaults.color = corTexto;
        Chart.defaults.borderColor = corBorda;
        Chart.defaults.font.size = 12;

        var dadosMemoriais = {
          labels: ['Publicados', 'Rascunhos'],
          datasets: [{
            data: [<?php echo (int) $memoriais_publicados; ?>, <?php echo (int) $memoriais_rascunho; ?>],
            backgroundColor: ['rgba(184,196,168,0.7)', 'rgba(255,255,255,0.2)'],
            borderColor: ['rgba(184,196,168,0.9)', 'rgba(255,255,255,0.3)'],
            borderWidth: 1,
            hoverOffset: 4
          }]
        };
        var ctxMemoriais = document.getElementById('chart-memoriais');
        if (ctxMemoriais && (<?php echo (int) $memoriais_publicados; ?> + <?php echo (int) $memoriais_rascunho; ?> > 0)) {
          new Chart(ctxMemoriais, {
            type: 'doughnut',
            data: dadosMemoriais,
            options: {
              responsive: true,
              maintainAspectRatio: true,
              plugins: { legend: { position: 'bottom' } },
              cutout: '55%'
            }
          });
        }

        var dadosMetricas = {
          labels: ['Usuários', 'Memoriais', 'Assinaturas', 'Acessos QR', 'Newsletter'],
          datasets: [{
            label: 'Quantidade',
            data: [<?php echo (int) $total_usuarios; ?>, <?php echo (int) $total_memoriais; ?>, <?php echo (int) $total_assinaturas; ?>, <?php echo (int) min($total_scans, 999999); ?>, <?php echo (int) $total_newsletter; ?>],
            backgroundColor: ['rgba(184,196,168,0.5)', 'rgba(184,196,168,0.4)', 'rgba(184,196,168,0.6)', 'rgba(184,196,168,0.35)', 'rgba(255,255,255,0.15)'],
            borderColor: ['rgba(184,196,168,0.8)', 'rgba(184,196,168,0.7)', 'rgba(184,196,168,0.9)', 'rgba(184,196,168,0.6)', 'rgba(255,255,255,0.3)'],
            borderWidth: 1,
            borderRadius: 6
          }]
        };
        var ctxMetricas = document.getElementById('chart-metricas');
        if (ctxMetricas) {
          new Chart(ctxMetricas, {
            type: 'bar',
            data: dadosMetricas,
            options: {
              responsive: true,
              maintainAspectRatio: true,
              indexAxis: 'y',
              plugins: { legend: { display: false } },
              scales: {
                x: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.04)' } },
                y: { grid: { display: false } }
              }
            }
          });
        }

        var dadosQR = {
          labels: ['Disponíveis', 'Vinculados'],
          datasets: [{
            data: [<?php echo (int) $qr_stats['disponivel']; ?>, <?php echo (int) $qr_stats['usado']; ?>],
            backgroundColor: ['rgba(184,196,168,0.5)', 'rgba(255,255,255,0.25)'],
            borderColor: ['rgba(184,196,168,0.8)', 'rgba(255,255,255,0.4)'],
            borderWidth: 1,
            hoverOffset: 4
          }]
        };
        var ctxQR = document.getElementById('chart-qrcodes');
        if (ctxQR && (<?php echo (int) $qr_stats['disponivel']; ?> + <?php echo (int) $qr_stats['usado']; ?> > 0)) {
          new Chart(ctxQR, {
            type: 'doughnut',
            data: dadosQR,
            options: {
              responsive: true,
              maintainAspectRatio: true,
              plugins: { legend: { position: 'bottom' } },
              cutout: '55%'
            }
          });
        }
      })();
      </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
