<?php
/**
 * Painel Admin — Configurações do site (telefone, redes sociais)
 */
$admin_page_title = 'Configurações';
require_once __DIR__ . '/includes/header.php';

$site_base = dirname($_SERVER['SCRIPT_NAME'], 2) . '/';
$base_url = dirname($_SERVER['SCRIPT_NAME']) . '/';

$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $phone = isset($_POST['contact_phone']) ? trim($_POST['contact_phone']) : '';
  if ($phone === '' || mb_strlen($phone) < 10) {
    $feedback = 'Informe um telefone válido (ex.: (17) 99777-1506).';
  } elseif (mb_strlen($phone) > 20) {
    $feedback = 'Telefone muito longo.';
  } else {
    setSiteSetting('contact_phone', $phone);
    foreach (SITE_SOCIAL_KEYS as $key) {
      $val = isset($_POST[$key]) ? trim($_POST[$key]) : '';
      if ($val !== '' && !filter_var($val, FILTER_VALIDATE_URL)) {
        setSiteSetting($key, '');
        continue;
      }
      setSiteSetting($key, $val);
    }
    header('Location: ' . $base_url . 'configuracoes.php?salvo=1');
    exit;
  }
}

if (isset($_GET['salvo'])) {
  $feedback = 'Configurações salvas com sucesso.';
}

$contact_phone = getSiteSetting('contact_phone', '(17) 99777-1506');
$social_facebook  = getSiteSetting('social_facebook', '');
$social_instagram = getSiteSetting('social_instagram', '');
$social_whatsapp   = getSiteSetting('social_whatsapp', '');
$social_youtube    = getSiteSetting('social_youtube', '');
$input_style = 'width: 100%; box-sizing: border-box; padding: 0.75rem 1rem; font-size: 1rem; color: #f0f2eb; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; margin-bottom: 1rem;';
?>
      <section class="admin-card" aria-labelledby="titulo-config">
        <h2 id="titulo-config">Contato do site</h2>
        <p style="margin: 0 0 1rem; font-size: 0.9375rem; color: #b8c4a8;">
          O telefone é exibido no rodapé. Formato sugerido: <strong>(17) 99777-1506</strong>.
        </p>
        <?php if ($feedback !== '') : ?>
        <p class="admin-feedback" role="status" style="margin: 0 0 1rem; padding: 0.75rem 1rem; background: rgba(184,196,168,0.15); border: 1px solid rgba(184,196,168,0.3); border-radius: 8px; color: #b8c4a8; font-size: 0.9375rem;">
          <?php echo htmlspecialchars($feedback); ?>
        </p>
        <?php endif; ?>
        <form method="post" action="">
          <label for="contact_phone" style="display: block; font-size: 0.9375rem; color: #b8c4a8; margin-bottom: 0.5rem;">Telefone de contato</label>
          <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($contact_phone); ?>"
                 placeholder="(17) 99777-1506" maxlength="20" required style="<?php echo $input_style; ?> max-width: 360px;">
      </section>

      <section class="admin-card" aria-labelledby="titulo-social">
        <h2 id="titulo-social">Redes sociais</h2>
        <p style="margin: 0 0 1rem; font-size: 0.9375rem; color: #b8c4a8;">
          URLs das redes sociais exibidas no rodapé do site. Deixe em branco para não exibir.
        </p>
          <div style="display: grid; gap: 1rem; max-width: 480px;">
            <div>
              <label for="social_facebook" style="display: block; font-size: 0.9375rem; color: #b8c4a8; margin-bottom: 0.5rem;">Facebook</label>
              <input type="url" id="social_facebook" name="social_facebook" value="<?php echo htmlspecialchars($social_facebook); ?>"
                     placeholder="https://facebook.com/sua-pagina" style="<?php echo $input_style; ?>">
            </div>
            <div>
              <label for="social_instagram" style="display: block; font-size: 0.9375rem; color: #b8c4a8; margin-bottom: 0.5rem;">Instagram</label>
              <input type="url" id="social_instagram" name="social_instagram" value="<?php echo htmlspecialchars($social_instagram); ?>"
                     placeholder="https://instagram.com/sua-conta" style="<?php echo $input_style; ?>">
            </div>
            <div>
              <label for="social_whatsapp" style="display: block; font-size: 0.9375rem; color: #b8c4a8; margin-bottom: 0.5rem;">WhatsApp</label>
              <input type="url" id="social_whatsapp" name="social_whatsapp" value="<?php echo htmlspecialchars($social_whatsapp); ?>"
                     placeholder="https://wa.me/5517997771506" style="<?php echo $input_style; ?>">
            </div>
            <div>
              <label for="social_youtube" style="display: block; font-size: 0.9375rem; color: #b8c4a8; margin-bottom: 0.5rem;">YouTube</label>
              <input type="url" id="social_youtube" name="social_youtube" value="<?php echo htmlspecialchars($social_youtube); ?>"
                     placeholder="https://youtube.com/@seu-canal" style="<?php echo $input_style; ?>">
            </div>
          </div>
          <button type="submit" style="margin-top: 0.5rem; padding: 0.75rem 1.5rem; font-size: 0.9375rem; font-weight: 600; color: #192112; background: #b8c4a8; border: none; border-radius: 8px; cursor: pointer;">
            Salvar configurações
          </button>
        </form>
      </section>

      <footer style="margin-top: 1.5rem; font-size: 0.875rem; color: #b8c4a8;">
        <a href="<?php echo $site_base; ?>index.php" style="color: #b8c4a8;">← Voltar ao site</a>
      </footer>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
