<?php
/**
 * Inscrição na newsletter — Imortalize
 * Recebe POST com e-mail e grava na tabela newsletter. Redireciona para a home com feedback.
 */
session_start();
require_once __DIR__ . '/config/database.php';

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$redirect = isset($_POST['redirect']) ? trim($_POST['redirect']) : 'index.php';
$redirect = preg_replace('/[^a-z0-9_\-\.\?\=\#]/i', '', $redirect) ?: 'index.php';
if (strpos($redirect, '..') !== false) {
  $redirect = 'index.php';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  header('Location: ' . $redirect . '?newsletter=erro');
  exit;
}

$pdo = getDB();
try {
  $stmt = $pdo->prepare('INSERT INTO newsletter (email) VALUES (?)');
  $stmt->execute([$email]);
  header('Location: ' . $redirect . '?newsletter=ok');
  exit;
} catch (PDOException $e) {
  if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate') !== false) {
    header('Location: ' . $redirect . '?newsletter=ja_cadastrado');
  } else {
    header('Location: ' . $redirect . '?newsletter=erro');
  }
  exit;
}
