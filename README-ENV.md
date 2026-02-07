# Configuração de Variáveis de Ambiente

Este projeto agora usa arquivos `.env` para armazenar credenciais e configurações sensíveis.

## Configuração Inicial

1. **Copie o arquivo de exemplo:**
   ```bash
   cp .env.example .env
   ```

2. **Edite o arquivo `.env`** e preencha com suas credenciais reais:
   - Chaves do Stripe
   - Credenciais do Google OAuth
   - Configurações do banco de dados (se necessário)

## Arquivos Ignorados pelo Git

Os seguintes arquivos contêm informações sensíveis e **NÃO** são versionados:

- `.env` - Arquivo com suas credenciais reais
- `config/stripe.config.php` - Configuração do Stripe (agora lê do .env)
- `config/google-oauth.config.php` - Configuração do Google OAuth (agora lê do .env)
- `config/database.config.php` - Configuração do banco de dados
- `config/site.config.php` - Configurações do site

## Como Funciona

Os arquivos de configuração (`config/stripe.config.php` e `config/google-oauth.config.php`) agora leem as variáveis do arquivo `.env` usando a função `env()` definida em `config/env.php`.

Exemplo:
```php
require_once __DIR__ . '/env.php';

return [
  'mode' => env('STRIPE_MODE', 'fake'),
  'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', ''),
  'secret_key' => env('STRIPE_SECRET_KEY', ''),
];
```

## Variáveis Disponíveis

### Banco de Dados
- `DB_HOST` - Host do banco de dados
- `DB_NAME` - Nome do banco de dados
- `DB_USER` - Usuário do banco
- `DB_PASS` - Senha do banco
- `DB_CHARSET` - Charset (padrão: utf8mb4)

### Stripe
- `STRIPE_MODE` - Modo: 'fake', 'test' ou 'live'
- `STRIPE_PUBLISHABLE_KEY` - Chave pública do Stripe
- `STRIPE_SECRET_KEY` - Chave secreta do Stripe
- `STRIPE_CURRENCY` - Moeda (padrão: brl)

### Google OAuth
- `GOOGLE_CLIENT_ID` - Client ID do Google OAuth
- `GOOGLE_CLIENT_SECRET` - Client Secret do Google OAuth
- `GOOGLE_REDIRECT_URI` - URI de redirecionamento (opcional)

## Importante

⚠️ **NUNCA** commite o arquivo `.env` no Git! Ele contém suas credenciais reais.

O arquivo `.env.example` serve como template e pode ser versionado, mas não contém valores reais.
