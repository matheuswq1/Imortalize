<?php
/**
 * Corrige permissão do usuário de teste — Imortalize
 *
 * Define is_admin = 0 para teste@imortalize.com, para que esse usuário
 * não tenha acesso ao painel admin. Execute UMA VEZ se o teste estava
 * entrando no painel indevidamente.
 *
 * Via navegador: http://localhost/imortalize/database/corrigir-permissao-teste.php
 * Ou: php database/corrigir-permissao-teste.php
 */
$base = dirname(__DIR__);
require_once $base . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Corrigir permissão</title></head><body style=\"font-family: sans-serif; padding: 2rem; max-width: 560px;\">\n";

$pdo = getDB();

try {
  $pdo->query('SELECT is_admin FROM users LIMIT 1');
} catch (PDOException $e) {
  $pdo->exec('ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 COMMENT "1 = acesso ao painel admin"');
  echo "<p>Coluna <code>is_admin</code> criada na tabela <code>users</code>.</p>\n";
}

$stmt = $pdo->prepare('UPDATE users SET is_admin = 0 WHERE email = ? LIMIT 1');
$stmt->execute(['teste@imortalize.com']);
$afetados = $stmt->rowCount();

if ($afetados > 0) {
  echo "<p><strong>Permissão corrigida.</strong> O usuário <code>teste@imortalize.com</code> não tem mais acesso ao painel admin (is_admin = 0).</p>\n";
} else {
  echo "<p>O usuário <code>teste@imortalize.com</code> já estava com is_admin = 0 ou não existe. Nenhuma alteração necessária.</p>\n";
}

echo "<p>Para dar acesso admin a outro usuário, use <code>config/admin.config.php</code> (lista <code>admin_emails</code>) ou no banco: <code>UPDATE users SET is_admin = 1 WHERE email = 'seu@email.com';</code></p>\n";
echo "<p><a href=\"../dashboard.php\">Ir para o dashboard</a></p>\n";
echo "</body></html>\n";
