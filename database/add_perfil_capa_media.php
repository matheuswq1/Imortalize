<?php
/**
 * Migração: adiciona perfil_media_id e capa_media_id na tabela memorials.
 * Acesse no navegador: http://localhost/Imortalize/database/add_perfil_capa_media.php
 * Execute UMA VEZ. Depois pode excluir ou ignorar este arquivo.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Migração perfil/capa</title></head><body><pre>\n";

$pdo = getDB();

// Verificar se as colunas já existem
$stmt = $pdo->query("SHOW COLUMNS FROM memorials LIKE 'perfil_media_id'");
if ($stmt->rowCount() > 0) {
  echo "Colunas perfil_media_id e capa_media_id já existem. Nada a fazer.\n";
  echo "</pre></body></html>";
  exit;
}

$erros = [];
$ok = [];

try {
  $pdo->exec("ALTER TABLE memorials ADD COLUMN perfil_media_id INT UNSIGNED DEFAULT NULL AFTER biografia");
  $ok[] = "Coluna perfil_media_id adicionada.";
} catch (PDOException $e) {
  $erros[] = "perfil_media_id: " . $e->getMessage();
}

try {
  $pdo->exec("ALTER TABLE memorials ADD COLUMN capa_media_id INT UNSIGNED DEFAULT NULL AFTER perfil_media_id");
  $ok[] = "Coluna capa_media_id adicionada.";
} catch (PDOException $e) {
  $erros[] = "capa_media_id: " . $e->getMessage();
}

// Índices (opcional)
foreach (['perfil_media_id' => 'memorials_perfil_media_id', 'capa_media_id' => 'memorials_capa_media_id'] as $col => $key) {
  try {
    $pdo->exec("ALTER TABLE memorials ADD KEY {$key} ({$col})");
    $ok[] = "Índice {$key} criado.";
  } catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key') !== false) {
      $ok[] = "Índice {$key} já existe.";
    } else {
      $erros[] = "Índice {$key}: " . $e->getMessage();
    }
  }
}

// Foreign keys (alguns servidores podem falhar aqui; o app funciona sem elas)
try {
  $pdo->exec("ALTER TABLE memorials ADD CONSTRAINT memorials_perfil_media_foreign FOREIGN KEY (perfil_media_id) REFERENCES memorial_media (id) ON DELETE SET NULL");
  $ok[] = "Foreign key memorials_perfil_media_foreign criada.";
} catch (PDOException $e) {
  $erros[] = "FK perfil (opcional): " . $e->getMessage();
}
try {
  $pdo->exec("ALTER TABLE memorials ADD CONSTRAINT memorials_capa_media_foreign FOREIGN KEY (capa_media_id) REFERENCES memorial_media (id) ON DELETE SET NULL");
  $ok[] = "Foreign key memorials_capa_media_foreign criada.";
} catch (PDOException $e) {
  $erros[] = "FK capa (opcional): " . $e->getMessage();
}

echo "--- Resultado ---\n";
foreach ($ok as $m) echo "[OK] $m\n";
foreach ($erros as $m) echo "[AVISO] $m\n";

if (count($ok) >= 2) {
  echo "\nMigração concluída. As colunas perfil_media_id e capa_media_id estão disponíveis.\n";
} else {
  echo "\nAlgo falhou. Verifique as mensagens acima ou execute o SQL manualmente no phpMyAdmin.\n";
}

echo "</pre></body></html>";
