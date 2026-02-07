<?php
/**
 * Lista os buckets do projeto Firebase/Google Cloud — Imortalize
 * Use para descobrir o nome exato do bucket quando der erro 404 "bucket does not exist".
 * Acesse no navegador: http://localhost/Imortalize/listar-buckets-firebase.php
 */
require_once __DIR__ . '/config/firebase.config.php';

$keyPath = FIREBASE_SERVICE_ACCOUNT_JSON;
$projectId = 'imortalize';

if (!file_exists($keyPath)) {
  die('Arquivo da conta de serviço não encontrado: ' . $keyPath);
}

$json = json_decode(file_get_contents($keyPath), true);
if (isset($json['project_id'])) {
  $projectId = $json['project_id'];
}

$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
  die('Rode antes: composer install');
}
require_once $autoload;

header('Content-Type: text/html; charset=utf-8');
echo '<h1>Buckets do projeto: ' . htmlspecialchars($projectId) . '</h1>';

try {
  $storage = new \Google\Cloud\Storage\StorageClient([
    'keyFilePath' => $keyPath,
  ]);
  $buckets = $storage->buckets();
  $lista = [];
  foreach ($buckets as $bucket) {
    $lista[] = $bucket->name();
  }
  if (count($lista) === 0) {
    echo '<p><strong>Nenhum bucket encontrado.</strong></p>';
    echo '<p>No Firebase Console: vá em <strong>Storage</strong> e clique em <strong>Get started</strong> para criar o bucket padrão.</p>';
    echo '<p>Depois execute este script de novo.</p>';
  } else {
    echo '<p>Use um dos nomes abaixo em <code>config/firebase.config.php</code> em <code>FIREBASE_STORAGE_BUCKET</code>:</p>';
    echo '<ul>';
    foreach ($lista as $nome) {
      echo '<li><code>' . htmlspecialchars($nome) . '</code></li>';
    }
    echo '</ul>';
  }
} catch (Exception $e) {
  echo '<p style="color: #c00;">Erro: ' . htmlspecialchars($e->getMessage()) . '</p>';
  echo '<p>Se for "Permission denied" ou "403", ative a API Cloud Storage no Google Cloud Console:<br>';
  echo ' <a href="https://console.cloud.google.com/apis/library/storage.googleapis.com?project=' . urlencode($projectId) . '" target="_blank">Ativar Cloud Storage API</a></p>';
  echo '<p>E no Firebase Console: Storage → Get started (para criar o bucket).</p>';
}
