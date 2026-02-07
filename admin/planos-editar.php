<?php
/**
 * Painel Admin — Criar ou editar plano (e benefícios)
 */
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once dirname(__DIR__) . '/config/database.php';
$stripe_config = file_exists(dirname(__DIR__) . '/config/stripe.config.php') ? require dirname(__DIR__) . '/config/stripe.config.php' : ['mode' => 'fake'];
require_once dirname(__DIR__) . '/config/stripe-sync.php';

if (empty($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'] ?? 0, $_SESSION['user_email'] ?? null)) {
  header('Location: ' . dirname($_SERVER['SCRIPT_NAME'], 2) . '/login.php');
  exit;
}

$base_url = dirname($_SERVER['SCRIPT_NAME']) . '/';
$pdo = getDB();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editar = $id > 0;
$plano = null;
$beneficios = [];

if ($editar) {
  $stmt = $pdo->prepare('SELECT id, nome, slug, descricao_curta, max_fotos, tem_audio, tem_video, ordem, ativo, preco_anual_cents, stripe_product_id, stripe_price_id FROM plans WHERE id = ? LIMIT 1');
  $stmt->execute([$id]);
  $plano = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$plano) {
    header('Location: ' . $base_url . 'planos.php');
    exit;
  }
  $stmtB = $pdo->prepare('SELECT id, descricao, ordem FROM plan_benefits WHERE plan_id = ? ORDER BY ordem ASC');
  $stmtB->execute([$id]);
  $beneficios = $stmtB->fetchAll(PDO::FETCH_ASSOC);
}

$erro = '';
$mensagem = '';

function slugFromNome($nome) {
  $s = mb_strtolower(trim($nome), 'UTF-8');
  $s = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $s);
  $s = preg_replace('/[\s\-]+/', '-', $s);
  return trim($s, '-') ?: 'plano';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
  $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
  if ($slug === '' && $nome !== '') {
    $slug = slugFromNome($nome);
  }
  $descricao_curta = isset($_POST['descricao_curta']) ? trim($_POST['descricao_curta']) : '';
  $max_fotos = isset($_POST['max_fotos']) ? (int) $_POST['max_fotos'] : 0;
  $tem_audio = isset($_POST['tem_audio']) ? 1 : 0;
  $tem_video = isset($_POST['tem_video']) ? 1 : 0;
  $ordem = isset($_POST['ordem']) ? (int) $_POST['ordem'] : 0;
  $ativo = isset($_POST['ativo']) ? 1 : 0;
  $beneficios_post = isset($_POST['beneficios']) && is_array($_POST['beneficios']) ? $_POST['beneficios'] : [];
  $beneficios_post = array_values(array_filter(array_map('trim', $beneficios_post)));

  $preco_anual_str = isset($_POST['preco_anual']) ? trim($_POST['preco_anual']) : '';
  $preco_anual_cents = null;
  if ($preco_anual_str !== '') {
    $v = (float) str_replace([',', ' '], ['.', ''], $preco_anual_str);
    if ($v >= 0) {
      $preco_anual_cents = (int) round($v * 100);
    }
  }

  if ($nome === '') {
    $erro = 'O nome do plano é obrigatório.';
  } elseif (mb_strlen($nome) > 100) {
    $erro = 'O nome deve ter no máximo 100 caracteres.';
  } elseif ($slug === '') {
    $erro = 'O slug é obrigatório.';
  } elseif (mb_strlen($slug) > 100) {
    $erro = 'O slug deve ter no máximo 100 caracteres.';
  } else {
    $stmt = $pdo->prepare('SELECT id FROM plans WHERE slug = ? AND id != ? LIMIT 1');
    $stmt->execute([$slug, $id]);
    if ($stmt->fetch()) {
      $erro = 'Já existe um plano com este slug. Escolha outro.';
    }
  }

  if ($erro === '') {
    $stripe_product_id = $editar ? ($plano['stripe_product_id'] ?? null) : null;
    $stripe_price_id = $editar ? ($plano['stripe_price_id'] ?? null) : null;
    $plan_id_para_sync = $editar ? $id : 0;
    $sync = stripeSyncPlan($pdo, $plan_id_para_sync, $nome, $slug, $descricao_curta, $preco_anual_cents, $stripe_product_id, $stripe_price_id, $stripe_config);
    if (!$sync['ok']) {
      $erro = $sync['erro'];
    } else {
      $stripe_product_id = $sync['stripe_product_id'];
      $stripe_price_id = $sync['stripe_price_id'];
      if ($editar) {
        $stmt = $pdo->prepare('UPDATE plans SET nome = ?, slug = ?, descricao_curta = ?, max_fotos = ?, tem_audio = ?, tem_video = ?, ordem = ?, ativo = ?, preco_anual_cents = ?, stripe_product_id = ?, stripe_price_id = ? WHERE id = ?');
        $stmt->execute([$nome, $slug, $descricao_curta, $max_fotos, $tem_audio, $tem_video, $ordem, $ativo, $preco_anual_cents, $stripe_product_id, $stripe_price_id, $id]);
        $stmt = $pdo->prepare('DELETE FROM plan_benefits WHERE plan_id = ?');
        $stmt->execute([$id]);
      } else {
        $stmt = $pdo->prepare('INSERT INTO plans (nome, slug, descricao_curta, max_fotos, tem_audio, tem_video, ordem, ativo, preco_anual_cents, stripe_product_id, stripe_price_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$nome, $slug, $descricao_curta, $max_fotos, $tem_audio, $tem_video, $ordem, $ativo, $preco_anual_cents, $stripe_product_id, $stripe_price_id]);
        $id = (int) $pdo->lastInsertId();
      }
      $stmt = $pdo->prepare('INSERT INTO plan_benefits (plan_id, descricao, ordem) VALUES (?, ?, ?)');
      foreach ($beneficios_post as $i => $desc) {
        if ($desc !== '') {
          $stmt->execute([$id, $desc, $i + 1]);
        }
      }
      $mensagem = $editar ? 'Plano atualizado com sucesso.' : 'Plano criado com sucesso.';
      header('Location: ' . $base_url . 'planos.php?ok=' . urlencode($mensagem));
      exit;
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro !== '') {
  $plano = [
    'nome' => $nome ?? '',
    'slug' => $slug ?? '',
    'descricao_curta' => $descricao_curta ?? '',
    'max_fotos' => $max_fotos ?? 0,
    'tem_audio' => $tem_audio ?? 0,
    'tem_video' => $tem_video ?? 0,
    'ordem' => $ordem ?? 0,
    'ativo' => $ativo ?? 0,
    'preco_anual_cents' => $preco_anual_cents ?? null,
  ];
  $beneficios = [];
  foreach ($beneficios_post as $i => $desc) {
    $beneficios[] = ['descricao' => $desc, 'ordem' => $i + 1];
  }
}

$admin_page_title = $editar ? 'Editar plano' : 'Novo plano';
require_once __DIR__ . '/includes/header.php';
?>
      <section class="admin-card">
        <h2 id="titulo-plano"><?php echo $editar ? 'Editar plano' : 'Novo plano'; ?></h2>
        <?php if ($erro !== '') : ?>
        <p style="color: #c88; margin-bottom: 1rem;"><?php echo htmlspecialchars($erro); ?></p>
        <?php endif; ?>
        <form method="post" action="" style="max-width: 560px;">
          <div style="margin-bottom: 1rem;">
            <label for="nome" style="display: block; margin-bottom: 0.35rem; color: #b8c4a8;">Nome do plano *</label>
            <input type="text" name="nome" id="nome" value="<?php echo htmlspecialchars($plano['nome'] ?? ''); ?>" maxlength="100" required style="width: 100%; padding: 0.5rem;">
          </div>
          <div style="margin-bottom: 1rem;">
            <label for="slug" style="display: block; margin-bottom: 0.35rem; color: #b8c4a8;">Slug (URL) *</label>
            <input type="text" name="slug" id="slug" value="<?php echo htmlspecialchars($plano['slug'] ?? ''); ?>" maxlength="100" required style="width: 100%; padding: 0.5rem;">
            <p style="font-size: 0.75rem; color: #b8c4a8; margin: 0.25rem 0 0;">Ex.: essencial, tributo. Deixe em branco para gerar a partir do nome.</p>
          </div>
          <div style="margin-bottom: 1rem;">
            <label for="descricao_curta" style="display: block; margin-bottom: 0.35rem; color: #b8c4a8;">Descrição curta</label>
            <input type="text" name="descricao_curta" id="descricao_curta" value="<?php echo htmlspecialchars($plano['descricao_curta'] ?? ''); ?>" maxlength="255" style="width: 100%; padding: 0.5rem;">
          </div>
          <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <div>
              <label for="max_fotos" style="display: block; margin-bottom: 0.35rem; color: #b8c4a8;">Máx. fotos</label>
              <input type="number" name="max_fotos" id="max_fotos" value="<?php echo (int) ($plano['max_fotos'] ?? 0); ?>" min="0" style="width: 100px; padding: 0.5rem;">
            </div>
            <div>
              <label for="ordem" style="display: block; margin-bottom: 0.35rem; color: #b8c4a8;">Ordem (exibição)</label>
              <input type="number" name="ordem" id="ordem" value="<?php echo (int) ($plano['ordem'] ?? 0); ?>" min="0" style="width: 80px; padding: 0.5rem;">
            </div>
            <div>
              <label for="preco_anual" style="display: block; margin-bottom: 0.35rem; color: #b8c4a8;">Preço anual (R$)</label>
              <input type="number" name="preco_anual" id="preco_anual" value="<?php echo isset($plano['preco_anual_cents']) && $plano['preco_anual_cents'] !== null ? number_format($plano['preco_anual_cents'] / 100, 2, '.', '') : ''; ?>" min="0" step="0.01" placeholder="Ex.: 99.00" style="width: 120px; padding: 0.5rem;">
              <p style="font-size: 0.75rem; color: #b8c4a8; margin: 0.25rem 0 0;">Taxa anual. Vinculado ao Stripe ao salvar.</p>
            </div>
          </div>
          <div style="margin-bottom: 1rem;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
              <input type="checkbox" name="tem_audio" value="1" <?php echo !empty($plano['tem_audio']) ? 'checked' : ''; ?>>
              <span style="color: #f0f2eb;">Inclui áudio</span>
            </label>
          </div>
          <div style="margin-bottom: 1rem;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
              <input type="checkbox" name="tem_video" value="1" <?php echo !empty($plano['tem_video']) ? 'checked' : ''; ?>>
              <span style="color: #f0f2eb;">Inclui vídeo</span>
            </label>
          </div>
          <div style="margin-bottom: 1rem;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
              <input type="checkbox" name="ativo" value="1" <?php echo (!isset($plano['ativo']) || !empty($plano['ativo'])) ? 'checked' : ''; ?>>
              <span style="color: #f0f2eb;">Plano ativo (visível para assinatura)</span>
            </label>
          </div>

          <div style="margin: 1.5rem 0 1rem;">
            <strong style="color: #f0f2eb;">Benefícios (itens da lista)</strong>
            <p style="font-size: 0.8125rem; color: #b8c4a8; margin: 0.25rem 0 0.5rem;">Um por linha. Deixe em branco para remover.</p>
            <div id="beneficios-wrap">
              <?php if (!empty($beneficios)) : ?>
                <?php foreach ($beneficios as $b) : ?>
                <div class="beneficio-row" style="margin-bottom: 0.5rem;">
                  <input type="text" name="beneficios[]" value="<?php echo htmlspecialchars($b['descricao']); ?>" maxlength="255" style="width: 100%; max-width: 400px; padding: 0.4rem;">
                </div>
                <?php endforeach; ?>
              <?php else : ?>
              <div class="beneficio-row" style="margin-bottom: 0.5rem;">
                <input type="text" name="beneficios[]" value="" maxlength="255" placeholder="Ex.: 12 Fotos" style="width: 100%; max-width: 400px; padding: 0.4rem;">
              </div>
              <?php endif; ?>
            </div>
            <button type="button" id="add-beneficio" style="margin-top: 0.5rem; padding: 0.35rem 0.75rem; font-size: 0.875rem; cursor: pointer; border-radius: 6px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.05); color: #b8c4a8;">+ Adicionar benefício</button>
          </div>

          <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1.5rem; align-items: center;">
            <button type="submit" style="padding: 0.5rem 1rem; cursor: pointer; border-radius: 8px; border: 1px solid rgba(184,196,168,0.4); background: rgba(184,196,168,0.15); color: #f0f2eb;"><?php echo $editar ? 'Salvar' : 'Criar plano'; ?></button>
            <a href="<?php echo $base_url; ?>planos.php" style="padding: 0.5rem 1rem; color: #b8c4a8; text-decoration: none; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15);">Cancelar</a>
            <?php if ($editar && $plano) : ?>
            <a href="<?php echo $base_url; ?>planos-excluir.php?id=<?php echo (int) $plano['id']; ?>" style="padding: 0.5rem 1rem; color: #c88; text-decoration: none; border-radius: 8px; border: 1px solid rgba(200,136,136,0.4); margin-left: auto;" onclick="return confirm('Excluir este plano? Ele será desativado e o produto será arquivado no Stripe. Assinantes atuais continuarão até o vencimento.');">Excluir este plano</a>
            <?php endif; ?>
          </div>
        </form>
      </section>
      <script>
      (function() {
        var wrap = document.getElementById('beneficios-wrap');
        var btn = document.getElementById('add-beneficio');
        if (btn && wrap) {
          btn.addEventListener('click', function() {
            var div = document.createElement('div');
            div.className = 'beneficio-row';
            div.style.marginBottom = '0.5rem';
            var input = document.createElement('input');
            input.type = 'text';
            input.name = 'beneficios[]';
            input.maxLength = 255;
            input.placeholder = 'Ex.: Biografia completa';
            input.style.cssText = 'width: 100%; max-width: 400px; padding: 0.4rem;';
            div.appendChild(input);
            wrap.appendChild(div);
          });
        }
        var nomeEl = document.getElementById('nome');
        var slugEl = document.getElementById('slug');
        if (nomeEl && slugEl) {
          nomeEl.addEventListener('blur', function() {
            if (slugEl.value === '' && nomeEl.value.trim() !== '') {
              var s = nomeEl.value.trim().toLowerCase()
                .replace(/[^\w\s\-àáâãäåèéêëìíîïòóôõöùúûüç]/gi, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
              slugEl.value = s || 'plano';
            }
          });
        }
      })();
      </script>
      <p><a href="<?php echo $base_url; ?>planos.php" style="color: #b8c4a8;">← Voltar para Planos</a></p>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
