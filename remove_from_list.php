<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
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
    }
}

header("Location: profile.php");
exit;
?>
