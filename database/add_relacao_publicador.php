<?php
/**
 * Migração: adiciona a coluna relacao_publicador na tabela memorials.
 * Acesse no navegador: http://localhost/Imortalize/database/add_relacao_publicador.php
 * Execute UMA VEZ. Depois pode excluir ou ignorar este arquivo.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Migração relação publicador</title></head><body><pre>\n";

$pdo = getDB();

$stmt = $pdo->query("SHOW COLUMNS FROM memorials LIKE 'relacao_publicador'");
if ($stmt->rowCount() > 0) {
  echo "Coluna 'relacao_publicador' já existe. Nada a fazer.\n";
  echo "</pre></body></html>";
  exit;
}

try {
  $pdo->exec("ALTER TABLE memorials ADD COLUMN relacao_publicador VARCHAR(50) DEFAULT NULL COMMENT 'Relação do publicador com a pessoa' AFTER nome");
  echo "Coluna 'relacao_publicador' adicionada com sucesso.\n";
} catch (PDOException $e) {
  echo "Erro: " . $e->getMessage() . "\n";
}

echo "</pre></body></html>";