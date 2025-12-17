<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Review não especificada.");
}

$review_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Buscar dados da review
$stmt = $conn->prepare("SELECT * FROM reviews WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $review_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Review não encontrada ou não te pertence.");
}

$review = $result->fetch_assoc();

// Atualizar review
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    $update = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ? AND user_id = ?");
    $update->bind_param("isii", $rating, $comment, $review_id, $user_id);
    $update->execute();

    header("Location: profile.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Editar Review - GameList</title>
    <style>
        body { background:#111; color:#fff; font-family:Arial,sans-serif; padding:40px; }
        form { max-width:500px; margin:auto; background:#222; padding:20px; border-radius:10px; }
        input, textarea { width:100%; margin-bottom:10px; padding:8px; border-radius:6px; border:none; }
        button { background:#00bfff; color:#fff; border:none; padding:10px; border-radius:6px; cursor:pointer; }
        button:hover { background:#0090cc; }
        a { color:#00bfff; text-decoration:none; }
    </style>
</head>
<body>
    <h1>Editar review de "<?php echo htmlspecialchars($review['game_name']); ?>"</h1>
    <form method="POST">
        <label>Nota (0-10):</label>
        <input type="number" name="rating" min="0" max="10" value="<?php echo htmlspecialchars($review['rating']); ?>" required>
        <label>Comentário:</label>
        <textarea name="comment" rows="4" required><?php echo htmlspecialchars($review['comment']); ?></textarea>
        <button type="submit">💾 Guardar alterações</button>
        <p><a href="profile.php">Cancelar</a></p>
    </form>
</body>
</html>
