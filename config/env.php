<?php
/**
 * Helper para carregar variáveis de ambiente do arquivo .env
 * 
 * Carrega o arquivo .env na raiz do projeto e disponibiliza via getenv() e $_ENV
 */

function loadEnv($envFile = null) {
  if ($envFile === null) {
    $envFile = dirname(__DIR__) . '/.env';
  }
  
  if (!file_exists($envFile)) {
    return;
  }
  
  $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  
  foreach ($lines as $line) {
    // Ignora comentários
    if (strpos(trim($line), '#') === 0) {
      continue;
    }
    
    // Ignora linhas sem =
    if (strpos($line, '=') === false) {
      continue;
    }
    
    list($key, $value) = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    
    // Remove aspas se existirem
    if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
        (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
      $value = substr($value, 1, -1);
    }
    
    // Define a variável de ambiente se ainda não estiver definida
    if (!array_key_exists($key, $_ENV)) {
      $_ENV[$key] = $value;
      putenv("$key=$value");
    }
  }
}

// Carrega o .env automaticamente quando este arquivo é incluído
loadEnv();

/**
 * Função helper para obter variável de ambiente com valor padrão
 * @param string $key Nome da variável
 * @param mixed $default Valor padrão se não existir
 * @return mixed
 */
function env($key, $default = null) {
  $value = $_ENV[$key] ?? getenv($key);
  if ($value === false) {
    return $default;
  }
  
  // Converte strings booleanas
  if (strtolower($value) === 'true' || strtolower($value) === '1') {
    return true;
  }
  if (strtolower($value) === 'false' || strtolower($value) === '0' || $value === '') {
    return false;
  }
  
  return $value;
}
