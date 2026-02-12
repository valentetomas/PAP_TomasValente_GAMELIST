<?php
header('Content-Type: application/json');
require_once 'includes/db.php';

$response = [];

try {
    // Log de debug - verificar conexão
    if (!$conn) {
        throw new Exception("Conexão com BD falhou");
    }
    
    // Verificar se a tabela reviews existe
    $tables_result = $conn->query("SHOW TABLES LIKE 'reviews'");
    if (!$tables_result || $tables_result->num_rows == 0) {
        throw new Exception("Tabela reviews não existe");
    }
    
    // Verificar quantas reviews existem
    $count_result = $conn->query("SELECT COUNT(*) as total FROM reviews");
    $count_data = $count_result->fetch_assoc();
    $total_reviews = $count_data['total'];
    
    error_log("=== GET_POPULAR_REVIEWS ===");
    error_log("Total de reviews na BD: " . $total_reviews);
    
    if ($total_reviews == 0) {
        echo json_encode([]);
        error_log("Nenhuma review encontrada");
        exit;
    }
    
    // Verificar se users existe
    $users_check = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$users_check || $users_check->num_rows == 0) {
        error_log("ERRO: Tabela users não existe!");
        echo json_encode([]);
        exit;
    }
    
    // Buscar as 4 reviews mais recentes com informação do utilizador e avatar
    $sql = "
        SELECT 
            r.id,
            r.user_id,
            r.game_id,
            r.game_name,
            r.game_image,
            r.rating,
            r.comment as review_text,
            r.created_at,
            u.username,
            u.avatar
        FROM reviews r
        INNER JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC
        LIMIT 4
    ";
    
    error_log("SQL Query: " . $sql);
    
    $result = $conn->query($sql);
    
    if (!$result) {
        error_log("ERRO na query: " . $conn->error);
        throw new Exception("Erro na query: " . $conn->error);
    }
    
    error_log("Rows retornadas: " . $result->num_rows);
    
    $reviews = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Contar likes reais
            $likes_sql = "SELECT COUNT(*) as count FROM review_likes WHERE review_id = ?";
            $likes_stmt = $conn->prepare($likes_sql);
            if ($likes_stmt) {
                $likes_stmt->bind_param("i", $row['id']);
                $likes_stmt->execute();
                $likes_result = $likes_stmt->get_result()->fetch_assoc();
                $row['likes_count'] = $likes_result['count'] ?? 0;
            } else {
                $row['likes_count'] = 0;
            }
            
            // Contar comentários reais
            $comments_sql = "SELECT COUNT(*) as count FROM review_comments WHERE review_id = ?";
            $comments_stmt = $conn->prepare($comments_sql);
            if ($comments_stmt) {
                $comments_stmt->bind_param("i", $row['id']);
                $comments_stmt->execute();
                $comments_result = $comments_stmt->get_result()->fetch_assoc();
                $row['comments_count'] = $comments_result['count'] ?? 0;
            } else {
                $row['comments_count'] = 0;
            }
            
            // Preparar avatar do utilizador
            $row['avatar_url'] = '';
            if (!empty($row['avatar']) && $row['avatar'] !== 'img/default.png') {
                $row['avatar_url'] = $row['avatar'];
            }
            
            $reviews[] = $row;
        }
    }
    
    error_log("Total enviado: " . count($reviews));
    echo json_encode($reviews);
    
} catch (Exception $e) {
    error_log("Erro em get_popular_reviews: " . $e->getMessage());
    echo json_encode([]);
}
?>
