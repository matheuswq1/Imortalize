<?php
/**
 * Configuração do Google OAuth — Imortalize
 * Copie para google-oauth.config.php e preencha com as credenciais do Google Cloud Console.
 *
 * Como obter:
 * 1. Acesse https://console.cloud.google.com/
 * 2. Crie um projeto ou selecione um existente
 * 3. APIs e Serviços > Credenciais > Criar credenciais > ID do cliente OAuth
 * 4. Tipo: Aplicativo da Web
 * 5. Em "URIs de redirecionamento autorizados" adicione a URL EXATA que o app usa.
 *
 * Para saber a URL exata: abra no navegador login-google-verificar-uri.php
 * (ex.: http://localhost/imortalize/login-google-verificar-uri.php) e copie o valor.
 * Cole essa URL no Google Cloud Console — deve ser idêntica (incluindo http/https e maiúsculas/minúsculas).
 *
 * NOTA: Este arquivo é apenas um exemplo.
 * A configuração real agora é feita através do arquivo .env
 * Copie .env.example para .env e preencha com suas credenciais reais.
 */

return [
  'client_id'     => 'seu_client_id_aqui.apps.googleusercontent.com',
  'client_secret' => 'seu_client_secret_aqui',

  // Opcional. Se vazio, o redirect_uri é montado automaticamente (protocolo + host + caminho + /login-google-callback.php).
  // Se definir aqui, use a MESMA URL que cadastrou no Google (ex.: 'http://localhost/imortalize/login-google-callback.php').
  'redirect_uri' => '',
];
