<?php
/**
 * Cadastra o usuário administrador no banco — Imortalize
 *
 * 1) Cria a coluna is_admin na tabela users (se ainda não existir)
 * 2) Se o usuário admin não existir: insere com is_admin = 1
 * 3) Se já existir: marca is_admin = 1
 *
 * Execute UMA VEZ após importar imortalize.sql:
 *   php database/cadastrar-admin.php
 *
 * Ou pelo navegador:
 *   http://localhost/Imortalize/database/cadastrar-admin.php
 */
$base = dirname(__DIR__);
require_once $base . '/config/database.php';

$email_admin = 'admin@imortalize.com';
$nome_admin = 'Administrador';
$senha_admin = '123456'; // Altere após o primeiro login em produção

$pdo = getDB();

// 1) Garante que a coluna is_admin existe
try {
  $pdo->query('SELECT is_admin FROM users LIMIT 1');
} catch (PDOException $e) {
  $pdo->exec('ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 COMMENT "1 = acesso ao painel admin"');
}

// 2) Verifica se o usuário admin já existe
$stmt = $pdo->prepare('SELECT id, nome FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email_admin]);
$existe = $stmt->fetch();

if ($existe) {
  $stmt = $pdo->prepare('UPDATE users SET is_admin = 1 WHERE id = ?');
  $stmt->execute([$existe['id']]);
  $msg = "Usuário admin já existia (ID: {$existe['id']}).";
  $acao = "Marcado como administrador (is_admin = 1). E-mail: {$email_admin}";
} else {
  $password_hash = password_hash($senha_admin, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare('INSERT INTO users (nome, email, password_hash, is_admin) VALUES (?, ?, ?, 1)');
  $stmt->execute([$nome_admin, $email_admin, $password_hash]);
  $msg = "Usuário administrador cadastrado com sucesso.";
  $acao = "E-mail: {$email_admin} | Senha: {$senha_admin}";
}

$saida = $msg . "\n" . $acao;

if (php_sapi_name() !== 'cli') {
  header('Content-Type: text/html; charset=utf-8');
  echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><title>Cadastrar admin</title>';
  echo '<style>body{font-family:sans-serif;max-width:500px;margin:2rem auto;padding:1rem;background:#192112;color:#f0f2eb;}';
  echo 'p{background:rgba(39,51,28,0.8);padding:1rem;border-radius:8px;border:1px solid rgba(255,255,255,0.1);}';
  echo 'a{color:#b8c4a8;}</style></head><body>';
  echo '<p>' . nl2br(htmlspecialchars($msg . "\n" . $acao)) . '</p>';
  echo '<p><a href="../login.php">Ir para o login</a> · <a href="../admin/">Painel admin</a></p>';
  echo '</body></html>';
  exit;
}

echo $saida . "\n";
