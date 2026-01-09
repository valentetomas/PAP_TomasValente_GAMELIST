<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $target_user_id = (int)($_POST['user_id'] ?? 0);

    // Validações
    if ($target_user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de utilizador inválido']);
        exit;
    }

    if ($user_id === $target_user_id) {
        echo json_encode(['success' => false, 'message' => 'Não podes seguir-te a ti mesmo']);
        exit;
    }

    // Verificar se o utilizador alvo existe
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->bind_param("i", $target_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Utilizador não encontrado']);
        exit;
    }
    
    $target_user = $result->fetch_assoc();

    if ($action === 'follow') {
        // Seguir utilizador
        $stmt = $conn->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE created_at = created_at");
        $stmt->bind_param("ii", $user_id, $target_user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Agora segues ' . htmlspecialchars($target_user['username']),
                'action' => 'followed'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao seguir utilizador']);
        }
    } elseif ($action === 'unfollow') {
        // Deixar de seguir
        $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->bind_param("ii", $user_id, $target_user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Deixaste de seguir ' . htmlspecialchars($target_user['username']),
                'action' => 'unfollowed'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao deixar de seguir']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
