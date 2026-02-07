<?php
/**
 * Painel Admin — Editar layout do QR Code (estilo, logo ao centro)
 */
$admin_page_title = 'Layout do QR Code';
require_once __DIR__ . '/includes/header.php';
require_once dirname(__DIR__) . '/config/qrcode-gerar.php';

$qr_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($qr_id <= 0) {
  header('Location: ' . $base_url . 'qrcodes.php');
  exit;
}

$qr = qrcodePorId($qr_id);
if (!$qr) {
  header('Location: ' . $base_url . 'qrcodes.php');
  exit;
}

$site_base = dirname($_SERVER['SCRIPT_NAME'], 2) . '/';
$root_path = dirname(__DIR__);
$dir_qr = $root_path . DIRECTORY_SEPARATOR . 'qrcodes';
$logo_global_path = $dir_qr . DIRECTORY_SEPARATOR . 'logo.png';
$layout_style = isset($qr['layout_style']) && in_array($qr['layout_style'], ['padrao', 'circular', 'com_logo'], true) ? $qr['layout_style'] : 'padrao';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $novo_estilo = isset($_POST['layout_style']) && in_array($_POST['layout_style'], ['padrao', 'circular', 'com_logo'], true) ? $_POST['layout_style'] : 'padrao';

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
        $dest = $logo_global_path;
        if ($mime === 'image/png') {
          move_uploaded_file($tmp, $dest);
        } else {
          $img = imagecreatefromjpeg($tmp);
          if ($img) {
            imagepng($img, $dest);
            imagedestroy($img);
          }
        }
      }
    }
  }

  $redirect_url = rtrim(getSiteBaseUrl(), '/') . '/redirect.php?id=' . $qr['unique_id'];

  if (empty($qr['imagem_path'])) {
    $filename = 'qr_' . preg_replace('/[^a-zA-Z0-9\-]/', '', $qr['unique_id']) . '.png';
    $path_abs = $dir_qr . DIRECTORY_SEPARATOR . $filename;
    if (!is_dir($dir_qr)) {
      @mkdir($dir_qr, 0755, true);
    }
    $path_rel = 'qrcodes/' . $filename;
    getDB()->prepare('UPDATE qrcodes SET imagem_path = ? WHERE id = ?')->execute([$path_rel, $qr_id]);
  } else {
    $path_abs = $root_path . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $qr['imagem_path']);
  }

  $opcoes = ['layout_style' => $novo_estilo];
  if ($novo_estilo === 'com_logo' && is_file($logo_global_path)) {
    $opcoes['logo_path'] = $logo_global_path;
  }

  if (gerarQrCodePng($redirect_url, $path_abs, $opcoes)) {
    qrcodeAtualizarLayoutStyle($qr_id, $novo_estilo);
    header('Location: ' . $base_url . 'qrcodes.php?ok=' . rawurlencode('Layout do QR Code atualizado.'));
    exit;
  }
  $erro = getLastQrCodeError() ?: 'Não foi possível gerar a imagem.';
}

$tem_logo_global = is_file($logo_global_path);
?>
      <section class="admin-card">
        <h2>Editar layout do QR Code</h2>
        <p style="color: #b8c4a8; font-size: 0.875rem; margin-bottom: 1rem;">QR #<?php echo (int) $qr['id']; ?> &middot; Estilo atual: <strong><?php echo htmlspecialchars($layout_style); ?></strong></p>
        <?php if (!empty($qr['imagem_path'])) : ?>
        <p style="margin-bottom: 1rem;">
          <img src="<?php echo htmlspecialchars($site_base . $qr['imagem_path']); ?>" width="140" height="140" alt="QR Code" style="vertical-align: middle; border: 1px solid rgba(255,255,255,0.1);">
        </p>
        <?php endif; ?>

        <?php if ($erro !== '') : ?>
        <p style="color: #c88; margin-bottom: 1rem;"><?php echo htmlspecialchars($erro); ?></p>
        <?php endif; ?>

        <form method="post" action="" enctype="multipart/form-data" style="max-width: 520px;">
          <div style="margin-bottom: 1.25rem;">
            <span style="display: block; font-size: 0.875rem; color: #b8c4a8; margin-bottom: 0.5rem;">Estilo do QR Code</span>
            <label style="display: block; margin-bottom: 0.5rem;">
              <input type="radio" name="layout_style" value="padrao"<?php echo $layout_style === 'padrao' ? ' checked' : ''; ?>>
              <strong>Padrão</strong> — módulos quadrados
            </label>
            <label style="display: block; margin-bottom: 0.5rem;">
              <input type="radio" name="layout_style" value="circular"<?php echo $layout_style === 'circular' ? ' checked' : ''; ?>>
              <strong>Circular</strong> — módulos redondos
            </label>
            <label style="display: block; margin-bottom: 0.5rem;">
              <input type="radio" name="layout_style" value="com_logo"<?php echo $layout_style === 'com_logo' ? ' checked' : ''; ?>>
              <strong>Com logo</strong> — espaço ao centro para logo
            </label>
          </div>
          <div id="qr-preview-box" style="margin-bottom: 1.25rem; padding: 1rem; background: rgba(0,0,0,0.2); border-radius: 8px; display: inline-block;">
            <p style="margin: 0 0 0.5rem; font-size: 0.875rem; color: #b8c4a8;">Preview</p>
            <img id="qr-preview-img" src="<?php echo htmlspecialchars($base_url); ?>qrcodes-preview.php?layout_style=<?php echo rawurlencode($layout_style); ?>&t=<?php echo time(); ?>" alt="Preview do layout" width="160" height="160" style="border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;">
            <span id="qr-preview-loading" style="display: none; font-size: 0.875rem; color: #b8c4a8;">Carregando…</span>
          </div>

          <div style="margin-bottom: 1.25rem;">
            <label for="logo" style="display: block; font-size: 0.875rem; color: #b8c4a8; margin-bottom: 0.35rem;">Logo central (usado no estilo &quot;Com logo&quot;)</label>
            <?php if ($tem_logo_global) : ?>
            <p style="font-size: 0.8125rem; color: #b8c4a8; margin-bottom: 0.35rem;">Logo atual: <img src="<?php echo htmlspecialchars($site_base . 'qrcodes/logo.png'); ?>?t=<?php echo time(); ?>" width="40" height="40" alt="Logo" style="vertical-align: middle;"> Substitua enviando um novo arquivo.</p>
            <?php else : ?>
            <p style="font-size: 0.8125rem; color: #b8c4a8; margin-bottom: 0.35rem;">Nenhum logo definido. Envie uma imagem (PNG ou JPG) para usar no centro do QR.</p>
            <?php endif; ?>
            <input type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/jpg" style="font-size: 0.875rem;">
          </div>

          <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            <button type="submit" style="padding: 0.5rem 1rem; cursor: pointer; border-radius: 8px; border: 1px solid rgba(184,196,168,0.4); background: rgba(184,196,168,0.15); color: #f0f2eb;">Aplicar layout</button>
            <a href="<?php echo $base_url; ?>qrcodes.php" style="padding: 0.5rem 1rem; color: #b8c4a8; text-decoration: none; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15);">Cancelar</a>
          </div>
        </form>

        <p style="font-size: 0.75rem; color: #b8c4a8; margin-top: 1rem;">O estilo &quot;Com logo&quot; usa correção de erros alta (ECC H) e reserva espaço no centro; o logo é desenhado por cima. Use uma imagem quadrada ou proporcional para melhor resultado.</p>
      </section>
      <script>
      (function() {
        var baseUrl = '<?php echo addslashes($base_url); ?>';
        var previewImg = document.getElementById('qr-preview-img');
        var previewLoading = document.getElementById('qr-preview-loading');
        if (!previewImg) return;
        document.querySelectorAll('input[name="layout_style"]').forEach(function(r) {
          r.addEventListener('change', function() {
            var style = this.value;
            previewImg.style.display = 'none';
            previewImg.src = '';
            if (previewLoading) previewLoading.style.display = 'inline';
            previewImg.onload = function() {
              previewImg.style.display = 'block';
              if (previewLoading) previewLoading.style.display = 'none';
            };
            previewImg.onerror = function() {
              if (previewLoading) previewLoading.textContent = 'Preview indisponível';
            };
            previewImg.src = baseUrl + 'qrcodes-preview.php?layout_style=' + encodeURIComponent(style) + '&t=' + Date.now();
          });
        });
      })();
      </script>
      <p><a href="<?php echo $base_url; ?>qrcodes.php" style="color: #b8c4a8;">← Voltar para QR Codes</a></p>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
