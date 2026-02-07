<?php
/**
 * Usuário de teste — Imortalize (simulação, sem banco de dados)
 * Use apenas para demonstração. Futuro: remover e usar autenticação real.
 */

define('USUARIO_TESTE_EMAIL', 'teste@imortalize.com');
define('USUARIO_TESTE_SENHA', '123456');
define('USUARIO_TESTE_NOME', 'Usuário Teste');

/**
 * Dados simulados do plano ativo do usuário teste
 */
function usuario_teste_plano() {
  return [
    'nome'       => 'Plano Tributo',
    'status'     => 'ativo',
    'status_label' => 'Ativo',
    'validade'   => '12 meses',
    'vencimento' => date('d/m/Y', strtotime('+1 year')),
    'beneficios' => [
      '24 Fotos',
      'Biografia completa',
      'Layout perfil e capa',
      'Áudio',
      'QR Code em aço inox',
    ],
  ];
}

/**
 * Verifica se as credenciais são do usuário teste
 */
function eh_usuario_teste($email, $senha) {
  $email = trim($email ?? '');
  $senha = $senha ?? '';
  return $email === USUARIO_TESTE_EMAIL && $senha === USUARIO_TESTE_SENHA;
}
