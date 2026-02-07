# Segurança das imagens (Firebase Storage)

## Sua dúvida

> O que impede um usuário qualquer de alterar uma imagem se ele não é dono do memorial, já que não usamos autenticação do Firebase?

Resposta: **quem grava no Firebase não é o navegador do usuário, e sim o nosso servidor PHP.** E o servidor só grava depois de verificar sessão e dono do memorial.

---

## Quem escreve no Firebase?

- **Não** é o navegador do visitante usando “login do Firebase”.
- **É o nosso servidor PHP** usando o arquivo **conta de serviço** (`firebase-service-account.json`).

Ou seja: o usuário **nunca** envia arquivo direto para o Firebase. Ele envia para o nosso site; o PHP valida e, se estiver tudo certo, **o PHP** envia a imagem para o Firebase.

Fluxo:

1. Usuário logado (sessão PHP) abre a página do memorial e escolhe uma foto.
2. O formulário envia a foto para **memorial-midia-upload.php** (nosso servidor).
3. O PHP:
   - verifica se há **sessão** (usuário logado);
   - verifica se o **memorial_id** enviado é **desse usuário** (dono do memorial);
   - só então: faz upload para o Firebase (usando a conta de serviço) e grava a URL no banco.

Se alguém tentar enviar um `memorial_id` de outro usuário, a consulta  
`SELECT id FROM memorials WHERE id = ? AND user_id = ?`  
não retorna linha (porque `user_id` da sessão não é o dono daquele memorial), e o script **não** faz upload e redireciona para a lista de memoriais.

---

## O que impede um “usuário qualquer” de alterar uma imagem?

1. **Upload**
   - Sem login (sessão): o PHP redireciona para a tela de login; nenhum upload é feito.
   - Logado, mas com `memorial_id` de outro: a checagem de dono falha; o PHP não envia nada para o Firebase e não insere nada no banco.

2. **Exclusão (quando existir botão “remover”)**
   - Será feita por um script PHP que usa algo como `removerMidiaMemorial($media_id, $user_id)`.
   - Essa função só apaga se a mídia pertencer a um memorial cujo `user_id` é o da sessão. Ou seja: só o dono do memorial pode “remover” a imagem do nosso sistema (banco + uso da URL).

3. **Alterar imagem “direto no Firebase”**
   - O navegador do usuário **não** tem a chave da conta de serviço.
   - Só o servidor tem. Então não há como um “usuário qualquer” autenticar no Firebase como administrador e alterar arquivos no Storage pela interface do Firebase ou por outro app usando a mesma conta.

Conclusão: **o que impede um usuário qualquer de alterar uma imagem é:**  
(a) autenticação e sessão no **nosso** sistema (PHP);  
(b) verificação de **propriedade do memorial** em todo upload (e em toda ação de remoção);  
(c) o fato de **só o servidor** escrever no Firebase, com a conta de serviço.

---

## Resumo do modelo

| Quem              | Autenticação        | Pode gravar no Firebase? |
|-------------------|---------------------|----------------------------|
| Navegador do user | Sessão PHP (login)  | Não (só envia arquivo ao PHP) |
| Servidor PHP      | Conta de serviço    | Sim, e só após validar dono do memorial |

Não utilizamos autenticação do Firebase para usuários finais; utilizamos **sessão PHP + checagem de dono do memorial**. O Firebase, no nosso fluxo, é só o “armazém” de arquivos; quem decide quem pode adicionar ou remover mídia é sempre o nosso backend PHP.
