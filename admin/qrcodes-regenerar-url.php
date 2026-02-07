<?php
/**
 * Painel Admin — Regenerar imagens dos QR Codes com a URL atual do site.
 * Útil quando a base_url mudou (ex.: de localhost para realizasites.com).
 * Toda lógica que faz redirect deve rodar ANTES de incluir o header.
 */
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/qrcode-gerar.php';

$base_url_admin = dirname($_SERVER['SCRIPT_NAME']) . '/';
$site_redirect = dirname($_SERVER['SCRIPT_NAME'], 2) . '/';
if (empty($_SESSION['user_id'])) {
  header('Location: ' . $site_redirect . 'login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
  exit;
}
if (!isAdmin($_SESSION['user_id'] ?? 0, $_SESSION['user_email'] ?? null)) {
  header('Location: ' . $site_redirect . 'dashboard.php');
  exit;
}

$root_path = dirname(__DIR__);
$dir_qr = $root_path . DIRECTORY_SEPARATOR . 'qrcodes';
$logo_global_path = $dir_qr . DIRECTORY_SEPARATOR . 'logo.png';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['confirmar'])) {
  $lista = function_exists('qrcodesListarComImagem') ? qrcodesListarComImagem() : [];
  $regenerados = 0;
  $base_redirect = rtrim(getSiteBaseUrl(), '/') . '/redirect.php?id=';

  $layout_forcar = isset($_POST['layout_forcar']) && in_array($_POST['layout_forcar'], ['padrao', 'circular', 'com_logo'], true) ? $_POST['layout_forcar'] : null;

  // Logo: enviado nesta ação ou logo global (só usado se layout for com_logo)
  $logo_path_esta_acao = null;
  $logo_usar_tmp_direto = false; // true = usar $_FILES['logo']['tmp_name'] em cada chamada (não apagar)
  if ($layout_forcar === 'com_logo' || (empty($layout_forcar) && !empty($lista))) {
    if (!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
      $tmp = $_FILES['logo']['tmp_name'];
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime = finfo_file($finfo, $tmp);
      finfo_close($finfo);
      if (in_array($mime, ['image/png', 'image/jpeg', 'image/jpg'], true)) {
        $ext = ($mime === 'image/png') ? '.png' : '.jpg';
        $logo_path_esta_acao = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'imortalize_regenerar_logo_' . uniqid('', true) . $ext;
        if (@copy($tmp, $logo_path_esta_acao) && is_file($logo_path_esta_acao)) {
          // cópia ok
        } else {
          $logo_path_esta_acao = $tmp;
          $logo_usar_tmp_direto = true;
        }
      }
    }
  }

  foreach ($lista as $qr) {
    $path_abs = $root_path . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $qr['imagem_path']);
    if (!is_file($path_abs)) {
      continue;
    }
    $layout_style = $layout_forcar !== null
      ? $layout_forcar
      : (isset($qr['layout_style']) && in_array($qr['layout_style'], ['padrao', 'circular', 'com_logo'], true) ? $qr['layout_style'] : 'padrao');
    $opcoes = ['layout_style' => $layout_style];
    if ($layout_style === 'com_logo') {
      if ($logo_path_esta_acao && is_file($logo_path_esta_acao)) {
        $opcoes['logo_path'] = $logo_path_esta_acao;
      } elseif (is_file($logo_global_path)) {
        $opcoes['logo_path'] = $logo_global_path;
      }
    }
    $url = $base_redirect . $qr['unique_id'];
    if (function_exists('gerarQrCodePng') && gerarQrCodePng($url, $path_abs, $opcoes)) {
      $regenerados++;
      if ($layout_forcar !== null && function_exists('qrcodeAtualizarLayoutStyle')) {
        qrcodeAtualizarLayoutStyle((int) $qr['id'], $layout_forcar);
      }
    }
  }

  if ($logo_path_esta_acao && is_file($logo_path_esta_acao) && !$logo_usar_tmp_direto) {
    @unlink($logo_path_esta_acao);
  }

  if ($regenerados > 0) {
    header('Location: ' . $base_url_admin . 'qrcodes-regenerar-url.php?ok=' . rawurlencode($regenerados . ' imagem(ns) de QR atualizada(s) com a URL do site.'));
  } else {
    $msg = empty($lista) ? 'Nenhum QR com imagem para regenerar.' : 'Nenhuma imagem pôde ser regenerada. Verifique permissões e extensão GD.';
    if ($layout_forcar === 'com_logo' && !$logo_path_esta_acao && !is_file($logo_global_path)) {
      $msg = 'Escolheu «Com logo ao centro» mas não foi enviado nenhum ficheiro de logo e não existe qrcodes/logo.png no servidor. Envie um PNG ou JPG no campo Logo.';
    }
    header('Location: ' . $base_url_admin . 'qrcodes-regenerar-url.php?erro=' . rawurlencode($msg));
  }
  exit;
}

$admin_page_title = 'Regenerar URL nos QRs';
require_once __DIR__ . '/includes/header.php';

$ok = isset($_GET['ok']) ? trim($_GET['ok']) : '';
$erro = isset($_GET['erro']) ? trim($_GET['erro']) : '';

$lista = function_exists('qrcodesListarComImagem') ? qrcodesListarComImagem() : [];
$total = count($lista);
$url_exemplo = rtrim(getSiteBaseUrl(), '/') . '/redirect.php?id=';
?>
      <?php if ($ok !== '') : ?>
      <section class="admin-card" style="border-color: rgba(136,170,136,0.5); background: rgba(40,60,30,0.5);">
        <p style="margin: 0; font-size: 1rem; color: #9c9;"><strong>✓ Sucesso:</strong> <?php echo htmlspecialchars($ok); ?></p>
      </section>
      <?php endif; ?>
      <?php if ($erro !== '') : ?>
      <section class="admin-card" style="border-color: rgba(200,136,136,0.5); background: rgba(60,30,30,0.5);">
        <p style="margin: 0; font-size: 1rem; color: #e99;"><strong>Aviso:</strong> <?php echo htmlspecialchars($erro); ?></p>
      </section>
      <?php endif; ?>
      <section class="admin-card">
        <h2>Regenerar URL nas imagens dos QR Codes</h2>
        <p style="color: #b8c4a8; font-size: 0.875rem; margin-bottom: 1rem;">
          As imagens PNG dos QRs contêm a URL que o leitor abre ao escanear. Se você mudou o domínio (ex.: de localhost para produção),
          as imagens antigas ainda apontam para a URL antiga. Esta ação regera cada PNG com a <strong>URL atual do site</strong>,
          mantendo o mesmo estilo (padrão, circular, com logo).
        </p>
        <p style="font-size: 0.875rem; color: #f0f2eb; margin-bottom: 0.5rem;">URL que será usada em todos os QRs:</p>
        <p style="margin: 0 0 1rem; word-break: break-all;"><code style="font-size: 0.8125rem; color: #b8c4a8;"><?php echo htmlspecialchars($url_exemplo); ?></code><span style="color: #b8c4a8;">{unique_id}</span></p>
        <p style="margin-bottom: 1rem; font-size: 0.875rem; color: #b8c4a8;">QR Codes com imagem: <strong><?php echo (int) $total; ?></strong></p>
        <?php if ($total > 0) : ?>
        <form method="post" action="" enctype="multipart/form-data" onsubmit="return confirm('Regenerar <?php echo (int) $total; ?> imagem(ns) de QR com a URL do site? Os ficheiros PNG serão sobrescritos.');">
          <input type="hidden" name="confirmar" value="1">
          <div style="margin-bottom: 1rem;">
            <label for="layout_forcar" style="display: block; font-size: 0.875rem; color: #b8c4a8; margin-bottom: 0.35rem;">Layout ao recriar</label>
            <select name="layout_forcar" id="layout_forcar" style="padding: 0.5rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #f0f2eb; min-width: 200px;">
              <option value="">Manter o layout atual de cada QR</option>
              <option value="padrao">Padrão (quadrado)</option>
              <option value="circular">Circular</option>
              <option value="com_logo">Com logo ao centro</option>
            </select>
            <p style="font-size: 0.75rem; color: #b8c4a8; margin-top: 0.25rem;">Escolha um para aplicar o mesmo estilo a todos; ou deixe «Manter» para preservar o estilo de cada um.</p>
          </div>
          <div style="margin-bottom: 1rem;" id="wrap_logo_regenerar">
            <label for="logo" style="display: block; font-size: 0.875rem; color: #b8c4a8; margin-bottom: 0.35rem;">Logo (para «Com logo ao centro»)</label>
            <input type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/jpg" style="padding: 0.35rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #f0f2eb; font-size: 0.875rem;">
            <p style="font-size: 0.75rem; color: #b8c4a8; margin-top: 0.25rem;">Envie um PNG ou JPG para usar nesta recriação. Se não enviar e escolher «Com logo», será usado o logo global da pasta <code>qrcodes/logo.png</code>.</p>
            <?php if (is_file($logo_global_path)) : ?>
            <p style="font-size: 0.75rem; color: #b8c4a8; margin-top: 0.35rem;">Logo global atual: <img src="<?php echo htmlspecialchars($site_base . 'qrcodes/logo.png'); ?>?t=<?php echo time(); ?>" width="40" height="40" alt="Logo" style="vertical-align: middle; border: 1px solid rgba(255,255,255,0.1); border-radius: 4px;"></p>
            <?php endif; ?>
          </div>
          <div style="margin-bottom: 1.5rem; padding: 1rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px;">
            <p style="font-size: 0.875rem; color: #b8c4a8; margin: 0 0 0.5rem;">Pré-visualização do QR</p>
            <div id="qr-preview-wrap" style="min-height: 180px; display: flex; align-items: center; justify-content: flex-start; gap: 1rem; flex-wrap: wrap;">
              <img id="qr-preview-regenerar" src="" alt="Preview do QR" width="160" height="160" style="display: none; border: 1px solid rgba(255,255,255,0.15); border-radius: 8px;">
              <p id="qr-preview-placeholder" style="margin: 0; font-size: 0.875rem; color: #b8c4a8;">Escolha um layout acima para ver como ficará o QR.</p>
              <p id="qr-preview-loading" style="margin: 0; font-size: 0.875rem; color: #b8c4a8; display: none;">A carregar…</p>
            </div>
          </div>
          <button type="submit" style="padding: 0.5rem 1rem; cursor: pointer; border-radius: 8px; border: 1px solid rgba(184,196,168,0.4); background: rgba(184,196,168,0.15); color: #f0f2eb;">Regenerar todas as imagens</button>
        </form>
        <?php else : ?>
        <p style="color: #b8c4a8; font-size: 0.875rem;">Nenhum QR com imagem no momento. Gere um estoque ou publique um memorial para ter imagens a regenerar.</p>
        <?php endif; ?>
      </section>
      <p><a href="<?php echo $base_url; ?>qrcodes.php" style="color: #b8c4a8;">← Voltar para QR Codes</a></p>
      <script>
      (function() {
        var baseUrl = <?php echo json_encode($base_url); ?>;
        var layoutSelect = document.getElementById('layout_forcar');
        var logoInput = document.getElementById('logo');
        var previewImg = document.getElementById('qr-preview-regenerar');
        var placeholder = document.getElementById('qr-preview-placeholder');
        var loading = document.getElementById('qr-preview-loading');
        var currentObjectUrl = null;

        function showLoading(show) {
          loading.style.display = show ? 'block' : 'none';
          if (show) {
            placeholder.style.display = 'none';
            previewImg.style.display = 'none';
          }
        }
        function showPreview(src) {
          if (currentObjectUrl) {
            URL.revokeObjectURL(currentObjectUrl);
            currentObjectUrl = null;
          }
          loading.style.display = 'none';
          placeholder.style.display = 'none';
          previewImg.style.display = 'block';
          previewImg.src = src;
          if (src.indexOf('blob:') === 0) currentObjectUrl = src;
        }
        function showPlaceholder() {
          if (currentObjectUrl) {
            URL.revokeObjectURL(currentObjectUrl);
            currentObjectUrl = null;
          }
          previewImg.style.display = 'none';
          previewImg.src = '';
          loading.style.display = 'none';
          placeholder.style.display = 'block';
        }

        function updatePreviewByLayout(layout) {
          if (!layout) {
            showPlaceholder();
            return;
          }
          showLoading(true);
          var url = baseUrl + 'qrcodes-preview.php?layout_style=' + encodeURIComponent(layout) + '&t=' + Date.now();
          previewImg.onload = function() { showLoading(false); showPreview(previewImg.src); };
          previewImg.onerror = function() { showLoading(false); showPlaceholder(); };
          previewImg.src = url;
        }

        function updatePreviewWithLogo(file) {
          if (!file) return;
          showLoading(true);
          var fd = new FormData();
          fd.append('layout_style', 'com_logo');
          fd.append('logo', file);
          fetch(baseUrl + 'qrcodes-preview.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) {
              if (!r.ok) throw new Error('Erro');
              return r.blob();
            })
            .then(function(blob) {
              showLoading(false);
              showPreview(URL.createObjectURL(blob));
            })
            .catch(function() {
              showLoading(false);
              showPlaceholder();
            });
        }

        if (layoutSelect) {
          layoutSelect.addEventListener('change', function() {
            var layout = this.value;
            if (layout === 'com_logo' && logoInput && logoInput.files && logoInput.files[0]) {
              updatePreviewWithLogo(logoInput.files[0]);
            } else {
              updatePreviewByLayout(layout);
            }
          });
        }
        if (logoInput) {
          logoInput.addEventListener('change', function() {
            var file = this.files && this.files[0];
            var layout = layoutSelect ? layoutSelect.value : '';
            if (file && (layout === 'com_logo' || !layout)) {
              updatePreviewWithLogo(file);
            } else if (file && layout) {
              updatePreviewWithLogo(file);
            } else if (layout) {
              updatePreviewByLayout(layout);
            }
          });
        }
      })();
      </script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
