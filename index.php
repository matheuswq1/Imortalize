<?php
/**
 * Página inicial — Imortalize
 * Links variam conforme o usuário estar logado; planos vêm do banco; newsletter funcional.
 */
session_start();
require_once __DIR__ . '/config/database.php';

$logado = !empty($_SESSION['user_id']);
$url_comprar = $logado ? 'alterar-plano.php' : 'cadastro.php';
$url_criar_memorial = $logado ? 'memoriais-criar.php' : 'cadastro.php';
$url_memoriais = $logado ? 'dashboard.php' : 'login.php';

$planos = function_exists('listarPlanos') ? listarPlanos() : [];
$newsletter_msg = '';
if (isset($_GET['newsletter'])) {
  if ($_GET['newsletter'] === 'ok') {
    $newsletter_msg = 'E-mail cadastrado! Em breve você receberá nossas novidades.';
  } elseif ($_GET['newsletter'] === 'ja_cadastrado') {
    $newsletter_msg = 'Este e-mail já está inscrito na nossa newsletter.';
  } else {
    $newsletter_msg = 'Não foi possível inscrever. Verifique o e-mail e tente novamente.';
  }
}
$contato_msg = '';
if (isset($_GET['contato'])) {
  if ($_GET['contato'] === 'ok') {
    $contato_msg = 'Mensagem enviada! Entraremos em contato em breve.';
  } else {
    $contato_msg = 'Não foi possível enviar. Verifique os dados e tente novamente.';
  }
}

$page_title = 'Imortalize — Conecte memórias, imortalize momentos';
$current_page = 'home';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <meta name="description" content="Um presente único e especial: Imortalize suas memórias em aço inoxidável, resistente como os sentimentos que carrega.">
  <link rel="stylesheet" href="css/site.css">
  <link rel="stylesheet" href="css/home.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body class="page-home">
  <a href="#main-content" class="skip-link">Pular para o conteúdo</a>
  <!-- Header -->
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
        <a href="index.php#quem-somos" class="nav-link">Quem Somos</a>
        <a href="index.php#contato" class="nav-link">Contato</a>
        <a href="index.php#planos" class="nav-link">Comprar</a>
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

  <main id="main-content" class="main-content" role="main">
    <!-- Hero (inspirado em layout split: esquerda clara, direita gradiente + visual) -->
    <section class="hero hero--split" aria-labelledby="hero-title">
      <div class="hero__left">
        <div class="hero__inner">
          <p class="hero__label">Mais que uma placa</p>
          <h1 id="hero-title" class="hero__title">Imortalize Momentos</h1>
          <p class="hero__text">
            Um presente único e especial: suas memórias em aço inoxidável, resistente como os sentimentos que carrega.
          </p>
          <a href="<?php echo $logado ? 'alterar-plano.php' : 'index.php#planos'; ?>" class="hero__cta">Imortalize Já!</a>
          <div class="hero__features">
            <div class="hero__feature">
              <span class="hero__feature-icon" aria-hidden="true"></span>
              <div>
                <strong>Aço inoxidável</strong>
                <span>Dura para sempre</span>
              </div>
            </div>
            <div class="hero__feature">
              <span class="hero__feature-icon hero__feature-icon--qr" aria-hidden="true"></span>
              <div>
                <strong>QR Code</strong>
                <span>Conecte ao digital</span>
              </div>
            </div>
            <div class="hero__feature">
              <span class="hero__feature-icon" aria-hidden="true"></span>
              <div>
                <strong>Memorial único</strong>
                <span>Histórias preservadas</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="hero__right">
        <img src="img/svg/hero-deco-qr.svg" alt="" class="hero__deco hero__deco--qr" width="120" height="120">
        <img src="img/svg/hero-deco-leaf.svg" alt="" class="hero__deco hero__deco--leaf" width="90" height="90">
        <div class="hero__visual">
          <img src="img/pomba.png" alt="Pomba com placa Imortalize — memorial em aço inoxidável" width="400" height="auto" loading="eager">
        </div>
      </div>
    </section>

    <!-- Quem somos (destaque) -->
    <section id="quem-somos" class="section quem-somos" aria-labelledby="quem-somos-title">
      <h2 id="quem-somos-title" class="section__title">Quem somos</h2>
      <div class="quem-somos__conteudo">
        <div class="quem-somos__texto">
          <p class="quem-somos__lead">
            Na Imortalize, acreditamos que cada história merece ser lembrada.
          </p>
          <p>
            Nossa plataforma transforma lembranças em memoriais digitais que permanecem vivos no tempo, acessíveis a qualquer hora e em qualquer lugar. Oferecemos placas em aço inoxidável com QR Code, unindo o físico ao digital para que as memórias de quem amamos nunca se apaguem.
          </p>
          <p>
            Trabalhamos com tecnologia, design e cuidado para que cada memorial seja único e resistente — assim como os sentimentos que carrega.
          </p>
        </div>
        <div class="quem-somos__media" aria-hidden="true">
          <div class="quem-somos__img-wrap">
            <img src="img/mulher escaneando QR code na lapide.jpg" alt="Pessoa escaneando QR Code em uma lápide — memorial Imortalize" width="480" height="320" loading="lazy">
          </div>
        </div>
      </div>
    </section>

    <!-- Conecte memórias -->
    <section id="conecte-memorias" class="section" aria-labelledby="conecte-title">
      <h2 id="conecte-title" class="section__title">Conecte memórias</h2>
      <p class="section__subtitle">
        Imortalize — a conexão entre lembranças e eternidade, feita em aço inoxidável para durar para sempre.
      </p>
      <div class="conecte-memorias">
        <div class="conecte-memorias__images">
          <div class="conecte-memorias__image">
            <img src="img/mão segurando um qr code da imortalize.png" alt="Mão segurando QR Code Imortalize" width="400" height="auto" loading="lazy">
          </div>
          <div class="conecte-memorias__thumbs">
            <img src="img/imgi_22_imortalize_qr-code-01.jpg" alt="QR Code Imortalize em aço" width="120" height="120" loading="lazy">
            <img src="img/imgi_20_imortalize_qr-code-02.jpg" alt="Placa com QR Code" width="120" height="120" loading="lazy">
            <img src="img/imgi_21_imortalize_qr-code-03.jpg" alt="Memorial com QR Code" width="120" height="120" loading="lazy">
            <img src="img/imgi_23_imortalize_qr-code-05.jpg" alt="QR Code em aço inoxidável" width="120" height="120" loading="lazy">
          </div>
        </div>
        <div class="conecte-memorias__text">
          <h3>Imortalize memórias</h3>
          <p>
            Conecte-se com suas lembranças em um presente especial e significativo, feito em aço inoxidável com durabilidade para toda a vida.
          </p>
          <a href="<?php echo htmlspecialchars($url_criar_memorial); ?>" class="hero__cta" style="margin-top: 1rem;">Criar memorial</a>
        </div>
      </div>
    </section>

    <!-- Veja como é fácil -->
    <section id="como-funciona" class="section como-funciona" aria-labelledby="como-funciona-title">
      <h2 id="como-funciona-title" class="section__title">Veja como é fácil</h2>
      <p class="section__subtitle">
        Imortalize: lembranças eternas em aço inoxidável, com significado e durabilidade que atravessam o tempo.
      </p>
      <div class="passos" id="passos-container">
        <div class="passo">
          <span class="passo__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></span>
          <span class="passo__numero" aria-hidden="true">1</span>
          <h3 class="passo__titulo">Crie um memorial</h3>
          <p class="passo__texto">Com as memórias do seu ente querido.</p>
        </div>
        <div class="passo">
          <span class="passo__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg></span>
          <span class="passo__numero" aria-hidden="true">2</span>
          <h3 class="passo__titulo">Instale de forma simples</h3>
          <p class="passo__texto">Pode ser feita com o material adesivo.</p>
        </div>
        <div class="passo">
          <span class="passo__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></span>
          <span class="passo__numero" aria-hidden="true">3</span>
          <h3 class="passo__titulo">Escaneie o código</h3>
          <p class="passo__texto">Abra a câmera do celular e faça a leitura do QR Code.</p>
        </div>
        <div class="passo">
          <span class="passo__icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></span>
          <span class="passo__numero" aria-hidden="true">4</span>
          <h3 class="passo__titulo">Acesse as memórias</h3>
          <p class="passo__texto">Momentos inesquecíveis, agora imortalizados.</p>
        </div>
      </div>
    </section>

    <!-- Planos (dinâmicos do banco) -->
    <section id="planos" class="section" aria-labelledby="planos-title">
      <h2 id="planos-title" class="section__title">Escolha seu plano</h2>
      <p class="section__subtitle">
        Conheça os planos que a Imortalize oferece.
      </p>
      <div class="planos">
        <?php
        $ordem_destaque = 2;
        if (!empty($planos)) :
          foreach ($planos as $i => $p) :
            $é_destaque = ((int) $p['id']) === $ordem_destaque;
            $classe = 'plano';
            if ($é_destaque) $classe .= ' plano--destaque';
        ?>
        <article class="<?php echo htmlspecialchars($classe); ?>">
          <?php if ($é_destaque) : ?>
          <span class="plano__badge" aria-hidden="true">Mais popular</span>
          <?php endif; ?>
          <?php
            $planos_imgs = ['img/imgi_8_qr-code-02.jpg', 'img/imgi_14_qr-code-04.png', 'img/imgi_15_qr-code-03.png', 'img/imgi_23_imortalize_qr-code-05.jpg'];
            $planos_img = $planos_imgs[($i ?? 0) % count($planos_imgs)];
          ?>
          <div class="plano__img-wrap"><img src="<?php echo htmlspecialchars($planos_img); ?>" alt="" width="200" height="140" loading="lazy"></div>
          <h3 class="plano__nome"><?php echo htmlspecialchars($p['nome']); ?></h3>
          <p class="plano__destaque"><?php echo htmlspecialchars($p['descricao_curta'] ?? ''); ?></p>
          <ul class="plano__lista">
            <?php foreach (isset($p['beneficios']) ? $p['beneficios'] : [] as $item) : ?>
            <li><?php echo htmlspecialchars($item); ?></li>
            <?php endforeach; ?>
          </ul>
          <div class="plano__cta-wrap">
            <a href="<?php echo htmlspecialchars($url_comprar); ?>" class="plano__cta"><?php echo $logado ? 'Escolher plano' : 'Comprar'; ?></a>
          </div>
        </article>
        <?php
          endforeach;
        else :
        ?>
        <article class="plano">
          <h3 class="plano__nome">Planos em breve</h3>
          <p class="plano__destaque">Entre em contato para conhecer nossas opções.</p>
          <div class="plano__cta-wrap">
            <a href="<?php echo htmlspecialchars($url_comprar); ?>" class="plano__cta"><?php echo $logado ? 'Ver planos' : 'Criar conta'; ?></a>
          </div>
        </article>
        <?php endif; ?>
      </div>
    </section>

    <!-- Banner O amor é eterno -->
    <section class="home-banner" aria-labelledby="banner-title">
      <div class="home-banner__inner">
        <div class="home-banner__qr-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="14" height="14" rx="2"/><rect x="22" y="4" width="6" height="6" rx="1"/><rect x="32" y="4" width="6" height="6" rx="1"/><rect x="22" y="14" width="6" height="6" rx="1"/><rect x="32" y="14" width="6" height="6" rx="1"/><rect x="4" y="22" width="6" height="6" rx="1"/><rect x="14" y="22" width="6" height="6" rx="1"/><rect x="24" y="22" width="14" height="14" rx="2"/><rect x="4" y="32" width="6" height="6" rx="1"/><rect x="14" y="32" width="6" height="6" rx="1"/></svg>
        </div>
        <h2 id="banner-title" class="home-banner__title">O amor é eterno, agora as memórias também</h2>
        <p class="home-banner__text">Imortalize suas lembranças em um presente especial e duradouro — feito em aço inoxidável. QR Code em aço para lápides, urnas, quadros e onde a memória merece ser celebrada.</p>
        <div class="home-banner__galeria" aria-hidden="true">
          <img src="img/imgi_14_qr-code-04.png" alt="" width="160" height="160" loading="lazy">
          <img src="img/imgi_15_qr-code-03.png" alt="" width="160" height="160" loading="lazy">
          <img src="img/imgi_8_qr-code-02.jpg" alt="" width="160" height="160" loading="lazy">
          <img src="img/imgi_23_imortalize_qr-code-05.jpg" alt="" width="160" height="160" loading="lazy">
        </div>
      </div>
    </section>

    <!-- Memoriais -->
    <section id="memoriais" class="section memoriais-preview" aria-labelledby="memoriais-title">
      <h2 id="memoriais-title" class="section__title">Memoriais</h2>
      <p class="section__subtitle">
        Imortalize suas lembranças em um presente especial e duradouro — feito em aço inoxidável.
      </p>
      <div class="memoriais-cta">
        <a href="<?php echo htmlspecialchars($url_memoriais); ?>"><?php echo $logado ? 'Ver meus memoriais' : 'Acessar ou criar memorial'; ?></a>
      </div>
    </section>

    <!-- Contato / Newsletter -->
    <section id="contato" class="contato-newsletter" aria-labelledby="contato-title">
      <h2 id="contato-title">Fale conosco</h2>

      <div class="contato-bloco contato-bloco--form">
        <h3 class="contato-bloco__titulo">Envie sua mensagem</h3>
        <?php if ($contato_msg !== '') : ?>
        <p class="newsletter-feedback contato-feedback" role="status"><?php echo htmlspecialchars($contato_msg); ?></p>
        <?php endif; ?>
        <form class="form-contato" action="contato-salvar.php" method="post" aria-label="Formulário Fale conosco">
          <label for="contato-nome">Nome</label>
          <input type="text" id="contato-nome" name="nome" placeholder="Seu nome" required maxlength="255" aria-required="true">
          <label for="contato-email">E-mail</label>
          <input type="email" id="contato-email" name="email" placeholder="Seu e-mail" required maxlength="255" aria-required="true">
          <label for="contato-mensagem">Mensagem</label>
          <textarea id="contato-mensagem" name="mensagem" placeholder="Sua mensagem" required rows="4" maxlength="5000" aria-required="true"></textarea>
          <button type="submit">Enviar mensagem</button>
        </form>
      </div>

      <div class="contato-bloco contato-bloco--newsletter">
        <h3 class="contato-bloco__titulo">Newsletter</h3>
        <p>Fique por dentro das novidades! Assine nossa newsletter.</p>
        <?php if ($newsletter_msg !== '') : ?>
        <p class="newsletter-feedback" role="status"><?php echo htmlspecialchars($newsletter_msg); ?></p>
        <?php endif; ?>
        <form class="form-inline" action="newsletter-inscrever.php" method="post" aria-label="Assinar newsletter">
          <input type="hidden" name="redirect" value="index.php#contato">
          <input type="email" name="email" placeholder="Seu e-mail" required aria-label="E-mail para newsletter">
          <button type="submit">Inscrever-se</button>
        </form>
      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer class="site-footer" role="contentinfo">
    <div class="site-footer__inner">
      <?php $footer_social = function_exists('getSiteSocialLinks') ? getSiteSocialLinks() : []; ?>
      <div class="site-footer__brand">
        <a href="index.php" class="site-footer__logo-link" aria-label="Imortalize - Página inicial">
          <img src="img/logo branca sem fundo.png" alt="Imortalize" class="site-footer__logo" width="120" height="30" loading="lazy">
        </a>
        <p class="site-footer__tagline">O amor é eterno, agora as memórias também.</p>
        <p class="site-footer__slogan">Memoriais digitais com QR Code, fotos e biografia para perpetuar a história de quem amamos.</p>
        <?php if (!empty($footer_social)) : ?>
        <div class="site-footer__social" aria-label="Redes sociais">
          <?php if (!empty($footer_social['facebook'])) : ?>
          <a href="<?php echo htmlspecialchars($footer_social['facebook']); ?>" target="_blank" rel="noopener noreferrer" class="site-footer__social-link" aria-label="Facebook">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          </a>
          <?php endif; ?>
          <?php if (!empty($footer_social['instagram'])) : ?>
          <a href="<?php echo htmlspecialchars($footer_social['instagram']); ?>" target="_blank" rel="noopener noreferrer" class="site-footer__social-link" aria-label="Instagram">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
          </a>
          <?php endif; ?>
          <?php if (!empty($footer_social['whatsapp'])) : ?>
          <a href="<?php echo htmlspecialchars($footer_social['whatsapp']); ?>" target="_blank" rel="noopener noreferrer" class="site-footer__social-link" aria-label="WhatsApp">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          </a>
          <?php endif; ?>
          <?php if (!empty($footer_social['youtube'])) : ?>
          <a href="<?php echo htmlspecialchars($footer_social['youtube']); ?>" target="_blank" rel="noopener noreferrer" class="site-footer__social-link" aria-label="YouTube">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
          </a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="site-footer__services">
        <h3 class="site-footer__heading">O que fazemos</h3>
        <ul class="site-footer__list">
          <li>Memorial digital personalizado</li>
          <li>QR Code para lápides e lembranças</li>
          <li>Fotos, biografia e datas</li>
          <li>Link permanente para compartilhar</li>
        </ul>
      </div>
      <div class="site-footer__contact">
        <h3 class="site-footer__heading">Fale conosco</h3>
        <address class="site-footer__address">
          <p class="site-footer__address-line">Rua São Paulo, 3049 — Sala 02<br>Patrimônio Novo</p>
          <p><a href="tel:+<?php echo htmlspecialchars(getSiteContactPhoneLink()); ?>" class="site-footer__tel"><?php echo htmlspecialchars(getSiteContactPhone()); ?></a></p>
        </address>
      </div>
      <div class="site-footer__nav">
        <h3 class="site-footer__heading">Links úteis</h3>
        <ul class="site-footer__links">
          <li><a href="index.php#quem-somos">Quem somos</a></li>
          <li><a href="index.php#como-funciona">Como funciona</a></li>
          <li><a href="index.php#planos">Nossos planos</a></li>
          <li><a href="index.php#contato">Contato</a></li>
          <?php if ($logado) : ?>
          <li><a href="dashboard.php">Minha área</a></li>
          <li><a href="logout.php">Sair</a></li>
          <?php else : ?>
          <li><a href="login.php">Entrar</a></li>
          <li><a href="cadastro.php">Criar conta</a></li>
          <?php endif; ?>
        </ul>
        <p class="site-footer__newsletter-cta">
          <a href="index.php#contato">Receba novidades por e-mail</a>
        </p>
      </div>
    </div>
    <div class="site-footer__bottom">
      <p class="site-footer__copy">&copy; <?php echo date('Y'); ?> Imortalize. Todos os direitos reservados.</p>
      <p class="site-footer__tagline-bottom">Feito com carinho para eternizar quem amamos.</p>
    </div>
  </footer>

  <script>
  (function() {
    var sections = document.querySelectorAll('.section, .hero, .como-funciona, .contato-newsletter, .quem-somos, .home-banner');
    var passos = document.getElementById('passos-container');
    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) entry.target.classList.add('in-view');
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0 });
    sections.forEach(function(el) { if (el) observer.observe(el); });
    if (passos) observer.observe(passos);
  })();
  </script>
</body>
</html>
