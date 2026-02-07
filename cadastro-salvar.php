<?php
/**
 * Processa o cadastro de novo usuário — grava no banco.
 */
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: cadastro.php');
  exit;
}
if (!csrf_validate()) {
  $_SESSION['cadastro_erro'] = 'Requisição inválida. Tente novamente.';
  header('Location: cadastro.php');
  exit;
}

$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
$senha = isset($_POST['senha']) ? $_POST['senha'] : '';
$confirmar_senha = isset($_POST['confirmar_senha']) ? $_POST['confirmar_senha'] : '';

$erros = [];

if (mb_strlen($nome) < 2) {
  $erros[] = 'Nome deve ter pelo menos 2 caracteres.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $erros[] = 'Informe um e-mail válido.';
}

if (mb_strlen($senha) < 6) {
  $erros[] = 'A senha deve ter pelo menos 6 caracteres.';
}

if ($senha !== $confirmar_senha) {
  $erros[] = 'As senhas não coincidem.';
}

if ($telefone === '' || mb_strlen($telefone) < 10) {
  $erros[] = 'Informe um telefone válido (com DDD).';
} elseif (mb_strlen($telefone) > 20) {
  $erros[] = 'Telefone muito longo.';
}

if (!empty($erros)) {
  $_SESSION['cadastro_erro'] = implode(' ', $erros);
  $_SESSION['cadastro_nome'] = $nome;
  $_SESSION['cadastro_email'] = $email;
  $_SESSION['cadastro_telefone'] = $telefone;
  header('Location: cadastro.php');
  exit;
}

$pdo = getDB();

// Verifica se o e-mail já existe
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
  $_SESSION['cadastro_erro'] = 'Este e-mail já está em uso. Use outro ou faça login.';
  $_SESSION['cadastro_nome'] = $nome;
  $_SESSION['cadastro_email'] = $email;
  $_SESSION['cadastro_telefone'] = $telefone;
  header('Location: cadastro.php');
  exit;
}

$password_hash = password_hash($senha, PASSWORD_DEFAULT);

try {
  $stmt = $pdo->prepare('INSERT INTO users (nome, email, telefone, password_hash) VALUES (?, ?, ?, ?)');
  $stmt->execute([$nome, $email, $telefone, $password_hash]);
} catch (PDOException $e) {
  error_log('Erro ao cadastrar usuário: ' . $e->getMessage());
  $_SESSION['cadastro_erro'] = 'Erro ao criar conta. Tente novamente.';
  $_SESSION['cadastro_nome'] = $nome;
  $_SESSION['cadastro_email'] = $email;
  $_SESSION['cadastro_telefone'] = $telefone;
  header('Location: cadastro.php');
  exit;
}

unset($_SESSION['cadastro_erro'], $_SESSION['cadastro_nome'], $_SESSION['cadastro_email'], $_SESSION['cadastro_telefone']);
$_SESSION['cadastro_ok'] = '1';
header('Location: login.php?cadastro=1');
exit;
