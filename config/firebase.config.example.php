<?php
/**
 * Configuração Firebase Storage — exemplo
 * Copie para firebase.config.php e preencha com os dados do seu projeto Firebase.
 *
 * 1. Acesse https://console.firebase.google.com/
 * 2. Selecione o projeto (ou crie um)
 * 3. Em Configurações do projeto > Contas de serviço > Gerar nova chave privada
 * 4. Salve o JSON em config/firebase-service-account.json (não versionar!)
 * 5. Em Storage, anote o nome do bucket (ex: seu-projeto.appspot.com)
 */

// Caminho para o arquivo JSON da conta de serviço (baixado do Firebase Console)
define('FIREBASE_SERVICE_ACCOUNT_JSON', __DIR__ . '/firebase-service-account.json');

// Nome do bucket do Firebase Storage (ex: imortalize-xxxxx.appspot.com)
define('FIREBASE_STORAGE_BUCKET', 'seu-projeto.appspot.com');
