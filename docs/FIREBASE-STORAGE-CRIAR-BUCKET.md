# Configurar o bucket ao criar o Storage no Firebase

Ao clicar em **Get started** no Firebase Console → Storage, siga estes passos.

---

## 1. Modo de produção (Production mode) ou teste (Test mode)

- Escolha **Production mode** (modo de produção).
- Depois ajustamos as regras para permitir só leitura pública (o upload é feito pelo nosso servidor, não pelo navegador).

---

## 2. Localização do bucket (Location)

- Escolha uma região próxima dos seus usuários.
- Exemplos:
  - **Brasil:** `southamerica-east1` (São Paulo)
  - **EUA:** `us-central1` ou `us-east1`
- Não é obrigatório ser a mais próxima; afeta só latência e custo.

---

## 3. Depois de criar: regras de segurança (Rules)

No Firebase Console → **Storage** → aba **Rules**, use algo assim:

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

**O que isso faz:**

- **read: if true** — qualquer pessoa pode **ver** as imagens (necessário para a página pública do memorial).
- **write: if false** — ninguém grava pelo **navegador**; o upload é feito só pelo **nosso servidor PHP** (conta de serviço), que não usa essas regras.

Clique em **Publish** para salvar.

---

## 4. O que não precisa configurar

- **Firebase Authentication** — não é necessário para este projeto; o controle de quem pode enviar foto é feito no nosso PHP (sessão + dono do memorial).
- **CORS** — em geral não é necessário; as imagens são acessadas por URL direta na página.

---

## 5. Depois de criar o bucket

1. Abra no navegador: `http://localhost/Imortalize/listar-buckets-firebase.php`
2. Copie o nome do bucket que aparecer.
3. Cole em `config/firebase.config.php` em **FIREBASE_STORAGE_BUCKET**.
4. Tente enviar uma imagem de novo no memorial.
