<?php
/**
 * Tela de Login — Imortalize
 * Conecta ao banco MySQL e valida credenciais.
 * Opção "Lembrar-me" salva o e-mail em cookie para preencher na próxima visita.
 */
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

define('COOKIE_LEMBRAR_EMAIL', 'imortalize_remember_email');
define('COOKIE_LEMBRAR_DAYS', 30);

$page_title = 'Acesse sua conta | Imortalize';
$erro = '';
$email_preenchido = '';
$lembrar_checked = false;

if (isset($_SESSION['login_erro']) && is_string($_SESSION['login_erro'])) {
  $erro = $_SESSION['login_erro'];
  unset($_SESSION['login_erro']);
}

// Carrega e-mail salvo do cookie
if (isset($_COOKIE[COOKIE_LEMBRAR_EMAIL]) && is_string($_COOKIE[COOKIE_LEMBRAR_EMAIL])) {
  $email_preenchido = trim($_COOKIE[COOKIE_LEMBRAR_EMAIL]);
  if (filter_var($email_preenchido, FILTER_VALIDATE_EMAIL)) {
    $lembrar_checked = true;
  } else {
    $email_preenchido = '';
  }
}

// POST: valida CSRF e credenciais
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email']) && isset($_POST['senha'])) {
  if (!csrf_validate()) {
    $erro = 'Requisição inválida. Tente novamente.';
  } else {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $lembrar = isset($_POST['lembrar']) && $_POST['lembrar'] === '1';

    $user = verificarLogin($email, $senha);
    if ($user) {
      if ($lembrar) {
        setcookie(COOKIE_LEMBRAR_EMAIL, $email, [
          'expires' => time() + (COOKIE_LEMBRAR_DAYS * 86400),
          'path' => '/',
          'domain' => '',
          'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
          'httponly' => false,
          'samesite' => 'Lax'
        ]);
      } else {
        setcookie(COOKIE_LEMBRAR_EMAIL, '', ['expires' => time() - 3600, 'path' => '/']);
      }

      session_regenerate_secure();
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['user_nome'] = $user['nome'];
      $_SESSION['user_email'] = $user['email'];
      header('Location: dashboard.php');
      exit;
    } else {
      $erro = 'E-mail ou senha incorretos. Tente novamente.';
      $email_preenchido = $email;
      $lembrar_checked = isset($_POST['lembrar']) && $_POST['lembrar'] === '1';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <link rel="stylesheet" href="css/login.css">
</head>
<body>
  <div class="login-page-wrap" aria-hidden="true"></div>
  <a href="index.php" class="login-back">← Voltar ao site</a>

  <main class="login-container" role="main">
    <div class="login-card">
      <img src="img/logo branca sem fundo.png" alt="Imortalize" class="login-card__logo" width="220" height="58">
      <h1 class="login-card__title">Bem-vindo de volta</h1>
      <p class="login-card__subtitle">Continue preservando as memórias que mais importam.</p>

      <?php if (isset($_GET['cadastro']) && $_GET['cadastro'] === '1') : ?>
      <div class="login-msg login-msg--success" role="status">
        Conta criada com sucesso. Faça login com seu e-mail e senha.
      </div>
      <?php endif; ?>
      <?php if ($erro) : ?>
      <div id="login-erro" class="login-msg login-msg--erro" role="alert" aria-live="polite">
        <?php echo htmlspecialchars($erro); ?>
        <a href="cadastro.php" class="login-msg__link">Quer criar uma conta?</a>
      </div>
      <?php endif; ?>

      <a href="login-google.php" class="btn-google" aria-label="Continuar com Google">
        <svg class="btn-google__icon" viewBox="0 0 24 24" aria-hidden="true">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        Continuar com Google
      </a>

      <div class="login-divider" aria-hidden="true"><span>ou</span></div>

      <form class="login-form" action="login.php" method="post" aria-label="Formulário de login" id="login-form">
        <?php echo csrf_field(); ?>
        <div class="form-group">
          <label for="email">E-mail</label>
          <div class="input-wrap input-wrap--email">
            <input type="email" id="email" name="email" placeholder="seunome@exemplo.com" autocomplete="email" value="<?php echo htmlspecialchars($email_preenchido); ?>" required autofocus aria-describedby="email-desc" aria-invalid="<?php echo $erro ? 'true' : 'false'; ?>">
            <svg class="input-icon input-icon--email" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
            <span class="input-feedback input-feedback--email" id="email-desc" aria-live="polite"></span>
          </div>
        </div>
        <div class="form-group">
          <label for="senha">Senha</label>
          <div class="input-wrap input-wrap--senha">
            <input type="password" id="senha" name="senha" placeholder="••••••••" autocomplete="current-password" required minlength="6" aria-describedby="senha-desc" aria-invalid="false">
            <svg class="input-icon input-icon--lock" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <button type="button" class="input-toggle-senha" aria-label="Mostrar senha" title="Mostrar senha" aria-pressed="false">
              <svg class="input-toggle-senha__icon input-toggle-senha__icon--hide" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="input-toggle-senha__icon input-toggle-senha__icon--show" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
            <span class="input-feedback input-feedback--senha" id="senha-desc" aria-live="polite"></span>
          </div>
        </div>
        <div class="form-group form-group--checkbox">
          <label class="login-remember">
            <input type="checkbox" name="lembrar" value="1"<?php if ($lembrar_checked) echo ' checked'; ?>>
            <span>Manter conectado neste dispositivo</span>
          </label>
        </div>
        <button type="submit" class="btn-entrar" id="btn-entrar">
          <span class="btn-entrar__text">Entrar</span>
          <span class="btn-entrar__loading" aria-hidden="true" hidden>
            <span class="btn-entrar__spinner"></span>
            Entrando…
          </span>
        </button>
      </form>

      <nav class="login-card__links" aria-label="Links secundários">
        <a href="cadastro.php">Criar conta</a>
        <a href="<?php echo file_exists(__DIR__ . '/recuperar-senha.php') ? 'recuperar-senha.php' : '#'; ?>">Esqueceu a senha?</a>
      </nav>

      <footer class="login-card__trust" aria-label="Segurança e privacidade">
        <p class="login-trust__secure">
          <svg class="login-trust__icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          Conexão segura (SSL/TLS). Seus dados estão criptografados.
        </p>
        <p class="login-trust__lgpd">Nunca compartilhamos seus dados. Proteção conforme LGPD.</p>
      </footer>
    </div>
  </main>
  <script src="js/login.js"></script>
</body>
</html>
