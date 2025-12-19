<?php
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['game_id'], $_POST['game_name'], $_POST['game_image'], $_POST['rating'], $_POST['comment'])) {
    $user_id = $_SESSION['user_id'];
    $game_id = intval($_POST['game_id']);
    $game_name = $_POST['game_name'];
    $game_image = $_POST['game_image'];
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    $stmt = $conn->prepare("INSERT INTO reviews (user_id, game_id, game_name, game_image, rating, comment) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissis", $user_id, $game_id, $game_name, $game_image, $rating, $comment);
    $stmt->execute();

    header("Location: profile.php"); // volta para o perfil
    exit();
} else {
    echo "❌ Dados em falta.";
}
?>
