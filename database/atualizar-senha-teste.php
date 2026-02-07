<?php
/**
 * Atualiza a senha do usuário de teste no banco para "123456".
 * Execute UMA VEZ após importar imortalize.sql (quando conectar o app ao MySQL):
 *
 *   php database/atualizar-senha-teste.php
 *
 * Requer: variáveis de conexão ou arquivo config com PDO/MySQLi.
 * Por ora, gere o hash e rode o UPDATE manualmente se preferir:
 *
 *   php -r "echo password_hash('123456', PASSWORD_DEFAULT);"
 *   -- depois: UPDATE users SET password_hash = 'HASH_GERADO' WHERE email = 'teste@imortalize.com';
 */

$senha = '123456';
$hash = password_hash($senha, PASSWORD_DEFAULT);

echo "Hash bcrypt para a senha \"{$senha}\":\n";
echo $hash . "\n\n";
echo "Execute no MySQL:\n";
echo "UPDATE users SET password_hash = " . var_export($hash, true) . " WHERE email = 'teste@imortalize.com';\n";
