<?php
/**
 * Página pública do memorial — Imortalize
 * Qualquer pessoa com o link pode acessar (sem login).
 * Só memoriais com status "publicado" são exibidos.
 * Este link pode ser compartilhado ou usado no sistema externo de QR Code.
 */
session_start();
require_once __DIR__ . '/config/database.php';

$logado = !empty($_SESSION['user_id']);
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if ($slug === '' || !slugValido($slug)) {
  header('HTTP/1.0 404 Not Found');
  $page_title = 'Memorial não encontrado | Imortalize';
  $memorial = false;
} else {
  $memorial = buscarMemorialPublico($slug);
  if (!$memorial) {
    header('HTTP/1.0 404 Not Found');
    $page_title = 'Memorial não encontrado | Imortalize';
  } else {
    $page_title = htmlspecialchars($memorial['titulo']) . ' | Imortalize';
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $page_title; ?></title>
  <meta name="description" content="<?php echo $memorial ? htmlspecialchars(mb_substr(strip_tags($memorial['biografia'] ?? ''), 0, 160)) : 'Memorial'; ?>">
  <link rel="stylesheet" href="css/site.css">
  <style>
    .memorial-publico-page {
      min-height: 100vh;
      padding: 0 0 4rem;
    }
    .memorial-publico-inner {
      max-width: 720px;
      margin: 0 auto;
    }
    .memorial-publico-capa {
      width: 100%;
      aspect-ratio: 21/9;
      max-height: 280px;
      overflow: hidden;
      background: rgba(0, 0, 0, 0.3);
    }
    .memorial-publico-capa img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .memorial-publico-capa-wrap {
      position: relative;
    }
    .memorial-publico-capa-wrap .memorial-capa-publicado-por {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      margin: 0;
      padding: 0.75rem 1rem;
      background: rgba(0, 0, 0, 0.55);
      color: rgba(240, 242, 235, 0.95);
      font-size: 0.875rem;
      z-index: 2;
    }
    .memorial-publico-perfil-wrap {
      padding: 0 1rem;
      margin-top: -3rem;
      position: relative;
      z-index: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .memorial-publico-nome {
      margin: 0.5rem 0 0;
      text-align: center;
      color: #f0f2eb;
      font-size: 1.125rem;
      font-weight: 500;
    }
    .memorial-publico-datas {
      margin: 0.4rem 0 0;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: center;
      gap: 0.5rem 0.75rem;
      font-size: 0.875rem;
      color: #b8c4a8;
    }
    .memorial-publico-datas .memorial-datas-item {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
    }
    .memorial-publico-datas .memorial-datas-icon {
      flex-shrink: 0;
    }
    .memorial-publico-datas .memorial-datas-sep {
      color: rgba(184, 196, 168, 0.5);
    }
    .memorial-publico-perfil {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      overflow: hidden;
      border: 4px solid rgba(26, 32, 22, 1);
      background: rgba(0, 0, 0, 0.3);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
    }
    .memorial-publico-perfil img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .memorial-publico-header {
      padding: 1rem 1rem 0;
      margin-bottom: 1.5rem;
      text-align: center;
    }
    .memorial-publico-header h1 {
      font-size: clamp(1.75rem, 4vw, 2.25rem);
      font-weight: 600;
      margin: 0 0 0.25rem;
      color: #f0f2eb;
    }
    .memorial-publico-header .subtitulo {
      font-size: 0.9375rem;
      color: #b8c4a8;
      margin: 0 0 0.5rem;
    }
    .memorial-publico-mensagem-curta {
      font-size: 1rem;
      line-height: 1.6;
      color: #b8c4a8;
      margin: 0 0 2rem;
    }
    .memorial-publico-tabs {
      display: flex;
      gap: 0;
      padding: 0 1rem;
      margin-top: 2rem;
      margin-bottom: 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .memorial-publico-tab {
      font-size: 1.125rem;
      font-weight: 600;
      color: #b8c4a8;
      padding: 0.75rem 1.25rem 0.5rem;
      margin-bottom: -1px;
      border: none;
      border-bottom: 3px solid transparent;
      background: none;
      cursor: pointer;
      font-family: inherit;
      transition: color 0.2s, border-color 0.2s;
    }
    .memorial-publico-tab:hover {
      color: #f0f2eb;
    }
    .memorial-publico-tab[aria-selected="true"] {
      color: #f0f2eb;
      border-bottom-color: #b8c4a8;
    }
    .memorial-publico-panel {
      display: none;
      padding: 1.5rem 1rem 2.5rem;
      margin-bottom: 0;
    }
    .memorial-publico-panel[aria-hidden="false"] {
      display: block;
    }
    .memorial-publico-section-title {
      font-size: 1.125rem;
      font-weight: 600;
      color: #f0f2eb;
      margin: 0 0 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .memorial-publico-bio {
      font-size: 1.0625rem;
      line-height: 1.75;
      color: #b8c4a8;
      margin-bottom: 0;
      padding: 0;
    }
    .memorial-publico-bio p {
      margin: 0 0 1rem;
    }
    .memorial-publico-memorias {
      padding: 0;
    }
    .memorial-publico-grade {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
      gap: 0.75rem;
      margin-bottom: 2rem;
    }
    .memorial-publico-grade-item {
      aspect-ratio: 1;
      border-radius: 12px;
      overflow: hidden;
      background: rgba(0, 0, 0, 0.2);
    }
    .memorial-publico-grade-item img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .memorial-publico-footer {
      margin-top: 3rem;
      padding: 2rem 1rem;
      text-align: center;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      background: rgba(0, 0, 0, 0.2);
    }
    .memorial-publico-footer__inner {
      max-width: 480px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.75rem;
    }
    .memorial-publico-footer__logo {
      display: inline-block;
      opacity: 0.9;
      transition: opacity 0.2s;
    }
    .memorial-publico-footer__logo:hover {
      opacity: 1;
    }
    .memorial-publico-footer__logo img {
      display: block;
      height: 26px;
      width: auto;
    }
    .memorial-publico-footer__link {
      margin: 0;
      font-size: 0.9375rem;
    }
    .memorial-publico-footer__link a {
      color: #b8c4a8;
      text-decoration: none;
    }
    .memorial-publico-footer__link a:hover {
      color: #f0f2eb;
    }
    .memorial-publico-footer__copy {
      margin: 0;
      font-size: 0.75rem;
      color: rgba(184, 196, 168, 0.7);
    }
    .memorial-404 {
      text-align: center;
      padding: 3rem 1rem;
    }
    .memorial-404 h1 {
      font-size: 1.5rem;
      color: #f0f2eb;
      margin: 0 0 0.5rem;
    }
    .memorial-404 p {
      color: #b8c4a8;
      margin: 0 0 1.5rem;
    }
    .memorial-404 a {
      color: #b8c4a8;
      text-decoration: none;
    }
    .memorial-404 a:hover {
      color: #f0f2eb;
    }
    /* Loading overlay — esconde até as imagens principais carregarem */
    .memorial-publico-loading {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: #192112;
      transition: opacity 0.4s ease, visibility 0.4s ease;
    }
    .memorial-publico-loading.hidden {
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
    }
    .memorial-publico-loading__spinner {
      width: 48px;
      height: 48px;
      border: 4px solid rgba(184, 196, 168, 0.2);
      border-top-color: #b8c4a8;
      border-radius: 50%;
      animation: memorial-publico-spin 0.9s linear infinite;
    }
    .memorial-publico-loading__text {
      margin-top: 1rem;
      font-size: 0.9375rem;
      color: #b8c4a8;
    }
    @keyframes memorial-publico-spin {
      to { transform: rotate(360deg); }
    }
    .memorial-publico-inner.memorial-publico-pending {
      opacity: 0;
    }
    .memorial-publico-inner.memorial-publico-content-ready {
      animation: memorial-publico-fade-in 0.5s ease forwards;
    }
    @keyframes memorial-publico-fade-in {
      from { opacity: 0; }
      to { opacity: 1; }
    }
  </style>
</head>
<body>
  <?php if ($memorial) : ?>
  <div class="memorial-publico-loading" id="memorial-publico-loading" aria-live="polite" aria-busy="true">
    <div class="memorial-publico-loading__spinner" aria-hidden="true"></div>
    <p class="memorial-publico-loading__text">Carregando memorial…</p>
  </div>
  <?php endif; ?>
  <header class="site-header" role="banner">
    <div class="site-header__inner">
      <a href="index.php" class="site-header__logo-link" aria-label="Imortalize - Página inicial">
        <img src="img/logo branca sem fundo.png" alt="Imortalize" class="site-header__logo" width="140" height="36">
      </a>
      <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-hidden="true" hidden>
      <label for="nav-toggle" class="nav-toggle-label" aria-label="Abrir ou fechar menu">
        <span class="nav-toggle-icon"></span>
      </label>
      <nav class="site-nav" aria-label="Navegação principal">
        <a href="index.php" class="nav-link">Início</a>
        <a href="index.php#planos" class="nav-link">Planos</a>
        <?php if ($logado) : ?>
        <a href="dashboard.php" class="btn-minha-area">Minha área</a>
        <a href="logout.php" class="btn-sair">Sair</a>
        <?php else : ?>
        <a href="login.php" class="btn-entrar">Entrar</a>
        <a href="cadastro.php" class="btn-criar-conta">Criar conta</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main class="memorial-publico-page">
    <div class="memorial-publico-inner<?php echo $memorial ? ' memorial-publico-pending' : ''; ?>">
      <?php if (!$memorial) : ?>
      <div class="memorial-404">
        <h1>Memorial não encontrado</h1>
        <p>Este link pode estar incorreto ou o memorial ainda não está publicado.</p>
        <a href="index.php">← Voltar ao início</a>
      </div>
      <?php else :
      $fotos_publico = array_filter($memorial['medias'], function ($m) { return $m['tipo'] === 'foto'; });
      $total_medias = count($memorial['medias']);
      $bio_texto = isset($memorial['biografia']) ? strip_tags($memorial['biografia']) : '';
      $mensagem_curta = trim(mb_substr($bio_texto, 0, 200));
      if (mb_strlen($bio_texto) > 200) {
        $mensagem_curta .= '…';
      }
      // Fotos que não são perfil nem capa para a grade "Memórias"
      $ids_perfil_capa = [];
      if (!empty($memorial['foto_perfil']['id'])) { $ids_perfil_capa[] = (int) $memorial['foto_perfil']['id']; }
      if (!empty($memorial['foto_capa']['id'])) { $ids_perfil_capa[] = (int) $memorial['foto_capa']['id']; }
      $fotos_grade = array_filter($fotos_publico, function ($m) use ($ids_perfil_capa) {
        return !in_array((int) $m['id'], $ids_perfil_capa, true);
      });
      $fotos_grade = array_values($fotos_grade);
      ?>
      <?php
      $publicador_texto_publico = '';
      $pub_nome_p = trim($memorial['publicador_nome'] ?? '');
      $relacao_label_publico = function_exists('getRelacaoPublicadorLabel') ? getRelacaoPublicadorLabel($memorial['relacao_publicador'] ?? null) : ($memorial['relacao_publicador'] ?? '');
      if ($pub_nome_p !== '' || $relacao_label_publico !== '') {
        $partes_p = [];
        if ($pub_nome_p !== '') $partes_p[] = 'Publicado por ' . htmlspecialchars($pub_nome_p);
        if ($relacao_label_publico !== '') $partes_p[] = '(' . htmlspecialchars($relacao_label_publico) . ')';
        $publicador_texto_publico = implode(' ', $partes_p);
      }
      ?>
      <?php if (!empty($memorial['foto_capa'])) : ?>
      <div class="memorial-publico-capa-wrap">
        <?php if ($publicador_texto_publico !== '') : ?>
        <p class="memorial-capa-publicado-por"><?php echo $publicador_texto_publico; ?></p>
        <?php endif; ?>
        <div class="memorial-publico-capa">
          <img src="<?php echo htmlspecialchars($memorial['foto_capa']['caminho']); ?>" alt="Capa do memorial" loading="eager">
        </div>
      </div>
      <?php else : ?>
      <div class="memorial-publico-capa-wrap">
        <?php if ($publicador_texto_publico !== '') : ?>
        <p class="memorial-capa-publicado-por"><?php echo $publicador_texto_publico; ?></p>
        <?php endif; ?>
        <div class="memorial-publico-capa" style="min-height: 140px; display: flex; align-items: center; justify-content: center;">
          <span style="color: rgba(184, 196, 168, 0.4); font-size: 0.875rem;">Sem foto de capa</span>
        </div>
      </div>
      <?php endif; ?>

      <div class="memorial-publico-perfil-wrap">
        <?php if (!empty($memorial['foto_perfil'])) : ?>
        <div class="memorial-publico-perfil">
          <img src="<?php echo htmlspecialchars($memorial['foto_perfil']['caminho']); ?>" alt="<?php echo htmlspecialchars($memorial['titulo']); ?>" loading="eager">
        </div>
        <?php else : ?>
        <div class="memorial-publico-perfil" style="background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center;">
          <span style="color: rgba(184, 196, 168, 0.5); font-size: 2rem;" aria-hidden="true">👤</span>
        </div>
        <?php endif; ?>
        <?php if (!empty(trim($memorial['nome'] ?? ''))) : ?>
        <p class="memorial-publico-nome"><?php echo htmlspecialchars(trim($memorial['nome'])); ?></p>
        <?php endif; ?>
        <?php
        $dn_publico = !empty($memorial['data_nascimento']) ? date('d/m/Y', strtotime($memorial['data_nascimento'])) : '';
        $do_publico = !empty($memorial['data_obito']) ? date('d/m/Y', strtotime($memorial['data_obito'])) : '';
        if ($dn_publico !== '' || $do_publico !== '') :
        ?>
        <p class="memorial-publico-datas" aria-label="<?php echo $dn_publico !== '' ? 'Nascimento: ' . $dn_publico : ''; ?><?php echo ($dn_publico !== '' && $do_publico !== '') ? ' ' : ''; ?><?php echo $do_publico !== '' ? 'Óbito: ' . $do_publico : ''; ?>">
          <?php if ($dn_publico !== '') : ?>
          <span class="memorial-datas-item"><svg class="memorial-datas-icon memorial-datas-icon-nascimento" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg><span><?php echo $dn_publico; ?></span></span>
          <?php endif; ?>
          <?php if ($dn_publico !== '' && $do_publico !== '') : ?><span class="memorial-datas-sep" aria-hidden="true">·</span><?php endif; ?>
          <?php if ($do_publico !== '') : ?>
          <span class="memorial-datas-item"><svg class="memorial-datas-icon memorial-datas-icon-obito" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2v8h6v2h-6v10h-2V12H4v-2h6V2h2z"/></svg><span><?php echo $do_publico; ?></span></span>
          <?php endif; ?>
        </p>
        <?php endif; ?>
      </div>

      <div class="memorial-publico-header">
        <h1><?php echo htmlspecialchars($memorial['titulo']); ?></h1>
        <p class="subtitulo">Memorial Imortalize</p>
        <?php if ($mensagem_curta !== '') : ?>
        <p class="memorial-publico-mensagem-curta"><?php echo nl2br(htmlspecialchars($mensagem_curta)); ?></p>
        <?php endif; ?>
      </div>

      <div class="memorial-publico-tabs" role="tablist" aria-label="História e Memórias">
        <button type="button" class="memorial-publico-tab" role="tab" id="tab-historia" aria-selected="true" aria-controls="panel-historia">História</button>
        <button type="button" class="memorial-publico-tab" role="tab" id="tab-memorias" aria-selected="false" aria-controls="panel-memorias">Memórias</button>
      </div>

      <div class="memorial-publico-panel" id="panel-historia" role="tabpanel" aria-labelledby="tab-historia" aria-hidden="false">
        <div class="memorial-publico-bio">
          <?php if (!empty($memorial['biografia'])) : ?>
          <?php echo nl2br(htmlspecialchars($memorial['biografia'])); ?>
          <?php else : ?>
          <p style="color: #8a9a7a;">Nenhuma história publicada.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="memorial-publico-panel" id="panel-memorias" role="tabpanel" aria-labelledby="tab-memorias" aria-hidden="true">
        <div class="memorial-publico-memorias">
          <?php if (count($fotos_grade) > 0) : ?>
          <div class="memorial-publico-grade">
            <?php foreach ($fotos_grade as $m) : ?>
            <div class="memorial-publico-grade-item">
              <img src="<?php echo htmlspecialchars($m['caminho']); ?>" alt="<?php echo htmlspecialchars($m['legenda'] ?? 'Foto'); ?>" loading="lazy">
            </div>
            <?php endforeach; ?>
          </div>
          <?php else : ?>
          <p style="color: #8a9a7a;">Nenhuma foto ainda.</p>
          <?php endif; ?>
        </div>
      </div>

      <script>
      (function() {
        var tablist = document.querySelector('.memorial-publico-tabs');
        if (!tablist) return;
        var tabs = tablist.querySelectorAll('.memorial-publico-tab');
        var panels = document.querySelectorAll('.memorial-publico-panel');
        function selectTab(selectedTab) {
          var id = selectedTab.getAttribute('aria-controls');
          tabs.forEach(function(t) { t.setAttribute('aria-selected', t === selectedTab ? 'true' : 'false'); });
          panels.forEach(function(p) {
            var show = p.id === id;
            p.setAttribute('aria-hidden', show ? 'false' : 'true');
          });
        }
        tabs.forEach(function(t) {
          t.addEventListener('click', function() { selectTab(t); });
        });
      })();
      </script>
      <script>
      (function() {
        var loadingEl = document.getElementById('memorial-publico-loading');
        var innerEl = document.querySelector('.memorial-publico-inner.memorial-publico-pending');
        if (!loadingEl || !innerEl) return;
        var imgs = [];
        innerEl.querySelectorAll('img').forEach(function(img) {
          if (img.getAttribute('loading') !== 'lazy') {
            imgs.push(img);
          }
        });
        var minShowMs = 400;
        var maxWaitMs = 6000;
        var start = Date.now();
        function finish() {
          loadingEl.classList.add('hidden');
          innerEl.classList.remove('memorial-publico-pending');
          innerEl.classList.add('memorial-publico-content-ready');
          loadingEl.setAttribute('aria-busy', 'false');
        }
        function waitMinThenFinish() {
          var elapsed = Date.now() - start;
          setTimeout(finish, Math.max(0, minShowMs - elapsed));
        }
        if (imgs.length === 0) {
          waitMinThenFinish();
          return;
        }
        var timeouter = setTimeout(waitMinThenFinish, maxWaitMs);
        var pending = imgs.length;
        function oneDone() {
          pending--;
          if (pending === 0) {
            clearTimeout(timeouter);
            waitMinThenFinish();
          }
        }
        imgs.forEach(function(img) {
          if (img.complete) {
            oneDone();
          } else {
            img.addEventListener('load', oneDone);
            img.addEventListener('error', oneDone);
          }
        });
      })();
      </script>

      <footer class="memorial-publico-footer" role="contentinfo">
        <?php $site_base_footer = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/'; ?>
        <div class="memorial-publico-footer__inner">
          <a href="<?php echo htmlspecialchars($site_base_footer); ?>index.php" class="memorial-publico-footer__logo" aria-label="Conheça o Imortalize">
            <img src="<?php echo htmlspecialchars($site_base_footer); ?>img/logo branca sem fundo.png" alt="Imortalize" width="100" height="26" loading="lazy">
          </a>
          <p class="memorial-publico-footer__link">
            <a href="<?php echo htmlspecialchars($site_base_footer); ?>index.php">Conheça o Imortalize</a>
          </p>
          <p class="memorial-publico-footer__copy">&copy; <?php echo date('Y'); ?> Imortalize</p>
        </div>
      </footer>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
