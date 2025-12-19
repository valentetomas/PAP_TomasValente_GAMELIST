<?php
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_POST['review_id'])) {
    $review_id = intval($_POST['review_id']);
    $user_id = $_SESSION['user_id'];

    // Garante que a review pertence ao utilizador logado
    $check = $conn->prepare("SELECT id FROM reviews WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $review_id, $user_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $delete = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        $delete->bind_param("i", $review_id);
        $delete->execute();
    }
}

header("Location: profile.php");
exit;
?>
