<?php
/**
 * Teste de conexão com o banco MySQL — Imortalize
 * Acesse: http://localhost/Imortalize/teste-conexao.php
 * Se aparecer "Conexão OK", está tudo certo!
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teste de Conexão — Imortalize</title>
  <style>
    body {
      font-family: system-ui, sans-serif;
      max-width: 600px;
      margin: 2rem auto;
      padding: 1rem;
      background: #192112;
      color: #f0f2eb;
    }
    .sucesso { background: rgba(0,255,0,0.1); border: 1px solid #0f0; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
    .erro { background: rgba(255,0,0,0.1); border: 1px solid #f00; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
    .info { background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 8px; margin: 1rem 0; font-size: 0.875rem; }
    h1 { color: #b8c4a8; }
    code { background: rgba(0,0,0,0.3); padding: 0.2rem 0.4rem; border-radius: 4px; }
  </style>
</head>
<body>
  <h1>Teste de Conexão — Imortalize</h1>

  <?php
  try {
    $pdo = getDB();
    echo '<div class="sucesso">✓ <strong>Conexão OK!</strong> O banco de dados está conectado corretamente.</div>';
    
    // Testar se as tabelas existem
    $tabelas = ['users', 'plans', 'subscriptions', 'memorials'];
    $tabelas_ok = [];
    foreach ($tabelas as $tabela) {
      $stmt = $pdo->query("SHOW TABLES LIKE '{$tabela}'");
      if ($stmt->rowCount() > 0) {
        $tabelas_ok[] = $tabela;
      }
    }
    
    if (count($tabelas_ok) === count($tabelas)) {
      echo '<div class="sucesso">✓ Todas as tabelas foram encontradas.</div>';
    } else {
      echo '<div class="erro">⚠ Algumas tabelas não foram encontradas. Verifique se importou o arquivo <code>database/imortalize.sql</code>.</div>';
    }
    
    // Testar usuário de teste
    $stmt = $pdo->prepare('SELECT id, nome, email FROM users WHERE email = ?');
    $stmt->execute(['teste@imortalize.com']);
    $user = $stmt->fetch();
    
    if ($user) {
      echo '<div class="sucesso">✓ Usuário de teste encontrado: <strong>' . htmlspecialchars($user['nome']) . '</strong> (' . htmlspecialchars($user['email']) . ')</div>';
      
      // Verificar plano ativo
      $plano = buscarPlanoAtivo($user['id']);
      if ($plano) {
        echo '<div class="sucesso">✓ Plano ativo encontrado: <strong>' . htmlspecialchars($plano['plano_nome']) . '</strong></div>';
      } else {
        echo '<div class="info">ℹ Usuário teste não possui plano ativo. Isso é normal se você ainda não criou a assinatura.</div>';
      }
    } else {
      echo '<div class="erro">⚠ Usuário de teste não encontrado. Execute o arquivo <code>database/imortalize.sql</code> novamente.</div>';
    }
    
    echo '<div class="info">';
    echo '<strong>Configuração atual:</strong><br>';
    echo 'Host: <code>' . DB_HOST . '</code><br>';
    echo 'Banco: <code>' . DB_NAME . '</code><br>';
    echo 'Usuário: <code>' . DB_USER . '</code><br>';
    echo 'Senha: <code>' . (DB_PASS ? '***' : '(vazia)') . '</code>';
    echo '</div>';
    
  } catch (PDOException $e) {
    echo '<div class="erro">✗ <strong>Erro de conexão:</strong><br>';
    echo htmlspecialchars($e->getMessage()) . '</div>';
    
    echo '<div class="info">';
    echo '<strong>Possíveis soluções:</strong><br>';
    echo '1. Verifique se o MySQL está rodando no XAMPP<br>';
    echo '2. Verifique se o banco <code>imortalize</code> existe<br>';
    echo '3. Se você configurou senha no MySQL, edite <code>config/database.php</code> e altere <code>DB_PASS</code><br>';
    echo '4. Execute: <code>mysql -u root -p &lt; database/imortalize.sql</code>';
    echo '</div>';
  }
  ?>

  <p style="margin-top: 2rem;">
    <a href="index.php" style="color: #b8c4a8;">← Voltar ao site</a>
  </p>
</body>
</html>
