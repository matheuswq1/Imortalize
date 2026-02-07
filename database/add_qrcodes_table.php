<?php
/**
 * Migração: cria tabela qrcodes.
 * Acesse: http://localhost/Imortalize/database/add_qrcodes_table.php
 * Execute UMA VEZ.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Migração QR Codes</title></head><body><pre>\n";

$pdo = getDB();
try {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS qrcodes (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      memorial_id INT UNSIGNED DEFAULT NULL,
      unique_id VARCHAR(36) NOT NULL,
      type ENUM('url','texto','wifi','vcard') NOT NULL DEFAULT 'url',
      content JSON DEFAULT NULL,
      scans INT UNSIGNED NOT NULL DEFAULT 0,
      status ENUM('disponivel','usado') NOT NULL DEFAULT 'disponivel',
      imagem_path VARCHAR(500) DEFAULT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY qrcodes_unique_id (unique_id),
      KEY qrcodes_memorial_id (memorial_id),
      KEY qrcodes_status (status),
      CONSTRAINT qrcodes_memorial_foreign FOREIGN KEY (memorial_id) REFERENCES memorials (id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ");
  echo "Tabela qrcodes criada com sucesso.\n";
} catch (PDOException $e) {
  echo "Erro: " . $e->getMessage() . "\n";
  if (strpos($e->getMessage(), 'already exists') !== false) {
    echo "A tabela já existe. Nada a fazer.\n";
  }
}
echo "</pre></body></html>";
