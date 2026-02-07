<?php
/**
 * Processa o formulário "Fale conosco" — Imortalize
 * Grava na tabela contact_messages. Redireciona para a home com feedback.
 */
session_start();
require_once __DIR__ . '/config/database.php';

$redirect = 'index.php#contato';
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$mensagem = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';

if ($nome === '' || mb_strlen($nome) > 255) {
  header('Location: ' . $redirect . '?contato=erro');
  exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
  header('Location: ' . $redirect . '?contato=erro');
  exit;
}
if ($mensagem === '' || mb_strlen($mensagem) > 5000) {
  header('Location: ' . $redirect . '?contato=erro');
  exit;
}

$pdo = getDB();
try {
  $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    lido TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY contact_messages_created (created_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
  header('Location: ' . $redirect . '?contato=erro');
  exit;
}
try {
  $stmt = $pdo->prepare('INSERT INTO contact_messages (nome, email, mensagem) VALUES (?, ?, ?)');
  $stmt->execute([$nome, $email, $mensagem]);
  header('Location: ' . $redirect . '?contato=ok');
  exit;
} catch (PDOException $e) {
  header('Location: ' . $redirect . '?contato=erro');
  exit;
}
