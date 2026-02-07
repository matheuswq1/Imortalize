<?php
/**
 * Migração: adiciona coluna layout_style na tabela qrcodes.
 * Acesse: http://localhost/Imortalize/database/add_qrcode_layout.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/database.php';
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Migração layout QR</title></head><body><pre>\n";
$pdo = getDB();
$stmt = $pdo->query("SHOW COLUMNS FROM qrcodes LIKE 'layout_style'");
if ($stmt->rowCount() > 0) {
  echo "Coluna layout_style já existe. Nada a fazer.\n";
} else {
  try {
    $pdo->exec("ALTER TABLE qrcodes ADD COLUMN layout_style VARCHAR(20) NOT NULL DEFAULT 'padrao' COMMENT 'padrao, circular, com_logo' AFTER status");
    echo "Coluna layout_style adicionada com sucesso.\n";
  } catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
  }
}
echo "</pre></body></html>";
