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

$page_title = 'Editar Review - Admin';
include 'includes/header.php';
?>
<style>
    .admin-edit-container {
        max-width: 800px;
        margin: 90px auto 60px;
        padding: 20px;
        background: #16171c;
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 14px;
        color: #fff;
    }
    .admin-edit-container h1 { margin-bottom: 18px; }
    .admin-edit-container form label { display: block; margin-top: 12px; font-weight:500; }
    .admin-edit-container form input,
    .admin-edit-container form textarea {
        width: 100%;
        padding: 10px;
        margin-top: 5px;
        background: #242629;
        border: 1px solid rgba(255,255,255,0.1);
        color: #fff;
        border-radius: 6px;
    }
    .admin-edit-container form button {
        margin-top: 20px;
        padding: 10px 20px;
        background: var(--accent);
        color: #000;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight:600;
    }
    .admin-edit-container form button:hover {
        background: #0080ff;
    }
</style>

<div class="admin-edit-container">
    <a href="admin.php" class="btn btn-sm btn-primary mb-3">← Voltar ao Painel</a>
    <h1>Editar Review</h1>
    <p><strong>Jogo:</strong> <?php echo htmlspecialchars($review['game_name']); ?></p>
    <p><strong>Utilizador:</strong> <?php echo htmlspecialchars($conn->query("SELECT username FROM users WHERE id = " . $review['user_id'])->fetch_assoc()['username']); ?></p>
    <form method="POST">
        <label for="rating">Nota (1-10):</label>
        <input type="number" id="rating" name="rating" min="1" max="10" value="<?php echo $review['rating']; ?>" required>
        <label for="comment">Comentário:</label>
        <textarea id="comment" name="comment" rows="5" required><?php echo htmlspecialchars($review['comment']); ?></textarea>
        <button type="submit">Guardar Alterações</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>