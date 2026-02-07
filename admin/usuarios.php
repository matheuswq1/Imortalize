<?php
/**
 * Painel Admin — Lista de usuários
 */
$admin_page_title = 'Usuários';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$usuarios = $pdo->query('
  SELECT u.id, u.nome, u.email, u.telefone, u.created_at,
    (SELECT COUNT(*) FROM memorials m WHERE m.user_id = u.id) as total_memoriais,
    (SELECT COUNT(*) FROM subscriptions s WHERE s.user_id = u.id AND s.status = "ativo" AND s.data_vencimento >= CURDATE()) as assinaturas_ativas
  FROM users u
  ORDER BY u.created_at DESC
')->fetchAll(PDO::FETCH_ASSOC);
?>
      <section class="admin-card" aria-labelledby="titulo-usuarios">
        <h2 id="titulo-usuarios">Usuários (<?php echo count($usuarios); ?>)</h2>
        <div style="overflow-x: auto;">
          <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Memoriais</th>
                <th>Plano ativo</th>
                <th>Cadastro</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($usuarios as $u) : ?>
              <tr>
                <td><?php echo (int) $u['id']; ?></td>
                <td><?php echo htmlspecialchars($u['nome']); ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td><?php echo $u['telefone'] ? htmlspecialchars($u['telefone']) : '—'; ?></td>
                <td><?php echo (int) $u['total_memoriais']; ?></td>
                <td><?php echo (int) $u['assinaturas_ativas'] > 0 ? 'Sim' : 'Não'; ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($usuarios)) : ?>
              <tr><td colspan="7" style="color: #b8c4a8;">Nenhum usuário cadastrado.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <footer style="margin-top: 1.5rem; font-size: 0.875rem; color: #b8c4a8;">
        <a href="<?php echo $site_base; ?>index.php" style="color: #b8c4a8;">← Voltar ao site</a>
      </footer>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
