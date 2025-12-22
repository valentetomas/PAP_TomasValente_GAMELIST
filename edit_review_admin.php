<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$review_id = intval($_GET['id']);
$review = $conn->query("SELECT * FROM reviews WHERE id = $review_id")->fetch_assoc();

if (!$review) {
    header("Location: admin.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $comment = $_POST['comment'];
    $rating = intval($_POST['rating']);
    $conn->query("UPDATE reviews SET comment = '$comment', rating = $rating WHERE id = $review_id");
    header("Location: admin.php");
    exit;
}

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Review - Admin</title>
    <link rel="icon" type="image/png" href="img/logo.png">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #111; color: #fff; font-family: Arial, sans-serif; padding-top: 64px; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; background: #222; border-radius: 8px; }
        form { display: flex; flex-direction: column; }
        label { margin-top: 10px; }
        input, textarea { padding: 10px; margin-top: 5px; background: #333; color: #fff; border: 1px solid #444; }
        button { margin-top: 20px; padding: 10px; background: #00bfff; color: #fff; border: none; cursor: pointer; }
        button:hover { background: #0080ff; }
    </style>
    <div class="container">
        <h1>Editar Review</h1>
        <p><strong>Jogo:</strong> <?php echo htmlspecialchars($review['game_name']); ?></p>
        <p><strong>Usuário:</strong> <?php echo htmlspecialchars($conn->query("SELECT username FROM users WHERE id = " . $review['user_id'])->fetch_assoc()['username']); ?></p>
        <form method="POST">
            <label for="rating">Nota (1-10):</label>
            <input type="number" id="rating" name="rating" min="1" max="10" value="<?php echo $review['rating']; ?>" required>
            <label for="comment">Comentário:</label>
            <textarea id="comment" name="comment" rows="5" required><?php echo htmlspecialchars($review['comment']); ?></textarea>
            <button type="submit">Salvar Alterações</button>
        </form>
    </div>
</body>
</html>