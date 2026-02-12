<?php
header('Content-Type: application/json');
require_once 'includes/db.php';

try {
    if (!isset($_GET['review_id'])) {
        throw new Exception("Review não especificada");
    }
    
    $review_id = intval($_GET['review_id']);
    
    // Buscar comentários da review
    $sql = "
        SELECT 
            rc.id,
            rc.user_id,
            rc.comment,
            rc.created_at,
            u.username,
            u.avatar
        FROM review_comments rc
        INNER JOIN users u ON rc.user_id = u.id
        WHERE rc.review_id = ?
        ORDER BY rc.created_at DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $review_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    
    echo json_encode(['success' => true, 'comments' => $comments]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
