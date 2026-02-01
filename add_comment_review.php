<?php
include 'includes/db.php';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function jsonResponse($status, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    $response = ['status' => $status, 'message' => $message];
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    if ($isAjax) jsonResponse('auth', 'Precisas estar logado', null, 401);
    die("Precisas estar logado.");
}

if (!isset($_POST['review_id'], $_POST['comment'])) {
    if ($isAjax) jsonResponse('error', 'Dados em falta', null, 400);
    die("Dados em falta.");
}

$user_id = $_SESSION['user_id'];
$review_id = intval($_POST['review_id']);
$comment = trim($_POST['comment']);

if (strlen($comment) < 2) {
    if ($isAjax) jsonResponse('error', 'Comentário muito curto', null, 400);
    die("Comentário muito curto.");
}

if (strlen($comment) > 1000) {
    if ($isAjax) jsonResponse('error', 'Comentário muito longo', null, 400);
    die("Comentário muito longo.");
}

// Verifica se a review existe
$checkRev = $conn->prepare("SELECT id FROM reviews WHERE id = ?");
$checkRev->bind_param("i", $review_id);
$checkRev->execute();
if ($checkRev->get_result()->num_rows === 0) {
    if ($isAjax) jsonResponse('error', 'Review não encontrada', null, 404);
    die("Review não encontrada.");
}

// Insere o comentário
$stmt = $conn->prepare("INSERT INTO review_comments (review_id, user_id, comment) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $review_id, $user_id, $comment);
$stmt->execute();
$comment_id = $conn->insert_id;

// Busca dados do utilizador para retornar
$user = $conn->prepare("SELECT username, avatar FROM users WHERE id = ?");
$user->bind_param("i", $user_id);
$user->execute();
$userData = $user->get_result()->fetch_assoc();

if ($isAjax) {
    jsonResponse('success', 'Comentário adicionado', [
        'comment_id' => $comment_id,
        'username' => $userData['username'],
        'avatar' => $userData['avatar'],
        'comment' => htmlspecialchars($comment),
        'created_at' => date('Y-m-d H:i:s')
    ]);
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>
