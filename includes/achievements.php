<?php
// Sistema de verificação e desbloqueio de conquistas
require_once 'includes/db.php';

function checkAndUnlockAchievements($user_id) {
    global $conn;
    
    $user_id = (int)$user_id;
    $newly_unlocked = [];
    
    // Buscar todas as conquistas disponíveis que o utilizador ainda não tem
    $stmt = $conn->prepare("
        SELECT a.* 
        FROM achievements a
        WHERE a.id NOT IN (
            SELECT achievement_id 
            FROM user_achievements 
            WHERE user_id = ?
        )
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $achievements = $stmt->get_result();
    
    while ($achievement = $achievements->fetch_assoc()) {
        $unlocked = false;
        $current_value = 0;
        
        switch ($achievement['type']) {
            case 'list_count':
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM lists WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $current_value = $stmt->get_result()->fetch_assoc()['count'];
                $unlocked = $current_value >= $achievement['requirement'];
                break;
                
            case 'review_count':
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ? AND approved = 1");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $current_value = $stmt->get_result()->fetch_assoc()['count'];
                $unlocked = $current_value >= $achievement['requirement'];
                break;
                
            case 'total_games':
                $stmt = $conn->prepare("SELECT COUNT(DISTINCT game_id) as count FROM list_items li JOIN lists l ON li.list_id = l.id WHERE l.user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $current_value = $stmt->get_result()->fetch_assoc()['count'];
                $unlocked = $current_value >= $achievement['requirement'];
                break;
                
            case 'follower_count':
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE following_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $current_value = $stmt->get_result()->fetch_assoc()['count'];
                $unlocked = $current_value >= $achievement['requirement'];
                break;
                
            case 'following_count':
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $current_value = $stmt->get_result()->fetch_assoc()['count'];
                $unlocked = $current_value >= $achievement['requirement'];
                break;
                
            case 'perfect_ratings':
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ? AND rating = 10 AND approved = 1");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $current_value = $stmt->get_result()->fetch_assoc()['count'];
                $unlocked = $current_value >= $achievement['requirement'];
                break;
                
            case 'worst_ratings':
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ? AND rating = 1 AND approved = 1");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $current_value = $stmt->get_result()->fetch_assoc()['count'];
                $unlocked = $current_value >= $achievement['requirement'];
                break;
                
            case 'account_created':
                $unlocked = true; // Sempre desbloqueada
                break;
                
            case 'account_age_days':
                $stmt = $conn->prepare("SELECT DATEDIFF(NOW(), created_at) as days FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $current_value = $stmt->get_result()->fetch_assoc()['days'];
                $unlocked = $current_value >= $achievement['requirement'];
                break;
        }
        
        // Se desbloqueou, adicionar à base de dados
        if ($unlocked) {
            $stmt = $conn->prepare("INSERT INTO user_achievements (user_id, achievement_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $achievement['id']);
            if ($stmt->execute()) {
                $newly_unlocked[] = $achievement;
            }
        }
    }
    
    return $newly_unlocked;
}

function getUserAchievements($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT a.*, ua.unlocked_at 
        FROM achievements a
        JOIN user_achievements ua ON a.id = ua.achievement_id
        WHERE ua.user_id = ?
        ORDER BY ua.unlocked_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getTotalAchievementPoints($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(a.points), 0) as total_points
        FROM user_achievements ua
        JOIN achievements a ON ua.achievement_id = a.id
        WHERE ua.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['total_points'];
}

function getAchievementProgress($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as unlocked
        FROM user_achievements
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $unlocked = $stmt->get_result()->fetch_assoc()['unlocked'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM achievements");
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    return [
        'unlocked' => $unlocked,
        'total' => $total,
        'percentage' => $total > 0 ? round(($unlocked / $total) * 100) : 0
    ];
}
