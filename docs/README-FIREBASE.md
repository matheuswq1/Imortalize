# Firebase Storage — Imortalize

As imagens dos memoriais são armazenadas no **Firebase Storage** (Google Cloud). O upload é feito pelo servidor PHP após o usuário enviar o arquivo.

## 1. Criar projeto no Firebase

1. Acesse [Firebase Console](https://console.firebase.google.com/).
2. Crie um projeto (ou use um existente).
3. No menu lateral: **Build** → **Storage** → **Get started**.
4. Crie o bucket (região de sua preferência). Anote o nome do bucket (ex: `seu-projeto.appspot.com`).

## 2. Chave da conta de serviço

1. No Firebase: **Configurações do projeto** (ícone de engrenagem) → **Contas de serviço**.
2. Clique em **Gerar nova chave privada**.
3. Salve o arquivo JSON em `config/firebase-service-account.json` (não versionar esse arquivo).

## 3. Regras do Storage (leitura pública)

Para que as fotos apareçam na página pública do memorial, as regras do Storage devem permitir leitura. No Firebase Console → Storage → **Rules**:

```
rules_version = '2';
service firebase.storage {
  match /b/{bucket}/o {
    match /memorials/{allPaths=**} {
      allow read: if true;
      allow write: if false;
    }
  }
}
```

(O upload é feito pelo backend com a conta de serviço; o cliente não escreve direto no Storage.)

## 4. Nome do bucket (erro "bucket does not exist")

Se ao enviar a imagem aparecer erro **404 - The specified bucket does not exist** no `logs/upload.log`:

**Passo 1 — Criar o bucket (se ainda não existir)**  
No **Firebase Console** (console.firebase.google.com) → projeto **imortalize** → menu **Storage** → clique em **Get started**. Isso cria o bucket padrão do projeto.

**Passo 2 — Descobrir o nome exato do bucket**  
Abra no navegador:

```
http://localhost/Imortalize/listar-buckets-firebase.php
```

O script lista os buckets do projeto. Copie o **nome exato** que aparecer e coloque em `config/firebase.config.php`:

```php
define('FIREBASE_STORAGE_BUCKET', 'NOME_EXATO_AQUI');
```

Se o script mostrar **"Nenhum bucket encontrado"**, volte ao Firebase Console → Storage → Get started e crie o bucket; depois execute o script de novo.

## 5. Configuração no projeto

1. Copie o arquivo de exemplo:
   ```
   config/firebase.config.example.php  →  config/firebase.config.php
   ```
2. Edite `config/firebase.config.php`:
   - **FIREBASE_SERVICE_ACCOUNT_JSON**: caminho para o JSON da conta de serviço (ex: `__DIR__ . '/firebase-service-account.json'`).
   - **FIREBASE_STORAGE_BUCKET**: nome do bucket (ex: `imortalize-xxxxx.appspot.com`).

## 6. Instalar dependência PHP

Na raiz do projeto:

```bash
composer install
```

Isso instala o cliente **Google Cloud Storage** (`google/cloud-storage`), usado para enviar os arquivos ao Firebase.

## 7. Testar

1. Faça login no sistema.
2. Abra um memorial e use o formulário **Adicionar foto (Firebase Storage)**.
3. Envie uma imagem; ela deve aparecer na lista de fotos e na página pública do memorial (se estiver publicado).

## Segurança

- O arquivo `config/firebase-service-account.json` contém credenciais. **Não** o coloque no Git; adicione ao `.gitignore`:
  ```
  config/firebase-service-account.json
  vendor/
  ```
