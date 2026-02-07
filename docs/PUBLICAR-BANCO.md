# Conectar o projeto ao banco de dados online

Depois de subir o banco de dados para o servidor (MySQL na hospedagem), use um **arquivo de configuração** para apontar o projeto para esse banco.

---

## Passo a passo

### 1. Criar o arquivo de configuração

Na pasta **`config/`**, copie o arquivo de exemplo e renomeie:

- De: `database.config.example.php`
- Para: **`database.config.php`**

### 2. Preencher com os dados do banco online

Abra **`config/database.config.php`** e preencha com as informações que a **hospedagem** fornece para o MySQL:

| Campo     | O que colocar |
|----------|----------------|
| **host** | Endereço do servidor MySQL (ex.: `mysql.seudominio.com` ou `localhost` ou o IP). A hospedagem informa isso no painel. |
| **dbname** | Nome do banco que você criou/importou (ex.: `imortalize` ou o nome que a hospedagem deu). |
| **user** | Usuário do MySQL criado na hospedagem. |
| **password** | Senha desse usuário. |
| **charset** | Deixe `utf8mb4`. |

Exemplo para um banco online:

```php
return [
  'host'     => 'mysql.seudominio.com.br',
  'dbname'   => 'usuario123_imortalize',
  'user'     => 'usuario123_imortalize',
  'password' => 'sua_senha_segura',
  'charset'  => 'utf8mb4',
];
```

### 3. Subir o arquivo no servidor

- Envie o **`config/database.config.php`** para o servidor (FTP, painel de arquivos, etc.).
- **Não** commite esse arquivo no Git (ele já está no `.gitignore`), para não expor usuário e senha.

### 4. Testar

Acesse o site no navegador. Se a conexão estiver correta, o site carrega usando o banco online. Se aparecer "Erro ao conectar ao banco de dados", confira host, nome do banco, usuário e senha no painel da hospedagem.

---

## Se preferir editar direto no código (não recomendado)

Se não quiser usar `database.config.php`, você pode alterar as constantes no início do arquivo **`config/database.php`**. Não é recomendado em produção porque as credenciais ficam no código versionado.

---

## Resumo

| Onde                         | O que fazer |
|-----------------------------|-------------|
| **config/database.config.php** | Criar a partir do `.example` e preencher **host**, **dbname**, **user** e **password** do banco online. |
| **.gitignore**              | Já inclui `config/database.config.php` para não versionar a senha. |

Depois disso, o projeto passa a usar o banco que está online.

---

## Desenvolvimento local acessando o banco no servidor

Se você roda o projeto **no seu PC** (XAMPP, etc.) e quer usar o banco que está **no servidor**:

1. No **`config/database.config.php`** use o **host do servidor** (IP ou hostname), por exemplo:  
   `'host' => '31.97.30.215'` (sem `https://`, sem porta de navegador).
2. Opcional: se o MySQL no servidor usar outra porta, adicione no config:  
   `'port' => 3306` (ou a porta que a hospedagem informar).
3. A conexão agora tem **timeout de 10 segundos**; se o servidor não responder, o erro aparece mais rápido.

**Importante:** a hospedagem precisa **permitir conexão remota ao MySQL**. Muitas bloqueiam por segurança. No painel da hospedagem procure por:

- **“Acesso remoto ao MySQL”** ou **“Remote MySQL”**
- **“Liberar IP para MySQL”** ou **“Hosts permitidos”** — cadastre o **IP da sua internet** (o que aparece em [meuip.com.br](https://meuip.com.br) ou similar)

Se não houver essa opção, a conexão local → servidor não será possível e você terá que usar um banco local na sua máquina e só usar o banco do servidor quando publicar o site.
