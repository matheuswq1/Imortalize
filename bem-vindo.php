<?php
/**
 * Tela exibida após o usuário criar a conta — Imortalize
 * Boas-vindas e orientação dos próximos passos (sem backend).
 */
$nome = isset($_GET['nome']) ? trim($_GET['nome']) : '';
$nome_exibir = $nome !== '' ? htmlspecialchars($nome) : null;
$page_title = 'Conta criada | Imortalize';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <link rel="stylesheet" href="css/site.css">
  <style>
    .bem-vindo-page {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
    }
    .bem-vindo-card {
      max-width: 520px;
      width: 100%;
      background: linear-gradient(180deg, rgba(39, 51, 28, 0.95) 0%, rgba(25, 33, 18, 0.98) 100%);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 2.5rem 2rem;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
      text-align: center;
    }
    .bem-vindo-card .bem-vindo-logo {
      max-width: 160px;
      height: auto;
      margin-bottom: 1.5rem;
      display: block;
      margin-left: auto;
      margin-right: auto;
    }
    .bem-vindo-card .badge-sucesso {
      display: inline-block;
      padding: 0.35rem 0.85rem;
      font-size: 0.8125rem;
      font-weight: 600;
      color: #192112;
      background: #b8c4a8;
      border-radius: 20px;
      margin-bottom: 1rem;
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }
    .bem-vindo-card h1 {
      font-size: clamp(1.5rem, 4vw, 1.875rem);
      font-weight: 600;
      margin: 0 0 0.5rem;
      color: #f0f2eb;
    }
    .bem-vindo-card .bem-vindo-texto {
      font-size: 1rem;
      color: #b8c4a8;
      margin: 0 0 1.75rem;
      line-height: 1.6;
    }
    .bem-vindo-card .proximos-passos {
      font-size: 0.9375rem;
      font-weight: 600;
      color: #f0f2eb;
      margin-bottom: 1.25rem;
    }
    .bem-vindo-opcoes {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }
    .bem-vindo-opcoes a {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 1rem 1.5rem;
      font-size: 1rem;
      font-weight: 600;
      color: #192112;
      background: #b8c4a8;
      border: none;
      border-radius: 12px;
      text-decoration: none;
      transition: background 0.25s ease, transform 0.25s ease, box-shadow 0.25s ease;
    }
    .bem-vindo-opcoes a:hover {
      background: #f0f2eb;
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    }
    .bem-vindo-opcoes a.secondary {
      background: transparent;
      color: #b8c4a8;
      border: 1px solid rgba(255, 255, 255, 0.15);
    }
    .bem-vindo-opcoes a.secondary:hover {
      background: rgba(255, 255, 255, 0.06);
      color: #f0f2eb;
    }
    .bem-vindo-card .voltar-inicio {
      margin-top: 1.5rem;
      font-size: 0.875rem;
    }
    .bem-vindo-card .voltar-inicio a {
      color: #b8c4a8;
      text-decoration: none;
    }
    .bem-vindo-card .voltar-inicio a:hover {
      color: #f0f2eb;
    }
  </style>
</head>
<body>
  <div class="bem-vindo-page">
    <div class="bem-vindo-card">
      <img src="img/logo branca sem fundo.png" alt="Imortalize" class="bem-vindo-logo" width="160" height="42">
      <span class="badge-sucesso">Conta criada com sucesso</span>
      <h1><?php echo $nome_exibir ? 'Olá, ' . explode(' ', $nome_exibir)[0] . '!' : 'Bem-vindo ao Imortalize!'; ?></h1>
      <p class="bem-vindo-texto">
        Sua conta foi criada. Agora você pode criar memoriais, escolher um plano e preservar as memórias que importam.
      </p>
      <p class="proximos-passos">O que você gostaria de fazer primeiro?</p>
      <div class="bem-vindo-opcoes">
        <a href="index.php#planos">Escolher um plano</a>
        <a href="dashboard.php" class="secondary">Ir para minha área</a>
        <a href="index.php" class="secondary">Explorar o site</a>
      </div>
      <p class="voltar-inicio">
        <a href="index.php">← Voltar ao início</a>
      </p>
    </div>
  </div>
</body>
</html>
