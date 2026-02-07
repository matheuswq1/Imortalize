<?php
/**
 * Migração: adiciona a coluna "nome" na tabela memorials (exibido abaixo da foto de perfil).
 * Acesse no navegador: http://localhost/Imortalize/database/add_nome_memorial.php
 * Execute UMA VEZ. Depois pode excluir ou ignorar este arquivo.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Migração nome</title></head><body><pre>\n";

$pdo = getDB();

$stmt = $pdo->query("SHOW COLUMNS FROM memorials LIKE 'nome'");
if ($stmt->rowCount() > 0) {
  echo "Coluna 'nome' já existe. Nada a fazer.\n";
  echo "</pre></body></html>";
  exit;
}

try {
  $pdo->exec("ALTER TABLE memorials ADD COLUMN nome VARCHAR(255) DEFAULT NULL COMMENT 'Nome exibido abaixo da foto de perfil' AFTER capa_media_id");
  echo "Coluna 'nome' adicionada com sucesso.\n";
} catch (PDOException $e) {
  echo "Erro: " . $e->getMessage() . "\n";
}

echo "</pre></body></html>";
