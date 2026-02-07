<?php
/**
 * Painel Admin — Vincular manualmente um link ao QR Code
 * Toda lógica que faz redirect deve rodar ANTES de incluir o header (que envia HTML).
 */
$admin_page_title = 'Vincular link ao QR Code';

require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$base_url = dirname($_SERVER['SCRIPT_NAME']) . '/';
$site_redirect = dirname($_SERVER['SCRIPT_NAME'], 2);

if (empty($_SESSION['user_id'])) {
  header('Location: ' . $site_redirect . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
  exit;
}
if (!isAdmin($_SESSION['user_id'] ?? 0, $_SESSION['user_email'] ?? null)) {
  header('Location: ' . $site_redirect . '/dashboard.php');
  exit;
}

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

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!empty($_POST['desvincular'])) {
    if (qrcodeDesvincular($qr_id)) {
      header('Location: ' . $base_url . 'qrcodes.php?ok=' . rawurlencode('QR Code desvinculado e disponível no estoque.'));
      exit;
    }
    $erro = 'Não foi possível desvincular.';
  } else {
    $url = isset($_POST['url']) ? trim($_POST['url']) : '';
    $memorial_id = isset($_POST['memorial_id']) ? (int) $_POST['memorial_id'] : 0;

    if ($memorial_id > 0) {
      $pdo = getDB();
      $stmt = $pdo->prepare('SELECT slug FROM memorials WHERE id = ? LIMIT 1');
      $stmt->execute([$memorial_id]);
      $m = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($m) {
        $url = urlMemorialPublico($m['slug']);
      }
    }

    if ($url === '') {
      $erro = 'Informe a URL de destino ou escolha um memorial.';
    } elseif (!preg_match('#^https?://#i', $url)) {
      $erro = 'A URL deve começar com http:// ou https://';
    } else {
      $url_atual_qr = '';
      if (!empty($qr['content'])) {
        $data = json_decode($qr['content'], true);
        if (!empty($data['url'])) {
          $url_atual_qr = trim($data['url']);
        }
      }
      // Normalizar URL se for localhost/127.0.0.1 para gravar sempre a base do site (produção)
      if (function_exists('normalizarUrlQrRedirect')) {
        $url = normalizarUrlQrRedirect($url);
      }
      $url_norm = rtrim($url, '/');
      $atual_norm = $url_atual_qr !== '' ? rtrim($url_atual_qr, '/') : '';
      if ($atual_norm !== '' && $url_norm === $atual_norm) {
        header('Location: ' . $base_url . 'qrcodes.php?ok=' . rawurlencode('O link já está correto. Nenhuma alteração necessária.'));
        exit;
      }
      $mid = ($memorial_id > 0) ? $memorial_id : null;
      if (qrcodeAtualizarLink($qr_id, $url, $mid)) {
        header('Location: ' . $base_url . 'qrcodes.php?ok=' . rawurlencode('Link vinculado com sucesso.'));
        exit;
      }
      $erro = 'Não foi possível salvar.';
    }
  }
}

$site_base = dirname($_SERVER['SCRIPT_NAME'], 2) . '/';
$url_atual = '';
if (!empty($qr['content'])) {
  $data = json_decode($qr['content'], true);
  if (!empty($data['url'])) {
    $url_atual = $data['url'];
  }
}

$memoriais = listarMemoriaisParaSelect();

require_once __DIR__ . '/includes/header.php';
?>
      <section class="admin-card">
        <h2>Vincular link ao QR Code</h2>
        <p style="color: #b8c4a8; font-size: 0.875rem; margin-bottom: 1rem;">QR #<?php echo (int) $qr['id']; ?> &middot; <code style="font-size: 0.75rem;"><?php echo htmlspecialchars(substr($qr['unique_id'], 0, 20)); ?>…</code></p>
        <?php if (!empty($qr['imagem_path'])) : ?>
        <p style="margin-bottom: 1rem;">
          <img src="<?php echo htmlspecialchars($site_base . $qr['imagem_path']); ?>" width="120" height="120" alt="QR Code" style="vertical-align: middle;">
        </p>
        <?php endif; ?>
        <?php if ($url_atual !== '') : ?>
        <p style="font-size: 0.875rem; color: #b8c4a8; margin-bottom: 1rem;">Link atual: <a href="<?php echo htmlspecialchars($url_atual); ?>" target="_blank" rel="noopener" style="color: #b8c4a8; word-break: break-all;"><?php echo htmlspecialchars($url_atual); ?></a></p>
        <?php endif; ?>

        <?php if ($erro !== '') : ?>
        <p style="color: #c88; margin-bottom: 1rem;"><?php echo htmlspecialchars($erro); ?></p>
        <?php endif; ?>

        <form method="post" action="" style="max-width: 520px;">
          <div style="margin-bottom: 1rem;">
            <label for="memorial_id" style="display: block; font-size: 0.875rem; color: #b8c4a8; margin-bottom: 0.35rem;">Ou escolha um memorial</label>
            <select name="memorial_id" id="memorial_id" style="width: 100%; padding: 0.5rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #f0f2eb;">
              <option value="0">— Nenhum (usar URL abaixo) —</option>
              <?php foreach ($memoriais as $m) : ?>
              <option value="<?php echo (int) $m['id']; ?>" data-url="<?php echo htmlspecialchars(urlMemorialPublico($m['slug'])); ?>"><?php echo htmlspecialchars($m['titulo']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="margin-bottom: 1rem;">
            <label for="url" style="display: block; font-size: 0.875rem; color: #b8c4a8; margin-bottom: 0.35rem;">URL de destino</label>
            <input type="url" name="url" id="url" value="<?php echo htmlspecialchars($url_atual); ?>" placeholder="https://... (ou escolha um memorial acima)" style="width: 100%; padding: 0.5rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); border-radius: 8px; color: #f0f2eb;">
            <p style="font-size: 0.75rem; color: #b8c4a8; margin-top: 0.25rem;">Ao escolher um memorial acima, a URL será preenchida automaticamente.</p>
          </div>
          <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            <button type="submit" style="padding: 0.5rem 1rem; cursor: pointer; border-radius: 8px; border: 1px solid rgba(184,196,168,0.4); background: rgba(184,196,168,0.15); color: #f0f2eb;">Salvar link</button>
            <a href="<?php echo $base_url; ?>qrcodes.php" style="padding: 0.5rem 1rem; color: #b8c4a8; text-decoration: none; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15);">Cancelar</a>
          </div>
        </form>

        <?php if ($qr['status'] === 'usado') : ?>
        <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.08); margin: 1.5rem 0;">
        <form method="post" action="" onsubmit="return confirm('Desvincular este QR Code? Ele voltará ao estoque como disponível.');">
          <input type="hidden" name="desvincular" value="1">
          <button type="submit" style="padding: 0.5rem 1rem; cursor: pointer; border-radius: 8px; border: 1px solid rgba(200,100,100,0.5); background: rgba(200,100,100,0.15); color: #f0a0a0;">Desvincular (voltar ao estoque)</button>
        </form>
        <?php endif; ?>
      </section>

      <script>
      (function() {
        var sel = document.getElementById('memorial_id');
        var url = document.getElementById('url');
        if (!sel || !url) return;
        sel.addEventListener('change', function() {
          var opt = this.options[this.selectedIndex];
          if (opt.value !== '0' && opt.getAttribute('data-url')) {
            url.value = opt.getAttribute('data-url');
          }
        });
      })();
      </script>

      <p><a href="<?php echo $base_url; ?>qrcodes.php" style="color: #b8c4a8;">← Voltar para QR Codes</a></p>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
