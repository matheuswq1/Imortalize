# Banco de dados — Imortalize

## Criar o banco e popular (MySQL)

### Opção 1: Linha de comando

```bash
mysql -u root -p < database/imortalize.sql
```

(Informe a senha do `root` quando solicitado.)

### Opção 2: Usuário específico

```bash
mysql -u seu_usuario -p imortalize < database/imortalize.sql
```

(Crie o usuário antes, se necessário.)

### Opção 3: phpMyAdmin

1. Acesse o phpMyAdmin (ex.: http://localhost/phpmyadmin).
2. Aba **Importar**.
3. Escolha o arquivo `database/imortalize.sql`.
4. Clique em **Executar**.

---

## Senha do usuário de teste

- **E-mail:** `teste@imortalize.com`
- **Senha:** `123456`

Se o login com essa senha não funcionar após conectar o app ao MySQL, gere o hash e atualize:

```bash
php database/atualizar-senha-teste.php
```

Copie o `UPDATE` exibido e execute no MySQL (ou no phpMyAdmin).

---

## Estrutura criada

| Tabela            | Descrição                                      |
|-------------------|------------------------------------------------|
| `users`           | Usuários (nome, email, telefone, senha)       |
| `plans`           | Planos (Essencial, Tributo, Legado)           |
| `plan_benefits`   | Benefícios de cada plano                      |
| `subscriptions`   | Assinaturas (usuário + plano, status, datas)  |
| `memorials`       | Memoriais (título, slug, biografia, status)   |
| `memorial_media`  | Fotos, vídeos, áudios do memorial             |
| `newsletter`      | E-mails da newsletter                         |

O usuário de teste já possui uma assinatura **ativa** do **Plano Tributo** (12 meses).
