<?php
/**
 * Configurações da conta — Imortalize
 * Alterar nome, e-mail, telefone e senha.
 */
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$user_id = (int) $_SESSION['user_id'];
$usuario = buscarUsuarioPorId($user_id);
if (!$usuario) {
  header('Location: dashboard.php');
  exit;
}

$page_title = 'Configurações | Imortalize';
$sucesso = isset($_GET['ok']) && $_GET['ok'] === '1';
$erro = '';
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_validate()) {
    $erro = 'Requisição inválida. Tente novamente.';
  } else {
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
    $senha_atual = isset($_POST['senha_atual']) ? $_POST['senha_atual'] : '';
    $nova_senha = isset($_POST['nova_senha']) ? $_POST['nova_senha'] : '';
    $confirmar_senha = isset($_POST['confirmar_senha']) ? $_POST['confirmar_senha'] : '';

    if (mb_strlen($nome) < 2) {
      $erros[] = 'Nome deve ter pelo menos 2 caracteres.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $erros[] = 'Informe um e-mail válido.';
    }
    if ($telefone !== '' && (mb_strlen($telefone) < 10 || mb_strlen($telefone) > 20)) {
      $erros[] = 'Telefone deve ter entre 10 e 20 caracteres (com DDD).';
    }

    $alterar_senha = $nova_senha !== '';
    if ($alterar_senha) {
      if (mb_strlen($nova_senha) < 6) {
        $erros[] = 'A nova senha deve ter pelo menos 6 caracteres.';
      }
      if ($nova_senha !== $confirmar_senha) {
        $erros[] = 'A confirmação da senha não coincide.';
      }
      if ($usuario['tem_senha'] && $senha_atual === '') {
        $erros[] = 'Informe sua senha atual para alterar a senha.';
      }
    }

    if (!empty($erros)) {
      $erro = implode(' ', $erros);
    } else {
      $ok_perfil = atualizarPerfilUsuario($user_id, $nome, $email, $telefone);
      if (!$ok_perfil) {
        $erro = 'Este e-mail já está em uso por outra conta.';
      } else {
        $_SESSION['user_nome'] = $nome;
        $_SESSION['user_email'] = $email;
        $usuario['nome'] = $nome;
        $usuario['email'] = $email;
        $usuario['telefone'] = $telefone ?: null;

        if ($alterar_senha) {
          $senha_atual_para_validar = $usuario['tem_senha'] ? $senha_atual : null;
          $ok_senha = alterarSenhaUsuario($user_id, $senha_atual_para_validar, $nova_senha);
          if (!$ok_senha) {
            $erro = 'Não foi possível alterar a senha. Verifique a senha atual.';
          } else {
            $usuario['tem_senha'] = true;
          }
        }

        if ($erro === '') {
          header('Location: configuracoes.php?ok=1');
          exit;
        }
      }
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
  <link rel="stylesheet" href="css/site.css">
  <style>
    .config-page {
      min-height: 100vh;
      padding: 2rem 1rem 3rem;
    }
    .config-inner {
      max-width: 520px;
      margin: 0 auto;
    }
    .config-header {
      margin-bottom: 2rem;
    }
    .config-header h1 {
      font-size: clamp(1.5rem, 4vw, 1.875rem);
      font-weight: 600;
      margin: 0 0 0.35rem;
      color: #f0f2eb;
    }
    .config-header p {
      font-size: 0.9375rem;
      color: #b8c4a8;
      margin: 0;
    }
    .config-card {
      background: linear-gradient(180deg, rgba(39, 51, 28, 0.8) 0%, rgba(25, 33, 18, 0.9) 100%);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 1.75rem;
      margin-bottom: 1.5rem;
    }
    .config-card h2 {
      font-size: 1rem;
      font-weight: 600;
      color: #f0f2eb;
      margin: 0 0 1.25rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .config-card h2 svg {
      width: 20px;
      height: 20px;
      color: #b8c4a8;
    }
    .form-group {
      margin-bottom: 1.25rem;
    }
    .form-group:last-child {
      margin-bottom: 0;
    }
    .form-group label {
      display: block;
      font-size: 0.875rem;
      font-weight: 500;
      color: #b8c4a8;
      margin-bottom: 0.4rem;
    }
    .form-group input {
      width: 100%;
      padding: 0.75rem 1rem;
      font-size: 1rem;
      color: #f0f2eb;
      background: rgba(0, 0, 0, 0.25);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 10px;
      box-sizing: border-box;
    }
    .form-group input::placeholder {
      color: #b8c4a8;
      opacity: 0.7;
    }
    .form-group input:focus {
      outline: none;
      border-color: rgba(184, 196, 168, 0.4);
    }
    .config-msg {
      padding: 1rem 1.25rem;
      border-radius: 10px;
      margin-bottom: 1.5rem;
      font-size: 0.9375rem;
    }
    .config-msg--sucesso {
      background: rgba(184, 196, 168, 0.2);
      border: 1px solid rgba(184, 196, 168, 0.4);
      color: #b8c4a8;
    }
    .config-msg--erro {
      background: rgba(255, 0, 0, 0.1);
      border: 1px solid rgba(255, 0, 0, 0.3);
      color: #ff9999;
    }
    .btn-salvar {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.85rem 1.75rem;
      font-size: 1rem;
      font-weight: 600;
      color: #192112;
      background: #b8c4a8;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: background 0.2s ease, transform 0.2s ease;
    }
    .btn-salvar:hover {
      background: #f0f2eb;
      transform: translateY(-1px);
    }
    .btn-salvar svg {
      width: 20px;
      height: 20px;
    }
    .config-footer {
      margin-top: 2rem;
      padding-top: 1.5rem;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
    .config-footer a {
      font-size: 0.875rem;
      color: #b8c4a8;
      text-decoration: none;
    }
    .config-footer a:hover {
      color: #f0f2eb;
    }
  </style>
</head>
<body>
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
        <a href="dashboard.php" class="btn-minha-area">Minha área</a>
        <a href="memoriais.php" class="nav-link">Memoriais</a>
        <a href="logout.php" class="btn-sair">Sair</a>
      </nav>
    </div>
  </header>

  <main class="config-page">
    <div class="config-inner">
      <header class="config-header">
        <h1>Configurações</h1>
        <p>Altere seus dados de perfil e senha.</p>
      </header>

      <?php if ($sucesso) : ?>
      <div class="config-msg config-msg--sucesso" role="alert">
        ✓ Alterações salvas com sucesso.
      </div>
      <?php endif; ?>
      <?php if ($erro) : ?>
      <div class="config-msg config-msg--erro" role="alert">
        <?php echo htmlspecialchars($erro); ?>
      </div>
      <?php endif; ?>

      <form method="post" action="configuracoes.php">
        <?php echo csrf_field(); ?>

        <div class="config-card">
          <h2>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Dados do perfil
          </h2>
          <div class="form-group">
            <label for="nome">Nome completo</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required autocomplete="name">
          </div>
          <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required autocomplete="email">
          </div>
          <div class="form-group">
            <label for="telefone">Telefone</label>
            <input type="tel" id="telefone" name="telefone" value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>" placeholder="(00) 00000-0000" maxlength="20" autocomplete="tel">
          </div>
        </div>

        <div class="config-card">
          <h2>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <?php echo $usuario['tem_senha'] ? 'Alterar senha' : 'Definir senha'; ?>
          </h2>
          <?php if ($usuario['tem_senha']) : ?>
          <div class="form-group">
            <label for="senha_atual">Senha atual</label>
            <input type="password" id="senha_atual" name="senha_atual" placeholder="Deixe em branco para não alterar" autocomplete="current-password">
          </div>
          <?php else : ?>
          <p style="font-size: 0.875rem; color: #b8c4a8; margin: 0 0 1rem;">Você entrou com Google. Defina uma senha para poder acessar também com e-mail e senha.</p>
          <?php endif; ?>
          <div class="form-group">
            <label for="nova_senha"><?php echo $usuario['tem_senha'] ? 'Nova senha' : 'Senha'; ?></label>
            <input type="password" id="nova_senha" name="nova_senha" placeholder="Mínimo 6 caracteres" autocomplete="new-password" minlength="6">
          </div>
          <div class="form-group">
            <label for="confirmar_senha">Confirmar senha</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Repita a senha" autocomplete="new-password" minlength="6">
          </div>
        </div>

        <button type="submit" class="btn-salvar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
          Salvar alterações
        </button>
      </form>

      <footer class="config-footer">
        <a href="dashboard.php">← Voltar ao dashboard</a>
      </footer>
    </div>
  </main>
</body>
</html>
