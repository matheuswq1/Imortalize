# Fluxo: Memorial, link público e QR Code

## Como funciona

1. **Nosso sistema (Imortalize)** gera um **link próprio** para cada memorial.
2. Esse link é **único e permanente** (ex.: `memorial-publico.php?slug=maria-silva`).
3. O usuário pode:
   - **Compartilhar o link** — qualquer pessoa com o link acessa a página pública do memorial.
   - **Usar no sistema externo de QR Code** — colar esse link no sistema de QR codes que vocês já usam; o QR code apontará para a nossa página do memorial.
4. **Não geramos QR code aqui.** O QR code é feito no sistema externo; nós só fornecemos a URL que será usada como destino.

## Regras

- **Só memorial publicado** aparece no link público. Rascunho não é acessível por quem não é dono.
- Quem acessa o link **não precisa estar logado** — a página é pública.
- O dono do memorial vê o **link para copiar** na área "Meus memoriais" e na tela do memorial, para compartilhar ou colar no sistema de QR Code.

---

## Modelo de URL: por que usar `?slug=...`?

O link usa **query string** (`?slug=maria-silva`). Esse modelo é **adequado e amplamente usado**:

| Aspecto | Query string (`?slug=xxx`) | URL “bonita” (`/memorial/xxx`) |
|--------|----------------------------|---------------------------------|
| Funcionamento | Funciona em qualquer servidor, sem configuração | Exige regras de reescrita (Apache/Nginx) |
| Compatibilidade | Qualquer hospedagem PHP | Depende de mod_rewrite ou equivalente |
| Segurança | Igual (slug validado do mesmo jeito) | Igual |
| Compartilhamento / QR | Links e QR funcionam normalmente | Idem |
| SEO | Google trata bem os dois | URL “limpa” pode ser um pouco mais elegante |

**Conclusão:** Manter `memorial-publico.php?slug=xxx` é uma **boa escolha** para simplicidade e portabilidade. Se no futuro quiserem URL “bonita” (ex.: `https://site.com/m/maria-silva`), basta configurar o servidor para reescrever para `memorial-publico.php?slug=maria-silva`; a lógica do PHP não precisa mudar.

---

## Segurança do link público

- **Slug validado:** Só são aceitos slugs com `a-z`, `0-9` e hífen, até 255 caracteres. Qualquer outro valor resulta em 404.
- **SQL injection:** A busca usa prepared statements (`?`), então o slug nunca é concatenado na query.
- **XSS:** Título, biografia e meta description são exibidos com `htmlspecialchars()` (e `strip_tags` na meta).
- **Acesso:** Apenas memoriais com status `publicado` são retornados; rascunhos não aparecem na URL pública.
- **404:** Slug vazio, inválido ou memorial inexistente/não publicado retornam 404, sem revelar detalhes.

Em produção, recomenda-se ainda: HTTPS, definir `BASE_URL` na config para o domínio correto e, se necessário, rate limiting no servidor.

---

## URL pública (exemplo)

- Desenvolvimento: `http://localhost/Imortalize/memorial-publico.php?slug=NOME-DO-SLUG`
- Produção: `https://seusite.com/memorial-publico.php?slug=NOME-DO-SLUG` (ou o domínio que vocês usarem)

O **slug** é gerado automaticamente ao criar o memorial (ex.: "Maria Silva" → `maria-silva`). Ele é único no sistema.
