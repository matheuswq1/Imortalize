# Onde ver os logs — Imortalize

Quando algo dá errado (por exemplo ao enviar uma imagem), use estes locais para ver o que aconteceu.

---

## 1. Log do upload (Firebase / nosso código)

**Arquivo:** `logs/upload.log` (na pasta raiz do projeto Imortalize)

- Caminho completo (XAMPP): `C:\xampp\htdocs\Imortalize\logs\upload.log`
- Erros do **upload para o Firebase** são gravados aqui (exceção do Google Cloud Storage).
- Cada linha tem data/hora e a mensagem de erro.

**Como usar:** Tente enviar a imagem de novo; depois abra esse arquivo no Bloco de notas ou no Cursor. A última linha será o erro mais recente.

---

## 2. Log do PHP (XAMPP)

**Arquivo:** `C:\xampp\php\logs\php_error_log`

- Erros gerais do PHP (sintaxe, falhas de extensão, etc.).
- Para saber o caminho exato no seu XAMPP: abra `C:\xampp\php\php.ini` e procure por `error_log`.

---

## 3. Log do Apache (XAMPP)

**Arquivo:** `C:\xampp\apache\logs\error.log`

- Erros do servidor web (requisição negada, timeout, etc.).
- Útil se a página nem carregar ou der 500 sem mensagem.

---

## Resumo rápido

| O que falhou              | Onde olhar primeiro      |
|---------------------------|---------------------------|
| Envio de imagem (Firebase)| `Imortalize/logs/upload.log` |
| Erro de PHP na página     | `xampp/php/logs/php_error_log` |
| Erro 500 / servidor       | `xampp/apache/logs/error.log` |

---

## Ativar exibição de erros no PHP (só em desenvolvimento)

Para ver erros na tela enquanto testa (não use em produção):

1. Abra `C:\xampp\php\php.ini`.
2. Procure e deixe assim:
   - `display_errors = On`
   - `log_errors = On`
3. Reinicie o Apache no painel do XAMPP.

Depois de achar o erro, volte `display_errors = Off` para não expor detalhes na página.
