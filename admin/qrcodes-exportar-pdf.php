<?php
/**
 * Painel Admin — Exportar estoque de QR Codes para PDF
 * Permite exportar todos ou um intervalo "De ID … Até ID".
 */
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once dirname(__DIR__) . '/config/database.php';

if (empty($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'] ?? 0, $_SESSION['user_email'] ?? null)) {
  header('Location: ' . dirname($_SERVER['SCRIPT_NAME'], 2) . '/login.php');
  exit;
}

$base_url = dirname($_SERVER['SCRIPT_NAME']) . '/';
$root_path = dirname(__DIR__);
$pdo = getDB();

$de = isset($_POST['de']) ? trim($_POST['de']) : (isset($_GET['de']) ? trim($_GET['de']) : '');
$ate = isset($_POST['ate']) ? trim($_POST['ate']) : (isset($_GET['ate']) ? trim($_GET['ate']) : '');
$gerar = isset($_POST['gerar']) || (isset($_GET['gerar']) && ($de !== '' || $ate !== ''));

$mensagem = '';
$erro = '';

if ($gerar) {
  $id_de = $de !== '' ? (int) $de : null;
  $id_ate = $ate !== '' ? (int) $ate : null;
  if ($id_de !== null && $id_ate !== null && $id_de > $id_ate) {
    $erro = 'O ID inicial não pode ser maior que o ID final.';
  } else {
    $sql = "SELECT id, unique_id, imagem_path FROM qrcodes WHERE status = 'disponivel' AND imagem_path IS NOT NULL AND imagem_path != ''";
    $params = [];
    if ($id_de !== null) {
      $sql .= " AND id >= ?";
      $params[] = $id_de;
    }
    if ($id_ate !== null) {
      $sql .= " AND id <= ?";
      $params[] = $id_ate;
    }
    $sql .= " ORDER BY id ASC LIMIT 500";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $qrcodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($qrcodes)) {
      $erro = 'Nenhum QR disponível no intervalo selecionado.';
    } else {
      $autoload = $root_path . '/vendor/autoload.php';
      if (!is_file($autoload)) {
        $erro = 'Biblioteca TCPDF não encontrada.';
      } else {
        require_once $autoload;
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Imortalize');
        $pdf->SetTitle('Estoque QR Codes');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        $colunas = 4;
        $largura_qr = 40;
        $altura_qr = 40;
        $espaco = 10;
        $x = 10;
        $y = 10;
        $contador = 0;

        foreach ($qrcodes as $qr) {
          $path_abs = $root_path . '/' . $qr['imagem_path'];
          if (!is_file($path_abs)) {
            continue;
          }
          $pdf->Image($path_abs, $x, $y, $largura_qr, $altura_qr, 'PNG');
          $pdf->SetXY($x, $y + $altura_qr + 2);
          $pdf->Cell($largura_qr, 5, 'ID ' . (int) $qr['id'], 0, 1, 'C');

          $x += $largura_qr + $espaco;
          $contador++;
          if ($contador % $colunas === 0) {
            $x = 10;
            $y += $altura_qr + 15;
            if ($y > 250) {
              $pdf->AddPage();
              $y = 10;
            }
          }
        }

        $pdf->Output('estoque_qr_codes.pdf', 'D');
        exit;
      }
    }
  }
}

$admin_page_title = 'Exportar estoque para PDF';
require_once __DIR__ . '/includes/header.php';
?>
      <section class="admin-card">
        <h2>Exportar estoque para PDF</h2>
        <?php if ($erro !== '') : ?>
        <p style="color: #c88; margin-bottom: 1rem;"><?php echo htmlspecialchars($erro); ?></p>
        <?php endif; ?>
        <p style="color: #b8c4a8; margin-bottom: 1rem;">Exporte apenas QRs <strong>disponíveis</strong> (estoque). Deixe em branco para exportar todos (até 500).</p>
        <form method="post" action="" style="max-width: 360px;">
          <input type="hidden" name="gerar" value="1">
          <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
            <label for="de" style="color: #f0f2eb;">De ID</label>
            <input type="number" name="de" id="de" value="<?php echo htmlspecialchars($de); ?>" min="1" placeholder="ex: 1" style="width: 100px; padding: 0.4rem;">
            <label for="ate" style="color: #f0f2eb;">até ID</label>
            <input type="number" name="ate" id="ate" value="<?php echo htmlspecialchars($ate); ?>" min="1" placeholder="ex: 50" style="width: 100px; padding: 0.4rem;">
          </div>
          <div style="display: flex; gap: 0.5rem;">
            <button type="submit" style="padding: 0.5rem 1rem; cursor: pointer; border-radius: 8px; border: 1px solid rgba(184,196,168,0.4); background: rgba(184,196,168,0.15); color: #f0f2eb;">Exportar PDF</button>
            <a href="<?php echo $base_url; ?>qrcodes.php" style="padding: 0.5rem 1rem; color: #b8c4a8; text-decoration: none; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15);">Cancelar</a>
          </div>
        </form>
      </section>
      <p><a href="<?php echo $base_url; ?>qrcodes.php" style="color: #b8c4a8;">← Voltar para QR Codes</a></p>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
