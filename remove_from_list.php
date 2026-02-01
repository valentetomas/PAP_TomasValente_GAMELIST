<?php
include 'includes/db.php';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function jsonResponse($status, $message, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    if ($isAjax) {
        jsonResponse('auth', 'Precisas de iniciar sessão para remover jogos.', 401);
    }
    header("Location: login.php");
    exit;
}

if (isset($_POST['list_id']) && isset($_POST['game_id'])) {
    $list_id = intval($_POST['list_id']);
    $game_id = intval($_POST['game_id']);
    $user_id = $_SESSION['user_id'];

    // Verifica se a lista pertence ao utilizador
    $check = $conn->query("SELECT id FROM lists WHERE id = $list_id AND user_id = $user_id");
    if ($check->num_rows > 0) {
        $conn->query("DELETE FROM list_items WHERE list_id = $list_id AND game_id = $game_id");
        if ($isAjax) {
            jsonResponse('removed', '🗑️ Jogo removido da lista.');
        }
    } else if ($isAjax) {
        jsonResponse('error', '❌ Lista inválida.', 403);
    }
} else if ($isAjax) {
    jsonResponse('error', '❌ Dados em falta.', 400);
}

header("Location: profile.php");
exit;
?>
