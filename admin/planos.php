<?php
/**
 * Painel Admin — Planos e benefícios
 */
$admin_page_title = 'Planos';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$planos = $pdo->query('
  SELECT p.id, p.nome, p.slug, p.descricao_curta, p.max_fotos, p.tem_audio, p.tem_video, p.ordem, p.ativo, p.preco_anual_cents, p.stripe_product_id, p.stripe_price_id,
    (SELECT COUNT(*) FROM subscriptions s WHERE s.plan_id = p.id AND s.status = "ativo" AND s.data_vencimento >= CURDATE()) as assinantes_ativos
  FROM plans p
  ORDER BY p.ordem ASC, p.id ASC
')->fetchAll(PDO::FETCH_ASSOC);

$beneficios_por_plano = [];
if (!empty($planos)) {
  $ids = array_column($planos, 'id');
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $pdo->prepare("SELECT plan_id, descricao, ordem FROM plan_benefits WHERE plan_id IN ($placeholders) ORDER BY plan_id, ordem");
  $stmt->execute($ids);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $beneficios_por_plano[$row['plan_id']][] = $row['descricao'];
  }
}
?>
      <?php if (isset($_GET['ok']) && $_GET['ok'] !== '') : ?>
      <section class="admin-card" style="border-color: rgba(184,196,168,0.3);">
        <p style="margin: 0; color: #b8c4a8;"><?php echo htmlspecialchars($_GET['ok']); ?></p>
      </section>
      <?php endif; ?>
      <?php if (isset($_GET['erro']) && $_GET['erro'] !== '') : ?>
      <section class="admin-card" style="border-color: rgba(200,100,100,0.4);">
        <p style="margin: 0; color: #e8a0a0;"><?php echo htmlspecialchars($_GET['erro']); ?></p>
      </section>
      <?php endif; ?>
      <section class="admin-card" aria-labelledby="titulo-planos">
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 1rem;">
          <h2 id="titulo-planos" style="margin: 0;">Planos (<?php echo count($planos); ?>)</h2>
          <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
            <a href="<?php echo $base_url; ?>planos-vincular-stripe.php" style="padding: 0.5rem 1rem; background: rgba(99,91,255,0.2); border: 1px solid rgba(99,91,255,0.5); border-radius: 8px; color: #b8b4ff; text-decoration: none; font-size: 0.875rem;">Vincular todos ao Stripe</a>
            <a href="<?php echo $base_url; ?>planos-editar.php" style="padding: 0.5rem 1rem; background: rgba(184,196,168,0.15); border: 1px solid rgba(184,196,168,0.4); border-radius: 8px; color: #f0f2eb; text-decoration: none; font-size: 0.875rem;">+ Novo plano</a>
          </div>
        </div>
        <div style="display: grid; gap: 1rem;">
          <?php foreach ($planos as $p) : ?>
          <div style="background: rgba(0,0,0,0.15); border-radius: 10px; padding: 1rem 1.25rem; border: 1px solid rgba(255,255,255,0.06);">
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.5rem;">
              <strong style="color: #f0f2eb;"><?php echo htmlspecialchars($p['nome']); ?></strong>
              <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                <span class="admin-badge admin-badge--<?php echo $p['ativo'] ? 'ativo' : 'rascunho'; ?>"><?php echo $p['ativo'] ? 'Ativo' : 'Inativo'; ?></span>
                <?php if (!empty($p['stripe_price_id'])) : ?>
                <span class="admin-badge" style="background: rgba(99,91,255,0.25); color: #b8b4ff;">Vinculado Stripe</span>
                <?php else : ?>
                <span class="admin-badge" style="background: rgba(200,150,80,0.25); color: #e8c890;">Não vinculado</span>
                <?php endif; ?>
                <span style="font-size: 0.8125rem; color: #b8c4a8;"><?php echo (int) $p['assinantes_ativos']; ?> assinante(s) ativo(s)</span>
                <a href="<?php echo $base_url; ?>planos-editar.php?id=<?php echo (int) $p['id']; ?>" style="font-size: 0.875rem; color: #b8c4a8;">Editar</a>
              </div>
            </div>
            <p style="font-size: 0.875rem; color: #b8c4a8; margin: 0 0 0.5rem;"><?php echo htmlspecialchars($p['descricao_curta'] ?? ''); ?></p>
            <p style="font-size: 0.8125rem; color: #b8c4a8; margin: 0;">
              Fotos: <?php echo (int) $p['max_fotos']; ?> ·
              Áudio: <?php echo $p['tem_audio'] ? 'Sim' : 'Não'; ?> ·
              Vídeo: <?php echo $p['tem_video'] ? 'Sim' : 'Não'; ?>
              <?php if (!empty($p['preco_anual_cents'])) : ?>
              · <strong style="color: #f0f2eb;">R$ <?php echo number_format($p['preco_anual_cents'] / 100, 2, ',', '.'); ?></strong> /ano
              <?php endif; ?>
            </p>
            <?php if (!empty($beneficios_por_plano[$p['id']])) : ?>
            <ul style="margin: 0.5rem 0 0; padding-left: 1.25rem; font-size: 0.8125rem; color: #b8c4a8;">
              <?php foreach ($beneficios_por_plano[$p['id']] as $b) : ?>
              <li><?php echo htmlspecialchars($b); ?></li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if (empty($planos)) : ?>
        <p style="color: #b8c4a8;">Nenhum plano cadastrado.</p>
        <?php endif; ?>
      </section>

      <footer style="margin-top: 1.5rem; font-size: 0.875rem; color: #b8c4a8;">
        <a href="<?php echo $site_base; ?>index.php" style="color: #b8c4a8;">← Voltar ao site</a>
      </footer>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
