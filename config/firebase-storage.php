<?php
/**
 * Upload de arquivos para Firebase Storage (Google Cloud Storage) — Imortalize
 * Requer: composer require google/cloud-storage e config/firebase.config.php
 */

if (!defined('FIREBASE_STORAGE_BUCKET') || !defined('FIREBASE_SERVICE_ACCOUNT_JSON')) {
  require_once __DIR__ . '/firebase.config.php';
}

/**
 * Faz upload de uma imagem para o Firebase Storage e retorna a URL pública.
 *
 * @param string $tmpPath Caminho temporário do arquivo (ex: $_FILES['foto']['tmp_name'])
 * @param int $memorial_id ID do memorial (para organizar na pasta)
 * @param string $originalName Nome original do arquivo (para extensão)
 * @return string|false URL pública da imagem ou false em caso de erro
 */
function _logUploadError($message) {
  $logDir = dirname(__DIR__, 1) . '/logs';
  $logFile = $logDir . '/upload.log';
  if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
  }
  $line = date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;
  @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

function uploadMemorialImageToFirebase($tmpPath, $memorial_id, $originalName) {
  if (!is_uploaded_file($tmpPath) && !is_readable($tmpPath)) {
    _logUploadError('[Firebase] Arquivo inválido ou não enviado: ' . $tmpPath);
    return false;
  }

  $bucketName = defined('FIREBASE_STORAGE_BUCKET') ? FIREBASE_STORAGE_BUCKET : '';
  $keyPath = defined('FIREBASE_SERVICE_ACCOUNT_JSON') ? FIREBASE_SERVICE_ACCOUNT_JSON : '';

  if ($bucketName === '' || $bucketName === 'seu-projeto.appspot.com' || !file_exists($keyPath)) {
    _logUploadError('[Firebase] Config inválida: bucket=' . $bucketName . ', keyPath existe=' . (file_exists($keyPath) ? 'sim' : 'não'));
    return false;
  }

  if (!class_exists('Google\Cloud\Storage\StorageClient')) {
    $autoload = dirname(__DIR__, 1) . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
      _logUploadError('[Firebase] vendor/autoload.php não encontrado. Rode: composer install');
      return false;
    }
    require_once $autoload;
  }

  try {
    $storage = new \Google\Cloud\Storage\StorageClient([
      'keyFilePath' => $keyPath,
    ]);
    $bucket = $storage->bucket($bucketName);

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
      $ext = 'jpg';
    }
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    $objectName = sprintf('memorials/%d/%s_%s.%s', (int) $memorial_id, uniqid(), substr($safeName, 0, 80), $ext);

    $object = $bucket->upload(fopen($tmpPath, 'r'), [
      'name' => $objectName,
      'metadata' => [
        'cacheControl' => 'public, max-age=31536000',
        'contentType' => 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext),
      ],
    ]);

    // URL pública no formato Firebase Storage
    $encoded = rawurlencode($objectName);
    return 'https://firebasestorage.googleapis.com/v0/b/' . $bucketName . '/o/' . $encoded . '?alt=media';
  } catch (Exception $e) {
    _logUploadError('[Firebase] Exceção: ' . $e->getMessage() . ' (code: ' . $e->getCode() . ')');
    return false;
  }
}

/**
 * Remove um objeto do Firebase Storage a partir da URL pública.
 * URL esperada: https://firebasestorage.googleapis.com/v0/b/BUCKET/o/ENCODED_PATH?alt=media
 *
 * @param string $url URL pública do objeto (caminho em memorial_media)
 * @return bool true se removeu ou não era URL Firebase; false em erro
 */
function deleteFirebaseObjectByUrl($url) {
  if (!is_string($url) || $url === '') {
    return true;
  }
  if (strpos($url, 'firebasestorage.googleapis.com') === false) {
    return true;
  }
  $bucketName = defined('FIREBASE_STORAGE_BUCKET') ? FIREBASE_STORAGE_BUCKET : '';
  $keyPath = defined('FIREBASE_SERVICE_ACCOUNT_JSON') ? FIREBASE_SERVICE_ACCOUNT_JSON : '';
  if ($bucketName === '' || $bucketName === 'seu-projeto.appspot.com' || !file_exists($keyPath)) {
    return true;
  }
  if (!preg_match('#/v0/b/([^/]+)/o/([^?]+)#', $url, $m)) {
    _logUploadError('[Firebase] URL inválida para delete: ' . $url);
    return false;
  }
  $objectName = rawurldecode($m[2]);
  if (!class_exists('Google\Cloud\Storage\StorageClient')) {
    $autoload = dirname(__DIR__, 1) . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
      _logUploadError('[Firebase] vendor/autoload.php não encontrado');
      return false;
    }
    require_once $autoload;
  }
  try {
    $storage = new \Google\Cloud\Storage\StorageClient(['keyFilePath' => $keyPath]);
    $bucket = $storage->bucket($bucketName);
    $object = $bucket->object($objectName);
    $object->delete();
    return true;
  } catch (Exception $e) {
    _logUploadError('[Firebase] deleteObject: ' . $e->getMessage());
    return false;
  }
}

/**
 * Verifica se o Firebase Storage está configurado e disponível.
 *
 * @return bool
 */
function firebaseStorageDisponivel() {
  if (!defined('FIREBASE_STORAGE_BUCKET') || !defined('FIREBASE_SERVICE_ACCOUNT_JSON')) {
    return false;
  }
  $bucket = FIREBASE_STORAGE_BUCKET;
  $path = FIREBASE_SERVICE_ACCOUNT_JSON;
  return $bucket !== '' && $bucket !== 'seu-projeto.appspot.com' && file_exists($path);
}
