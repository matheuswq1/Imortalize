<?php
/**
 * Despublica um memorial (volta para rascunho).
 */
session_start();
require_once __DIR__ . '/config/database.php';

// Verifica se o usuário está logado
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$memorial_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Validação básica
if ($memorial_id <= 0) {
    header('Location: memoriais.php');
    exit;
}

// Verifica se o memorial pertence ao usuário
$pdo = getDB();
$stmt = $pdo->prepare('SELECT id, status FROM memorials WHERE id = ? AND user_id = ?');
$stmt->execute([$memorial_id, $user_id]);
$memorial = $stmt->fetch();

if (!$memorial) {
    header('Location: memoriais.php');
    exit;
}

// Verifica se está publicado
if ($memorial['status'] !== 'publicado') {
    header('Location: memorial.php?id=' . $memorial_id);
    exit;
}

// Volta para rascunho
try {
    $stmt = $pdo->prepare('UPDATE memorials SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
    $stmt->execute(['rascunho', $memorial_id, $user_id]);
    
    header('Location: memorial.php?id=' . $memorial_id . '&salvo=1&msg=' . urlencode('Memorial voltou para rascunho.'));
    exit;
} catch (Exception $e) {
    error_log('Erro ao despublicar memorial: ' . $e->getMessage());
    header('Location: memorial.php?id=' . $memorial_id . '&salvo_erro=' . urlencode('Erro ao despublicar. Tente novamente.'));
    exit;
}
