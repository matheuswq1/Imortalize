<?php
/**
 * Painel Admin — Gerar estoque de QR Codes
 * Permite escolher o layout ao criar: padrão, copiar de um QR existente ou definir o seu (logo, circular, etc.).
 * Não altera QRs já existentes.
 */
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/qrcode-gerar.php';

if (empty($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'] ?? 0, $_SESSION['user_email'] ?? null)) {
  header('Location: ' . dirname($_SERVER['SCRIPT_NAME'], 2) . '/login.php');
  exit;
}

$base_url = dirname($_SERVER['SCRIPT_NAME']) . '/';
$site_base = dirname($_SERVER['SCRIPT_NAME'], 2) . '/';
$root_path = dirname(__DIR__);
$dir_qr = $root_path . DIRECTORY_SEPARATOR . 'qrcodes';
$logo_global_path = $dir_qr . DIRECTORY_SEPARATOR . 'logo.png';

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $quantidade = isset($_POST['quantidade']) ? (int) $_POST['quantidade'] : 0;
  $quantidade = max(1, min(500, $quantidade));

  $layout_tipo = isset($_POST['layout_tipo']) && in_array($_POST['layout_tipo'], ['padrao', 'copiar', 'definir'], true) ? $_POST['layout_tipo'] : 'padrao';
  $layout_style = 'padrao';
  $logo_path = null;

  if ($layout_tipo === 'copiar') {
    $copiar_id = isset($_POST['layout_copiar_id']) ? (int) $_POST['layout_copiar_id'] : 0;
    if ($copiar_id > 0) {
      $qr_ref = qrcodePorId($copiar_id);
      if ($qr_ref && isset($qr_ref['layout_style'])) {
        $layout_style = in_array($qr_ref['layout_style'], ['padrao', 'circular', 'com_logo'], true) ? $qr_ref['layout_style'] : 'padrao';
      }
    }
  } elseif ($layout_tipo === 'definir') {
    $layout_style = isset($_POST['layout_style']) && in_array($_POST['layout_style'], ['padrao', 'circular', 'com_logo'], true) ? $_POST['layout_style'] : 'padrao';
  }

  if ($layout_style === 'com_logo') {
    if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
      $tmp = $_FILES['logo']['tmp_name'];
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime = finfo_file($finfo, $tmp);
      finfo_close($finfo);
      if (in_array($mime, ['image/png', 'image/jpeg', 'image/jpg'], true)) {
        if (!is_dir($dir_qr)) {
          @mkdir($dir_qr, 0755, true);
        }
        if (is_writable($dir_qr)) {
          if ($mime === 'image/png') {
            move_uploaded_file($tmp, $logo_global_path);
          } else {
            $img = @imagecreatefromjpeg($tmp);
            if ($img) {
              imagepng($img, $logo_global_path);
              imagedestroy($img);
            }
          }
        }
      }
    }
    if (is_file($logo_global_path)) {
      $logo_path = $logo_global_path;
    }
  }

  if (!extension_loaded('gd')) {
    $erro = 'A extensão GD do PHP não está ativa. Ative no php.ini e reinicie o Apache.';
  } elseif (!is_dir($dir_qr)) {
    @mkdir($dir_qr, 0755, true);
  }
  if ($erro === '' && (!is_dir($dir_qr) || !is_writable($dir_qr))) {
    $erro = 'Pasta qrcodes não existe ou não tem permissão de escrita.';
  }

  if ($erro === '') {
    $autoload = $root_path . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
    if (!is_file($autoload)) {
      $erro = 'Execute: composer require chillerlan/php-qrcode';
    } else {
      $gerados = 0;
      // URL que será gravada no QR: usar sempre a base do site (site.config.php) para novos QRs
      $base_redirect = rtrim(getSiteBaseUrl(), '/') . '/redirect.php?id=';
      $dir_qr_abs = realpath($dir_qr) ?: $dir_qr;
      $opcoes = ['layout_style' => $layout_style];
      if ($logo_path) {
        $opcoes['logo_path'] = $logo_path;
      }
      for ($i = 0; $i < $quantidade; $i++) {
        $unique_id = qrcodeGerarUniqueId();
        $redirect_url = $base_redirect . $unique_id;
        $filename = 'qr_' . preg_replace('/[^a-zA-Z0-9\-]/', '', $unique_id) . '.png';
        $path_abs = $dir_qr_abs . DIRECTORY_SEPARATOR . $filename;
        $path_rel = 'qrcodes/' . $filename;

        if (function_exists('gerarQrCodePng') && gerarQrCodePng($redirect_url, $path_abs, $opcoes)) {
          qrcodeInserir($unique_id, $path_rel, null, null, 'disponivel', $layout_style);
          $gerados++;
        }
      }
      $mensagem = $gerados === $quantidade
        ? "Estoque de {$gerados} QR codes gerado com sucesso (layout: {$layout_style})."
        : "Gerados {$gerados} de {$quantidade}.";
      if ($gerados < $quantidade && function_exists('getLastQrCodeError') && getLastQrCodeError() !== null) {
        $mensagem .= ' Motivo: ' . getLastQrCodeError();
      }
      header('Location: ' . $base_url . 'qrcodes.php?ok=' . urlencode($mensagem));
      exit;
    }
  }
}

$qrs_para_copiar = function_exists('qrcodesListarParaCopiarLayout') ? qrcodesListarParaCopiarLayout(80) : [];
$tem_logo_global = is_file($logo_global_path);

$admin_page_title = 'Gerar estoque de QR Codes';
require_once __DIR__ . '/includes/header.php';
?>
      <section class="admin-card">
        <h2>Gerar estoque de QR Codes</h2>
        <?php if ($erro) : ?>
        <p style="color: #c88;"><?php echo htmlspecialchars($erro); ?></p>
        <?php endif; ?>
        <p style="color: #b8c4a8; margin-bottom: 1rem;">Os novos QRs terão o layout que você escolher abaixo. <strong>QRs já existentes não são alterados.</strong></p>

        <form method="post" action="" enctype="multipart/form-data" style="max-width: 560px;">
          <div style="margin-bottom: 1.25rem;">
            <label for="quantidade" style="display: block; margin-bottom: 0.35rem; color: #b8c4a8;">Quantidade (1 a 500)</label>
            <input type="number" name="quantidade" id="quantidade" value="100" min="1" max="500" style="padding: 0.5rem; width: 120px;">
          </div>

          <div style="margin-bottom: 1.25rem;">
            <span style="display: block; margin-bottom: 0.5rem; color: #f0f2eb; font-weight: 600;">Layout para os novos QRs</span>
            <label style="display: block; margin-bottom: 0.5rem;">
              <input type="radio" name="layout_tipo" value="padrao" checked>
              <strong>Padrão</strong> — módulos quadrados (clássico)
            </label>
            <label style="display: block; margin-bottom: 0.5rem;">
              <input type="radio" name="layout_tipo" value="copiar">
              <strong>Copiar layout de um QR existente</strong>
            </label>
            <div id="copiar-layout-wrap" style="margin-left: 1.5rem; margin-top: 0.35rem; display: none;">
              <select name="layout_copiar_id" id="layout_copiar_id" style="padding: 0.4rem; min-width: 200px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; color: #f0f2eb;">
                <option value="0">— Escolha um QR —</option>
                <?php foreach ($qrs_para_copiar as $q) : $estilo = isset($q['layout_style']) ? $q['layout_style'] : 'padrao'; ?>
                <option value="<?php echo (int) $q['id']; ?>">QR #<?php echo (int) $q['id']; ?> — <?php echo $estilo === 'padrao' ? 'Padrão' : ($estilo === 'circular' ? 'Circular' : 'Com logo'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <label style="display: block; margin-bottom: 0.5rem;">
              <input type="radio" name="layout_tipo" value="definir">
              <strong>Definir meu layout</strong> — escolher estilo (e opcionalmente logo)
            </label>
            <div id="definir-layout-wrap" style="margin-left: 1.5rem; margin-top: 0.5rem; display: none;">
              <div style="margin-bottom: 0.5rem;">
                <label style="margin-right: 0.75rem;"><input type="radio" name="layout_style" value="padrao" checked> Padrão</label>
                <label style="margin-right: 0.75rem;"><input type="radio" name="layout_style" value="circular"> Circular</label>
                <label><input type="radio" name="layout_style" value="com_logo"> Com logo</label>
              </div>
              <div id="qr-preview-box" style="margin-top: 1rem; padding: 1rem; background: rgba(0,0,0,0.2); border-radius: 8px; display: inline-block;">
                <p style="margin: 0 0 0.5rem; font-size: 0.875rem; color: #b8c4a8;">Preview</p>
                <img id="qr-preview-img" src="" alt="Preview do layout" width="160" height="160" style="display: none; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;">
                <span id="qr-preview-loading" style="display: none; font-size: 0.875rem; color: #b8c4a8;">Carregando…</span>
              </div>
              <div id="logo-upload-wrap" style="display: none; margin-top: 0.5rem;">
                <label for="logo" style="font-size: 0.875rem; color: #b8c4a8;">Logo central (PNG ou JPG)</label>
                <?php if ($tem_logo_global) : ?>
                <p style="font-size: 0.75rem; color: #b8c4a8; margin: 0.25rem 0;">Já existe um logo. Envie outro para substituir.</p>
                <?php endif; ?>
                <input type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/jpg" style="font-size: 0.875rem;">
              </div>
            </div>
          </div>

          <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            <button type="submit" style="padding: 0.5rem 1rem; cursor: pointer; border-radius: 8px; border: 1px solid rgba(184,196,168,0.4); background: rgba(184,196,168,0.15); color: #f0f2eb;">Gerar</button>
            <a href="<?php echo $base_url; ?>qrcodes.php" style="padding: 0.5rem 1rem; color: #b8c4a8; text-decoration: none; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15);">Cancelar</a>
          </div>
        </form>
      </section>
      <script>
      (function() {
        var baseUrl = '<?php echo addslashes($base_url); ?>';
        var tipoPadrao = document.querySelector('input[name="layout_tipo"][value="padrao"]');
        var tipoCopiar = document.querySelector('input[name="layout_tipo"][value="copiar"]');
        var tipoDefinir = document.querySelector('input[name="layout_tipo"][value="definir"]');
        var copiarWrap = document.getElementById('copiar-layout-wrap');
        var definirWrap = document.getElementById('definir-layout-wrap');
        var logoWrap = document.getElementById('logo-upload-wrap');
        var styleComLogo = document.querySelector('input[name="layout_style"][value="com_logo"]');
        var previewBox = document.getElementById('qr-preview-box');
        var previewImg = document.getElementById('qr-preview-img');
        var previewLoading = document.getElementById('qr-preview-loading');
        var logoInput = document.getElementById('logo');

        function estiloSelecionado() {
          var r = document.querySelector('input[name="layout_style"]:checked');
          return r ? r.value : 'padrao';
        }

        function atualizarPreview() {
          if (!tipoDefinir || !tipoDefinir.checked || !previewBox || !previewImg) return;
          var style = estiloSelecionado();
          var file = logoInput && logoInput.files && logoInput.files[0];
          previewBox.style.display = 'inline-block';
          previewImg.style.display = 'none';
          if (previewImg.src && previewImg.src.indexOf('blob:') === 0) {
            URL.revokeObjectURL(previewImg.src);
          }
          previewImg.src = '';
          if (previewLoading) {
            previewLoading.style.display = 'inline';
            previewLoading.textContent = 'Carregando…';
          }
          if (style === 'com_logo' && file) {
            var fd = new FormData();
            fd.append('layout_style', style);
            fd.append('logo', file);
            fetch(baseUrl + 'qrcodes-preview.php', { method: 'POST', body: fd, credentials: 'same-origin' })
              .then(function(r) { return r.ok ? r.blob() : Promise.reject(); })
              .then(function(blob) {
                previewImg.onload = function() {
                  if (previewLoading) previewLoading.style.display = 'none';
                };
                previewImg.onerror = function() {
                  if (previewLoading) { previewLoading.style.display = 'inline'; previewLoading.textContent = 'Preview indisponível'; }
                };
                previewImg.src = URL.createObjectURL(blob);
                previewImg.style.display = 'block';
              })
              .catch(function() {
                if (previewLoading) { previewLoading.style.display = 'inline'; previewLoading.textContent = 'Preview indisponível'; }
              });
          } else {
            previewImg.onload = function() {
              previewImg.style.display = 'block';
              if (previewLoading) previewLoading.style.display = 'none';
            };
            previewImg.onerror = function() {
              if (previewLoading) previewLoading.textContent = 'Preview indisponível';
            };
            previewImg.src = baseUrl + 'qrcodes-preview.php?layout_style=' + encodeURIComponent(style) + '&t=' + Date.now();
          }
        }

        function atualizar() {
          copiarWrap.style.display = tipoCopiar && tipoCopiar.checked ? 'block' : 'none';
          definirWrap.style.display = tipoDefinir && tipoDefinir.checked ? 'block' : 'none';
          logoWrap.style.display = (tipoDefinir && tipoDefinir.checked && styleComLogo && styleComLogo.checked) ? 'block' : 'none';
          if (tipoDefinir && tipoDefinir.checked) {
            atualizarPreview();
          } else if (previewBox) {
            previewBox.style.display = 'none';
          }
        }
        [tipoPadrao, tipoCopiar, tipoDefinir].forEach(function(r) { if (r) r.addEventListener('change', atualizar); });
        document.querySelectorAll('input[name="layout_style"]').forEach(function(r) { r.addEventListener('change', atualizar); });
        if (logoInput) logoInput.addEventListener('change', atualizarPreview);
        atualizar();
      })();
      </script>
      <p><a href="<?php echo $base_url; ?>qrcodes.php" style="color: #b8c4a8;">← Voltar para QR Codes</a></p>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
