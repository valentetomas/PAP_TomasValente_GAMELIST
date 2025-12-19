<?php
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT username, email, avatar, banner, created_at FROM users WHERE id = $user_id")->fetch_assoc();

$lists = $conn->query("SELECT * FROM lists WHERE user_id = $user_id ORDER BY created_at DESC");
$reviews = $conn->query("SELECT * FROM reviews WHERE user_id = $user_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Perfil - GameList</title>
<link rel="icon" type="image/png" href="img/logo.png">
<link rel="stylesheet" href="css/style.css">
<style>
    body { background:#111; color:#fff; font-family:Arial,sans-serif; margin:0; padding:0; overflow-x:hidden; }
    .container { padding:20px; max-width:1100px; margin:auto; }
    h1, h2 { color:#00bfff; }

    /* --- Banner e Avatar --- */
    .banner-container {
        position: relative;
        width: 100%;
        height: 250px;
        border-radius: 12px;
        overflow: hidden;
        background-color: #222;
    }
    .banner-container img.banner {
        width: 100%;
        height: 250px;
        object-fit: cover;
    }
    .edit-banner-btn {
        position: absolute;
        top: 10px; right: 10px;
        background: rgba(0,0,0,0.6);
        color: #fff;
        border: none;
        padding: 6px 10px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 18px;
    }
    .edit-banner-btn:hover { background: #00bfff; }

    .avatar-container {
        position: relative;
        width: 130px;
        height: 130px;
        margin: -65px auto 0;
    }
    .avatar-container img {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        border: 4px solid #111;
        object-fit: cover;
        background: #111;
    }
    .edit-avatar-btn {
        position: absolute;
        bottom: 0; right: 0;
        background: rgba(0,0,0,0.6);
        color: #fff;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-size: 16px;
        padding: 5px 7px;
    }
    .edit-avatar-btn:hover { background: #00bfff; }

    .profile-info {
        text-align: center;
        margin-top: 15px;
        background: #222;
        padding: 20px;
        border-radius: 12px;
    }

    /* --- Modal --- */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(3px);
        z-index: 999;
        justify-content: center;
        align-items: center;
    }
    .modal {
        background: #222;
        padding: 25px;
        border-radius: 12px;
        width: 90%;
        max-width: 450px;
        text-align: center;
        box-shadow: 0 0 20px rgba(0,0,0,0.6);
        position: relative;
        animation: slideIn 0.3s ease;
    }
    @keyframes slideIn {
        from { transform: translateY(-30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .modal h3 { margin-top:0; color:#00bfff; }
    .modal input[type="file"] {
        display: block;
        margin: 10px auto;
        color: #ccc;
    }
    .modal button {
        background: #00bfff;
        border: none;
        color: white;
        padding: 8px 15px;
        border-radius: 6px;
        cursor: pointer;
        margin-top: 10px;
    }
    .modal button:hover { background: #0099cc; }
    .close-btn {
        background: none;
        border: none;
        color: #aaa;
        font-size: 22px;
        position: absolute;
        top: 10px;
        right: 15px;
        cursor: pointer;
    }
    .close-btn:hover { color: #fff; }

    /* --- Preview dentro do modal --- */
    .preview-box {
        position: relative;
        background: #111;
        border-radius: 10px;
        overflow: visible; /* permitir que o avatar ultrapasse */
        margin-bottom: 70px; /* mais espaço para o avatar não cobrir o formulário */
        height: 230px;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        flex-direction: column;
    }

    .preview-banner {
        width: 100%;
        height: 160px;
        object-fit: cover;
        border-radius: 10px 10px 0 0;
    }

    .preview-avatar {
        position: absolute;
        bottom: -40px; /* um pouco mais acima que antes */
        left: 50%;
        transform: translateX(-50%);
        width: 90px;
        height: 90px;
        border-radius: 50%;
        border: 4px solid #111;
        object-fit: cover;
        background: #111;
    }

    /* --- Listas e Reviews --- */
    .lists, .reviews { margin-top:30px; }
    .list { margin-bottom:40px; }
    .game-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(180px,1fr)); gap:15px; }
    .game-card { background:#222; border-radius:10px; padding:10px; text-align:center; transition:transform 0.2s; }
    .game-card:hover { transform:scale(1.03); }
    .game-card img { width:100%; border-radius:10px; height:180px; object-fit:cover; }
    .review-card { background:#222; padding:10px; border-radius:10px; margin-bottom:10px; }
    .review-card p { margin:5px 0; }
    .review-rating { color:#ffcc00; font-weight:bold; }
    a { color:#00bfff; text-decoration:none; }
    a:hover { text-decoration:underline; }
    small { color:#aaa; }
    button.remove-btn { background:#ff4444; border:none; padding:5px 10px; border-radius:6px; color:#fff; cursor:pointer; margin-top:5px; }
    button.remove-btn:hover { background:#cc0000; }
</style>
</head>
<body>
<div class="container">

    <!-- Banner -->
    <div class="banner-container">
        <img src="<?php echo $user['banner'] ?: 'https://via.placeholder.com/1000x250?text=Sem+Banner'; ?>" class="banner" alt="Banner">
        <button class="edit-banner-btn" onclick="openModal()">🖉</button>
    </div>

    <!-- Avatar -->
    <div class="avatar-container">
        <img src="<?php echo $user['avatar'] ?: 'https://via.placeholder.com/130x130?text=Avatar'; ?>" alt="Avatar">
        <button class="edit-avatar-btn" onclick="openModal()">🖉</button>
    </div>

    <!-- Info -->
    <div class="profile-info">
        <h1>👤 <?php echo htmlspecialchars($user['username']); ?></h1>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><small>Conta criada em: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></small></p>
        <p><a href="logout.php" style="color:#ff4444;">Terminar sessão</a></p>
    </div>

    <!-- MODAL -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <button class="close-btn" onclick="closeModal()">×</button>
            <h3>Alterar imagens do perfil</h3>

            <!-- Preview -->
            <div class="preview-box" id="previewBox">
                <img src="<?php echo $user['banner'] ?: 'https://via.placeholder.com/1000x250?text=Sem+Banner'; ?>" id="previewBanner" class="preview-banner">
                <img src="<?php echo $user['avatar'] ?: 'https://via.placeholder.com/130x130?text=Avatar'; ?>" id="previewAvatar" class="preview-avatar">
            </div>

            <form action="update_profile_images.php" method="POST" enctype="multipart/form-data">
                <label>🖼️ Novo banner:</label>
                <input type="file" name="banner" id="bannerInput" accept="image/*">
                <label>👤 Novo avatar:</label>
                <input type="file" name="avatar" id="avatarInput" accept="image/*">
                <button type="submit">Guardar Alterações</button>
            </form>
        </div>
    </div>

    <!-- Listas -->
    <div class="lists">
        <h2>🎮 As minhas listas</h2>
        <?php if ($lists->num_rows > 0): ?>
            <?php while($list = $lists->fetch_assoc()): ?>
                <div class="list">
                    <h3><?php echo htmlspecialchars($list['name']); ?> </h3>
                    <?php
                        $list_id = $list['id'];
                        $items = $conn->query("SELECT * FROM list_items WHERE list_id = $list_id ORDER BY added_at DESC");
                        if ($items->num_rows > 0):
                    ?>
                        <div class="game-grid">
                            <?php while($item = $items->fetch_assoc()): ?>
                                <div class="game-card">
                                    <a href="game.php?id=<?php echo $item['game_id']; ?>&name=<?php echo urlencode($item['game_name']); ?>&image=<?php echo urlencode($item['game_image']); ?>">
                                        <img src="<?php echo htmlspecialchars($item['game_image']); ?>" alt="<?php echo htmlspecialchars($item['game_name']); ?>">
                                        <p><?php echo htmlspecialchars($item['game_name']); ?></p>
                                    </a>
                                    <form method="POST" action="remove_from_list.php">
                                        <input type="hidden" name="list_id" value="<?php echo $list_id; ?>">
                                        <input type="hidden" name="game_id" value="<?php echo $item['game_id']; ?>">
                                        <button type="submit" class="remove-btn">❌ Remover</button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p>Lista vazia.</p>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Ainda não tens listas criadas.</p>
        <?php endif; ?>
    </div>

    <!-- Reviews -->
    <div class="reviews">
        <h2>📝 As minhas reviews</h2>
        <?php if ($reviews->num_rows > 0): ?>
            <?php while($review = $reviews->fetch_assoc()): ?>
                <div class="review-card">
                    <p><strong><?php echo htmlspecialchars($review['game_name']); ?></strong></p>
                    <p class="review-rating">⭐ <?php echo $review['rating']; ?>/10</p>
                    <p><?php echo htmlspecialchars($review['comment']); ?></p>
                    <small>Escrita em <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></small>
                    
                    <div style="margin-top:10px;">
                        <a href="edit_review.php?id=<?php echo $review['id']; ?>" style="background:#00bfff;padding:5px 10px;border-radius:6px;color:#fff;text-decoration:none;">✏️ Editar</a>
                        <form method="POST" action="remove_review.php" style="display:inline;">
                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                            <button type="submit" class="remove-btn">❌ Apagar</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Ainda não escreveste nenhuma review.</p>
        <?php endif; ?>
    </div>

</div>

<script>
    function openModal() {
        document.getElementById('modalOverlay').style.display = 'flex';
    }
    function closeModal() {
        document.getElementById('modalOverlay').style.display = 'none';
    }

    // Preview das imagens escolhidas
    const bannerInput = document.getElementById('bannerInput');
    const avatarInput = document.getElementById('avatarInput');
    const previewBanner = document.getElementById('previewBanner');
    const previewAvatar = document.getElementById('previewAvatar');

    bannerInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) previewBanner.src = URL.createObjectURL(file);
    });

    avatarInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) previewAvatar.src = URL.createObjectURL(file);
    });
</script>
</body>
</html>
