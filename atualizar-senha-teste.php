<?php
/**
 * Atualiza a senha do usuário de teste no banco para "123456"
 * Execute: php atualizar-senha-teste.php
 * Ou acesse: http://localhost/Imortalize/atualizar-senha-teste.php
 */

require_once __DIR__ . '/config/database.php';

$senha = '123456';
$email = 'teste@imortalize.com';

// Gerar hash bcrypt
$hash = password_hash($senha, PASSWORD_DEFAULT);

echo "=== Atualizar senha do usuário teste ===\n\n";
echo "E-mail: {$email}\n";
echo "Senha: {$senha}\n";
echo "Hash gerado: {$hash}\n\n";

try {
  $pdo = getDB();
  
  // Verificar se o usuário existe
  $stmt = $pdo->prepare('SELECT id, nome FROM users WHERE email = ?');
  $stmt->execute([$email]);
  $user = $stmt->fetch();
  
  if (!$user) {
    echo "ERRO: Usuário {$email} não encontrado no banco!\n";
    echo "Execute primeiro: mysql -u root -p < database/imortalize.sql\n";
    exit(1);
  }
  
  // Atualizar a senha
  $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
  $stmt->execute([$hash, $email]);
  
  echo "✓ Senha atualizada com sucesso!\n";
  echo "✓ Usuário: " . htmlspecialchars($user['nome']) . "\n";
  echo "\nAgora você pode fazer login com:\n";
  echo "  E-mail: {$email}\n";
  echo "  Senha: {$senha}\n";
  
} catch (PDOException $e) {
  echo "ERRO ao atualizar: " . $e->getMessage() . "\n";
  echo "\nComando SQL manual (execute no MySQL):\n";
  echo "UPDATE users SET password_hash = " . var_export($hash, true) . " WHERE email = '{$email}';\n";
  exit(1);
}
