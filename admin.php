<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'includes/db.php';

// Processar POST antes de qualquer output
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_role'])) {
    $user_id = intval($_POST['user_id']);
    $new_role = $_POST['role'];
    $conn->query("UPDATE users SET role = '$new_role' WHERE id = $user_id");
    $conn->query("INSERT INTO admin_logs (admin_id, action, target_id, details) VALUES ({$_SESSION['user_id']}, 'update_role', $user_id, 'Role changed to $new_role')");
    header("Location: admin.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_ban'])) {
    $user_id = intval($_POST['user_id']);
    $current_banned = $conn->query("SELECT banned FROM users WHERE id = $user_id")->fetch_assoc()['banned'];
    $new_banned = $current_banned ? 0 : 1;
    $conn->query("UPDATE users SET banned = $new_banned WHERE id = $user_id");
    $action = $new_banned ? 'ban_user' : 'unban_user';
    $conn->query("INSERT INTO admin_logs (admin_id, action, target_id, details) VALUES ({$_SESSION['user_id']}, '$action', $user_id, 'User banned status changed')");
    header("Location: admin.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// Processar delete review
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_review'])) {
    $review_id = intval($_POST['review_id']);
    $conn->query("DELETE FROM reviews WHERE id = $review_id");
    header("Location: admin.php");
    exit;
}

// Processar approve/reject review
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_review'])) {
    $review_id = intval($_POST['review_id']);
    $conn->query("UPDATE reviews SET approved = 1 WHERE id = $review_id");
    header("Location: admin.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reject_review'])) {
    $review_id = intval($_POST['review_id']);
    $conn->query("DELETE FROM reviews WHERE id = $review_id"); // Ou UPDATE approved = 0
    header("Location: admin.php");
    exit;
}

include 'includes/header.php';

// Funcionalidades de admin: listar usuários
$users = $conn->query("SELECT id, username, email, role, banned, created_at FROM users ORDER BY created_at DESC");

// Estatísticas
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_reviews = $conn->query("SELECT COUNT(*) as count FROM reviews")->fetch_assoc()['count'];
$top_games = $conn->query("SELECT game_name, COUNT(*) as count FROM reviews GROUP BY game_name ORDER BY count DESC LIMIT 3");

// Listar reviews pendentes e aprovadas
$pending_reviews = $conn->query("SELECT r.id, r.comment, r.rating, r.created_at, u.username, r.game_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.approved = 0 ORDER BY r.created_at DESC");
$approved_reviews = $conn->query("SELECT r.id, r.comment, r.rating, r.created_at, u.username, r.game_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.approved = 1 ORDER BY r.created_at DESC");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Admin - GameList</title>
    <link rel="icon" type="image/png" href="img/logo.png">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #111; color: #fff; font-family: Arial, sans-serif; padding-top: 64px; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: #222; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #444; }
        th { background: #333; }
        .btn { padding: 5px 10px; background: #00bfff; color: #fff; border: none; cursor: pointer; }
        .btn:hover { background: #0080ff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Painel de Administração</h1>
        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
            <div style="background: #333; padding: 15px; border-radius: 8px; flex: 1;">
                <h3>Total Usuários</h3>
                <p><?php echo $total_users; ?></p>
            </div>
            <div style="background: #333; padding: 15px; border-radius: 8px; flex: 1;">
                <h3>Total Reviews</h3>
                <p><?php echo $total_reviews; ?></p>
            </div>
            <div style="background: #333; padding: 15px; border-radius: 8px; flex: 1;">
                <h3>Jogos Mais Comentados</h3>
                <ul>
                    <?php while ($game = $top_games->fetch_assoc()): ?>
                        <li><?php echo htmlspecialchars($game['game_name']); ?> (<?php echo $game['count']; ?>)</li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
        <h2>Gerenciar Usuários</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Criado em</th>
                <th>Ações</th>
            </tr>
            <?php while ($user = $users->fetch_assoc()): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo $user['role']; ?></td>
                <td><?php echo $user['banned'] ? 'Banido' : 'Ativo'; ?></td>
                <td><?php echo $user['created_at']; ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <select name="role">
                            <option value="user" <?php if($user['role']=='user') echo 'selected'; ?>>User</option>
                            <option value="admin" <?php if($user['role']=='admin') echo 'selected'; ?>>Admin</option>
                        </select>
                        <button type="submit" name="update_role" class="btn">Atualizar</button>
                    </form>
                    <form method="POST" style="display:inline; margin-left: 10px;">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <button type="submit" name="toggle_ban" class="btn" style="background: <?php echo $user['banned'] ? '#4caf50' : '#f44336'; ?>;">
                            <?php echo $user['banned'] ? 'Desbanir' : 'Banir'; ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <div class="container">
        <h2>Gerenciar Reviews Pendentes</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Usuário</th>
                <th>Jogo</th>
                <th>Comentário</th>
                <th>Nota</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
            <?php while ($review = $pending_reviews->fetch_assoc()): ?>
            <tr>
                <td><?php echo $review['id']; ?></td>
                <td><?php echo htmlspecialchars($review['username']); ?></td>
                <td><?php echo htmlspecialchars($review['game_name']); ?></td>
                <td><?php echo htmlspecialchars($review['comment']); ?></td>
                <td><?php echo $review['rating']; ?>/10</td>
                <td><?php echo $review['created_at']; ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <button type="submit" name="approve_review" class="btn" style="background: #4caf50;">Aprovar</button>
                    </form>
                    <form method="POST" style="display:inline; margin-left: 5px;">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <button type="submit" name="reject_review" class="btn" style="background: #f44336;" onclick="return confirm('Rejeitar e deletar esta review?')">Rejeitar</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <div class="container">
        <h2>Gerenciar Reviews Aprovadas</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Usuário</th>
                <th>Jogo</th>
                <th>Comentário</th>
                <th>Nota</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
            <?php while ($review = $approved_reviews->fetch_assoc()): ?>
            <tr>
                <td><?php echo $review['id']; ?></td>
                <td><?php echo htmlspecialchars($review['username']); ?></td>
                <td><?php echo htmlspecialchars($review['game_name']); ?></td>
                <td><?php echo htmlspecialchars($review['comment']); ?></td>
                <td><?php echo $review['rating']; ?>/10</td>
                <td><?php echo $review['created_at']; ?></td>
                <td>
                    <a href="edit_review_admin.php?id=<?php echo $review['id']; ?>" class="btn" style="background: #ffa500; margin-right: 5px;">Editar</a>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <button type="submit" name="delete_review" class="btn" style="background: #f44336;" onclick="return confirm('Tem certeza que quer deletar esta review?')">Deletar</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>