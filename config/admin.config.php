<?php
/**
 * Configuração do painel administrativo — Imortalize
 *
 * Lista de e-mails com acesso ao painel admin (usado apenas se a coluna is_admin
 * não existir na tabela users). Quando is_admin existir, ela tem prioridade.
 *
 * Para dar acesso admin: coloque o e-mail aqui OU execute no banco:
 *   UPDATE users SET is_admin = 1 WHERE email = 'seu@email.com';
 */

return [
  'admin_emails' => [
    // 'admin@imortalize.com',  // descomente e adicione os e-mails de administradores
  ],
];
