<?php
include 'includes/db.php';
require_once 'includes/achievements.php';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function jsonResponse($status, $message, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    if ($isAjax) {
        jsonResponse('auth', 'Precisas de iniciar sessão para adicionar jogos.', 401);
    }
    die("Precisas de iniciar sessão para adicionar jogos.");
}

if (isset($_POST['list_name'], $_POST['game_id'], $_POST['game_name'], $_POST['game_image'])) {
    $user_id = $_SESSION['user_id'];
    $list_name = $_POST['list_name'];
    $game_id = $_POST['game_id'];
    $game_name = $_POST['game_name'];
    $game_image = $_POST['game_image'];

    // Encontra o ID da lista correspondente
    $listQuery = $conn->prepare("SELECT id FROM lists WHERE user_id = ? AND name = ?");
    $listQuery->bind_param("is", $user_id, $list_name);
    $listQuery->execute();
    $listResult = $listQuery->get_result();

    if ($listResult->num_rows > 0) {
        $list = $listResult->fetch_assoc();
        $list_id = $list['id'];

        // Verifica se o jogo já está na lista
        $check = $conn->prepare("SELECT * FROM list_items WHERE list_id = ? AND game_id = ?");
        $check->bind_param("ii", $list_id, $game_id);
        $check->execute();
        $exists = $check->get_result();

        if ($exists->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO list_items (list_id, game_id, game_name, game_image) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $list_id, $game_id, $game_name, $game_image);
            $stmt->execute();
            
            // Verificar conquistas após adicionar jogo
            checkAndUnlockAchievements($user_id);
            
            if ($isAjax) {
                jsonResponse('added', '✅ Jogo adicionado!');
            }
            $_SESSION['msg'] = 'added';
            header("Location: game.php?id=$game_id&name=" . urlencode($game_name) . "&image=" . urlencode($game_image));
            exit;
        } else {
            if ($isAjax) {
                jsonResponse('exists', '⚠️ O jogo já está na lista.');
            }
            $_SESSION['msg'] = 'exists';
            header("Location: game.php?id=$game_id&name=" . urlencode($game_name) . "&image=" . urlencode($game_image));
            exit;
        }
    } else {
        if ($isAjax) {
            jsonResponse('error', '❌ Lista não encontrada.', 404);
        }
        echo "❌ Lista não encontrada.";
    }
} else {
    if ($isAjax) {
        jsonResponse('error', '❌ Dados em falta.', 400);
    }
    echo "❌ Dados em falta.";
}
?>
