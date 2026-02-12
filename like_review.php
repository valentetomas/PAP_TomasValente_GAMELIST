<?php
include 'includes/db.php';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$isJson = isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;

function jsonResponse($status, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    $response = ['status' => $status, 'message' => $message];
    if ($data !== null) $response['data'] = $data;
    // Enviar dados de compatibilidade
    if ($status === 'success' && isset($data['likes'])) {
        $response['likes_count'] = $data['likes'];
        $response['success'] = true;
    }
    echo json_encode($response);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    if ($isAjax || $isJson) jsonResponse('auth', 'Precisas estar logado', null, 401);
    die("Precisas estar logado.");
}

$review_id = null;

// Suportar ambos JSON e POST
if ($isJson) {
    $data = json_decode(file_get_contents('php://input'), true);
    $review_id = intval($data['review_id'] ?? 0);
} else {
    $review_id = intval($_POST['review_id'] ?? 0);
}

if (!$review_id) {
    if ($isAjax || $isJson) jsonResponse('error', 'Review não especificada', null, 400);
    die("Review não especificada.");
}

$user_id = $_SESSION['user_id'];

// Verifica se o like já existe
$check = $conn->prepare("SELECT id FROM review_likes WHERE review_id = ? AND user_id = ?");
$check->bind_param("ii", $review_id, $user_id);
$check->execute();
$exists = $check->get_result();

if ($exists->num_rows > 0) {
    // Remove o like
    $delete = $conn->prepare("DELETE FROM review_likes WHERE review_id = ? AND user_id = ?");
    $delete->bind_param("ii", $review_id, $user_id);
    $delete->execute();
    $action = 'removed';
} else {
    // Adiciona o like
    $insert = $conn->prepare("INSERT INTO review_likes (review_id, user_id) VALUES (?, ?)");
    $insert->bind_param("ii", $review_id, $user_id);
    $insert->execute();
    $action = 'added';
}

// Conta likes
$count = $conn->prepare("SELECT COUNT(*) as total FROM review_likes WHERE review_id = ?");
$count->bind_param("i", $review_id);
$count->execute();
$result = $count->get_result()->fetch_assoc();
$totalLikes = $result['total'];

if ($isAjax || $isJson) {
    jsonResponse('success', 'Like ' . ($action === 'added' ? 'adicionado' : 'removido'), ['likes' => $totalLikes, 'action' => $action]);
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>
