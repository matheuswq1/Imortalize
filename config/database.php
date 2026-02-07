<?php
/**
 * Configuração de conexão com o banco de dados — Imortalize
 *
 * Para usar banco online: copie config/database.config.example.php para
 * config/database.config.php e preencha host, dbname, user e password.
 * Os valores abaixo são usados só se database.config.php não existir (desenvolvimento local).
 */

$db_config_file = __DIR__ . '/database.config.php';
if (file_exists($db_config_file)) {
  $db = require $db_config_file;
  define('DB_HOST', $db['host'] ?? 'localhost');
  define('DB_NAME', $db['dbname'] ?? 'imortalize');
  define('DB_USER', $db['user'] ?? 'root');
  define('DB_PASS', $db['password'] ?? '');
  define('DB_CHARSET', $db['charset'] ?? 'utf8mb4');
  define('DB_PORT', isset($db['port']) ? (int) $db['port'] : null);
  define('DB_CONNECT_TIMEOUT', isset($db['connect_timeout']) ? (int) $db['connect_timeout'] : 10);
} else {
  define('DB_HOST', 'localhost');
  define('DB_NAME', 'imortalize');
  define('DB_USER', 'root');
  define('DB_PASS', '');
  define('DB_CHARSET', 'utf8mb4');
  define('DB_PORT', null);
  define('DB_CONNECT_TIMEOUT', 10);
}

$site_config_file = __DIR__ . '/site.config.php';
if (file_exists($site_config_file)) {
  $site = require $site_config_file;
  $base_url = isset($site['base_url']) ? trim($site['base_url']) : '';
  if ($base_url !== '') {
    define('BASE_URL', rtrim($base_url, '/'));
  }
}

/**
 * Retorna uma conexão PDO com o banco de dados
 * @return PDO
 */
function getDB() {
  static $pdo = null;
  
  if ($pdo === null) {
    try {
      $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
      if (defined('DB_PORT') && DB_PORT !== null && DB_PORT > 0) {
        $dsn .= ';port=' . DB_PORT;
      }
      $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
      ];
      if (defined('DB_CONNECT_TIMEOUT') && DB_CONNECT_TIMEOUT > 0) {
        $options[PDO::ATTR_TIMEOUT] = DB_CONNECT_TIMEOUT;
      }
      $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
      die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
    }
  }
  
  return $pdo;
}

/**
 * Garante que a tabela site_settings existe
 */
function ensureSiteSettingsTable() {
  $pdo = getDB();
  $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
    `key` VARCHAR(64) NOT NULL PRIMARY KEY,
    `value` TEXT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Retorna uma configuração do site (ex.: telefone de contato)
 * @param string $key Chave (ex.: contact_phone)
 * @param string $default Valor padrão se não existir
 * @return string
 */
function getSiteSetting($key, $default = '') {
  ensureSiteSettingsTable();
  $pdo = getDB();
  $stmt = $pdo->prepare('SELECT `value` FROM site_settings WHERE `key` = ? LIMIT 1');
  $stmt->execute([$key]);
  $row = $stmt->fetch();
  return $row !== false && $row['value'] !== null && $row['value'] !== '' ? $row['value'] : $default;
}

/**
 * Salva uma configuração do site
 * @param string $key Chave (ex.: contact_phone)
 * @param string $value Valor
 */
function setSiteSetting($key, $value) {
  ensureSiteSettingsTable();
  $pdo = getDB();
  $stmt = $pdo->prepare('INSERT INTO site_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
  $stmt->execute([$key, $value]);
}

/**
 * Retorna o telefone de contato do site (formato exibição)
 * @return string
 */
function getSiteContactPhone() {
  return getSiteSetting('contact_phone', '(17) 99777-1506');
}

/**
 * Retorna o telefone de contato para link tel: (apenas dígitos, com 55 se não tiver)
 * @return string Ex.: 5517997771506
 */
function getSiteContactPhoneLink() {
  $phone = getSiteSetting('contact_phone', '(17) 99777-1506');
  $digits = preg_replace('/\D/', '', $phone);
  if ($digits === '') return '5517997771506';
  if (strlen($digits) <= 11 && substr($digits, 0, 2) !== '55') return '55' . $digits;
  return $digits;
}

/** Chaves de redes sociais em site_settings */
define('SITE_SOCIAL_KEYS', ['social_facebook', 'social_instagram', 'social_whatsapp', 'social_youtube']);

/**
 * Retorna as URLs das redes sociais configuradas (apenas as preenchidas)
 * @return array Associativo: ['facebook' => 'url', 'instagram' => 'url', ...]
 */
function getSiteSocialLinks() {
  $labels = [
    'social_facebook'  => 'facebook',
    'social_instagram' => 'instagram',
    'social_whatsapp'  => 'whatsapp',
    'social_youtube'   => 'youtube',
  ];
  $out = [];
  foreach ($labels as $key => $slug) {
    $url = getSiteSetting($key, '');
    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
      $out[$slug] = $url;
    }
  }
  return $out;
}

/** Opções pré-definidas para "Qual sua relação com essa pessoa?" (chave => rótulo) */
function getRelacaoPublicadorOpcoes() {
  return [
    'filha' => 'Filha',
    'filho' => 'Filho',
    'mae' => 'Mãe',
    'pai' => 'Pai',
    'esposa' => 'Esposa',
    'esposo' => 'Esposo',
    'irma' => 'Irmã',
    'irmao' => 'Irmão',
    'neta' => 'Neta',
    'neto' => 'Neto',
    'sobrinha' => 'Sobrinha',
    'sobrinho' => 'Sobrinho',
    'amiga' => 'Amiga',
    'amigo' => 'Amigo',
    'prima' => 'Prima',
    'primo' => 'Primo',
    'outro' => 'Outro(a)',
  ];
}

/**
 * Retorna o rótulo da relação pelo valor salvo (chave).
 * @param string|null $chave
 * @return string
 */
function getRelacaoPublicadorLabel($chave) {
  if ($chave === null || $chave === '') return '';
  $opcoes = getRelacaoPublicadorOpcoes();
  return isset($opcoes[$chave]) ? $opcoes[$chave] : $chave;
}

/**
 * Verifica se as credenciais (e-mail + senha) são válidas no banco.
 * Usuários com password_hash NULL (ex.: login só via Google) não podem entrar por senha.
 *
 * @param string $email
 * @param string $senha
 * @return array|false Retorna dados do usuário ou false se inválido
 */
function verificarLogin($email, $senha) {
  $pdo = getDB();
  
  $stmt = $pdo->prepare('SELECT id, nome, email, telefone, password_hash FROM users WHERE email = ? LIMIT 1');
  $stmt->execute([$email]);
  $user = $stmt->fetch();
  
  if (!$user) {
    return false;
  }
  // Usuário criado só pelo Google não tem senha
  if ($user['password_hash'] === null || $user['password_hash'] === '') {
    return false;
  }
  if (!password_verify($senha, $user['password_hash'])) {
    return false;
  }
  unset($user['password_hash']);
  return $user;
}

/**
 * Busca usuário por google_id ou cria/atualiza com dados do Google.
 * Usado no callback do OAuth. Cria usuário com password_hash NULL se não existir.
 *
 * @param string $google_id
 * @param string $email
 * @param string $nome
 * @return array|null Dados do usuário (id, nome, email, telefone) ou null em erro
 */
function buscarOuCriarUsuarioGoogle($google_id, $email, $nome) {
  $pdo = getDB();
  $stmt = $pdo->prepare('SELECT id, nome, email, telefone, password_hash FROM users WHERE google_id = ? LIMIT 1');
  $stmt->execute([$google_id]);
  $user = $stmt->fetch();
  if ($user) {
    unset($user['password_hash']);
    return $user;
  }
  $stmt = $pdo->prepare('SELECT id, nome, email, telefone, password_hash FROM users WHERE email = ? LIMIT 1');
  $stmt->execute([$email]);
  $user = $stmt->fetch();
  if ($user) {
    $up = $pdo->prepare('UPDATE users SET google_id = ?, nome = COALESCE(NULLIF(?, ""), nome) WHERE id = ?');
    $up->execute([$google_id, $nome, $user['id']]);
    unset($user['password_hash']);
    return $user;
  }
  try {
    $ins = $pdo->prepare('INSERT INTO users (nome, email, google_id, telefone, password_hash) VALUES (?, ?, ?, NULL, NULL)');
    $ins->execute([$nome ?: 'Usuário', $email, $google_id]);
    $id = (int) $pdo->lastInsertId();
    return ['id' => $id, 'nome' => $nome ?: 'Usuário', 'email' => $email, 'telefone' => null];
  } catch (PDOException $e) {
    error_log('buscarOuCriarUsuarioGoogle: ' . $e->getMessage());
    return null;
  }
}

/**
 * Busca dados do usuário por ID (para tela de configurações).
 * @param int $user_id
 * @return array|null id, nome, email, telefone, tem_senha (true se tem password_hash)
 */
function buscarUsuarioPorId($user_id) {
  $pdo = getDB();
  $stmt = $pdo->prepare('SELECT id, nome, email, telefone, password_hash FROM users WHERE id = ? LIMIT 1');
  $stmt->execute([$user_id]);
  $user = $stmt->fetch();
  if (!$user) return null;
  $user['tem_senha'] = !empty($user['password_hash']);
  unset($user['password_hash']);
  return $user;
}

/**
 * Atualiza nome, email e telefone do usuário.
 * @param int $user_id
 * @param string $nome
 * @param string $email
 * @return bool true se ok; false se e-mail já em uso por outro usuário
 */
function atualizarPerfilUsuario($user_id, $nome, $email, $telefone) {
  $pdo = getDB();
  $email = trim($email);
  $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
  $stmt->execute([$email, $user_id]);
  if ($stmt->fetch()) return false;
  $stmt = $pdo->prepare('UPDATE users SET nome = ?, email = ?, telefone = ? WHERE id = ?');
  $stmt->execute([trim($nome), $email, $telefone ? trim($telefone) : null, $user_id]);
  return true;
}

/**
 * Altera a senha do usuário. Para quem não tem senha (ex.: login Google), $senha_atual pode ser null.
 * @param int $user_id
 * @param string|null $senha_atual Senha atual (null se usuário ainda não tem senha)
 * @param string $senha_nova
 * @return bool true se alterado; false se senha atual incorreta ou erro
 */
function alterarSenhaUsuario($user_id, $senha_atual, $senha_nova) {
  $pdo = getDB();
  $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
  $stmt->execute([$user_id]);
  $row = $stmt->fetch();
  if (!$row) return false;
  if ($row['password_hash'] !== null && $row['password_hash'] !== '') {
    if (!password_verify($senha_atual, $row['password_hash'])) return false;
  }
  $hash = password_hash($senha_nova, PASSWORD_DEFAULT);
  $up = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
  $up->execute([$hash, $user_id]);
  return true;
}

/**
 * Busca o plano ativo de um usuário
 * @param int $user_id
 * @return array|false Retorna dados do plano e assinatura ou false
 */
function buscarPlanoAtivo($user_id) {
  $pdo = getDB();
  
  $stmt = $pdo->prepare('
    SELECT 
      s.id as subscription_id,
      s.status,
      s.data_inicio,
      s.data_vencimento,
      p.id as plan_id,
      p.nome as plano_nome,
      p.slug as plano_slug,
      p.max_fotos,
      p.tem_audio,
      p.tem_video
    FROM subscriptions s
    INNER JOIN plans p ON s.plan_id = p.id
    WHERE s.user_id = ? AND s.status = "ativo" AND s.data_vencimento >= CURDATE()
    ORDER BY s.data_vencimento DESC
    LIMIT 1
  ');
  
  $stmt->execute([$user_id]);
  $subscription = $stmt->fetch();
  
  if ($subscription) {
    // Buscar benefícios do plano
    $stmtBenefits = $pdo->prepare('
      SELECT descricao 
      FROM plan_benefits 
      WHERE plan_id = ? 
      ORDER BY ordem ASC
    ');
    $stmtBenefits->execute([$subscription['plan_id']]);
    $benefits = $stmtBenefits->fetchAll(PDO::FETCH_COLUMN);
    
    $subscription['beneficios'] = $benefits;
    
    // Formatar datas
    $subscription['validade'] = calcularValidade($subscription['data_inicio'], $subscription['data_vencimento']);
    $subscription['vencimento_formatado'] = date('d/m/Y', strtotime($subscription['data_vencimento']));
    $subscription['status_label'] = ucfirst($subscription['status']);
  }
  
  return $subscription;
}

/**
 * Calcula a validade em meses
 */
function calcularValidade($data_inicio, $data_vencimento) {
  $inicio = new DateTime($data_inicio);
  $vencimento = new DateTime($data_vencimento);
  $diff = $inicio->diff($vencimento);
  
  $meses = ($diff->y * 12) + $diff->m;
  return $meses . ' meses';
}

/**
 * Lista todos os planos ativos com benefícios (para tela de alterar plano).
 *
 * @return array Lista de planos com chave 'beneficios' em cada um
 */
function listarPlanos() {
  $pdo = getDB();
  $stmt = $pdo->query('
    SELECT id, nome, slug, descricao_curta, max_fotos, tem_audio, tem_video, ordem, preco_anual_cents, stripe_price_id
    FROM plans
    WHERE ativo = 1
      AND stripe_price_id IS NOT NULL AND stripe_price_id != ""
    ORDER BY ordem ASC
  ');
  $planos = $stmt->fetchAll();
  foreach ($planos as &$p) {
    $stmtB = $pdo->prepare('SELECT descricao FROM plan_benefits WHERE plan_id = ? ORDER BY ordem ASC');
    $stmtB->execute([$p['id']]);
    $p['beneficios'] = $stmtB->fetchAll(PDO::FETCH_COLUMN);
  }
  return $planos;
}

/**
 * Busca um plano por ID (para checkout).
 * @param int $plan_id
 * @return array|null Plano com 'beneficios' ou null
 */
function buscarPlanoPorId($plan_id) {
  $pdo = getDB();
  $stmt = $pdo->prepare('
    SELECT id, nome, slug, descricao_curta, max_fotos, tem_audio, tem_video, ordem, preco_anual_cents, stripe_price_id
    FROM plans
    WHERE id = ? AND ativo = 1
      AND stripe_price_id IS NOT NULL AND stripe_price_id != ""
    LIMIT 1
  ');
  $stmt->execute([$plan_id]);
  $plano = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$plano) return null;
  $stmtB = $pdo->prepare('SELECT descricao FROM plan_benefits WHERE plan_id = ? ORDER BY ordem ASC');
  $stmtB->execute([$plan_id]);
  $plano['beneficios'] = $stmtB->fetchAll(PDO::FETCH_COLUMN);
  return $plano;
}

/**
 * Altera o plano do usuário: cancela a assinatura ativa e cria nova (12 meses).
 * Retorna true se sucesso, false se plano não existir ou estiver inativo.
 *
 * @param int $user_id
 * @param int $plan_id
 * @return bool
 */
function alterarPlano($user_id, $plan_id) {
  $pdo = getDB();
  $stmt = $pdo->prepare('SELECT id FROM plans WHERE id = ? AND ativo = 1 LIMIT 1');
  $stmt->execute([$plan_id]);
  if (!$stmt->fetch()) {
    return false;
  }
  $pdo->beginTransaction();
  try {
    $stmt = $pdo->prepare('UPDATE subscriptions SET status = "cancelado" WHERE user_id = ? AND status = "ativo"');
    $stmt->execute([$user_id]);
    $inicio = date('Y-m-d');
    $vencimento = date('Y-m-d', strtotime('+12 months'));
    $stmt = $pdo->prepare('INSERT INTO subscriptions (user_id, plan_id, status, data_inicio, data_vencimento) VALUES (?, ?, "ativo", ?, ?)');
    $stmt->execute([$user_id, $plan_id, $inicio, $vencimento]);
    $pdo->commit();
    return true;
  } catch (Exception $e) {
    $pdo->rollBack();
    return false;
  }
}

/**
 * Busca todos os memoriais de um usuário
 * @param int $user_id
 * @return array Lista de memoriais
 */
function buscarMemoriais($user_id) {
  $pdo = getDB();
  
  $stmt = $pdo->prepare('
    SELECT 
      id,
      titulo,
      slug,
      biografia,
      status,
      qr_code_path,
      created_at,
      updated_at
    FROM memorials
    WHERE user_id = ?
    ORDER BY created_at DESC
  ');
  
  $stmt->execute([$user_id]);
  $memoriais = $stmt->fetchAll();
  
  // Buscar contagem de mídias por memorial
  foreach ($memoriais as &$memorial) {
    $stmtMedia = $pdo->prepare('
      SELECT COUNT(*) as total, tipo
      FROM memorial_media
      WHERE memorial_id = ?
      GROUP BY tipo
    ');
    $stmtMedia->execute([$memorial['id']]);
    $medias = $stmtMedia->fetchAll();
    
    $memorial['total_fotos'] = 0;
    $memorial['total_videos'] = 0;
    $memorial['total_audios'] = 0;
    
    foreach ($medias as $media) {
      if ($media['tipo'] === 'foto') $memorial['total_fotos'] = (int)$media['total'];
      if ($media['tipo'] === 'video') $memorial['total_videos'] = (int)$media['total'];
      if ($media['tipo'] === 'audio') $memorial['total_audios'] = (int)$media['total'];
    }
    
    $memorial['data_formatada'] = date('d/m/Y', strtotime($memorial['created_at']));
  }
  
  return $memoriais;
}

/**
 * Cria um novo memorial com todos os campos opcionais (iguais à edição).
 * @param int $user_id
 * @param string $titulo
 * @param string|null $biografia
 * @param string|null $nome Nome exibido abaixo da foto de perfil
 * @param string|null $relacao_publicador
 * @param string|null $data_nascimento (Y-m-d)
 * @param string|null $data_obito (Y-m-d)
 * @param string|null $mensagem_curta (máx. 120 caracteres)
 * @return int|false ID do memorial criado ou false
 */
function criarMemorial($user_id, $titulo, $biografia = null, $nome = null, $relacao_publicador = null, $data_nascimento = null, $data_obito = null, $mensagem_curta = null) {
  $pdo = getDB();
  
  $slug_base = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titulo)));
  $slug = $slug_base;
  $contador = 1;
  
  while (true) {
    $stmt = $pdo->prepare('SELECT id FROM memorials WHERE slug = ?');
    $stmt->execute([$slug]);
    if (!$stmt->fetch()) break;
    $slug = $slug_base . '-' . $contador;
    $contador++;
  }
  
  $nome_val = $nome !== null && $nome !== '' ? trim($nome) : null;
  $relacao_val = $relacao_publicador !== null && $relacao_publicador !== '' ? $relacao_publicador : null;
  $dn_val = $data_nascimento !== null && $data_nascimento !== '' ? $data_nascimento : null;
  $do_val = $data_obito !== null && $data_obito !== '' ? $data_obito : null;
  $msg_val = $mensagem_curta !== null && $mensagem_curta !== '' ? trim($mensagem_curta) : null;
  if ($msg_val !== null && mb_strlen($msg_val) > 120) $msg_val = mb_substr($msg_val, 0, 120);

  try {
    $stmt = $pdo->prepare('
      INSERT INTO memorials (user_id, titulo, slug, biografia, status, nome, relacao_publicador, data_nascimento, data_obito, mensagem_curta)
      VALUES (?, ?, ?, ?, "rascunho", ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$user_id, $titulo, $slug, $biografia, $nome_val, $relacao_val, $dn_val, $do_val, $msg_val]);
    return (int) $pdo->lastInsertId();
  } catch (PDOException $e) {
    error_log('criarMemorial: ' . $e->getMessage());
    return false;
  }
}

/**
 * Verifica se um slug é válido para uso na URL pública (segurança e consistência).
 * Aceita apenas a-z, 0-9 e hífen; comprimento entre 1 e 255.
 *
 * @param string $slug
 * @return bool
 */
function slugValido($slug) {
  if (!is_string($slug) || $slug === '') {
    return false;
  }
  $len = strlen($slug);
  if ($len > 255) {
    return false;
  }
  return (bool) preg_match('/^[a-z0-9\-]+$/', $slug);
}

/**
 * Retorna a URL base do site (para montar links públicos dos memoriais).
 * Em produção, você pode definir BASE_URL em um .env ou config para forçar o domínio.
 */
function getBaseUrl() {
  if (defined('BASE_URL') && BASE_URL !== '') {
    return rtrim(BASE_URL, '/');
  }
  $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
  $path = str_replace('\\', '/', $path);
  if ($path === '/' || $path === '.') {
    $path = '';
  }
  return $scheme . '://' . $host . $path;
}

/**
 * Retorna a URL da raiz do site (onde ficam memorial-publico.php, redirect.php, etc.).
 * Quando a requisição é em /admin/..., sobe um nível para não apontar para /admin/.
 */
function getSiteBaseUrl() {
  if (defined('BASE_URL') && BASE_URL !== '') {
    return rtrim(BASE_URL, '/');
  }
  $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $script = $_SERVER['SCRIPT_NAME'] ?? '';
  $script = str_replace('\\', '/', $script);
  $path = (strpos($script, '/admin/') !== false) ? dirname($script, 2) : dirname($script);
  $path = str_replace('\\', '/', $path);
  if ($path === '/' || $path === '.') {
    $path = '';
  }
  return $scheme . '://' . $host . $path;
}

 /**
 * Normaliza URL salva no QR: se for localhost/127.0.0.1, reescreve para a base do site (produção).
 * Assim QRs editados em ambiente local passam a redirecionar para o domínio correto.
 * Se for memorial-publico.php, reconstrói a URL canónica para evitar path local (/imortalize/...).
 *
 * @param string $url URL armazenada (pode ser http://localhost/... ou produção)
 * @return string URL final para redirect
 */
function normalizarUrlQrRedirect($url) {
  $url = trim($url);
  if ($url === '') {
    return $url;
  }
  $parsed = @parse_url($url);
  if (!$parsed || empty($parsed['host'])) {
    return $url;
  }
  $host = strtolower($parsed['host']);
  $is_local = ($host === 'localhost' || $host === '127.0.0.1' || strpos($host, 'localhost.') === 0);
  if (!$is_local) {
    return $url;
  }
  $base = getSiteBaseUrl();
  $path = isset($parsed['path']) ? $parsed['path'] : '/';
  $query = isset($parsed['query']) && $parsed['query'] !== '' ? '?' . $parsed['query'] : '';

  // URL do memorial: sempre reconstruir para a base do site (evita /imortalize/ em produção)
  if (strpos($path, 'memorial-publico.php') !== false) {
    return rtrim($base, '/') . '/memorial-publico.php' . $query;
  }

  $path = ($path !== '/' && strpos($path, '/') === 0) ? substr($path, 1) : $path;
  return rtrim($base, '/') . '/' . ltrim($path, '/') . $query;
}

/**
 * Retorna a URL pública de um memorial (para compartilhar ou usar no sistema de QR Code).
 * O usuário pode colar esse link no sistema externo de QR Code.
 *
 * @param string $slug Slug do memorial
 * @return string URL completa
 */
function urlMemorialPublico($slug) {
  return getSiteBaseUrl() . '/memorial-publico.php?slug=' . urlencode($slug);
}

/**
 * Busca um memorial pelo slug para exibição pública.
 * Só retorna se status = 'publicado'.
 *
 * @param string $slug Slug do memorial
 * @return array|false Dados do memorial ou false se não encontrado/não publicado
 */
function buscarMemorialPublico($slug) {
  if (!slugValido($slug)) {
    return false;
  }
  $pdo = getDB();
  
  $stmt = $pdo->prepare('
    SELECT m.id, m.titulo, m.nome, m.slug, m.biografia, m.data_nascimento, m.data_obito,
           m.perfil_media_id, m.capa_media_id, m.relacao_publicador, m.status, m.created_at, m.updated_at,
           u.nome AS publicador_nome
    FROM memorials m
    INNER JOIN users u ON u.id = m.user_id
    WHERE m.slug = ? AND m.status = "publicado"
    LIMIT 1
  ');
  $stmt->execute([$slug]);
  $memorial = $stmt->fetch();
  
  if (!$memorial) {
    return false;
  }
  
  // Buscar mídias
  $stmt = $pdo->prepare('SELECT * FROM memorial_media WHERE memorial_id = ? ORDER BY ordem ASC, created_at ASC');
  $stmt->execute([$memorial['id']]);
  $memorial['medias'] = $stmt->fetchAll();
  
  // Resolver URLs da foto de perfil e da capa (para o layout público)
  $memorial['foto_perfil'] = null;
  $memorial['foto_capa'] = null;
  $perfil_id = isset($memorial['perfil_media_id']) ? (int) $memorial['perfil_media_id'] : 0;
  $capa_id = isset($memorial['capa_media_id']) ? (int) $memorial['capa_media_id'] : 0;
  foreach ($memorial['medias'] as $m) {
    if ((int) $m['id'] === $perfil_id && $m['tipo'] === 'foto') {
      $memorial['foto_perfil'] = $m;
    }
    if ((int) $m['id'] === $capa_id && $m['tipo'] === 'foto') {
      $memorial['foto_capa'] = $m;
    }
  }
  
  return $memorial;
}

/**
 * Publica um memorial (status = publicado).
 * Só o dono pode publicar.
 *
 * @param int $memorial_id
 * @param int $user_id
 * @return bool
 */
function publicarMemorial($memorial_id, $user_id) {
  $pdo = getDB();
  $stmt = $pdo->prepare('UPDATE memorials SET status = "publicado" WHERE id = ? AND user_id = ?');
  $stmt->execute([$memorial_id, $user_id]);
  return $stmt->rowCount() > 0;
}

/**
 * Adiciona uma mídia (foto, vídeo, áudio) ao memorial. Verifica se o memorial pertence ao usuário.
 *
 * @param int $memorial_id
 * @param int $user_id
 * @param string $tipo 'foto', 'video', 'audio', 'texto'
 * @param string $caminho URL ou caminho (ex: URL do Firebase Storage)
 * @param string|null $legenda
 * @return int|false ID da mídia inserida ou false
 */
function adicionarMidiaMemorial($memorial_id, $user_id, $tipo, $caminho, $legenda = null) {
  $pdo = getDB();
  $stmt = $pdo->prepare('SELECT id FROM memorials WHERE id = ? AND user_id = ? LIMIT 1');
  $stmt->execute([$memorial_id, $user_id]);
  if (!$stmt->fetch()) {
    return false;
  }
  $tipos = ['foto', 'video', 'audio', 'texto'];
  if (!in_array($tipo, $tipos, true)) {
    $tipo = 'foto';
  }
  $stmt = $pdo->prepare('INSERT INTO memorial_media (memorial_id, tipo, caminho, legenda, ordem) VALUES (?, ?, ?, ?, 0)');
  $stmt->execute([$memorial_id, $tipo, $caminho, $legenda]);
  return (int) $pdo->lastInsertId();
}

/**
 * Remove uma mídia do memorial. Só remove se o memorial pertencer ao usuário.
 *
 * @param int $media_id
 * @param int $user_id
 * @return bool
 */
function removerMidiaMemorial($media_id, $user_id) {
  $pdo = getDB();
  $stmt = $pdo->prepare('
    DELETE mm FROM memorial_media mm
    INNER JOIN memorials m ON m.id = mm.memorial_id
    WHERE mm.id = ? AND m.user_id = ?
  ');
  $stmt->execute([$media_id, $user_id]);
  return $stmt->rowCount() > 0;
}

/**
 * Remove do memorial todas as mídias cujo id NÃO está em $keep_ids.
 * Só remove se o memorial pertencer ao usuário.
 *
 * @param int $memorial_id
 * @param int $user_id
 * @param int[] $keep_ids IDs das mídias a manter (vazio = remove todas)
 * @return int Quantidade removida
 */
function removerMidiasMemorialExceto($memorial_id, $user_id, array $keep_ids) {
  $pdo = getDB();
  $stmt = $pdo->prepare('SELECT id FROM memorials WHERE id = ? AND user_id = ? LIMIT 1');
  $stmt->execute([$memorial_id, $user_id]);
  if (!$stmt->fetch()) {
    return 0;
  }
  if (empty($keep_ids)) {
    $stmt = $pdo->prepare('DELETE mm FROM memorial_media mm INNER JOIN memorials m ON m.id = mm.memorial_id WHERE mm.memorial_id = ? AND m.user_id = ?');
    $stmt->execute([$memorial_id, $user_id]);
    return $stmt->rowCount();
  }
  $placeholders = implode(',', array_fill(0, count($keep_ids), '?'));
  $stmt = $pdo->prepare("
    DELETE mm FROM memorial_media mm
    INNER JOIN memorials m ON m.id = mm.memorial_id
    WHERE mm.memorial_id = ? AND m.user_id = ? AND mm.id NOT IN ($placeholders)
  ");
  $stmt->execute(array_merge([$memorial_id, $user_id], $keep_ids));
  return $stmt->rowCount();
}

/**
 * Gera um unique_id para QR (UUID-like, sem dependência externa).
 * @return string
 */
function qrcodeGerarUniqueId() {
  $data = random_bytes(16);
  $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Busca um QR disponível do estoque (para vincular a um memorial).
 * @return array|false Linha do qrcodes ou false
 */
function qrcodePegarDisponivel() {
  $pdo = getDB();
  $stmt = $pdo->query("SELECT id, unique_id, imagem_path FROM qrcodes WHERE status = 'disponivel' LIMIT 1 FOR UPDATE");
  return $stmt->fetch();
}

/**
 * Vincula um QR ao memorial e define content (URL do memorial público).
 * @param int $qr_id ID em qrcodes
 * @param int $memorial_id ID do memorial
 * @param string $url_memorial URL pública do memorial
 * @return bool
 */
function qrcodeVincularMemorial($qr_id, $memorial_id, $url_memorial) {
  $pdo = getDB();
  $content = json_encode(['url' => $url_memorial]);
  $stmt = $pdo->prepare("UPDATE qrcodes SET memorial_id = ?, status = 'usado', content = ? WHERE id = ? AND status = 'disponivel'");
  $stmt->execute([$memorial_id, $content, $qr_id]);
  return $stmt->rowCount() > 0;
}

/**
 * Atualiza memorials.qr_code_path com o caminho da imagem do QR.
 * @param int $memorial_id
 * @param string|null $path Caminho relativo (ex: qrcodes/qr_xxx.png)
 * @return bool
 */
function memorialAtualizarQrCodePath($memorial_id, $path) {
  $pdo = getDB();
  $stmt = $pdo->prepare('UPDATE memorials SET qr_code_path = ? WHERE id = ?');
  $stmt->execute([$path, $memorial_id]);
  return $stmt->rowCount() > 0;
}

/**
 * Busca QR por unique_id (para redirect).
 * @param string $unique_id
 * @return array|false
 */
function qrcodePorUniqueId($unique_id) {
  if ($unique_id === '' || strlen($unique_id) > 36) {
    return false;
  }
  $pdo = getDB();
  $stmt = $pdo->prepare('SELECT id, content, scans, status FROM qrcodes WHERE unique_id = ? LIMIT 1');
  $stmt->execute([$unique_id]);
  return $stmt->fetch();
}

/**
 * Incrementa contador de scans do QR.
 * @param string $unique_id
 */
function qrcodeIncrementarScans($unique_id) {
  $pdo = getDB();
  $stmt = $pdo->prepare('UPDATE qrcodes SET scans = scans + 1 WHERE unique_id = ?');
  $stmt->execute([$unique_id]);
}

/**
 * Insere um novo registro em qrcodes.
 * @param string $unique_id
 * @param string|null $imagem_path Caminho relativo (ex: qrcodes/qr_xxx.png)
 * @param int|null $memorial_id NULL para estoque
 * @param string|null $content JSON; NULL para estoque
 * @param string $status 'disponivel' ou 'usado'
 * @param string $layout_style 'padrao', 'circular' ou 'com_logo'
 * @return int|false ID inserido ou false
 */
function qrcodeInserir($unique_id, $imagem_path = null, $memorial_id = null, $content = null, $status = 'disponivel', $layout_style = 'padrao') {
  $pdo = getDB();
  $layout_style = in_array($layout_style, ['padrao', 'circular', 'com_logo'], true) ? $layout_style : 'padrao';
  try {
    $stmt = $pdo->prepare('INSERT INTO qrcodes (unique_id, imagem_path, memorial_id, content, status, layout_style) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$unique_id, $imagem_path, $memorial_id, $content, $status, $layout_style]);
  } catch (PDOException $e) {
    if (strpos($e->getMessage(), 'layout_style') !== false) {
      $stmt = $pdo->prepare('INSERT INTO qrcodes (unique_id, imagem_path, memorial_id, content, status) VALUES (?, ?, ?, ?, ?)');
      $stmt->execute([$unique_id, $imagem_path, $memorial_id, $content, $status]);
    } else {
      throw $e;
    }
  }
  return (int) $pdo->lastInsertId();
}

/**
 * Lista QRs que têm imagem (para regenerar PNG com nova URL).
 * @return array [ ['id', 'unique_id', 'imagem_path', 'layout_style'], ... ]
 */
function qrcodesListarComImagem() {
  $pdo = getDB();
  try {
    $stmt = $pdo->query("SELECT id, unique_id, imagem_path, COALESCE(layout_style, 'padrao') AS layout_style FROM qrcodes WHERE imagem_path IS NOT NULL AND imagem_path != '' ORDER BY id ASC");
  } catch (PDOException $e) {
    if (strpos($e->getMessage(), 'layout_style') !== false) {
      $stmt = $pdo->query("SELECT id, unique_id, imagem_path FROM qrcodes WHERE imagem_path IS NOT NULL AND imagem_path != '' ORDER BY id ASC");
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
      foreach ($rows as &$r) {
        $r['layout_style'] = 'padrao';
      }
      return $rows;
    }
    throw $e;
  }
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as &$r) {
    if (!in_array($r['layout_style'], ['padrao', 'circular', 'com_logo'], true)) {
      $r['layout_style'] = 'padrao';
    }
  }
  return $rows;
}

/**
 * Lista QRs para dropdown "Copiar layout" (id, layout_style).
 * @param int $limit
 * @return array
 */
function qrcodesListarParaCopiarLayout($limit = 100) {
  $pdo = getDB();
  $has_layout = false;
  try {
    $stmt = $pdo->query('SELECT id, layout_style FROM qrcodes ORDER BY id DESC LIMIT ' . (int) $limit);
    $has_layout = true;
  } catch (PDOException $e) {
    if (strpos($e->getMessage(), 'layout_style') === false) {
      throw $e;
    }
    $stmt = $pdo->query('SELECT id FROM qrcodes ORDER BY id DESC LIMIT ' . (int) $limit);
  }
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if ($has_layout) {
    return $rows;
  }
  foreach ($rows as &$r) {
    $r['layout_style'] = 'padrao';
  }
  return $rows;
}

/**
 * Lista todos os QRs (admin) com dados do memorial e usuário.
 * @return array
 */
function qrcodesListarAdmin() {
  $pdo = getDB();
  $stmt = $pdo->query("
    SELECT q.id, q.unique_id, q.memorial_id, q.type, q.content, q.scans, q.status, q.imagem_path, q.created_at,
           m.titulo as memorial_titulo, m.slug as memorial_slug, m.user_id
    FROM qrcodes q
    LEFT JOIN memorials m ON m.id = q.memorial_id
    ORDER BY q.id ASC
  ");
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Conta total de QRs (admin) com filtro opcional.
 * @param string $filtro 'todos' | 'disponivel' | 'usado'
 * @return int
 */
function qrcodesContarAdmin($filtro = 'todos', $busca_id = null) {
  if ($busca_id !== null && (int) $busca_id > 0) {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT 1 FROM qrcodes WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $busca_id]);
    return $stmt->fetch() ? 1 : 0;
  }
  $pdo = getDB();
  $sql = 'SELECT COUNT(*) FROM qrcodes q';
  $params = [];
  if ($filtro === 'disponivel') {
    $sql .= ' WHERE q.status = ?';
    $params[] = $filtro;
  } elseif ($filtro === 'usado') {
    $sql .= " WHERE (q.status = 'usado' OR (q.content IS NOT NULL AND LENGTH(CAST(q.content AS CHAR)) > 2))";
  }
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  return (int) $stmt->fetchColumn();
}

/**
 * Lista QRs (admin) com filtro e paginação. Ordenado por id ASC (numeração fixa).
 * @param string $filtro 'todos' | 'disponivel' | 'usado'
 * @param int $limit
 * @param int $offset
 * @return array
 */
function qrcodesListarAdminFiltrado($filtro = 'todos', $limit = 20, $offset = 0, $busca_id = null) {
  $pdo = getDB();
  $sql = "
    SELECT q.id, q.unique_id, q.memorial_id, q.type, q.content, q.scans, q.status, q.imagem_path, q.created_at,
           m.titulo as memorial_titulo, m.slug as memorial_slug, m.user_id
    FROM qrcodes q
    LEFT JOIN memorials m ON m.id = q.memorial_id
  ";
  $params = [];
  if ($busca_id !== null && (int) $busca_id > 0) {
    $sql .= ' WHERE q.id = ?';
    $params[] = (int) $busca_id;
  } elseif ($filtro === 'disponivel') {
    $sql .= ' WHERE q.status = ?';
    $params[] = $filtro;
  } elseif ($filtro === 'usado') {
    $sql .= " WHERE (q.status = 'usado' OR (q.content IS NOT NULL AND LENGTH(CAST(q.content AS CHAR)) > 2))";
  }
  $sql .= ' ORDER BY q.id ASC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Busca um QR por ID (admin).
 * @param int $id ID em qrcodes
 * @return array|false
 */
function qrcodePorId($id) {
  $pdo = getDB();
  $sql = '
    SELECT q.id, q.unique_id, q.memorial_id, q.type, q.content, q.scans, q.status, q.imagem_path, q.created_at,
           m.titulo as memorial_titulo, m.slug as memorial_slug
    FROM qrcodes q
    LEFT JOIN memorials m ON m.id = q.memorial_id
    WHERE q.id = ?
    LIMIT 1
  ';
  try {
    $stmt = $pdo->prepare('
      SELECT q.id, q.unique_id, q.memorial_id, q.type, q.content, q.scans, q.status, q.layout_style, q.imagem_path, q.created_at,
             m.titulo as memorial_titulo, m.slug as memorial_slug
      FROM qrcodes q
      LEFT JOIN memorials m ON m.id = q.memorial_id
      WHERE q.id = ?
      LIMIT 1
    ');
    $stmt->execute([(int) $id]);
  } catch (PDOException $e) {
    if (strpos($e->getMessage(), 'layout_style') !== false) {
      $stmt = $pdo->prepare($sql);
      $stmt->execute([(int) $id]);
    } else {
      throw $e;
    }
  }
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($row && !array_key_exists('layout_style', $row)) {
    $row['layout_style'] = 'padrao';
  }
  return $row;
}

/**
 * Atualiza o estilo de layout de um QR (padrao, circular, com_logo).
 * @param int $qr_id ID em qrcodes
 * @param string $layout_style
 * @return bool
 */
function qrcodeAtualizarLayoutStyle($qr_id, $layout_style) {
  $pdo = getDB();
  $layout_style = in_array($layout_style, ['padrao', 'circular', 'com_logo'], true) ? $layout_style : 'padrao';
  try {
    $stmt = $pdo->prepare('UPDATE qrcodes SET layout_style = ? WHERE id = ?');
    $stmt->execute([$layout_style, (int) $qr_id]);
    return $stmt->rowCount() > 0;
  } catch (PDOException $e) {
    return false;
  }
}

/**
 * Atualiza o link (URL) de um QR e marca como usado.
 * @param int $qr_id ID em qrcodes
 * @param string $url URL de destino
 * @param int|null $memorial_id ID do memorial (opcional; NULL para link externo)
 * @return bool
 */
function qrcodeAtualizarLink($qr_id, $url, $memorial_id = null) {
  $pdo = getDB();
  $content = json_encode(['url' => $url]);
  $stmt = $pdo->prepare('UPDATE qrcodes SET content = ?, memorial_id = ?, status = ? WHERE id = ?');
  $stmt->execute([$content, $memorial_id ?: null, 'usado', (int) $qr_id]);
  return $stmt->rowCount() > 0;
}

/**
 * Desvincula um QR (volta para estoque).
 * @param int $qr_id ID em qrcodes
 * @return bool
 */
function qrcodeDesvincular($qr_id) {
  $pdo = getDB();
  $stmt = $pdo->prepare('UPDATE qrcodes SET content = NULL, memorial_id = NULL, status = ? WHERE id = ?');
  $stmt->execute(['disponivel', (int) $qr_id]);
  return $stmt->rowCount() > 0;
}

/**
 * Lista memoriais (id, titulo, slug) para select no admin.
 * @return array
 */
function listarMemoriaisParaSelect() {
  $pdo = getDB();
  $stmt = $pdo->query('SELECT id, titulo, slug FROM memorials ORDER BY titulo ASC');
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Conta QRs por status (admin).
 * @return array ['disponivel' => n, 'usado' => n]
 */
function qrcodesContarPorStatus() {
  $pdo = getDB();
  $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM qrcodes GROUP BY status");
  $out = ['disponivel' => 0, 'usado' => 0];
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $out[$row['status']] = (int) $row['total'];
  }
  return $out;
}

/**
 * Total de acessos (scans) em todos os QRs.
 * @return int
 */
function qrcodesTotalScans() {
  $pdo = getDB();
  $stmt = $pdo->query("SELECT COALESCE(SUM(scans), 0) FROM qrcodes");
  return (int) $stmt->fetchColumn();
}

/**
 * Top QRs por quantidade de acessos (para métricas no admin).
 * @param int $limit
 * @return array
 */
function qrcodesTopPorScans($limit = 10) {
  $pdo = getDB();
  $stmt = $pdo->prepare("
    SELECT q.id, q.unique_id, q.scans, q.status, q.imagem_path, m.titulo as memorial_titulo, m.id as memorial_id
    FROM qrcodes q
    LEFT JOIN memorials m ON m.id = q.memorial_id
    WHERE q.scans > 0
    ORDER BY q.scans DESC
    LIMIT " . (int) $limit
  );
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Verifica se o usuário tem acesso ao painel administrativo
 * Usa a coluna is_admin da tabela users. Se a coluna não existir, usa config (admin_emails).
 * @param int $user_id ID do usuário (ex: $_SESSION['user_id'])
 * @param string|null $user_email E-mail do usuário (fallback se coluna is_admin não existir)
 * @return bool
 */
function isAdmin($user_id, $user_email = null) {
  $pdo = getDB();
  try {
    $stmt = $pdo->prepare('SELECT is_admin FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $user_id]);
    $row = $stmt->fetch();
    if ($row !== false && isset($row['is_admin'])) {
      return (int) $row['is_admin'] === 1;
    }
  } catch (PDOException $e) {
    // Coluna is_admin pode não existir ainda
  }
  // Fallback: lista de e-mails no config
  if ($user_email !== null && $user_email !== '') {
    $config_file = __DIR__ . '/admin.config.php';
    if (is_file($config_file)) {
      $config = require $config_file;
      $emails = isset($config['admin_emails']) && is_array($config['admin_emails']) ? $config['admin_emails'] : [];
      return in_array(strtolower(trim($user_email)), array_map('strtolower', $emails), true);
    }
  }
  return false;
}
