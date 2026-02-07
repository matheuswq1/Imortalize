# Configuração do Stripe — Imortalize

Este guia explica como configurar o Stripe no **painel do Stripe** e no **projeto**, para que a **taxa anual dos planos** fique vinculada automaticamente ao criar, alterar ou excluir um plano no admin.

---

## Parte 1: No painel do Stripe (dashboard.stripe.com)

### 1. Criar conta e ativar o Brasil

1. Acesse [https://dashboard.stripe.com](https://dashboard.stripe.com) e faça login (ou crie uma conta).
2. No menu lateral: **Settings** (Configurações) → **Business settings** → **Customer information**.
3. Confirme que o **país** está como **Brazil** e que pagamentos em **BRL** estão disponíveis.

### 2. Obter as chaves da API

1. No menu lateral: **Developers** → **API keys**.
2. Você verá duas chaves:
   - **Publishable key** (começa com `pk_test_` em teste ou `pk_live_` em produção).
   - **Secret key** (começa com `sk_test_` ou `sk_live_`) — clique em **Reveal** para copiar.
3. **Modo teste:** use as chaves **Test** para desenvolvimento (pagamentos simulados).
4. **Produção:** quando for ao ar, ative sua conta Stripe para pagamentos reais e use as chaves **Live**.

### 3. (Opcional) Webhooks para eventos

Para renovações automáticas e cancelamentos no futuro, você pode configurar um webhook:

1. **Developers** → **Webhooks** → **Add endpoint**.
2. **Endpoint URL:** `https://seu-dominio.com/webhook-stripe.php` (crie este arquivo depois se quiser).
3. **Events to send:** por exemplo `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`, `invoice.paid`.
4. Salve e copie o **Signing secret** (começa com `whsec_`) para usar no `stripe.config.php` como `webhook_secret`.

Por enquanto o projeto funciona **sem webhook** (checkout único anual). Você pode adicionar o webhook depois.

### 4. Produtos e preços no Stripe

No Imortalize, **não é obrigatório** criar produtos manualmente no painel. Eles são criados/atualizados automaticamente quando você:

- **Cria um novo plano** no admin → o sistema cria um **Product** e um **Price** (recorrente anual) no Stripe.
- **Altera o valor (preço anual)** do plano → o sistema cria um novo **Price** no Stripe e vincula ao mesmo Product.
- **Exclui** um plano → o sistema **arquiva** o Product no Stripe (não apaga, para manter histórico).

Se quiser conferir depois: no painel, **Product catalog** mostra os produtos criados pelo sistema.

---

## Parte 2: No projeto Imortalize

### 1. Instalar o SDK do Stripe (PHP)

No diretório do projeto:

```bash
composer require stripe/stripe-php
```

### 2. Configurar as chaves no projeto

1. Copie o arquivo de exemplo (se ainda não tiver):

   ```text
   config/stripe.config.example.php  →  config/stripe.config.php
   ```

2. Edite `config/stripe.config.php` e preencha:

   ```php
   return [
     'mode' => 'live',  // use 'fake' para desativar Stripe; 'test' ou 'live' para usar API
     'publishable_key' => 'pk_test_xxxxxxxx...',  // ou pk_live_...
     'secret_key' => 'sk_test_xxxxxxxx...',      // ou sk_live_...
     'currency' => 'brl',
     // Opcional, para webhooks no futuro:
     // 'webhook_secret' => 'whsec_xxxxxxxx...',
   ];
   ```

   - **Modo `fake`:** nenhuma chamada ao Stripe; checkout simulado (como antes).
   - **Modo `test` / `live`:** o sistema usa a API do Stripe e sincroniza planos ao criar/editar/excluir no admin.

### 3. Rodar a migração do banco

Execute o SQL que adiciona os campos de preço e Stripe na tabela `plans`:

```bash
# No MySQL (ou phpMyAdmin), execute o conteúdo de:
database/add_stripe_plans.sql
```

Isso adiciona: `preco_anual_cents`, `stripe_product_id`, `stripe_price_id`.

### 4. Preencher o preço nos planos existentes

1. Acesse o **admin** → **Planos**.
2. **Edite** cada plano e informe o **Preço anual (R$)**.
3. Salve. O sistema criará/atualizará o Product e o Price no Stripe e guardará os IDs no banco.

A partir daí:

- **Novo plano:** ao criar e salvar, o Product e o Price (anual) são criados no Stripe.
- **Alterar valor:** ao mudar o preço anual e salvar, um novo Price é criado no Stripe e vinculado ao plano.
- **Excluir plano:** ao excluir no admin, o Product é arquivado no Stripe (e o plano fica inativo ou é removido no seu banco, conforme implementado).

---

## Resumo do fluxo

| Ação no admin      | O que acontece no Stripe                                      |
|--------------------|---------------------------------------------------------------|
| Criar novo plano   | Cria Product + Price (recorrente anual) e salva IDs no plano. |
| Editar plano       | Atualiza nome/descrição do Product; se o preço mudou, cria novo Price e atualiza `stripe_price_id`. |
| Excluir plano      | Arquiva o Product no Stripe.                                  |

O checkout da aplicação usa o `stripe_price_id` do plano para criar a sessão de pagamento (Stripe Checkout Session ou PaymentIntent), garantindo que a **taxa anual** exibida e cobrada seja sempre a do plano cadastrado.
