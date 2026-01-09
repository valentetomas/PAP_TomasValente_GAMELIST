<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Query muito curta']);
    exit;
}

// Pesquisar utilizadores com prepared statement
$search = "%{$query}%";
$stmt = $conn->prepare("
    SELECT 
        u.id, 
        u.username, 
        u.avatar,
        (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as follower_count
    FROM users u
    WHERE u.username LIKE ? 
    AND u.banned = 0
    LIMIT 5
");
$stmt->bind_param("s", $search);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        'id' => (int)$row['id'],
        'username' => $row['username'],
        'avatar' => $row['avatar'],
        'follower_count' => (int)$row['follower_count']
    ];
}

echo json_encode([
    'success' => true,
    'users' => $users
]);
