<?php
/**
 * Salva informações textuais de um memorial (formulário POST).
 * Campos: título, biografia, datas, mensagem curta.
 */
session_start();
require_once __DIR__ . '/config/database.php';

// Verifica se o usuário está logado
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verifica se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: memoriais.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$memorial_id = isset($_POST['memorial_id']) ? (int) $_POST['memorial_id'] : 0;
$titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$relacao_publicador = isset($_POST['relacao_publicador']) ? trim($_POST['relacao_publicador']) : '';
$biografia = isset($_POST['biografia']) ? trim($_POST['biografia']) : '';
$data_nascimento = isset($_POST['data_nascimento']) ? trim($_POST['data_nascimento']) : '';
$data_obito = isset($_POST['data_obito']) ? trim($_POST['data_obito']) : '';
$mensagem_curta = isset($_POST['mensagem_curta']) ? trim($_POST['mensagem_curta']) : '';

// Validações básicas
if ($memorial_id <= 0) {
    header('Location: memoriais.php');
    exit;
}

if ($titulo === '') {
    header('Location: memorial.php?id=' . $memorial_id . '&salvo_erro=' . urlencode('O título não pode ficar vazio.'));
    exit;
}

if (mb_strlen($titulo) > 35) {
    header('Location: memorial.php?id=' . $memorial_id . '&salvo_erro=' . urlencode('O título deve ter no máximo 35 caracteres.'));
    exit;
}

if (mb_strlen($mensagem_curta) > 120) {
    header('Location: memorial.php?id=' . $memorial_id . '&salvo_erro=' . urlencode('A mensagem curta deve ter no máximo 120 caracteres.'));
    exit;
}

if (mb_strlen($nome) > 120) {
    header('Location: memorial.php?id=' . $memorial_id . '&salvo_erro=' . urlencode('O nome deve ter no máximo 120 caracteres.'));
    exit;
}

if (mb_strlen($biografia) > 5000) {
    header('Location: memorial.php?id=' . $memorial_id . '&salvo_erro=' . urlencode('A biografia deve ter no máximo 5.000 caracteres.'));
    exit;
}

$opcoes_relacao = function_exists('getRelacaoPublicadorOpcoes') ? getRelacaoPublicadorOpcoes() : [];
if ($relacao_publicador !== '' && !array_key_exists($relacao_publicador, $opcoes_relacao)) {
    $relacao_publicador = '';
}

// Verifica se o memorial pertence ao usuário
$pdo = getDB();
$stmt = $pdo->prepare('SELECT id FROM memorials WHERE id = ? AND user_id = ?');
$stmt->execute([$memorial_id, $user_id]);
$memorial = $stmt->fetch();

if (!$memorial) {
    header('Location: memoriais.php');
    exit;
}

// Normaliza datas (vazias viram NULL; ano com 4 dígitos)
$data_nascimento_val = null;
$data_obito_val = null;
if ($data_nascimento !== '') {
    $parts = explode('-', $data_nascimento);
    if (count($parts) >= 1 && strlen($parts[0]) > 4) {
        $parts[0] = substr($parts[0], 0, 4);
        $data_nascimento = implode('-', $parts);
    }
    $data_nascimento_val = $data_nascimento;
}
if ($data_obito !== '') {
    $parts = explode('-', $data_obito);
    if (count($parts) >= 1 && strlen($parts[0]) > 4) {
        $parts[0] = substr($parts[0], 0, 4);
        $data_obito = implode('-', $parts);
    }
    $data_obito_val = $data_obito;
}
$mensagem_curta_val = $mensagem_curta !== '' ? $mensagem_curta : null;
$nome_val = $nome !== '' ? $nome : null;
$relacao_val = $relacao_publicador !== '' ? $relacao_publicador : null;

$hoje = date('Y-m-d');

if ($data_nascimento_val !== null && $data_nascimento_val > $hoje) {
    header('Location: memorial.php?id=' . $memorial_id . '&salvo_erro=' . urlencode('A data de nascimento não pode ser superior à data atual.'));
    exit;
}
if ($data_obito_val !== null && $data_obito_val > $hoje) {
    header('Location: memorial.php?id=' . $memorial_id . '&salvo_erro=' . urlencode('A data do óbito não pode ser superior à data atual.'));
    exit;
}
if ($data_nascimento_val !== null && $data_obito_val !== null && $data_obito_val < $data_nascimento_val) {
    header('Location: memorial.php?id=' . $memorial_id . '&salvo_erro=' . urlencode('A data do óbito não pode ser anterior à data de nascimento.'));
    exit;
}

// Atualiza os dados do memorial
try {
    $stmt = $pdo->prepare('UPDATE memorials SET titulo = ?, nome = ?, relacao_publicador = ?, biografia = ?, data_nascimento = ?, data_obito = ?, mensagem_curta = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
    $stmt->execute([$titulo, $nome_val, $relacao_val, $biografia, $data_nascimento_val, $data_obito_val, $mensagem_curta_val, $memorial_id, $user_id]);

    header('Location: memorial.php?id=' . $memorial_id . '&salvo=1&msg=' . urlencode('Informações atualizadas.'));
    exit;
} catch (Exception $e) {
    error_log('Erro ao salvar dados do memorial: ' . $e->getMessage());
    header('Location: memorial.php?id=' . $memorial_id . '&salvo_erro=' . urlencode('Erro ao salvar. Tente novamente.'));
    exit;
}

