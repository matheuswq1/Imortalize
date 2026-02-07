<?php
/**
 * Configuração Firebase Storage — Imortalize
 * Bucket e caminho da conta de serviço para upload de imagens no servidor (PHP).
 *
 * Falta apenas: baixar o JSON da conta de serviço no Firebase Console e salvar
 * em config/firebase-service-account.json (veja instruções abaixo).
 */

// Caminho para o arquivo JSON da conta de serviço (baixado do Firebase Console)
// Firebase Console → Configurações do projeto → Contas de serviço → Gerar nova chave privada
define('FIREBASE_SERVICE_ACCOUNT_JSON', __DIR__ . '/firebase-service-account.json');

// Nome do bucket do Firebase Storage (o que aparece no Firebase Console / listar-buckets-firebase.php)
define('FIREBASE_STORAGE_BUCKET', 'imortalize.firebasestorage.app');
