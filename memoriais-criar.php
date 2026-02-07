<?php
/**
 * Criar memorial — Imortalize
 * Formulário igual ao de edição (memorial.php). Exige plano ativo.
 */
session_start();
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$plano = buscarPlanoAtivo($user_id);
$tem_plano_ativo = !empty($plano);

$erro = '';
$sucesso = '';

// Processar formulário (só se tiver plano ativo)
if ($tem_plano_ativo && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['titulo'])) {
  $titulo = trim($_POST['titulo']);
  $biografia = trim($_POST['biografia'] ?? '');
  $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
  $relacao_publicador = isset($_POST['relacao_publicador']) ? trim($_POST['relacao_publicador']) : '';
  $data_nascimento = isset($_POST['data_nascimento']) ? trim($_POST['data_nascimento']) : '';
  $data_obito = isset($_POST['data_obito']) ? trim($_POST['data_obito']) : '';
  $mensagem_curta = isset($_POST['mensagem_curta']) ? trim($_POST['mensagem_curta']) : '';

  if (mb_strlen($titulo) < 3) {
    $erro = 'O título deve ter pelo menos 3 caracteres.';
  } elseif (mb_strlen($titulo) > 35) {
    $erro = 'O título deve ter no máximo 35 caracteres.';
  } elseif (mb_strlen($nome) > 120) {
    $erro = 'O nome deve ter no máximo 120 caracteres.';
  } elseif (mb_strlen($biografia) > 5000) {
    $erro = 'A biografia deve ter no máximo 5.000 caracteres.';
  } elseif (mb_strlen($mensagem_curta) > 120) {
    $erro = 'A mensagem curta deve ter no máximo 120 caracteres.';
  } else {
    if ($data_nascimento !== '') {
      $parts = explode('-', $data_nascimento);
      if (count($parts) >= 1 && strlen($parts[0]) > 4) {
        $parts[0] = substr($parts[0], 0, 4);
        $data_nascimento = implode('-', $parts);
      }
    }
    if ($data_obito !== '') {
      $parts = explode('-', $data_obito);
      if (count($parts) >= 1 && strlen($parts[0]) > 4) {
        $parts[0] = substr($parts[0], 0, 4);
        $data_obito = implode('-', $parts);
      }
    }

    $hoje = date('Y-m-d');
    if ($data_nascimento !== '' && $data_nascimento > $hoje) {
      $erro = 'A data de nascimento não pode ser superior à data atual.';
    } elseif ($data_obito !== '' && $data_obito > $hoje) {
      $erro = 'A data do óbito não pode ser superior à data atual.';
    } elseif ($data_nascimento !== '' && $data_obito !== '' && $data_obito < $data_nascimento) {
      $erro = 'A data do óbito não pode ser anterior à data de nascimento.';
    } else {
      $opcoes_relacao = function_exists('getRelacaoPublicadorOpcoes') ? getRelacaoPublicadorOpcoes() : [];
      $relacao_val = ($relacao_publicador !== '' && array_key_exists($relacao_publicador, $opcoes_relacao)) ? $relacao_publicador : null;

      $memorial_id = criarMemorial($user_id, $titulo, $biografia ?: null, $nome ?: null, $relacao_val, $data_nascimento ?: null, $data_obito ?: null, $mensagem_curta ?: null);
      if ($memorial_id) {
        header('Location: memorial.php?id=' . $memorial_id . '&criado=1');
        exit;
      }
      $erro = 'Erro ao criar o memorial. Tente novamente.';
    }
  }
}

$page_title = 'Criar memorial | Imortalize';
$opcoes_relacao = function_exists('getRelacaoPublicadorOpcoes') ? getRelacaoPublicadorOpcoes() : [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <link rel="stylesheet" href="css/site.css">
  <style>
    .criar-memorial-page { min-height: 100vh; padding: 2rem 1rem 3rem; }
    .criar-memorial-inner { max-width: 720px; margin: 0 auto; }
    .criar-memorial-header { margin-bottom: 2rem; }
    .criar-memorial-header h1 { font-size: clamp(1.5rem, 4vw, 1.875rem); font-weight: 600; margin: 0 0 0.5rem; color: #f0f2eb; }
    .criar-memorial-header p { color: #b8c4a8; margin: 0; }
    .memorial-dados-section {
      background: rgba(39, 51, 28, 0.6);
      border: 1px solid rgba(184, 196, 168, 0.15);
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 2rem;
    }
    .memorial-dados-section h2 { font-size: 1.125rem; font-weight: 600; color: #f0f2eb; margin: 0 0 1rem; }
    .memorial-campo { margin-bottom: 1.25rem; }
    .memorial-campo:last-of-type { margin-bottom: 0; }
    .memorial-campo label { display: block; font-size: 0.875rem; font-weight: 600; color: #b8c4a8; margin-bottom: 0.5rem; }
    .memorial-campo input[type="text"],
    .memorial-campo input[type="date"],
    .memorial-campo textarea,
    .memorial-campo select {
      width: 100%;
      padding: 0.75rem 1rem;
      font-size: 1rem;
      font-family: inherit;
      color: #f0f2eb;
      background: rgba(0, 0, 0, 0.25);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      transition: border-color 0.2s, box-shadow 0.2s;
      box-sizing: border-box;
    }
    .memorial-campo input:focus,
    .memorial-campo textarea:focus,
    .memorial-campo select:focus {
      outline: none;
      border-color: rgba(184, 196, 168, 0.5);
      box-shadow: 0 0 0 3px rgba(184, 196, 168, 0.1);
    }
    .memorial-campo select {
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23b8c4a8' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 0.75rem center;
      padding-right: 2.25rem;
    }
    .memorial-campo select option {
      background: #2d3d22;
      color: #f0f2eb;
      padding: 0.5rem 0.75rem;
    }
    .memorial-campo textarea { min-height: 120px; resize: vertical; }
    .memorial-campo .hint { font-size: 0.8125rem; color: #b8c4a8; margin: 0.35rem 0 0; }
    .memorial-dados-actions { margin-top: 1.25rem; display: flex; gap: 0.75rem; flex-wrap: wrap; }
    .btn-salvar-dados {
      padding: 0.75rem 1.5rem;
      font-size: 0.9375rem;
      font-weight: 600;
      color: #192112;
      background: #b8c4a8;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn-salvar-dados:hover { background: #f0f2eb; }
    .btn-secondary {
      padding: 0.75rem 1.5rem;
      font-size: 0.9375rem;
      font-weight: 600;
      color: #b8c4a8;
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 10px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      transition: all 0.2s;
    }
    .btn-secondary:hover { background: rgba(255, 255, 255, 0.06); color: #f0f2eb; }
    .alert-erro { background: rgba(255, 100, 100, 0.1); border: 1px solid rgba(255, 100, 100, 0.3); border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem; color: #ffb3b3; font-size: 0.9375rem; }
    .sem-plano-box {
      background: linear-gradient(180deg, rgba(39, 51, 28, 0.8) 0%, rgba(25, 33, 18, 0.9) 100%);
      border: 1px solid rgba(184, 196, 168, 0.25);
      border-radius: 16px;
      padding: 2rem;
      text-align: center;
      margin-bottom: 2rem;
    }
    .sem-plano-box h2 { font-size: 1.25rem; font-weight: 600; color: #f0f2eb; margin: 0 0 0.75rem; }
    .sem-plano-box p { color: #b8c4a8; margin: 0 0 1.5rem; line-height: 1.5; }
    .sem-plano-box .btn-plano {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.85rem 1.5rem;
      font-size: 1rem;
      font-weight: 600;
      color: #192112;
      background: #b8c4a8;
      border: none;
      border-radius: 12px;
      text-decoration: none;
      transition: background 0.2s, transform 0.2s;
    }
    .sem-plano-box .btn-plano:hover { background: #f0f2eb; transform: translateY(-2px); }
    .criar-memorial-footer { margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.08); }
    .criar-memorial-footer a { font-size: 0.875rem; color: #b8c4a8; text-decoration: none; }
    .criar-memorial-footer a:hover { color: #f0f2eb; }
    .memorial-campo-row { display: flex; gap: 1rem; flex-wrap: wrap; }
    .memorial-campo-row .memorial-campo { flex: 1 1 140px; min-width: 0; }
  </style>
</head>
<body>
  <?php $logado_header = !empty($_SESSION['user_id']); ?>
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
        <?php if ($logado_header) : ?>
        <a href="dashboard.php" class="btn-minha-area">Minha área</a>
        <a href="memoriais.php" class="nav-link">Memoriais</a>
        <a href="logout.php" class="btn-sair">Sair</a>
        <?php else : ?>
        <a href="login.php" class="btn-entrar">Entrar</a>
        <a href="cadastro.php" class="btn-criar-conta">Criar conta</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main class="criar-memorial-page">
    <div class="criar-memorial-inner">
      <header class="criar-memorial-header">
        <h1>Criar memorial</h1>
        <p>Preencha as informações para criar um novo memorial. Depois você poderá adicionar fotos e publicar.</p>
      </header>

      <?php if (!$tem_plano_ativo) : ?>
      <div class="sem-plano-box">
        <h2>Plano ativo necessário</h2>
        <p>Para criar um memorial você precisa de um plano ativo. Escolha um plano e assine para começar a preservar as memórias de quem você ama.</p>
        <a href="alterar-plano.php" class="btn-plano">Escolher plano</a>
      </div>
      <div class="criar-memorial-footer">
        <a href="memoriais.php">← Voltar para meus memoriais</a>
      </div>
      <?php else : ?>
      <?php if ($erro) : ?>
      <div class="alert-erro"><?php echo htmlspecialchars($erro); ?></div>
      <?php endif; ?>

      <div class="memorial-dados-section">
        <h2>Informações do memorial</h2>
        <form method="post" action="memoriais-criar.php">
          <div class="memorial-campo">
            <label for="nome">Nome (abaixo da foto de perfil)</label>
            <input type="text" id="nome" name="nome" value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>" maxlength="120" placeholder="Ex.: Maria Silva">
            <p class="hint"><span class="memorial-contador" data-max="120" aria-live="polite"></span></p>
          </div>
          <div class="memorial-campo">
            <label for="titulo">Título *</label>
            <input type="text" id="titulo" name="titulo" value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>" required maxlength="35" minlength="3" placeholder="Ex.: Maria Silva">
            <p class="hint">Mínimo 3 caracteres. <span class="memorial-contador" data-max="35" aria-live="polite"></span></p>
          </div>
          <div class="memorial-campo">
            <label for="relacao_publicador">Qual sua relação com essa pessoa?</label>
            <select id="relacao_publicador" name="relacao_publicador">
              <option value="">— Selecione —</option>
              <?php foreach ($opcoes_relacao as $valor => $rotulo) : ?>
              <option value="<?php echo htmlspecialchars($valor); ?>"<?php echo (isset($_POST['relacao_publicador']) && $_POST['relacao_publicador'] === $valor) ? ' selected' : ''; ?>><?php echo htmlspecialchars($rotulo); ?></option>
              <?php endforeach; ?>
            </select>
            <p class="hint">Será exibido na capa como &quot;Publicado por [seu nome] ([relação])&quot;.</p>
          </div>
          <div class="memorial-campo-row">
            <div class="memorial-campo">
              <label for="data_nascimento">Data de nascimento</label>
              <input type="date" id="data_nascimento" name="data_nascimento" min="1000-01-01" max="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($_POST['data_nascimento']) ? htmlspecialchars($_POST['data_nascimento']) : ''; ?>">
            </div>
            <div class="memorial-campo">
              <label for="data_obito">Data do óbito</label>
              <input type="date" id="data_obito" name="data_obito" min="1000-01-01" max="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($_POST['data_obito']) ? htmlspecialchars($_POST['data_obito']) : ''; ?>">
            </div>
          </div>
          <div class="memorial-campo">
            <label for="mensagem_curta">Mensagem curta (até 120 caracteres)</label>
            <input type="text" id="mensagem_curta" name="mensagem_curta" maxlength="120" value="<?php echo isset($_POST['mensagem_curta']) ? htmlspecialchars($_POST['mensagem_curta']) : ''; ?>" placeholder="Uma frase marcante sobre o ente querido">
            <p class="hint"><span class="memorial-contador" data-max="120" aria-live="polite"></span></p>
          </div>
          <div class="memorial-campo">
            <label for="biografia">Biografia</label>
            <textarea id="biografia" name="biografia" maxlength="5000" placeholder="Escreva uma biografia ou história sobre a pessoa homenageada..."><?php echo isset($_POST['biografia']) ? htmlspecialchars($_POST['biografia']) : ''; ?></textarea>
            <p class="hint"><span class="memorial-contador" data-max="5000" aria-live="polite"></span></p>
          </div>
          <div class="memorial-dados-actions">
            <button type="submit" class="btn-salvar-dados">Criar memorial</button>
            <a href="memoriais.php" class="btn-secondary">Cancelar</a>
          </div>
        </form>
      </div>

      <div class="criar-memorial-footer">
        <a href="memoriais.php">← Voltar para meus memoriais</a>
      </div>
      <?php endif; ?>
    </div>
  </main>
  <script>
  (function() {
    function atualizarContador(span, max, len) {
      var restantes = Math.max(0, max - len);
      span.textContent = restantes + ' restantes / ' + max;
    }
    document.querySelectorAll('.memorial-contador').forEach(function(span) {
      var max = parseInt(span.getAttribute('data-max'), 10);
      if (isNaN(max)) return;
      var camp = span.closest('.memorial-campo');
      var input = camp ? camp.querySelector('input[maxlength], textarea[maxlength]') : null;
      if (!input) return;
      atualizarContador(span, max, (input.value || '').length);
      input.addEventListener('input', function() { atualizarContador(span, max, (input.value || '').length); });
    });
  })();
  </script>
  <script>
  (function() {
    function normalizarAnoQuatroDigitos(el) {
      if (!el || el.type !== 'date') return;
      var v = (el.value || '').trim();
      if (v.length < 10) return;
      var parts = v.split('-');
      if (parts.length >= 1 && parts[0].length > 4) {
        parts[0] = parts[0].substring(0, 4);
        el.value = parts.join('-');
      }
    }
    var dn = document.getElementById('data_nascimento');
    var do_ = document.getElementById('data_obito');
    [dn, do_].forEach(function(input) {
      if (input) {
        input.addEventListener('input', function() { normalizarAnoQuatroDigitos(input); });
        input.addEventListener('change', function() { normalizarAnoQuatroDigitos(input); });
      }
    });
  })();
  </script>
</body>
</html>
